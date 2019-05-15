<?php

namespace Eckinox\Nex\Ui;

use Eckinox\{
    config
};

use Eckinox\{
    Annotation
};

class Breadcrumb {
    use config;

    protected $variables = [];

    public function get($var) {
        return $var === false ? $this->variables : $this->variables[$var] ?? false;
    }

    public function set($var, $value) {
        return $var === false? $this->variables = $value : $this->variables[$var] = $value;
    }

    public function from_annotation($obj, $vars = []) {
        $max_count = $this->config('Nex.annotation.breadcrumb.parent_max_count') ?: 255;
        $stack = [];

        $annotation = Annotation::instance();
        $list = $annotation->get_methods_list();

        foreach($list[ get_class($obj) ]['methods'] as $method) {
            if ( $obj->url_current === ( $method['name'] ?? false ) ) {
                if ( $method['breadcrumb'] ?? false ) {
                    $parent  = $method;
                    $stack[] = $method;

                    while ( false !== $parent['breadcrumb']['parent'] ?? false ) {
                        $stack[] = $parent = $annotation->get_from_method_name($parent['breadcrumb']['parent']) ;

                        if ( ! $parent or empty($parent['breadcrumb']) ) {
                            break;
                        }

                        if ( $max_count-- === 0) {
                            trigger_error("Maximum recursivity reached while looking for breadcrumb's parent of {$this->url_current}");
                            exit();
                        }
                    }
                }
            }
        }

        return array_reverse($stack);
    }
}
