<?php

namespace Eckinox\Nex;

use Eckinox\{
    Arrayobj,
    Annotation,
    config,
    singleton
};

class Router {
    use config, singleton;

    const GET = 'get';
    const POST = 'post';
    const PUT = 'put';
    const DELETE = 'delete';
    const PATCH = 'patch';
    const ANY = 'any';

    protected $config = [
        'base'   => '/',
        'domain' => [],
        'port'   => [ 80, 443 ]
    ];

    protected $routes = [
        self::GET => [],
        self::POST => [],
        self::PUT => [],
        self::DELETE => [],
        self::PATCH => [],
        self::ANY => []
    ];

    protected function __construct($config = []) {
        $this->config = array_replace_recursive($this->config, $config);
    }

    public function get_method($route, $callable) {
        return $this->add_route($route, $callable, static::GET);
    }

    public function post_method($route, $callable) {
        return $this->add_route($route, $callable, static::POST);
    }

    public function patch_method($route, $callable) {
        return $this->add_route($route, $callable, static::PATCH);
    }

    public function put_method($route, $callable) {
        return $this->add_route($route, $callable, static::PUT);
    }

    public function delete_method($route, $callable) {
        return $this->add_route($route, $callable, static::DELETE);
    }

    public function add_route($uri, $callback, $method = self::ANY) {
        $this->routes[$method][implode('/', $this->uri_segments($uri))] = $callback;
        return $this;
    }

    /**
     * Connexion's method used to reach the HTTP server (GET, POST, etc...)
     *
     * @return Type    Description
     */
    public function method() {
        return strtolower($_SERVER['REQUEST_METHOD'] ?? static::GET);
    }

    /**
     * Port used by server to which we can specify a route
     * default port listening to are 80 and 443
     */
    public function port() {
        return $_SERVER['REQUEST_METHOD'] ?? 80;
    }

    /**
     * Routing occures in this section. Each route is analyzed, and the ones matching the closest
     * route will see itself dispatched as it should.
     *
     * Note that if you define a route with only a slash, it will be the default fallback URL returned.
     *
     * @return array
     */
    public function route() {
        $method    = $this->method();
        $port      = $this->port();
        $callback  = null;
        $uri_parts = Arrayobj::array_filter_string(explode('/', $this->uri(false))) ?: [''];

        $args = [];

        while ($uri_parts) {
            $uri = implode('/', $uri_parts);

            if ($callback = $this->_find_matching_route($uri, [ $method, static::ANY ])) {
                break;
            }

            $args[] = array_pop($uri_parts);
        }

        if (!$callback) {
            $callback = $this->_find_matching_route('');
        }

        return $callback ? [
            'callback' => $callback,
            'arguments' => array_reverse($args)
        ] : false;
    }

    /**
     * Will try to find a matching route from given methods (and in given order).
     * A good pratice would be to allow only the protocol method used and static::ANY as a fallback
     *
     * @param string $uri       A cleaned uri
     * @param string $method    Order into which to check for route
     *
     * @return mixed            Will return a callback to call. Else will return FALSE if no routes matches
     */
    protected function _find_matching_route($uri, $methods = [ self::GET, self::POST, self::PUT, self::DELETE, self::PATCH, self::ANY ]) {

        foreach ($methods as $item) {
            if (isset($this->routes[$item][$uri])) {
                return $this->routes[$item][$uri];
            }
        }

        return false;
    }

    /**
     * Check if the current URI contains index.php or not
     * And return the Uri segments in array
     * @param string            $uri complete uri to be segmented
     * @return Array            $segments Array of cleaned segments
     */
    public function uri_segments($uri = null) {
        return $this->secure(array_filter(explode('/', url::stripQuery($uri ?: static::uri()))));
    }

    /**
     * Return URI formatted
     */
    public function uri($query_string = false) {
        $uri = $_SERVER['REQUEST_URI'] ?? $this->php_self() . ($query_string ? $this->query_string() : "");

        if ( ! $query_string ) {
            $uri = explode('?', $uri, 2)[0];
        }

        if (($base = $this->config['base'] ?? false) && ( stripos($uri, $base) === 0 )) {
            $uri = substr($uri, strlen($base));
        }
        return '/' . ltrim($uri, '/');
    }

    public function php_self() {
        return isset($_SERVER['PHP_SELF']) ? str_replace(NEX . '/', '', $_SERVER['PHP_SELF']) : null;
    }

    /**
     * Return query string
     */
    public function query_string() {
        return isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : "";
    }

    /**
     * Check if Uri's segments are valid
     * @param array $segments The uri's segments
     */
    public function secure($segments = []) {
        return array_diff($segments, ['..', '://']);
    }

    public function domain() {
        $domain_list = (array) $this->config('Eckinox.system.url.root');
        $current = strtolower($_SERVER['HTTP_HOST']);

        return $domain = in_array($current, $domain_list) ? $current : $domain_list[0];
    }

}
