<?php
namespace Eckinox\Nex;

use Eckinox\{
    config,
    Eckinox,
    Event
};

class View_function {
    use config, views;

    protected $extended = [];

    protected $switch_state = [];

    protected $foreach_state = [];

    protected $lang_state = [];

    protected $controller = null;

    protected $uid;

    protected $event;

    protected $section_name = [];

    protected $listen_on = [
        'foreach'    => "foreach_function",
        'or'         => "or_function",
        'endforeach' => "endforeach_function",
        'for'        => "for_function",
        'endfor'     => "endfor_function",
        'if'         => "if_function",
        'else'       => "else_function",
        'elseif'     => "elseif_function",
        'endif'      => "endif_function",
        'view'       => "view_function",
        'extends'    => "extends_function",
        'section'    => "section_function",
        'endsection' => "end_section_function",
        'lang'       => "lang_function",
        'endlang'    => "endlang_function",
        '_'          => "putlang_function",
        'switch'     => "switch_function",
        'case'       => "case_function",
        'endcase'    => "endcase_function",
        'break'      => "endcase_function",
        'default'    => "default_function",
        'endswitch'  => "endswitch_function",
        'url'        => "url_function",
        'route'      => "route_function",
        'dump'       => "dump_function",
        'form'       => "form_function",
        'endform'    => "endform_function",
        'asset'      => "asset_function",
        'popup'      => "popup_function",
    ];


    public function __construct($controller) {
        $this->uid = uniqid();

        $this->controller = $controller;

        $this->event = Event::instance();

        foreach($this->listen_on as $key => $function) {
            $this->event->on("Nex.view.function.$key", [ $this, $function ], [], $this->uid);
        }
    }

    public function __destruct() {
        foreach($this->listen_on as $key => $unused) {
            $this->event->off("Nex.view.function.$key", $this->uid);
        }
    }

    public static function build_section($name, $section_list) {

        foreach([ 'prepend', 'default', 'append' ] as $item) {
            $stack = $section_list[$item] ?? [];

            usort($stack, function($a, $b) {
                return $a['order'] <=> $b['order'];
            });

            foreach($stack as $section) {
                $section['callback']();

                # We quit after first print if this is the default value
                if ( $item === 'default' ) {
                    break 1;
                }
            }
        }

    }

    public function view_function($e, $path) {
        $e->stop_propagation();

        return $e['output'] = "<?php echo \$this->view($path); ?>";

        # We precompile the view and then output it directly into
        # the view, allowing less file read per request.

        # todo ?!
        $uid = '$'.uniqid("nex_view_var");

        $e['output'] = "<?php $uid = get_defined_vars(); ( function() use ($uid) { extract($uid); ?>" . PHP_EOL .
            ($src = $this->controller->view(trim($path, "\"\' \t"), [], true)) . PHP_EOL .
            "<?php })(); unset($uid) ?>";
    }

    public function foreach_function($e, $args) {
        $e->stop_propagation();

        $unique_var = "$".uniqid("foreach_");

        $this->foreach_state[] = [
            'or'   => false,
            'unique_var' => $unique_var,
        ];

        $e['output'] = "<?php foreach ($args): {$unique_var} = true; ?>";
    }

    public function or_function($e) {
        $e->stop_propagation();

        # Setting latest item from array at OR state
        $key = count($this->foreach_state ) - 1;
        $this->foreach_state[$key]['or'] = true;
        $e['output'] = "<?php endforeach; if(empty({$this->foreach_state[$key]['unique_var']})): ?>";
    }

    public function endforeach_function($e) {
        $e->stop_propagation();

        $current = end($this->foreach_state);

        if ( $current['or'] === false ) {
            $e['output'] = "<?php endforeach; ?>";
        }
        else {
            $e['output'] = "<?php endif; ?>";
        }

        array_pop($this->foreach_state);
    }

    public function for_function($e, $args) {
        $e->stop_propagation();
/*
        $unique_var = "$".uniqid("foreach_");

        $this->for_state[] = [
            'or'   => false,
            'unique_var' => $unique_var,
        ];
*/
        $e['output'] = "<?php for ($args):?>"; # {$unique_var} = true; ";
    }

