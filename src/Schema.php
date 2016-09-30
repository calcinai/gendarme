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
    public $properties = [];

    /**
     * @var Schema[]
     */
    public $pattern_properties = [];


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

        return empty($this->class_name) ? self::$default_class : $this->class_name;
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
        return $inflector->singularize($inflector->camelize($inflector->underscore(trim($fragment, '/'))));

    }


    private function buildClassAndNS(){

        $full_class = $this->getRelativeClassName();

        $last_slash = strrpos($full_class, '\\');

        $this->class_name = substr($full_class, $last_slash +1);
        $this->namespace = substr($full_class, 0, $last_slash);

    }


    /**
     * Used to get the actual type hints for generation.
     *
     * Currently ignores scalars, but can implement in future for PHP7
     *
     * @param bool $include_scalar
     * @return array
     */
    public function getHintableClasses($include_scalar = false){

        $hints = [];

        if($this->items instanceof Schema && false){
            $hints[] = $this->items->getRelativeClassName();
        }

        foreach($this->anyof as $item) {
            $hints += $this->getHintsFromSchema($item, $include_scalar);
        }

        foreach($this->allof as $item) {
            $hints += $this->getHintsFromSchema($item, $include_scalar);
        }

        foreach($this->oneof as $item) {
            $hints += $this->getHintsFromSchema($item, $include_scalar);
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

        if(in_array($item->type, [Schema::TYPE_OBJECT, Schema::TYPE_ARRAY])) {
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
