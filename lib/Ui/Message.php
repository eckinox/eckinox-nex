<?php

namespace Eckinox\Nex\Ui;

use Eckinox\config,
    Eckinox\Arrayobj;

use Eckinox\Nex\{
    sessions,
    HtmlNode
};

class Message {
    use config, sessions;

    protected $session_key;

    const TYPE_ALL      = 0;
    const TYPE_ERROR    = 1;
    const TYPE_WARNING  = 2;
    const TYPE_INFO     = 3;
    const TYPE_SUCCESS  = 4;

    public $main_node = null;

    protected $message_list = [];

    protected $message_type = [];

    public function __construct($session_key = 'Nex.ui.message') {
        $this->session_key = $session_key;
        # $this->session($this->session_key, null);
        $this->message_type = $this->config('Nex.ui.message.type');
        $this->message_list = array_filter( $this->session( $this->session_key ) ?: [] );
    }

    public function add($key, $message, $title = "", $type = self::TYPE_ERROR, $persistent = false, $priority = 100) {
        $this->message_list[$key] = [
            'type'       => $type,
            'type_str'   => $this->message_type[$type],
            'content'    => $message,
            'priority'   => $priority,
            'persistent' => $persistent,
            'title'      => $title
        ];

        if ( $persistent ) {
            $this->session($this->session_key.".".str_replace(".", "~", $key), $this->message_list[$key]);
        }

        return $this;
    }

    public function clear() {
        $this->message_list = [];
        $this->session($this->session_key, null);
    }

    public function remove($key) {
        unset($this->message_list[$key]);
        $this->session($this->session_key.".".str_replace(".", "~", $key), null);
        return $this;
    }

    public function message_list($key = null) {
        return $key === null ? $this->message_list : $this->message_list[$key] ?? false;
    }

    public function output($type = self::TYPE_ALL) {
        $empty = true;

        $this->main_node = HtmlNode::create('nex-message');

        if ( $list = $this->message_list() ) {

            Arrayobj::order_by($list, 'priority');
            foreach($type ? (array) $type : range(static::TYPE_ALL, static::TYPE_SUCCESS) as $type) {
                foreach($list as $key => $item) {
                    if ( $item['type'] == $type ) {
                        $this->main_node->append(
                            $this->render_message($key)
                        );

                        $empty = false;
                        $this->remove($key);
                    }
                }
            }
        }

        if ( $empty ) {
            $this->main_node->attr([ 'hidden' ]);
        }

        return $this->main_node;
    }

    public function has_error() {
        return $this->has(static::TYPE_ERROR);
    }

    public function error($key, $message, $title = "", $persistent = false, $priority = 100) {
        return $this->add($key, $message, $title, static::TYPE_ERROR, $persistent, $priority);
    }

    public function render_error() {
        return $this->output(static::TYPE_ERROR);
    }

    public function has_warning() {
        return $this->has(static::TYPE_WARNING);
    }

    public function warning($key, $message, $title = "", $persistent = false, $priority = 100) {
        return $this->add($key, $message, $title, static::TYPE_WARNING, $persistent, $priority);
    }

    public function render_warning() {
        return $this->output(static::TYPE_WARNING);
    }

    public function has_info() {
        return $this->has(static::TYPE_INFO);
    }

    public function info($key, $message, $title = "", $persistent = false, $priority = 100) {
        return $this->add($key, $message, $title, static::TYPE_INFO, $persistent, $priority);
    }

    public function render_info() {
        return $this->output(static::TYPE_INFO);
    }

    public function has_success() {
        return $this->has(static::TYPE_SUCCESS);
    }

    public function success($key, $message, $title = "", $persistent = false, $priority = 100) {
        return $this->add($key, $message, $title, static::TYPE_SUCCESS, $persistent, $priority);
    }

    public function render_success() {
        return $this->output(static::TYPE_SUCCESS);
    }

    public function render_all($order = [ self::TYPE_ERROR, self::TYPE_WARNING, self::TYPE_INFO, self::TYPE_SUCCESS ]) {
        return implode('', array_map(function($key) {
            return $this->output($key);
        }, $order));
    }

    public function render_message($key, $item = null, $wrap = false) {
        $item || ( $item = $this->message_list($key) );

        $message = HtmlNode::create('div', [
            'class' => implode(' ', [ "nex-message-item", "type-".$this->message_type[ $item['type'] ] ])
        ])->append( HtmlNode::create('span')->html( $item['content'] ) );

        return $wrap ? $this->output_wrapper()->append($message) : $message;
    }

    public function output_wrapper() {
        return HtmlNode::create('div', [
            'class' => "nex-ui-message-wrapper",
        ]);
    }

    public function has($type) {
        foreach($this->message_list() as $item) {
            if ($item['type'] === $type) {
                return true;
            }
        }
    }
}
