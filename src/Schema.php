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


    public $properties = [];

    public $pattern_properties = [];

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

        /** @noinspection PhpUnusedLocalVariableInspection */
        list($path, $fragment) = explode('#', $this->id, 2);

        //Do something here to make sure it's from the source doc.  Not sure how best to handle multiple schema spaces
        //echo $path;

        $inflector = Inflector::get();
        $class = $inflector->camelize($inflector->underscore($fragment));

        if($this->type === self::TYPE_ARRAY){
            $class = $inflector->singularize($class);
        }

        return $class;
    }


    public function getHintableClasses(){

        $classes = [];

        if($this->items instanceof Schema){
            $classes[] = $this->items->getClassName();
        }

        foreach($this->anyof as $item) {
            $classes[] = $item->getClassName();
        }

        foreach($this->allof as $item) {
            $classes[] = $item->getClassName();
        }

        foreach($this->oneof as $item) {
            $classes[] = $item->getClassName();
        }

        if(empty($classes)){
            return [$this->getClassName()];
        }

        return $classes;
    }

}
