<?php
/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme;


use Calcinai\Gendarme\Generator\Printer;
use Calcinai\Gendarme\Templates\BaseSchema;
use ICanBoogie\Inflector;
use PhpParser\Builder\Method;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\ParserFactory;

/**
 * This class is pretty big.  All it does is take parsed schemas and write them out as PHP classes.
 *
 * Class Generator
 * @package Calcinai\Gendarme
 */
class Generator {

    /**
     * The base (user-defined) namespace
     * 
     * @var string
     */
    private $base_namespace;

    /**
     * The base class that al schemas extend
     * 
     * @var string
     */
    private $base_schema_class;

    /**
     * Directory to write the output to
     * 
     * @var
     */
    private $output_dir;


    /**
     * Builder for the components of the generated nodes
     * 
     * @var BuilderFactory
     */
    private $builder_factory;

    /**
     * Output printer/formatter.  Converts AST to PHP
     * 
     * @var Printer
     */
    private $printer;

    /**
     * Inflector for string transformation
     *
     * @var Inflector
     */
    private $inflector;

    /**
     * Generator constructor.
     * @param $base_namespace
     * @param $output_dir
     */
    public function __construct($base_namespace, $output_dir) {
        $this->base_namespace = $base_namespace;
        $this->output_dir = $output_dir;


        $this->inflector = Inflector::get();

        $this->builder_factory = new BuilderFactory();
        $this->printer = new Printer(['shortArraySyntax' => true]);

    }


    /**
     *
     * @param Schema[] $schemas
     */
    public function generateClasses($schemas){

        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

        //Then process the base schema
        $base_schema_rc = new \ReflectionClass(BaseSchema::class);
        $base_schema_file = $parser->parse(file_get_contents($base_schema_rc->getFileName()));

        $this->base_schema_class = $base_schema_rc->getShortName();

        //Find the namespace node
        foreach($base_schema_file as $base_schema_namespace){
            if($base_schema_namespace instanceof Stmt\Namespace_){
                break;
            }
        }

        /** @noinspection PhpUndefinedVariableInspection - if it isn't, it's an internal error anyway since it'll mean the template class is broken */
        $base_schema_namespace->name = new Name(explode('\\', $this->base_namespace));


        $this->writeClass($this->base_schema_class, $base_schema_namespace);

        foreach($schemas as $schema){
            if($schema->type !== Schema::TYPE_OBJECT){
                continue;
            }

            $this->writeClass($schema->getRelativeClassName(), $this->buildModel($schema));
        }
    }


    private function writeClass($class_name, Node $node){

        $filename = sprintf('%s%s%s.php', $this->output_dir, DIRECTORY_SEPARATOR, strtr($class_name, ['\\' => DIRECTORY_SEPARATOR]));
        $file_info = pathinfo($filename);

        if(!file_exists($file_info['dirname'])){
            mkdir($file_info['dirname'], 0777, true);
        }

        $code = $this->printer->prettyPrintFile([$node]);

        file_put_contents($filename, $code);

    }


