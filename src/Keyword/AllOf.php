<?php
/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme\Keyword;


use Calcinai\Gendarme\Schema;
use Calcinai\Gendarme\Parser;

class AllOf extends AbstractKeyword {

    /**
     * @param Parser $parser
     * @param Schema $schema
     * @param $node
     * @return Schema
     */
    public static function parse(Parser $parser, Schema $schema, $node) {

        foreach($node as $index => $allof){

            $allof_schema_id = sprintf('%s/allOf/%s', $schema->id, $index);

            $child_schema = $parser->parseNode($allof_schema_id, $allof);
            $schema->addAllOf($child_schema);
        }

        return $schema;

    }
}