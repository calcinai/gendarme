<?php
/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme\Keyword;


use Calcinai\Gendarme\Schema;
use Calcinai\Gendarme\Parser;

class AnyOf extends AbstractKeyword {

    /**
     * @param Parser $parser
     * @param Schema $schema
     * @param $node
     * @return Schema
     */
    public static function parse(Parser $parser, Schema $schema, $node) {

        foreach($node as $index => $anyof){

            $anyof_schema_id = sprintf('%s/anyOf/%s', $schema->id, $index);

            $child_schema = $parser->parseNode($anyof_schema_id, $anyof);
            $child_schema->addRequired($schema->getRequired());

            $schema->addAnyOf($child_schema);
        }

        return $schema;

    }
}