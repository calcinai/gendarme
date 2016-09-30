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

        $property_schema_id = sprintf('%s/additionalProperties', $schema->id);


//        foreach($node as $property_name => $property){
//
//            $property_schema_id = sprintf('%s/additionalProperties/%s', $schema->id, $property_name);
//
//            if(!$parser->hasSchema($property_schema_id)){
//                $parser->parseNode($property_schema_id, $property);
//            }
//
//            $schema->addProperty($property_name, $parser->getSchema($property_schema_id));
//        }



        return $schema;
    }
}