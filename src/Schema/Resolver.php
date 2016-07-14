<?php

/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */
namespace Calcinai\Gendarme\Schema;

class Resolver {

    private $root_schema;
    private $remote_cache;


    const ROOT_CACHE_INDEX = '__root';

    public function __construct() {
        $this->remote_cache = [];
    }


    public function getSchema($ref){

        //Empty string padded to be more flexible with hashes
        list($remote_path, $fragment) = explode('#', $ref, 2) + ['',''];

        //If the remote cache is empty, this is the actual schema to be loaded.
        //There's no other way to get past this.
        if(!isset($this->remote_cache[self::ROOT_CACHE_INDEX]) || empty($remote_path)){
            $cache_key = self::ROOT_CACHE_INDEX;
        } else {
            $cache_key = $remote_path;
        }

        if(!isset($this->remote_cache[$cache_key])){
            $this->remote_cache[$cache_key] = json_decode(file_get_contents($remote_path));
        }

        $schema = $this->remote_cache[$cache_key];

        if(!empty($fragment)){
            //remove leading slash
            $fragment = ltrim($fragment, '/');
            return $this->followFragment($schema, explode('/', $fragment));
        }

        return $schema;
    }


    private function decodeJSONPointer($str) {
        // http://tools.ietf.org/html/draft-ietf-appsawg-json-pointer-07#section-3
        return preg_replace_callback('/~[0-1]/', function($x){
            return $x === '~1' ? '/' : '~';
        }, rawurldecode($str));
    }


    /**
     * Recursively find the target ID
     *
     * @param $schema
     * @param $fragments
     * @return mixed
     * @throws \Exception
     */
    private function followFragment($schema, $fragments){

        if(empty($fragments)){
            return $schema;
        }

        $node = self::decodeJSONPointer(array_shift($fragments));

        if(!isset($schema->$node)){
            throw new \Exception('Bad fragment reference in schema');
        }

        return $this->followFragment($schema->$node, $fragments);

    }
}