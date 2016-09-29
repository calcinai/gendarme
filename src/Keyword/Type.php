<?php
/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme\Keyword;


use Calcinai\Gendarme\Schema;
use Calcinai\Gendarme\Parser;

class Type extends AbstractKeyword {

    /**
     * @param Parser $parser
     * @param Schema $schema
     * @param $node
     * @return Schema
     */
    public static function parse(Parser $parser, Schema $schema, $node) {

        $schema->setType($node);

        switch($node){
            case 'array':

            case 'object':


            case 'boolean':
            case 'integer':
            case 'null':
            case 'number':
            case 'string':
            default:
                //Scalar property

        }
    }
}