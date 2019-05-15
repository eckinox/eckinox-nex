<?php namespace Eckinox\Nex\Controller;

use Eckinox;

use Eckinox\Nex\{
    url_function,
    Migrate
};

use Eckinox\Nex;

class Tools {
    use url_function;

    public function __construct(...$uri) {
        $this->url_route(...$uri);
    }

    public function index() {
        echo "<h1>This is the new tool section of Nex's framework ...</h1>";
    }

    public function profiler() {
        echo "Welcome to the profiler!";
    }

    public function setup() {
        Eckinox\Eckinox::instance()->create_cms_folders();
    }

    public function migrate() {
        Migrate\Migrate::instance()->autoload()->migrate();
    }
}
