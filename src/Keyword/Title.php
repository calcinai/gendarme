<?php
/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme\Keyword;

use Calcinai\Gendarme\Schema;
use Calcinai\Gendarme\Parser;

class Title extends AbstractKeyword {

    /**
     * @param Parser $parser
     * @param Schema $schema
     * @param $node
     * @return Schema
     */
    public static function parse(Parser $parser, Schema $schema, $node) {
        $schema->setTitle($node);
        return $schema;
    }
}