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

            if(!$parser->hasSchema($anyof_schema_id)){
                $parser->parseNode($anyof_schema_id, $anyof);
            }

            $schema->addAnyOf($parser->getSchema($anyof_schema_id));
        }

        return $schema;

    }
}