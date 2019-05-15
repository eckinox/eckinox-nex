<?php

namespace Eckinox\Nex;

use Eckinox\Eckinox,
    Eckinox\Git;

trait asset {
    public function asset($url, $param = [], $path = "asset") {
        $v = $this->_get_asset_version();
        return url::addParam(url::site(ltrim("$path/$url", '/')), [ 'v' => $v ] + $param);
    }

    protected function _get_asset_version() {
        static $version = null;
        return $version ?: $version = ( Eckinox::debug() ? uniqid("debug") : Git::instance()->getCommit() );
    }
}
