<?php
/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme\Keyword;

use Calcinai\Gendarme\Schema;
use Calcinai\Gendarme\Parser;

class AdditionalItems extends AbstractKeyword {

    /**
     * @param Parser $parser
     * @param Schema $schema
     * @param $node
     * @return Schema
     */
    public static function parse(Parser $parser, Schema $schema, $node) {

        if(is_bool($node)){
            $schema->setAdditionalItems($node);
            return $schema;
        }

        $item_schema_id = sprintf('%s/additionalItems', $schema->id);

        $child_schema = $parser->parseNode($item_schema_id, $node);
        $schema->setAdditionalItems($child_schema);

        return $schema;
    }
}