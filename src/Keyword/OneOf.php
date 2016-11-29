<?php
/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme\Keyword;


use Calcinai\Gendarme\Schema;
use Calcinai\Gendarme\Parser;

class OneOf extends AbstractKeyword {

    /**
     * @param Parser $parser
     * @param Schema $schema
     * @param $node
     * @return Schema
     */
    public static function parse(Parser $parser, Schema $schema, $node) {

        foreach($node as $index => $oneof){

            $oneof_schema_id = sprintf('%s/oneOf/%s', $schema->id, $index);

            $child_schema = $parser->parseNode($oneof_schema_id, $oneof);
            $child_schema->addRequired($schema->getRequired());

            $schema->addOneOf($child_schema);
        }

        return $schema;

    }
}