    /**
     * Make the AST for a schema
     *
     * @param Schema $schema
     * @return \PhpParser\Node
     */
    private function buildModel(Schema $schema) {

        $default_values = [];
        $class_resolver = new ClassResolver($this->base_namespace);
        $fq_base_class = $class_resolver->addClass($this->base_schema_class);
        $fq_model_class = $class_resolver->addClass($schema->getRelativeClassName());

        $namespace = $this->builder_factory->namespace($class_resolver->getNamespace($fq_model_class));

        $class = $this->builder_factory->class($class_resolver->getClassName($fq_model_class))->extend($class_resolver->getClassName($fq_base_class));

        if(!empty($schema->description)){
            $class->setDocComment($this->formatDocComment([$schema->description]));
        }

        $property_hints = [];
        $property_enums = [];
        foreach($schema->getProperties() as $property_name => $child_schema){

            //Actual classes that will be used in method type hinting
            $hintable_classes = array_map(function($class_name) use($class_resolver){
                $fq_class = $class_resolver->addClass($class_name);
                return $class_resolver->getClassAlias($fq_class);
            }, $child_schema->getHintableClasses());

            //This should all line up.
            $property_hints[$property_name] = $child_schema->getHintableClasses();

            //Append the enums
            if(!empty($child_schema->getEnum())){
                $property_enums[$property_name] = $child_schema->getEnum();
            }

            //All types for the doc block - TODO - clean up
            $all_types = array_map(function($type) use($class_resolver){
                if(in_array($type, ['bool', 'int', 'string', 'mixed'])){
                    return $type;
                }

                $fq_class = $class_resolver->addClass($type);
                return $class_resolver->getClassAlias($fq_class);
            }, $child_schema->getHintableClasses(true));


            //Build parameter hint
            if(count($all_types) === 1 && count($hintable_classes) === 1){
                $parameter_hint = current($hintable_classes);
            } else {
                $parameter_hint = null;
            }

            $setter_parameter = $this->buildParameter($property_name, $parameter_hint);

            //Create the setters/adders for the properties that are arrays
            if($child_schema->type === Schema::TYPE_ARRAY){
                $class->addStmt($this->buildArraySetter($property_name, $setter_parameter, $all_types, $child_schema->description));
                $class->addStmt($this->buildArrayGetter($property_name, $this->sanitisePropertyName($property_name), $all_types, $child_schema->description));

                //Create a simple setter (hinted if possible)
            } else {
                $class->addStmt($this->buildSetter($property_name, $setter_parameter, $all_types, $child_schema->description));
                $class->addStmt($this->buildGetter($property_name, $this->sanitisePropertyName($property_name), $all_types, $child_schema->description));
            }

            //The isRequired check should possibly go in the parser.
            if(!empty($child_schema->default) && $schema->isRequired($property_name)){
                $default_values[$property_name] = $child_schema->default;
            }

        }


        $class->addStmt($this->builder_factory->property('data')
            ->makeProtected()
            ->setDefault($default_values)
            ->setDocComment($this->formatDocComment(['Array to store schema data and default values', '@var array'])));


        $class->addStmt($this->builder_factory->property('enums')
            ->makeProtected()
            ->makeStatic()
            ->setDefault($property_enums)
            ->setDocComment($this->formatDocComment(['Any enums that exist on this object', '@var array'])));

        $class->addStmt($this->builder_factory->property('properties')
            ->makeProtected()
            ->makeStatic()
            ->setDefault($property_hints)
            ->setDocComment($this->formatDocComment(['Properties and types', '@var array'])));


        if($schema->additional_properties instanceof Schema){
            $additional_properties = $schema->additional_properties->getHintableClasses();
        } else {
            $additional_properties = $schema->additional_properties;
        }

        $class->addStmt($this->builder_factory->property('additional_properties')
            ->makeProtected()
            ->makeStatic()
            ->setDefault($additional_properties)
            ->setDocComment($this->formatDocComment(['Allowed additional properties', '@var array'])));

        $parsed_pattern_props = array_map(function(Schema $child_schema){
            return $child_schema->getHintableClasses();
        }, $schema->pattern_properties);


        $class->addStmt($this->builder_factory->property('pattern_properties')
            ->makeProtected()
            ->makeStatic()
            ->setDefault($parsed_pattern_props)
            ->setDocComment($this->formatDocComment(['Array to store any allowed pattern properties', '@var array'])));


        foreach($class_resolver->getForeignClasses($fq_model_class) as $fq_class){
            $use = $this->builder_factory->use($fq_class);

            if(null !== $alias = $class_resolver->getClassAlias($fq_class)){
                $use->as($alias);
            }

            $namespace->addStmt($use);
        }

        return $namespace->addStmt($class)->getNode();

    }

    /**
     * Unfortunately these need to be full since there's no easy way to know if they'll be conflicting in advance.
     *
     * @param $property_name
     * @param null $type_hint
     * @return Param
     */
    private function buildParameter($property_name, $type_hint = null){

        $property_name = $this->sanitisePropertyName($property_name);

        $parameter = $this->builder_factory->param($property_name);

        if($type_hint !== null){
            $parameter->setTypeHint($type_hint);
        }

        return $parameter;

    }


