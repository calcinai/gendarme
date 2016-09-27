<?php
/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme\Component;

class Schema {

    public $id;
    public $properties;
    public $pattern_properties;

    public function __construct($id){
        $this->id = $id;
    }

    public function addProperty($property_name, Property $property) {
        $this->properties[$property_name] = $property;
    }

    public function addPatternProperty($property_name, $property) {
        $this->pattern_properties[$property_name] = $property;

    }

}
