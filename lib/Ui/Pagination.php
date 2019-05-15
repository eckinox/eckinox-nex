<?php

namespace Eckinox\Nex\Ui;

use Eckinox\Nex\views;

class Pagination {
    use views;

    protected $limit = 0;

    protected $offset = 0;

    protected $page = 1;

    protected $count = 0;

    protected $url = "";

    protected $parameters = [];

    public function __construct($page = 1, $limit = 0, $count = 0, $url = "", $parameters = []) {
        $this->page($page);
        $this->limit($limit);
        $this->count($count);
        $this->url($url);
        $this->parameters($parameters);
    }

    public function url($set = null) {
        return $set === null ? $this->url : $this->url = $set;
    }

    public function parameters($set = null) {
        return $set === null ? $this->parameters : $this->parameters = $set;
    }

    public function page($set = null) {
        return $set === null ? $this->page : $this->page = (int) $set;
    }

    public function limit($set = null) {
        return $set === null ? $this->limit : $this->limit = (int) $set;
    }

    public function count($set = null) {
        return $set === null ? $this->count : $this->count = (int) $set;
    }

    public function page_count() {
        return (int) ceil( $this->count() / $this->limit() );
    }

    public function offset() {
        return ( $this->page() - 1 ) * $this->limit();
    }

    public function required() {
        return $this->count() > $this->limit();
    }

}
