<?php
/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme;

use Calcinai\Gendarme\Schema\Resolver;

class Schema {

    /**
     * @var Resolver
     */
    private $resolver;

    public function __construct(Resolver $resolver){

        $this->resolver = $resolver;
    }


    public function parse($schema){

        print_r( $this->resolver->getSchema($schema) );
        print_r( $this->resolver->getSchema('#') );



    }
}