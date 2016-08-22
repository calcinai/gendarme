<?php
/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme;

class Schema {

    private $name;
    private $schemas = [];

    public function __construct($name){
        $this->name = $name;
    }

    public function addSchema($schema) {
        $this->schemas[] = $schema;
    }
}
