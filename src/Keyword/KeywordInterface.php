<?php
/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme\Keyword;

use Calcinai\Gendarme\Schema;
use Calcinai\Gendarme\Parser;

interface KeywordInterface {
    /**
     * @param Parser $parser
     * @param Schema $schema
     * @param $node
     * @return void|Schema
     */
    public static function parse(Parser $parser, Schema $schema, $node);
}