    public function endfor_function($e) {
        $e->stop_propagation();

        $e['output'] = "<?php endfor; ?>";
        /*
        $current = end($this->foreach_state);

        if ( $current['or'] === false ) {
            $e['output'] = "<?php endfor; ?>";
        }
        else {
            $e['output'] = "<?php endif; ?>";
        }

        array_pop($this->foreach_state);*/
    }

    public function if_function($e, $args) {
        $e->stop_propagation();
        $e['output'] = "<?php if ($args): ?>";
    }

    public function else_function($e) {
        $e->stop_propagation();
        $e['output'] = "<?php else: ?>";
    }

    public function elseif_function($e, $args) {
        $e->stop_propagation();
        $e['output'] = "<?php elseif ($args): ?>";
    }

    public function endif_function($e) {
        $e->stop_propagation();
        $e['output'] = "<?php endif; ?>";
    }

    public function switch_function($e, $args) {
        $e->stop_propagation();
        $this->switch_state[] = true;
        $e['output'] = "<?php switch($args):";
    }

    public function case_function($e, $param) {
        $e->stop_propagation();

        if ($this->switch_state) {
            array_pop($this->switch_state);
        }
        else {
            $e['output'] = "<?php ";
        }

        $e['output'] .= "case $param: ?>";
    }

    public function default_function($e, $param) {
        $e->stop_propagation();
        $e['output'] = "<?php default: ?>";
    }

    public function endcase_function($e) {
        $e->stop_propagation();
        $e['output'] = "<?php break; ?>";
    }

    public function endswitch_function($e) {
        $e->stop_propagation();
        $e['output'] = "<?php endswitch; ?>";
    }

    public function extends_function($e, $path) {
        $e->stop_propagation();

        $this->extended[] = $path;

        # Triming string's quotes
        $path = trim($path, "\"\' \t");

        # Adding defined vars into each views
        Event::instance()->on("Nex.view.custom_tags.done", function($e2, &$html) use ($path) {
            $e2->off()->stop_propagation();

            array_pop($this->extended);

            $html .= $this->controller->view($path, [], true);
        });


        $e['output'] = "";
    }

    public function section_function($e, $name) {
        $e->stop_propagation();
        $split = explode('=>', $name, 2);
        $order = 0;
        $name = array_shift($split);
        $key = 'default';

        if ( $split ) {
            $param = json_decode(trim($split[0]), true);

            if ( json_last_error() ) {
                trigger_error("dev: There seem to be a problem with your JSON section's arguments '$name'.", E_USER_WARNING );
            }
            else {
                if ( $param['lang_key'] ?? false ) {
                    foreach($param['lang_vars'] ?? [] as $key => $value) {
                        $vars[] = "'$key' => $value";
                    }

                    $vars = "[" . ( isset($vars) ? implode(',', $vars) : "" ) . "]";
                    $lang = $this->lang_function($e, "'{$param['lang_key']}', $vars");
                }

                if ( $append = $param['append']  ?? false ) {
                    $key = "append";
                }

                if ( $prepend = $param['prepend'] ?? false ) {
                    $key = "prepend";
                }

                $order = $param['order'] ?? 0;
            }
        }

        # Useful to debug unclosed sections from views
        # @todo !
        $this->opened_section = trim($name, "\"\'");

        # Adding defined vars into each views
        $this->section_uid = '$'.uniqid("nex_view_var");
        $this->section_name[] = $name;

        $e['output'] = "<?php {$this->section_uid} = get_defined_vars(); \$this->section[$name] ?? \$this->section[$name] = [];";

        #if ( ! $this->extended ) {
        #    $e['output'] .= " !empty( \$this->section[$name] ) ? " .
        #        " \Eckinox\Nex\View_function::build_section($name, \$this->section[$name]) : (";
        #}
        #else {
            $e['output'] .= " \$this->section[$name]['$key'] ?? \$this->section[$name]['$key'] = [];".
                " \$this->section[$name]['$key'][] = [ 'order' => '$key' === 'default' ? count(\$this->section[$name]['$key']) : $order, 'callback' => ";
        #}

        $e['output'] .= "function() use ({$this->section_uid}) { extract({$this->section_uid}); ?>".
            ( $lang ?? "" );
    }