    /**
     * @param $data_index
     * @param Param $parameter
     * @param string[] $types
     * @param string|null $description
     * @return Method
     */
    private function buildArraySetter($data_index, Param $parameter, $types, $description = null) {

        $parameter_name = $parameter->getNode()->name;
        $method_name = sprintf('add%s', $this->inflector->singularize($this->inflector->camelize($parameter_name)));

        $setter = $this->builder_factory->method($method_name)->makePublic();
        $setter->addParam($parameter);

        //$this->add('$data_index', $property)
        $setter->addStmt(new Expr\MethodCall(
            new Expr\Variable('this'),
            'add',
            [
                new Node\Scalar\String_($data_index),
                new Expr\Variable($parameter_name)
            ]
        ));

        //return $this;
        $setter->addStmt(new Stmt\Return_(new Expr\Variable('this')));

        //Doc comments
        if(!empty($description)){
            $setter_lines[] = $description;
        }

        $setter_lines[] = sprintf('@param %s $%s',  implode("|", $types), $parameter_name);
        $setter_lines[] = '@return $this';

        $setter->setDocComment($this->formatDocComment($setter_lines));

        return $setter;
    }

    /**
     * @param $data_index
     * @param Param $parameter
     * @param string[] $types
     * @param string|null $description
     * @return Method
     */
    private function buildSetter($data_index, Param $parameter, $types, $description = null) {

        $parameter_name = $parameter->getNode()->name;
        $method_name = sprintf('set%s', $this->inflector->camelize($parameter_name));

        $setter = $this->builder_factory->method($method_name)->makePublic();
        $setter->addParam($parameter);

        //$this->set('$data_index', $property)
        $setter->addStmt(new Expr\MethodCall(
            new Expr\Variable('this'),
            'set',
            [
                new Node\Scalar\String_($data_index),
                new Expr\Variable($parameter_name)
            ]
        ));

        //return $this;
        $setter->addStmt(new Stmt\Return_(new Expr\Variable('this')));


        //Doc comments
        if(!empty($description)){
            $setter_lines[] = $description;
        }

        $setter_lines[] = sprintf('@param %s $%s', implode("|", $types), $parameter_name);
        $setter_lines[] = '@return $this';

        $setter->setDocComment($this->formatDocComment($setter_lines));

        return $setter;
    }

    /**
     * @param $data_index
     * @param string[] $types
     * @param string|null $description
     * @return Method
     */
    private function buildGetter($data_index, $property_name, $types, $description = null) {

        $getter = $this->builder_factory->method(sprintf('get%s', $this->inflector->camelize($property_name)))->makePublic();

        //$this->get('$data_index')
        $getter->addStmt(new Stmt\Return_(
                new Expr\MethodCall(
                    new Expr\Variable('this'),
                    'get',
                    [
                        new Node\Scalar\String_($data_index)
                    ]
                )
            )
        );

        //Doc comments
        if(!empty($description)){
            $getter_lines[] = $description;
        }

        $getter_lines[] = sprintf('@return %s',  implode("|", $types));

        $getter->setDocComment($this->formatDocComment($getter_lines));

        return $getter;
    }


    /**
     * Overload just to change the comments
     *
     * @param $data_index
     * @param $property_name
     * @param $types
     * @param $description
     *
     * @return Method
     */
    private function buildArrayGetter($data_index, $property_name, $types, $description) {

        $getter = $this->buildGetter($data_index, $property_name, $types, $description);

        //Doc comments
        if(!empty($description)){
            $getter_lines[] = $description;
        }

        $getter_lines[] = sprintf('@return %s[]',  implode("|", $types));

        $getter->setDocComment($this->formatDocComment($getter_lines));

        return $getter;
    }


    /**
     * Format an array into a doccomment
     *
     * @param $lines
     * @return string
     */
    public static function formatDocComment($lines){
        return sprintf("/**\n * %s\n */", implode("\n * ", $lines));
    }

    private function sanitisePropertyName($name) {
        return preg_replace('/(^[^a-z]|[^a-z0-9_\-])/i', '', $name);
    }

}