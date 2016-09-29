<?php

/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme;

use Calcinai\Gendarme\Keyword;
use JsonSchema\SchemaStorage;

class Parser {

    private $json_schema_path;
    private $schema_stroage;

    private $schemas = [];
    private $schema_aliases = [];

    public function __construct($schema_file) {

        $this->json_schema_path = sprintf('file://%s', realpath($schema_file));

        $this->schema_stroage = new SchemaStorage();
        $this->schema_stroage->addSchema($this->json_schema_path);

    }


    public function parse(){

        $root_schema_id = sprintf('%s#', $this->json_schema_path);
        $root_schema = $this->schema_stroage->resolveRef($root_schema_id);

        $this->parseNode($root_schema_id, $root_schema);
    }


    public function parseNode($json_schema_id, $json_schema){

        if(isset($json_schema->{'$ref'})){
            $this->addSchemaAlias($json_schema_id, $json_schema->{'$ref'});

            $json_schema_id = $json_schema->{'$ref'};
            $json_schema = $this->schema_stroage->resolveRef($json_schema_id);
        }

        $schema = $this->getSchema($json_schema_id);

        //More of these need implementing
        //Could probably be dynamic too.
        foreach($json_schema as $keyword => $node){

            switch($keyword){
                case 'id':
                    break;
                case '$schema':
                    break;
                case 'title':
                    break;
                case 'description':
                    Keyword\Description::parse($this, $schema, $node);
                    break;
                case 'default':
                    Keyword\Default_::parse($this, $schema, $node);
                    break;
                case 'multipleOf':
                    break;
                case 'maximum':
                    break;
                case 'exclusiveMaximum':
                    break;
                case 'minimum':
                    break;
                case 'exclusiveMinimum':
                    break;
                case 'maxLength':
                    break;
                case 'minLength':
                    break;
                case 'pattern':
                    break;
                case 'additionalItems':
                    break;
                case 'items':
                    Keyword\Items::parse($this, $schema, $node);
                    break;
                case 'maxItems':
                    break;
                case 'minItems':
                    break;
                case 'uniqueItems':
                    break;
                case 'maxProperties':
                    break;
                case 'minProperties':
                    break;
                case 'required':
                    break;
                case 'additionalProperties':
                    break;
                case 'definitions':
                    break;
                case 'properties':
                    Keyword\Properties::parse($this, $schema, $node);
                    break;
                case 'patternProperties':
                    Keyword\PatternProperties::parse($this, $schema, $node);
                    break;
                case 'dependencies':
                    break;
                case 'enum':
                    Keyword\Enum::parse($this, $schema, $node);
                    break;
                case 'type':
                    Keyword\Type::parse($this, $schema, $node);
                    break;
                case 'allOf':
                    Keyword\AllOf::parse($this, $schema, $node);
                    break;
                case 'anyOf':
                    Keyword\AnyOf::parse($this, $schema, $node);
                    break;
                case 'oneOf':
                    Keyword\OneOf::parse($this, $schema, $node);
                    break;
                case 'not':
                    break;
            }

        }

    }


    public function hasSchema($schema_id){
        $schema_id = $this->getRealSchemaID($schema_id);

        return isset($this->schemas[$schema_id]);
    }

    /**
     * @param $schema_id
     * @return Schema
     */
    public function getSchema($schema_id){

        $schema_id = $this->getRealSchemaID($schema_id);

        if(!isset($this->schemas[$schema_id])){
            $this->schemas[$schema_id] = new Schema($schema_id);
        }

        return $this->schemas[$schema_id];
    }

    /**
     * @param Schema $schema
     * @return $this
     */
    public function addSchema(Schema $schema){
        $this->schemas[$schema->id] = &$schema;
        return $this;
    }

    /**
     * @return Schema[]
     */
    public function getSchemas() {
        return $this->schemas;
    }

    private function addSchemaAlias($json_schema_id, $real_schema_id) {
        $this->schema_aliases[$json_schema_id] = $real_schema_id;
    }


    public function getRealSchemaID($json_schema_id){
        if(isset($this->schema_aliases[$json_schema_id])){
            return $this->schema_aliases[$json_schema_id];
        }

        return $json_schema_id;
    }

}