    public function end_section_function($e) {
        $e->stop_propagation();
        /*$e['output'] = "<?php }]; ?>";
        $e['output'] = ! $this->extended ? "<?php })(); unset({$this->section_uid}) ?>" : "<?php }]; ?>";*/
        $name = array_pop($this->section_name);
        $uid = $this->section_uid;
        $e['output'] = ( ! $this->extended ? "<?php }]; \Eckinox\Nex\View_function::build_section($name, \$this->section[$name]); " : "<?php }];" ) . " unset($uid) ?>";
    }

    public function url_function($e, $param) {
        $e->stop_propagation();

        $e['output'] = '<?php echo $this->url('.$param.'); ?>';
    }

    public function route_function($e, $param) {
        $e->stop_propagation();

        $e['output'] = '<?php echo $this->url_from_name('.$param.'); ?>';
    }

    public function dump_function($e, $param) {
        $e->stop_propagation();

        $e['output'] =  Eckinox::debug() ? "<?php \dump($param); ?>" : "";
    }

    public function form_function($e, $param) {
        $e->stop_propagation();

        if ( $csrf = $this->config('Nex.view.form.security.csrf') ) {
            $csrf_field = $this->config('Nex.view.form.fields.csrf');
        }

        if ( $honeypot = $this->config('Nex.view.form.security.honeypot') ) {
            $honeypot_field = $this->config('Nex.view.form.fields.honeypot');
        }

        return $e['output'] = "<form $param>";
    }

    public function endform_function($e, $param) {
        $e->stop_propagation();

        if ( $csrf = $this->config('Nex.view.form.security.csrf') ) {
            $csrf_field = $this->config('Nex.view.form.fields.csrf');
        }

        if ( $honeypot = $this->config('Nex.view.form.security.honeypot') ) {
            $honeypot_field = $this->config('Nex.view.form.fields.honeypot');
        }

        return $e['output'] = /* CSRF */ (
            $csrf ? '<?php echo form::hidden("' . $csrf_field . '[". md5(__FILE__.__LINE__) ."]", $this->form_csrf( md5(__FILE__.__LINE__), md5(random_bytes(16)) ), [ "onchange" => "console.error(\'CSRF key was changed. An error will most likely occurs in the controller validation code.\')" ]);?>' : ""
        ).
        /* Honeypot */ (
            $honeypot ? '<?php echo form::text("' . $honeypot_field . '", "", "", [ "onchange" => "this.value && console.error(\'A field which should not have changed did [' . $honeypot_field . ']. An error will most likely occurs in the controller validation code.\')", "style" => "position:absolute;visibility:hidden" ]); ?>' : ""
        )."</form>";
    }

    public function asset_function($e, $param) {
        $e->stop_propagation();
        return $e['output'] = "<?php echo \$this->asset($param) ?>";
    }

    public function lang_function($e, $param) {
        $e->stop_propagation();

        /*
        $key_value = trim($param, "\'\"");

        if ( substr(trim($param, "\'\"")) === '.' ) {
            if ( $this->lang_state ) {
                $key = end($this->lang_state);
                $param = "'$key'";
            }
            else {
                trigger_error('Trying to append a lang key to a previously unset lang key');
            }
        }
        */

        $this->lang_state[] = $key = $param;
        return $e['output'] = "<?php \$this->set_lang($key); ?>";
    }

    public function endlang_function($e) {
        $e->stop_propagation();

        array_pop($this->lang_state);

        if ( $this->lang_state ) {
            $key = end($this->lang_state);
        }

        return $e['output'] = $key ?? false ? "<?php \$this->set_lang($key); ?>" : "";
    }

    public function putlang_function($e, $param) {
        $e->stop_propagation();
        return $e['output'] = "<?php echo \$this->_($param); ?>";
    }

    public function popup_function($e, $args) {
        $e->stop_propagation();
        return $e['output'] = "<?php echo \$this->popup($args); ?>";
    }
}
