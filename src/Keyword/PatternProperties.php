<?php
/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme\Keyword;

use Calcinai\Gendarme\Schema;
use Calcinai\Gendarme\Parser;

class PatternProperties extends AbstractKeyword {

    /**
     * @param Parser $parser
     * @param Schema $schema
     * @param $node
     * @return Schema
     */
    public static function parse(Parser $parser, Schema $schema, $node) {

        foreach($node as $property_name => $property){

            $property_schema_id = sprintf('%s/patternProperties/%s', $schema->id, strtr($property_name, ['/' => '~1', '~' => '~0', '%' => '%25']));

            if(!$parser->hasSchema($property_schema_id)){
                $parser->parseNode($property_schema_id, $property);
            }

            $schema->addPatternProperty($property_name, $parser->getSchema($property_schema_id));
        }

        return $schema;
    }
}