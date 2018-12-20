<?php namespace Eckinox\Nex;

use Eckinox\{
    Arrayobj,
    config,
    lang
};

use Eckinox\Nex\{
    Stringobj
};

class HtmlNode implements \Iterator, \JsonSerializable {
    use config, lang;
    
    const INSERT_MODE_APPEND   = 1;
    const INSERT_MODE_PREPEND  = 2;
    
    public $name     = "";    # Can be access with selector ":name='blabla'"
    public $tag      = 'div'; # div is default tag when nothing is set
    public $attr     = [
        'style' => []
    ];
    
    public $childs   = [];

    /**
     * supported options are for now :
     *
     *  key                  value           uses
     *  --------------------------------------------------------------------------------------------
     *  single-tag                           Only render a single tag. Cannot contains any childrens
     *  force-tag-open       tag             Control the way the opening tag is rendered
     *  force-tag-close      tag-close       "    "    "    "    closing   "    "    "  
     *  no-attr                              Attributes will not be rendered
     *  no-id                                ID is not automatically given (* may be removed *)
     *  escape-tag-end                       If you need to echo the Node inside another string, it may be useful to escape the "/" char using "\/"
     */
    
    public $options  = [];
    public $selected = null;
    public $content;
    
    public function __construct() {
        $this->options = new Arrayobj();
    }
    
    public static function stylesheet( $attr = [], $options = [] ) {
        return static::create('link', ( is_array($attr) ? $attr : [] ) + array_filter([
            'href'  => is_string($attr) ? $attr : null,
            'rel'   => "stylesheet",
            'type'  => "text/css"
        ]), [ 'tag-type' => 'single' ] + $options);
    }
    
    public static function script( $attr = [], $options = [] ) {
        return static::create('script', ( is_array($attr) ? $attr : [] ) + array_filter([
            'src'  => is_string($attr) ? $attr : null,
            'type' => 'text/javascript',
        ]), $options);
    }
    
    public static function span( $text, $attr = [], $options = [] ) {
        return static::create('span', $attr, $options)->text($text);
    }
    
    public static function meta( $attr = [], $options = [] ) {
        return static::create('meta', $attr, $options);
    }

    public static function create( $tag = "div", $attr = [], $options = [], $name = null ) {
        $obj = new self();
        $obj->tag = $tag;
        $attr && $obj->attr($attr);
        $obj->options = new Arrayobj($options);
        $obj->name    = $name;
        
        if ( $custom = $obj->config("Nex.htmlnode.tags.$tag") ) {
            !empty($custom['tag'])        && ( $obj->tag = $custom['tag'] );
            !empty($custom['attributes']) && $obj->attr($custom['attributes']);
            !empty($custom['options'])    && is_array($custom['options']) && $obj->options->merge($custom['options']);
        }
        
        /* @TODO : Implements selector into $tag so you can create anything 
         *         from string ( div.classname[attr=test] )
         */
        $tag = Stringobj::create($obj->tag);
        
        if ( $tag->contains('.') ) {
            $tag = $tag->split('\.');
            $obj->tag = array_shift($tag);
            $obj->attr['class'] = (isset($obj->attr['class']) ? $obj->attr['class'] : '') . implode(' ', $tag);
        }

        return $obj;
    }

    /* TO DO = complete !
    public static function from_html($content, $element = 'body') {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($content);
        libxml_clear_errors();
        return static::from_dom_node( $dom->getElementsByTagName($element) );
    }
    
    public static function from_dom_node($source) {
        var_dump($source->tagName);
        $node = static::make();
        
        if ( $source->hasChildNodes() ) {
            foreach($source->childNodes as $child) {
                var_dump($child->tagName);
                $node->append( static::from_dom_node( $child ) );
            }
        }

        if ( $source->hasAttributes() ) {
            foreach($source->getAttribute() as $item) {
                #var_dump($item);
            }
        }
        
        return $node;
    }*/

    public function create_in( $tag, $attr = [] ) {
        $this->append(static::create($tag, $attr));
        return $this;
    }

