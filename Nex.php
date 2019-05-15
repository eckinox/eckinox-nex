<?php

namespace Eckinox\Nex;

use Eckinox\{
    Eckinox,
    Annotation,
    Event,
    Bootstrap,
    Component,
    singleton
};

use Eckinox\Nex\Driver\ErrorHandler\{
    JavascriptConsole,
    JsonLog,
    Html
};

class Nex extends Component {
    use singleton;

    public function initialize() {
        parent::initialize();

        $this->_define_error_handling();

        Event::make()->on('eckinox.bootstrap.completed', function($e) {
            $annotation = Annotation::instance();

            if ( Eckinox::debug() ) {
                $annotation->autoload();
            }

            $this->_route_application($e);
        });
    }

    protected function _route_from_config($router) {
        foreach( (array) $this->config('Nex.router.routes') as $item) {
            if ( ( $item['autoload'] ?? false ) !== false ) {
                $domain = $item['domain'] ?? '*';
                $method = $item['method'] ?? [];

                foreach($method as $m => $data) {
                    foreach($data as $uri => $action) {
                        $router->add_route($uri, $this->_render_callback($action['obj']), $m);
                    }
                }
            }
        }
    }

    protected function _route_from_annotation($router) {
        foreach(Annotation::instance()->get_methods_list() as $class => $annotation) {
            foreach($annotation['methods'] ?? [] as $method => $definition) {
                if ( $definition['route'] ?? false ) {
                    $m = $definition['route']['method'] ?? Router::ANY;
                    $uri = is_array($definition['route']) ? $definition['route']['uri'] : $definition['route'];
                    $router->add_route($uri, $this->_render_callback("$class->$method", $definition['name']), $m);
                }
            }
        }
    }

    protected function _route_application($e) {
        $router = Router::instance();

        $this->_route_from_config($router);
        $this->_route_from_annotation($router);

        try {
            $callback = $router->route();
        }
        catch( Throwable $e ) {
            return $e->getMessage();
        }

        if ( $callback ) {
            $callback_value = $callback['callback'](...$callback['arguments']);

            switch(true) {
                case $callback_value instanceof \ArrayAccess:
                case is_array($callback_value):
                    header('Content-Type: application/json');
                    echo json_encode($callback_value, ! request::is_ajax() ? JSON_PRETTY_PRINT : 0);
                    break;

                case is_integer($callback_value):
                case is_string($callback_value):
                    echo $callback_value;
                    break;
            }
        }
    }

    protected function _render_callback($obj, $route_name = "") {
        if ( strstr($obj, '::') !== false ) {
            return explode('::', $obj);
        }
        elseif ( strstr($obj, '->') !== false ) {
            list($obj, $function) = explode('->', $obj);

            return function(...$arguments) use ($obj, $function, $route_name) {
                $obj = new $obj();
                $obj->url_current = $route_name;
                return $obj->$function(...$arguments);
            };
        }
        # @todo A better way should be implemented here to differenciate those two  ^ v
        elseif ( strstr($obj, '=>') !== false ) {
            list($obj, $function) = explode('=>', $obj);

            return function(...$arguments) use ($obj, $function) {
                return $obj::instance()->$function(...$arguments);
            };
        }
        elseif ( substr($obj, 0, 1) === '\\' ) {
            return function(...$arguments) use ($obj) {
                return new $obj(...$arguments);
            };
        }

        trigger_error("Unknown callback format used within route '$obj'");
        return false;
    }

    protected function _define_error_handling() {
        $handler = ErrorHandler::instance();

        if ( Eckinox::debug() ) {
            Eckinox::error_reporting( $this->config('Nex.errorhandler.env.development.error_report') );

            #if ( ! request::is_ajax() ) {
#                $handler->register( new JsonLog($this->config('Nex.errorhandler.jsonlog')) );
                $handler->register( new Html($this->config('Nex.errorhandler.html')) );
                $handler->register( new JavascriptConsole($this->config('Nex.errorhandler.javascript')) );
            #}
        #    else {
                # $handler->register( new AjaxResponse($this->config('Nex.errorhandler.ajax')) );
            #}
        }
        else {
           # Eckinox::error_reporting( $this->config('Nex.errorhandler.env.production.error_report') );
            $handler->register( new JsonLog($this->config('Nex.errorhandler.jsonlog')) );
        }
    }

    public static function path_log() {
        return Eckinox::path_var().LOG_DIR;
    }

}
