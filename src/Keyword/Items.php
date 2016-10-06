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

        $child_schema = $parser->parseNode($property_schema_id, $node);
        $schema->setItems($child_schema);

        //If we encounter items, it implies it's an object.
        $schema->setType(Schema::TYPE_ARRAY);

        return $schema;
    }
}