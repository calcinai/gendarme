<?php
/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme\Keyword;


use Calcinai\Gendarme\Schema;
use Calcinai\Gendarme\Parser;

class Default_ extends AbstractKeyword {

    /**
     * @param Parser $parser
     * @param Schema $schema
     * @param $node
     * @return void|Schema
     */
    public static function parse(Parser $parser, Schema $schema, $node) {

        if(is_scalar($node)){
            return;
        }

        $schema->setDefault($node);
    }
}
