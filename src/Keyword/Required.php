<?php
/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme\Keyword;


use Calcinai\Gendarme\Schema;
use Calcinai\Gendarme\Parser;

class Enum extends AbstractKeyword {

    /**
     * @param Parser $parser
     * @param Schema $schema
     * @param $node
     * @return Schema
     */
    public static function parse(Parser $parser, Schema $schema, $node) {
        $schema->setEnum($node);

        //Make things easier for the user - if there's only one value allowed, it may as well be set from the start.
        //Potentially should see if it is required first.
        if(count($node) === 1){
            $schema->setDefault(current($node));
        }
    }
}