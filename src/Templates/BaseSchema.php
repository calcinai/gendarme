<?php

/**
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme\Templates;

abstract class BaseSchema {

    /**
     * Pattern matching validation
     * @param $name
     * @param $value
     */
    public function __set($name, $value) {


    }


    public static function create($data = null) {
        return new static($data);
    }

}