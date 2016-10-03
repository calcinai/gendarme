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
            $schema->allow_additional_properties = $node;
            return $schema;
        }

        $property_schema_id = sprintf('%s/additionalProperties', $schema->id);

        if(!$parser->hasSchema($property_schema_id)){
            $parser->parseNode($property_schema_id, $node);
        }

        $schema->setAdditionalProperties($parser->getSchema($property_schema_id));

        return $schema;
    }
}