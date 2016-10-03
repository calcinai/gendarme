<?php
/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme;

use ICanBoogie\Inflector;

class Schema {


    const TYPE_ARRAY   = 'array';
    const TYPE_OBJECT  = 'object';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_INTEGER = 'integer';
    const TYPE_NULL    = 'null';
    const TYPE_NUMBER  = 'number';
    const TYPE_STRING  = 'string';


    public static $base_namespace = '';
    public static $default_class = '';


    public $id;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $title;

    /**
     * @var []
     */
    public $enum;

    /**
     * @var mixed
     */
    public $default;

    /**
     * @var Schema
     */
    public $items;

    /**
     * @var Schema[]
     */
    public $oneof = [];

    /**
     * @var Schema[]
     */
    public $anyof = [];

    /**
     * @var Schema[]
     */
    public $allof = [];


    /**
     * @var Schema[]
     */
    private $properties = [];

    /**
     * @var Schema[]
     */
    public $pattern_properties = [];

    /**
     * @var Schema
     */
    public $additional_properties;

    /**
     * @var bool
     */
    public $allow_additional_properties = false;


    private $class_name;
    private $namespace;

    public function __construct($id){
        $this->id = $id;
    }

    public function addProperty($property_name, Schema $schema){
        $this->properties[$property_name] = $schema;
        return $this;
    }

    public function addPatternProperty($property_name, Schema $schema) {
        $this->pattern_properties[$property_name] = $schema;
        return $this;
    }


    public function setAdditionalProperties(Schema $schema) {
        $this->additional_properties = $schema;
    }

    /**
     * @param string $description
     * @return Schema
     */
    public function setDescription($description) {
        $this->description = $description;
        return $this;
    }

    /**
     * @param string $title
     * @return Schema
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    /**
     * @param string $type
     * @return Schema
     */
    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    /**
     * @param Schema $schema
     * @return Schema
     */
    public function setItems(Schema $schema) {
        $this->items = $schema;
        return $this;
    }

    /**
     * @param Schema $schema
     * @return Schema
     */
    public function addOneOf(Schema $schema) {
        $this->oneof[] = &$schema;
        return $this;
    }

    /**
     * @param Schema $schema
     * @return Schema
     */
    public function addAnyOf(Schema $schema) {
        $this->anyof[] = &$schema;
        return $this;
    }

    /**
     * @param Schema $schema
     * @return Schema
     */
    public function addAllOf(Schema $schema) {
        $this->allof[] = &$schema;
        return $this;
    }

    /**
     * @param mixed $default
     * @return Schema
     */
    public function setDefault($default) {
        $this->default = $default;
        return $this;
    }

    /**
     * @param mixed $enum
     * @return Schema
     */
    public function setEnum($enum) {
        $this->enum = $enum;
        return $this;
    }


    public function getClassName(){
        if(!isset($this->class_name)){
            $this->buildClassAndNS();
        }

        return $this->class_name;
    }

    public function getNamespace(){
        if(!isset($this->namespace)){
            $this->buildClassAndNS();
        }

        return rtrim(sprintf('%s\\%s', self::$base_namespace, $this->namespace), '\\');
    }


    public function getRelativeClassName(){

        /** @noinspection PhpUnusedLocalVariableInspection */
        list($path, $fragment) = explode('#', $this->id, 2);

        //Do something here to make sure it's from the source doc.  Not sure how best to handle multiple schema spaces
        //echo $path;

        $inflector = Inflector::get();
        $name = $inflector->singularize($inflector->camelize($inflector->underscore(trim($fragment, '/'))));

        return empty($name) ? self::$default_class : $name;


    }


    private function buildClassAndNS(){

        $full_class = $this->getRelativeClassName();

        $last_slash = strrpos($full_class, '\\');

        $this->class_name = ltrim(substr($full_class, $last_slash), '\\');
        $this->namespace = substr($full_class, 0, $last_slash);

    }



    public function getProperties() {

        foreach($this->properties as $property_name => $property){
            yield $property_name => $property;
        }

        if(isset($this->additional_properties)){
            //Would be nice.
//            yield from $this->additional_properties->getProperties();
            foreach($this->additional_properties->getProperties() as $property_name => $property){
                yield $property_name => $property;
            }
        }
    }



    /**
     * Used to get the actual type hints for generation.
     *
     * Currently ignores scalars, but can enable in future for PHP7
     *
     * @param bool $include_scalar
     * @return array
     */
    public function getHintableClasses($include_scalar = false){


        $hints = [];

        if($this->items instanceof Schema && false){
            $hints[] = $this->items->getHintableClasses($include_scalar);
        }

        foreach($this->anyof as $item) {
            $hints += $item->getHintableClasses($include_scalar);
        }

        foreach($this->allof as $item) {
            $hints += $item->getHintableClasses($include_scalar);
        }

        foreach($this->oneof as $item) {
            $hints += $item->getHintableClasses($include_scalar);
        }

        if(empty($hints)){
            $hints += $this->getHintsFromSchema($this, $include_scalar);
        }

        return $hints;
    }


    /**
     * @param Schema $item
     * @param $include_scalar
     * @return array
     */
    private function getHintsFromSchema(Schema $item, $include_scalar){

        $hints = [];

        if($item->type === Schema::TYPE_OBJECT) {
            $hints[] = $item->getRelativeClassName();
        } elseif($include_scalar){

            switch($this->type){
                case self::TYPE_BOOLEAN:
                    $hints[] = 'bool';
                    break;
                case self::TYPE_NUMBER :
                case self::TYPE_INTEGER:
                    $hints[] = 'int';
                    break;
                    break;
                case self::TYPE_STRING :
                    $hints[] = 'string';
                    break;
            }
        }

        return $hints;

    }



    /**
     * @param string $default_class
     */
    public static function setDefaultClass($default_class) {
        self::$default_class = $default_class;
    }

    /**
     * @param string $base_namespace
     */
    public static function setBaseNamespace($base_namespace) {
        self::$base_namespace = $base_namespace;
    }


}
