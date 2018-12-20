<?php

namespace Eckinox\Nex\Driver;

use Eckinox\Nex;

use Eckinox\{
    config
};

class Cache {
    use config;
    
    protected $config;

    public function __construct() {
        $this->config = $this->config('Nex.cache');
    }

    public function setExt($ext) {
        $this->config['ext'] = $ext;
    }

    public function setDir($dir) {
        $this->config['dir'] = $dir;
    }

    public function getPath($key) {
        return Eckinox::path_cache() . $this->config['dir'] . Nex\Cache::hash($key) . DIRECTORY_SEPARATOR . $key . $this->config['ext'];
    }

}
