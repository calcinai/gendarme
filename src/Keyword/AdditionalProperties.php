<?php
/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme\Keyword;

use Calcinai\Gendarme\Schema;
use Calcinai\Gendarme\Parser;

class AdditionalProperties extends AbstractKeyword {

    /**
     * @param Parser $parser
     * @param Schema $schema
     * @param $node
     * @return Schema
     */
    public static function parse(Parser $parser, Schema $schema, $node) {

        if(is_bool($node)){
            $schema->setAdditionalProperties($node);
            return $schema;
        }

        $property_schema_id = sprintf('%s/additionalProperties', $schema->id);

        $child_schema = $parser->parseNode($property_schema_id, $node);
        $schema->setAdditionalProperties($child_schema);

        return $schema;
    }
}