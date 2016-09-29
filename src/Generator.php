<?php
/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme;


use ICanBoogie\Inflector;
use PhpParser\BuilderFactory;
use PhpParser\PrettyPrinter\Standard;

class Generator {

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var Schema[]
     */
    private $schemas;

    /**
     * Generator constructor.
     * @param $namespace
     * @param Schema[] $schemas
     */
    public function __construct($namespace, $schemas) {
        $this->schemas = $schemas;
        $this->namespace = $namespace;


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

        $class = $factory->class($schema->getClassName());

        foreach($schema->properties as $property_name => $child_schema){
            $method_name = sprintf('set%s', $inflector->camelize($property_name));

            $setter = $factory->method($method_name);
            $property = $factory->property($property_name);
            $parameter = $factory->param($property_name);

            $hintable_classes = $child_schema->getHintableClasses();

            if(count($hintable_classes) === 1){
                $parameter->setTypeHint(current($hintable_classes));
            }

            $setter->addParam($parameter);
            $class->addStmt($property);
            $class->addStmt($setter);
        }





        $node = $factory->namespace(sprintf('%s\\%s', $this->namespace, $schema->getNamespace()))->addStmt($class)







//                ->extend('SomeOtherClass')
//                ->implement('A\Few', '\Interfaces')
//                ->makeAbstract() // ->makeFinal()
//
//                ->addStmt($factory->method('someMethod')
//                    ->makePublic()
//                    ->makeAbstract() // ->makeFinal()
//                    ->setReturnType('bool')
//                    ->addParam($factory->param('someParam')->setTypeHint('SomeClass'))
//                    ->setDocComment('/**
//                              * This method does something.
//                              *
//                              * @param SomeClass And takes a parameter
//                              */')
//                )
//
//                ->addStmt($factory->method('anotherMethod')
//                    ->makeProtected() // ->makePublic() [default], ->makePrivate()
//                    ->addParam($factory->param('someParam')->setDefault('test'))
//                    // it is possible to add manually created nodes
//                    ->addStmt(new Node\Expr\Print_(new Node\Expr\Variable('someParam')))
//                )
//
//                // properties will be correctly reordered above the methods
//                ->addStmt($factory->property('someProperty')->makeProtected())
//                ->addStmt($factory->property('anotherProperty')->makePrivate()->setDefault(array(1, 2, 3)))
//            )
//
            ->getNode();

        $stmts = array($node);
        $prettyPrinter = new Standard();
        echo $prettyPrinter->prettyPrintFile($stmts);


    }


}