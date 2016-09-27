<?php

/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme;

use Calcinai\Gendarme\Component\Property;
use Calcinai\Gendarme\Component\Schema;
use JsonSchema\Exception\UnresolvableJsonPointerException;
use JsonSchema\SchemaStorage;

class Parser {

    private $json_schema_path;
    private $json_schema;

    private $schemas = [];

    public function __construct($schema_file) {

        $this->json_schema_path = sprintf('file://%s', realpath($schema_file));

        $this->json_schema = new SchemaStorage();
        $this->json_schema->addSchema($this->json_schema_path);

        //        return $this->json_schema->resolveRef(sprintf('%s%s', $this->json_schema_path, $ref));

        $this->parseSchema(sprintf('%s#', $this->json_schema_path));

    }


    public function parseSchema($schema_id){

        if($this->getSchema($schema_id) !== null){
            return;
        }


        $schema = new Schema($schema_id);

        switch($this->json_schema->resolveRef($schema_id)->type){
            case 'boolean':
            case 'integer':
            case 'null':
            case 'number':
            case 'string':
                $schema->addProperty(new Property())
                break;
            case 'array':
                break;
            case 'object':
                print_r($this->json_schema->resolveRef($schema_id)->properties);
                break;

        }











        try {
            $properties = $this->json_schema->resolveRef(sprintf('%s/properties', $schema_id));

            print_r($properties);

            //process properties

        } catch (UnresolvableJsonPointerException $e){
            //Fall through
        }



        //process unreferenced definitions
//        try {
//            $definitions = $this->json_schema->resolveRef(sprintf('%s/definitions', $schema_id));
//
//            foreach($definitions as $definition_name => $definition){
//                $definition_id = sprintf('%s/%s', $schema_id, $definition_name);
//                $this->parseSchema($definition_id);
//            }
//
//            //process definitions
//
//        } catch (UnresolvableJsonPointerException $e){
//            //Fall through
//        }



        $this->addSchema($schema_id, $schema);

//        $schema = new Schema($node_ref);
//        $this->schemas[] = $schema;
//
//        if(isset($node->{'$ref'}) && !isset($this->schemas[$node->{'$ref'}])){
//            //It's a ref that hasn't been parsed
//            $node = $this->resolveRef($node->{'$ref'});
//        }
//
//        foreach($node as $property_name => $property){
//            var_dump($property_name);
//        }
        //print_r($node->properties->info);
//        if(is_scalar($node)){
//            return;
//        }
//
//        foreach($node as $child_node_name => $child_node){
//            $this->parseNode($child_node_name, $child_node);
//        }
    }



    public function getSchema($schema_id){
        if(isset($this->schemas[$schema_id])){
            return $this->schemas[$schema_id];
        }

        return null;
    }


    public function addSchema($schema_id, Schema $schema){
        $this->schemas[$schema_id] = $schema;
        return $this;
    }


}