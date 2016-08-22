<?php

/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme;

use JsonSchema\SchemaStorage;

class Parser {

    private $json_schema_path;
    private $json_schema;

    public function __construct($schema_file) {

        $this->json_schema_path = sprintf('file://%s', realpath($schema_file));

        $this->json_schema = new SchemaStorage();
        $this->json_schema->addSchema($this->json_schema_path);

        $this->parseNode('root', $this->resolveRef(''));

    }


    public function parseNode($node_name, $node){
        var_dump($node_name);

        if(is_scalar($node)){
            return;
        }

        foreach($node as $child_node_name => $child_node){
            $this->parseNode($child_node_name, $child_node);
        }
    }



    /**
     * Helper to resolve ref in the root local schema
     *
     * @param $ref
     * @return object
     */
    public function resolveRef($ref){
        return $this->json_schema->resolveRef(sprintf('%s#%s', $this->json_schema_path, $ref));
    }
}