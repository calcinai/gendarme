<?php
/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme\Component;

class Schema {

    public $name;

    public function __construct($name){
        $this->name = $name;
    }

}
