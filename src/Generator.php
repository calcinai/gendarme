<?php
/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme;


use Calcinai\Gendarme\Templates\BaseSchema;
use ICanBoogie\Inflector;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Return_;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class Generator {

    /**
     * @var string
     */
    private $base_namespace;


    private $base_schema_class;


    private $printer;
    private $root_class_name;

    private $output_dir;

    /**
     * Generator constructor.
     * @param $base_namespace
     * @param $root_class_name
     * @param $output_dir
     */
    public function __construct($base_namespace, $root_class_name, $output_dir) {
        $this->base_namespace = $base_namespace;
        $this->root_class_name = $root_class_name;
        $this->output_dir = $output_dir;

        $this->printer = new Standard(['shortArraySyntax' => true]);


        Schema::setBaseNamespace($base_namespace);
        Schema::setDefaultClass($root_class_name);

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

        $inflector = Inflector::get();
        $factory = new BuilderFactory();

        $used_class_roots = [];

        $namespace = $factory->namespace($schema->getNamespace())->addStmt(
            $factory->use(sprintf('%s\\%s', $this->base_namespace, $this->base_schema_class))
        );
        $class = $factory->class($schema->getClassName())->extend($this->base_schema_class);

        if(!empty($schema->description)){
            $class->setDocComment($this->formatDocComment([$schema->description]));
        }

        foreach($schema->getProperties() as $property_name => $child_schema){

            //Create the setters/adders for the properties that are arrays
            if($child_schema->type === Schema::TYPE_ARRAY){
                $method_name = sprintf('add%s', $inflector->singularize($inflector->camelize($property_name)));
                $setter = $factory->method($method_name)->makePublic();
                $setter->addStmt(new Expr\Assign(
                        new Expr\ArrayDimFetch(
                            new Expr\PropertyFetch(new Expr\Variable('this'), $property_name)
                        ),
                        new Expr\Variable($property_name))
                );
            //Create a simple setter (hinted if possible)
            } else {
                $method_name = sprintf('set%s', $inflector->camelize($property_name));
                $setter = $factory->method($method_name)->makePublic();
                $setter->addStmt(new Expr\Assign(
                    new Expr\PropertyFetch(new Expr\Variable('this'), $property_name),
                    new Expr\Variable($property_name))
                );
            }

            //Makew the setters fluent
            $setter->addStmt(new Return_(new Expr\Variable('this')));
            $property = $factory->property($property_name)->makeProtected();
            $parameter = $factory->param($property_name);

            if(!empty($child_schema->default)){
                $property->setDefault($child_schema->default);
            }

            $hintable_classes = $child_schema->getHintableClasses();

            if(count($hintable_classes) === 1){
                $parameter->setTypeHint(current($hintable_classes));
            }

            foreach($hintable_classes as $hintable_class){
                $used_class_roots[strtok($hintable_class, '\\')] = true;
            }

            if(!empty($child_schema->description)) {
                $setter_lines =
                $parameter_lines = [$child_schema->description, ''];
            } else {
                $setter_lines =
                $parameter_lines = [];
            }

            $setter_lines[] = sprintf('@param %s $%s',  implode('|', $child_schema->getHintableClasses(true)), $property_name);
            $setter_lines[] = '@return $this';

            $parameter_lines[] = sprintf('@var %s', implode('|', $child_schema->getHintableClasses(true)));

            $setter->setDocComment($this->formatDocComment($setter_lines));
            $property->setDocComment($this->formatDocComment($parameter_lines));

            $setter->addParam($parameter);
            $class->addStmt($property);
            $class->addStmt($setter);
        }


        $parsed_pattern_props = [];

        foreach($schema->pattern_properties as $patern => $child_schema){
            $parsed_pattern_props[$patern] = $child_schema->getHintableClasses(true);
        }

        $class->addStmt($factory->property('pattern_properties')
            ->makeProtected()
            ->makeStatic()
            ->setDefault($parsed_pattern_props)
            ->setDocComment($this->formatDocComment(['Array to store any allowed pattern properties', '@var array'])));



        foreach(array_keys($used_class_roots) as $class_root){
            $namespace->addStmt($factory->use(sprintf('%s\\%s', $this->base_namespace, $class_root)));
        }

        return $namespace->addStmt($class)->getNode();

    }


    /**
     * Format an array into a doccomment
     *
     * @param $lines
     * @return string
     */
    public function formatDocComment($lines){
        return sprintf("\n/**\n * %s\n */", implode("\n * ", $lines));
    }

}