    public function add_class( $classname ) {
        
        if ( key_exists('class', array_change_key_case($this->attr, CASE_LOWER)) ) {
            $result = explode(' ', $this->attr['class']);
            if ( !in_array($classname, $result) ) {
                $result[] = $classname;
                $this->attr['class'] = implode(' ', $result);
            }
        }
        else {
            $this->attr('class', $classname);
        }

        return $this;
    }

    public function remove_class( $classname ) {
        if ( key_exists('class', array_change_key_case($this->attr, CASE_LOWER)) ) {
            $result = explode(' ', $this->attr['class']);
            if ( ($exist = array_search($classname, $result)) !== false ) {
                unset($result[$exist]);
                $this->attr['class'] = implode(' ', $result);
            }
        }

        return $this;
    }

    public function option( $var, $value = 1 ) {
        $this->options[$var] = $value;
        return $this;
    }

    public function render() {
        $attrlist = [];
        
        foreach ( $this->attr as $key => $value ) {
            
            if ( is_array($value) ) {
                if (empty($value)) continue;
                
                if ($key === 'style') {
                    $style = [];
                    
                    foreach($value as $k2 => $v2 ) {
                        $style[] = "$k2:$v2";
                    }
                    
                    $attrlist[] = "$key=\"".implode(';', $style).'"';
                
                }
                else {
                    $attrlist[] = implode(' ', $value);
                }
            }else if ( !is_numeric($key) ) {
                # will output something like  <tag $key=$value></tag>
                $attrlist[] = "$key=\"$value\"";
            }
            else {
                # will output something like  <tag $value></tag>
                $attrlist[] = $value;
            }
            
        }

        $content = "";
        
        foreach ( $this->childs as $item ) {
            if ( is_object($item) ) {
                $content .= $item->render();
            }
            else if ( is_string($item) ) {
                $content .= $item;
            }
        }

        if ( $this->options->exist('no-tag') ) {
            return $this->content . $content;
        }
        else {
            $attrstr = (count($attrlist) && !$this->options->exist('no-attr') ? " " . implode($attrlist, " ") : "");

            # Force the node to contain a certain opening tag (php is a good example)
            if ( $this->options->exist('force-tag-open') ) {
                $opentag = $this->options['force-tag-open'] . $attrstr;
            }
            else {
                $opentag = $this->tag ? "<{$this->tag}" . $attrstr . ">" : "";
            }

            if ( $this->options->exist('tag-type') ) {
                if ( $this->options['tag-type'] === "single" ) {
                    return $opentag;
                }
            }
            else if ( $this->options->exist('force-tag-close') ) {
                $closetag = $this->options['force-tag-close'];
            }
            else {
                $closetag = $this->tag ? "<" . ($this->options->exist('escape-tag-end') ? "\/" : "/" ) . "{$this->tag}>" : "";
            }

            return $opentag . $this->content . $content . $closetag;
        }
    }

    public function html(...$args) {
        if ( !$args ) {
            return $this->content;
        }
        
        $this->content = $args[0];
        return $this;
    }

    public function html_append(...$args) {
        if ( $args = array_filter($args) ) {
            foreach($args as $item) {
                $this->content .= $item;
            }
        }

        return $this;
    }

    public function html_prepend(...$args) {
        if ( $args = array_filter($args) ) {
            foreach($args as $item) {
                $this->content = $item . $this->content;
            }
        }

        return $this;
    }
    
    public function text() {
        $args = func_get_args();
        $args = is_array($args[0] ?? null) ? $args[0] : $args;
        
        if ( $args ) {
            $this->content = htmlspecialchars( $args[0], ENT_NOQUOTES );
        }
        else {
            return $this->content;
        }

        return $this;
    }
    
    public function lang($key) {
        return $this->text( $this->lang»get_from($key) );
    }

    public function text_append( $set = null, $is_lang_key = false ) {
        if ( $set ) {
            $this->content .= htmlspecialchars($is_lang_key ? $this->lang($set) : $set, ENT_NOQUOTES);
        }

        return $this;
    }
    
    public function append( ...$arguments ) {
        return $this->insert( static::INSERT_MODE_APPEND, ...$arguments);
    }

    public function prepend( ...$arguments ) {
        return $this->insert( static::INSERT_MODE_PREPEND, ...(is_array($arguments) ? array_reverse($arguments) : $arguments));
    }
    
