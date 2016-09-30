<?php
/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme;


use ICanBoogie\Inflector;
use PhpParser\BuilderFactory;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\Return_;
use PhpParser\PrettyPrinter\Standard;

class Generator {

    /**
     * @var Schema[]
     */
    private $schemas;

    /**
     * @var string
     */
    private $base_namespace;

    /**
     * Generator constructor.
     * @param $base_namespace
     * @param $root_class_name
     * @param Schema[] $schemas
     */
    public function __construct($base_namespace, $root_class_name, $schemas) {
        $this->schemas = $schemas;
        $this->base_namespace = $base_namespace;

        Schema::setBaseNamespace($base_namespace);
        Schema::setDefaultClass($root_class_name);

        foreach($schemas as $schema){
            if(!in_array($schema->type, [Schema::TYPE_OBJECT, Schema::TYPE_ARRAY])){
                continue;
            }

            $this->buildModel($schema);
        }

    }



    private function buildModel(Schema $schema) {

        $inflector = Inflector::get();
        $factory = new BuilderFactory();

        $used_class_roots = [];

        $namespace = $factory->namespace($schema->getNamespace());
        $class = $factory->class($schema->getClassName());

        if(!empty($schema->description)){
            $class->setDocComment($this->formatDocComment([$schema->description]));
        }

        foreach($schema->properties as $property_name => $child_schema){

            if($child_schema->type === Schema::TYPE_ARRAY){
                $method_name = sprintf('add%s', $inflector->singularize($inflector->camelize($property_name)));
                $setter = $factory->method($method_name)->makePublic();
                $setter->addStmt(new Expr\Assign(
                        new Expr\ArrayDimFetch(
                            new Expr\PropertyFetch(new Expr\Variable('this'), $property_name)
                        ),
                        new Expr\Variable($property_name))
                );
            } else {
                $method_name = sprintf('set%s', $inflector->camelize($property_name));
                $setter = $factory->method($method_name)->makePublic();
                $setter->addStmt(new Expr\Assign(
                    new Expr\PropertyFetch(new Expr\Variable('this'), $property_name),
                    new Expr\Variable($property_name))
                );
            }

            $setter->addStmt(new Return_(new Expr\Variable('this')));
            $property = $factory->property($property_name)->makePrivate();
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

            $setter_lines[] = sprintf('@param %s %s',  implode('|', $child_schema->getHintableClasses(true)), $property_name);
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
            ->setDefault($parsed_pattern_props));





        foreach(array_keys($used_class_roots) as $class_root){
            $namespace->addStmt($factory->use(sprintf('%s\\%s', $this->base_namespace, $class_root)));
        }

        $node = $namespace->addStmt($class)->getNode();

        $prettyPrinter = new Standard();
        echo $prettyPrinter->prettyPrintFile([$node]);


    }


    public function formatDocComment($lines){
        return sprintf("\n/**\n * %s\n */", implode("\n * ", $lines));
    }


}