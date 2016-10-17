<?php
/**
 * @package    gendarme
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Gendarme;


class ClassResolver {

    private $namespace;

    private $class_names = [];
    private $class_aliases = [];
    private $namespaces = [];

    public function __construct($namespace) {
        $this->namespace = $namespace;
    }

    public function addClass($relative_class_name){

        $fq_class = $relative_class_name;

        if(!empty($this->namespace)){
            $fq_class = rtrim(sprintf('%s\\%s', $this->namespace, $fq_class));
        }

        //If it's already been processed, pass it back
        if(isset($this->class_names[$fq_class])){
            return $fq_class;
        }

        $last_slash_pos = strrpos($fq_class, '\\');

        $class_name = substr($fq_class, $last_slash_pos +1);
        $namespace = substr($fq_class, 0, $last_slash_pos);

        $this->namespaces[$fq_class] = $namespace;
        $this->class_names[$fq_class] = $class_name;

        if(false === array_search($class_name, $this->class_aliases)){
            //Doesn't need an alias
            $this->class_aliases[$fq_class] = null;
        } else {
            //Append some random(enough) at the end
            $this->class_aliases[$fq_class] = sprintf('%s%s', $class_name, substr(md5(microtime()), rand(0, 26), 5));
        }

        return $fq_class;
    }

    public function getNamespace($fq_class){
        return $this->namespaces[$fq_class];
    }

    public function getClassName($fq_class){
        return $this->class_names[$fq_class];
    }

    public function getClassAlias($fq_class){
        return $this->class_aliases[$fq_class];
    }

    public function getForeignClasses() {
        foreach($this->namespaces as $fq_class => $namespace){
            if($namespace !== $this->namespace){
                yield $fq_class;
            }
        }
    }

}