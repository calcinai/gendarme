<?php
/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme\Generator;

use PhpParser\Node\Stmt;
use PhpParser\PrettyPrinter\Standard;

class Printer extends Standard {

    public function pStmt_Class(Stmt\Class_ $node) {
        return sprintf("\n%s", parent::pStmt_Class($node));
    }

    public function pStmt_Property(Stmt\Property $node) {
        return sprintf("%s\n", parent::pStmt_Property($node));
    }

    public function pStmt_ClassMethod(Stmt\ClassMethod $node) {
        return sprintf("%s\n", parent::pStmt_ClassMethod($node));
    }
}