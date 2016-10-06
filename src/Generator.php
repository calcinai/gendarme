<?php
/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme;


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
use PhpParser\PrettyPrinter\Standard;

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
     * @var Standard
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
        $this->printer = new Standard(['shortArraySyntax' => true]);

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
        $used_class_roots = [];

        $schema_namespace = $schema->getNamespace();

        if(!empty($this->base_namespace)){
            $schema_namespace = rtrim(sprintf('%s\\%s', $this->base_namespace, $schema_namespace), '\\');
        }


        $namespace = $this->builder_factory->namespace($schema_namespace);

        if($this->base_namespace !== $schema_namespace){
            $namespace->addStmt(
                $this->builder_factory->use(sprintf('%s\\%s', $this->base_namespace, $this->base_schema_class))
            );
        }


        $class = $this->builder_factory->class($schema->getClassName())->extend($this->base_schema_class);

        if(!empty($schema->description)){
            $class->setDocComment($this->formatDocComment([$schema->description]));
        }

        foreach($schema->getProperties() as $property_name => $child_schema){

            $hintable_classes = $child_schema->getHintableClasses();
            $types = $child_schema->getHintableClasses(true);

            $setter_parameter = $this->buildParameter($property_name, $hintable_classes);

            //Create the setters/adders for the properties that are arrays
            if($child_schema->type === Schema::TYPE_ARRAY){
                $class->addStmt($this->buildArraySetter($property_name, $setter_parameter, $types, $child_schema->description));
                $class->addStmt($this->buildArrayGetter($property_name, $this->sanitisePropertyName($property_name), $types, $child_schema->description));

                //Create a simple setter (hinted if possible)
            } else {
                $class->addStmt($this->buildSetter($property_name, $setter_parameter, $types, $child_schema->description));
                $class->addStmt($this->buildGetter($property_name, $this->sanitisePropertyName($property_name), $types, $child_schema->description));
            }

            foreach($hintable_classes as $hintable_class){
                $used_class_roots[strtok($hintable_class, '\\')] = true;
            }

            if(!empty($child_schema->default)){
                $default_values[$property_name] = $child_schema->default;
            }

        }


        $class->addStmt($this->builder_factory->property('data')
            ->makeProtected()
            ->setDefault($default_values)
            ->setDocComment($this->formatDocComment(['Array to store schema data and default values', '@var array'])));


        $parsed_pattern_props = array_map(function(Schema $child_schema){
            return $child_schema->getHintableClasses(true);
        }, $schema->pattern_properties);

        $class->addStmt($this->builder_factory->property('pattern_properties')
            ->makeProtected()
            ->makeStatic()
            ->setDefault($parsed_pattern_props)
            ->setDocComment($this->formatDocComment(['Array to store any allowed pattern properties', '@var array'])));

//        $class->addStmt($this->builder_factory->property('allow_additional_properties')
//            ->makeProtected()
//            ->makeStatic()
//            ->setDefault($schema->allow_additional_properties)
//            ->setDocComment($this->formatDocComment(['If the schema allows arbitrary properties', '@var bool'])));


        foreach(array_keys($used_class_roots) as $class_root){
            $namespace->addStmt($this->builder_factory->use(sprintf('%s\\%s', $this->base_namespace, $class_root)));
        }

        return $namespace->addStmt($class)->getNode();

    }

    /**
     * @param $property_name
     * @param $types
     * @return Param
     */
    private function buildParameter($property_name, $types){

        $property_name = $this->sanitisePropertyName($property_name);

        $parameter = $this->builder_factory->param($property_name);

        if(count($types) === 1){
            $parameter->setTypeHint(current($types));
        }

        return $parameter;

    }


    /**
     * @param Param $parameter
     * @param string[] $types
     * @param string|null $description
     *
     * @return Method
     */
    private function buildArraySetter($data_index, Param $parameter, $types, $description = null) {

        $parameter_name = $parameter->getNode()->name;
        $method_name = sprintf('add%s', $this->inflector->singularize($this->inflector->camelize($parameter_name)));

        $setter = $this->builder_factory->method($method_name)->makePublic();
        $setter->addParam($parameter);

        //$this->property['prop'][] = &$property;
        $setter->addStmt(new Expr\AssignRef(
            new Expr\ArrayDimFetch(
                new Expr\ArrayDimFetch(
                    new Expr\PropertyFetch(new Expr\Variable('this'), 'data'),
                    new Node\Scalar\String_($data_index)
                )
            ),
            new Expr\Variable($parameter_name))
        );

        //return $this;
        $setter->addStmt(new Stmt\Return_(new Expr\Variable('this')));

        //Doc comments
        if(!empty($description)){
            $setter_lines[] = $description;
        }

        $setter_lines[] = sprintf('@param %s $%s',  implode("|\n *        ", $types), $parameter_name);
        $setter_lines[] = '@return $this';

        $setter->setDocComment($this->formatDocComment($setter_lines));

        return $setter;
    }

    /**
     * @param Param $parameter
     * @param string[] $types
     * @param string|null $description
     *
     * @return Method
     */
    private function buildSetter($data_index, Param $parameter, $types, $description = null) {

        $parameter_name = $parameter->getNode()->name;
        $method_name = sprintf('set%s', $this->inflector->camelize($parameter_name));

        $setter = $this->builder_factory->method($method_name)->makePublic();
        $setter->addParam($parameter);

        //$this->property = $property;
        $setter->addStmt(new Expr\Assign(
            new Expr\ArrayDimFetch(
                new Expr\PropertyFetch(new Expr\Variable('this'), 'data'),
                new Node\Scalar\String_($data_index)
            ),
            new Expr\Variable($parameter_name))
        );

        //return $this;
        $setter->addStmt(new Stmt\Return_(new Expr\Variable('this')));


        //Doc comments
        if(!empty($description)){
            $setter_lines[] = $description;
        }

        $setter_lines[] = sprintf('@param %s $%s', implode("|\n *        ", $types), $parameter_name);
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
        $getter->addStmt(new Stmt\Return_(
                new Expr\ArrayDimFetch(
                    new Expr\PropertyFetch(new Expr\Variable('this'), 'data'),
                    new Node\Scalar\String_($data_index)
                )
            )
        );

        //Doc comments
        if(!empty($description)){
            $getter_lines[] = $description;
        }

        $getter_lines[] = sprintf('@return %s',  implode("|\n *         ", $types));

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

        $getter_lines[] = sprintf('@return %s[]',  implode("|\n *         ", $types));

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
        return sprintf("\n/**\n * %s\n */", implode("\n * ", $lines));
    }

    private function sanitisePropertyName($name) {
        return preg_replace('/(^[^a-z]|[^a-z0-9_\-])/i', '', $name);
    }

}