    public function insert($insert_mode = self::INSERT_MODE_APPEND, ...$arguments) {
        if ( ! $arguments || !( $count = count($arguments) ) ) {
            return $this;
        }
        
        $insert = function($content) use ( $insert_mode ) {
            if ( self::is_node($content) ) {
                if ($insert_mode === static::INSERT_MODE_APPEND ) {
                    $this->childs[] = $content;
                }
                elseif ($insert_mode === static::INSERT_MODE_PREPEND ) {
                    array_unshift($this->childs, $content);
                }
            }
        };
    
        if ( $count == 1 && !is_array($arguments) ) {
            // Single node to add
            $insert($arguments);
        }
        else {
            // multiple node to add
            foreach ( $arguments as $item ) {
                if ( is_array($item) ) {
                    foreach ( $item as $key => $value ) {
                        if ( !is_numeric($key) ) {
                            $value->name = $key;
                        }

                        $this->insert($insert_mode, $value);
                    }
                }
                else {
                    $insert($item);
                }
            }
        }

        return $this;
    }
    
    
    # Append next to current node.
    # <div id="1"></div>
    # <div id="2"></div>
    public function append_next() {
        # @todo
    }

    public function attr() {
        if ( empty($args = func_get_args()) ) {
            return $this->attr;
        }
        
        if (  is_array( $args[0] ) ) {
            # ->attr(['id' => 'hello', 'class' => 'even'])
            foreach($args[0] as $key => $value) {
                switch((string)$key) {
                    case 'class' :
                        $this->add_class($value);
                        break;
                    
                    case 'style' :
                        foreach($value as $var => $val) {
                            $this->css($var, $val);
                        }
                        
                        break;
                    
                    default:
                        $this->attr($key, is_array($value) ? implode(' ', $value) : $value);
                        break;
                }
            }
        }
        else {
            if ( ($count = count($args)) === 1 ) { 
                return $this->attr[$args[0]] ?? null;
            }
            # ->attr('id', 'hello')
            else if ( $count == 2 ) {
                $this->attr = array_merge_recursive([
                    $args[0] => $args[1]
                ], $this->attr);
            }
        }
        
        return $this;
    }
    
    public function has_attr($key) {
        return isset($this->attr[$key]);
    }
    
    /**
     * Recursive function, allowing both $array as param and $key, $value
     */
    public function css(...$arguments) {
        foreach($arguments as $item) {
            
            if ( is_array($item) ) {
                foreach($item as $key => $value) {
                    $this->css($key, $value);
                }
            }
            else {
                $this->attr['style'][$arguments[0]] = $arguments[1];
            }
        }
 
        return $this;
    }

    public function has_class( $classname ) {
        if ( key_exists('class', array_change_key_case($this->attr, CASE_LOWER)) ) {
            $result = explode(' ', $this->attr['class']);

            if ( in_array($classname, $result) ) {
                return true;
            }
        }

        return $this;
    }

    public function delete() {
        
    }

    public function count() {
        return count($this->childs);
    }
    
    public function html_file($file) {
        # push variable into file
        
        ob_start(function($buffer) {
            $this->html($buffer);
        });
        
        include($file);
        
        ob_end_flush();
    }
    
    public function jsonSerialize() {
        return [
            'tag'     => $this->tag,
            'attr'    => $this->attr,
            'options' => $this->options,
            'childs'  => $this->childs
        ];
    }

    public function offsetSet( $offset, $value ) {
        if ( is_numeric($offset) ) {
            return $this->selected[$offset] = $value;
        }
        elseif ( is_null($offset) ) {
            return $this->childs[] = $value;
        }
        else {
            return $this->childs[$offset] = $value;
        }
    }

    public function rewind() {
        reset($this->childs);
    }

    public function current() {
        $var = current($this->childs);
        return $var;
    }

    public function key() {
        $var = key($this->childs);
        return $var;
    }

    public function next() {
        $var = next($this->childs);
        return $var;
    }

    public function valid() {
        $key = key($this->childs);
        $var = ($key !== NULL && $key !== FALSE);
        return $var;
    }
    
    public static function is_node($obj) {
        return (is_object($obj) && get_class($obj) === __CLASS__);
    }

    public function __toString() {
        return $this->render();
    }
}
