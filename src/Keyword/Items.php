<?php
/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme\Keyword;

use Calcinai\Gendarme\Schema;
use Calcinai\Gendarme\Parser;

class Items extends AbstractKeyword {

    /**
     * @param Parser $parser
     * @param Schema $schema
     * @param $node
     * @return Schema
     */
    public static function parse(Parser $parser, Schema $schema, $node) {

        $property_schema_id = sprintf('%s/items', $schema->id);

        if(!$parser->hasSchema($property_schema_id)){
            $parser->parseNode($property_schema_id, $node);
        }

        $schema->setItems($parser->getSchema($property_schema_id));

        return $schema;
    }
}