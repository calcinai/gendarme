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
     * @var Schema|Schema[]
     */
    public $items;

    /**
     * @var Schema
     */
    public $additional_items;

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



    private $namespace;
    private $class_name;
    private $relative_class_name;

    public function __construct($id){
        $this->id = $id;

        $this->computeClassName();
    }


    /**
     * http://json-schema.org/draft-04/schema -> Org\JsonSchema\Draft04\Schema
     * file://x/y/schema.json > Schema
     */
    private function computeClassName() {

        /** @noinspection PhpUnusedLocalVariableInspection */
        list($path, $fragment) = explode('#', $this->id, 2);

        $parsed = parse_url($path);

        if(isset($parsed['host'])) {
            $relative_class = sprintf('%s/', implode('/', array_reverse(explode('.', $parsed['host']))));

            $pathinfo = pathinfo($parsed['path']);
            $relative_class .= sprintf('%s/%s', $pathinfo['dirname'], $pathinfo['filename']);
        } else {
            //It's probably debatable in this scenario that the host is pulled from the schema itself
            $relative_class = '';//pathinfo($parsed['path'], PATHINFO_FILENAME);
        }

        if(!empty($fragment)) {
            $relative_class .= ltrim($fragment, '/');
        }


        $inflector = Inflector::get();

        $this->relative_class_name = $inflector->camelize($inflector->underscore($relative_class));

        $last_slash_pos = strrpos($this->relative_class_name, '\\');

        $this->class_name = ltrim(substr($this->relative_class_name, $last_slash_pos), '\\');
        $this->namespace = substr($this->relative_class_name, 0, $last_slash_pos);


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
     * @param Schema|bool $schema
     * @return $this
     */
    public function setAdditionalProperties($schema) {
        $this->additional_properties = $schema;
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
     * @param Schema|Schema[] $schema
     * @return Schema
     */
    public function setItems($schema) {
        $this->items = $schema;
        return $this;
    }

    /**
     * @param Schema|bool $items
     * @return $this
     */
    public function setAdditionalItems($items) {
        $this->additional_items = $items;
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

    /**
     * @return mixed
     */
    public function getEnum() {
        return $this->enum;
    }


    /**
     * @param $class
     * @return $this
     */
    public function setClassName($class) {
        $this->class_name = $class;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getClassName(){
        return $this->class_name;
    }

    /**
     * @return mixed
     */
    public function getRelativeClassName(){
        return $this->relative_class_name;
    }

    /**
     * @param $class_name
     * @return $this
     */
    public function setRelativeClassName($class_name) {
        $this->relative_class_name = $class_name;
        return $this;
    }


    public function getNamespace(){
        return $this->namespace;
    }


    public function getProperties() {
        return $this->properties;
    }

    public function getAdditionalProperties() {
        return $this->additional_properties;
    }


    public function getItems() {
        return $this->items;
    }

    public function getAdditionalItems() {
        return $this->additional_items;
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

        if($this->items instanceof Schema){
            $hints = $this->items->getHintableClasses($include_scalar);
        } elseif (is_array($this->items)){
            $hints[] = 'array';
        }

        //No sane schema would have all of these in it together, would it?

        foreach($this->anyof as $item) {
            $hints = array_merge($hints, $item->getHintableClasses($include_scalar));
        }

        foreach($this->oneof as $item) {
            $hints =  array_merge($hints, $item->getHintableClasses($include_scalar));
        }

        if(!empty($this->allof)){
            $hints = array_merge($hints, $this->getHintsFromSchema($this, $include_scalar));
        }



        
        //There must be a cleaner way to do this
        if(empty($hints) && empty($this->properties) && $this->additional_properties instanceof Schema){
            $hints = array_merge($hints, $this->additional_properties->getHintableClasses($include_scalar));
        }


        //Finally, if there's nothing from other properties, it's this.
        if(empty($hints)){
            $hints = array_merge($hints, $this->getHintsFromSchema($this, $include_scalar));
        }

        return $hints;
    }


    /**
     * @param Schema $item
     * @param $include_scalar
     * @return array
     */
    private function getHintsFromSchema(Schema $item, $include_scalar){

        if($item->type === Schema::TYPE_OBJECT) {
            return [$item->getRelativeClassName()];
        } elseif($include_scalar){

            switch($this->type){
                case self::TYPE_BOOLEAN:
                    return ['bool'];
                case self::TYPE_NUMBER :
                case self::TYPE_INTEGER:
                    return ['int'];
                case self::TYPE_STRING :
                    return ['string'];
                default:
                    return ['mixed'];
            }
        }

        return [];
    }


}
