<?php

namespace Eckinox\Nex;

use Eckinox\Eckinox,
    Eckinox\Exception,
    Eckinox\Event,
    Eckinox\lang,
    Eckinox\config,
    Eckinox\apps;

class View {
    use config, lang;
    
    const DEFAULT_EXT = "phtml";

    // Added vars to template
    protected $vars = [];
    
    protected static $global_vars = [];
    
    // View that share vars data
    protected $shares = [];
    
    // Path to app
    protected $app_path;
    
    // Full path to template
    protected $full_path;

    // Original path given as parameter
    protected $given_path;
    
    // Original object
    protected $given_object;
    
    // View path
    protected $view_path;
    
    // Cache path
    protected $cache_path;
    
    // Can be like 16/my_template.phtml.php
    protected $cache_key; 
    
    // Extension used for views
    protected $ext;
    
    // Configuration of view
    protected $config;
    
    // Lang file used
    protected $lang_file;
    
    // Lang vars passed at every call
    protected $lang_vars = [];

    protected $component = "";
    
    /*
     * Create a new instance of the class
     * Store the include path
     *
     * @param string       $file_path Path to the template starting from view/
     * @param bool         $system Display an Engine's Template
     */

    public function __construct($file_path, $object) {
        $this->given_path = $original_path = $file_path;
        $this->given_object = $object;
        
        $this->component = apps::component_of($object)[0];
        
        $this->config = $this->config('Nex.view');

        $this->ext = ( $this->config['ext'] !== null ) ? $this->config['ext'] : static::DEFAULT_EXT;
        
        $this->view_path = "$file_path.{$this->ext}";

        $file_path = apps::find_from($object, VIEW_DIR, $this->view_path);
        
        if (!$file_path || !is_file($file_path)) {
            throw new Exception('View file could not be found : "' . $this->view_path . '" ', NEX_E_VIEW_LOAD);
            return false;
        }

        $this->cache_key = static::cache_name($file_path, $original_path);
        
        $this->full_path = $file_path;
    }

    public function getIncludePath() {
        return $this->full_path;
    }

    public function getOriginalIncludePath() {
        return $this->original_full_path;
    }

    public function inlineView($path, $vars = []) {        
        return $this->view($path, $vars);
    }
    
    /**
     * Render a view in a view
     */
    public function view($path, $vars = []) {
        
        if ( substr($path, 0, 1) !== '/' ) {
            $path = substr($this->given_path, 0, strrpos($this->given_path, '/'))."/".$path;
        }
        
        $view = new static($path, $this->given_object);
        $view->assign(array_merge($this->vars, $vars));
        $view->setLangFile($this->lang_file);
        
        return $view->render();
    }

    /**
     * Compile a view into cache file
     * Does it only if compiled file doesnt exist or is older then view file
     */
    public function compile($use_cache = false) {
                
        // Cache file creation, stop compilation if cache file recent enought exist
        $cache_path = Eckinox::path_tmp() . $this->config['cache_dir'] . dirname($this->cache_key) . DIRECTORY_SEPARATOR;
        $cache_file = basename($this->cache_key) .".phtml";

        $view_path = $this->full_path;
        $this->full_path = $cache_path . $cache_file;

        if ( ! file_exists($cache_path) ) {
            mkdir($cache_path, 0775, true);
        }
        
        if ( $use_cache ) {
            if ( file_exists( $this->full_path ) ) {
               return true;
            }
        }

        $view_content = $this->_handle_custom_tags(file_get_contents($view_path));
        
        $handler = fopen($this->full_path, 'w');
        fwrite($handler, $view_content);
        fclose($handler);

        return true;
    }

    /**
     * Store a new variable on the fly
     *
     * @param string        $name - Name of variable
     * @param string        $value - Value of variable
     */
    public function set($name, $value) {
        if (is_object($value) && ($value instanceof self)) {
            $str = $value->render(false);
            $this->vars[$name] = $str;
        } else {
            $this->vars[$name] = $value;
        }

        return $this;
    }

    /**
     * Get var from view
     * @param string $name
     */
    public function get($name) {
        if (isset($this->vars[$name])) {
            return $this->vars[$name];
        } elseif (isset(self::$global_vars[$name])) {
            return self::$global_vars[$name];
        }

        return null;
    }

    /**
     * Assign vars to inner variables
     * @param array $vars - associative array
     * @param bool $global goes in global array ( Available to all views )
     */
    public function assign($vars, $global = false) {
        if ($global === true) {
            self::$global_vars = array_merge(self::$global_vars, (array) $vars);
        } else {
            $this->vars = array_merge($this->vars, (array) $vars);
        }

        return $this;
    }

    public static function assignGlobal($vars) {
        self::$global_vars = array_merge(self::$global_vars, (array) $vars);
    }

    /**
     * Share vars to view in argument
     * @param View Passed by reference
     */
    public function share(&$view) {
        $view->assign($this->vars);

        return $this;
    }

    /**
     * Remove a variable from the view
     * @param string        $name - name of variable
     * @return string       value of the var removed
     */
    public function delete($name) {
        if (!isset($this->vars[$name])) {
            return false;
        }

        $var = $this->vars[$name];
        unset($this->vars[$name]);

        return $var;
    }
    
    public function vars() {
        return $this->vars;
    }

    /*
     * Render the template with the php vars
     * @param bool $_now display output or store it var
     */

    public function render($_now = false) {
        // Extract variables from normal var array
        extract(array_merge(self::$global_vars, $this->vars), EXTR_SKIP);

        // Start fresh buffer
        ob_start();

        $this->compile( ! Eckinox::debug() );
        
        if ($this->config('Eckinox.system.debug.view'))
            echo "<script>console.log('{$this->original_full_path}')</script>";

        include($this->full_path);

        // Get content
        $_output = ob_get_clean();

        // return if now === false
        if ( ! $_now ) {
            return $_output;
        }

        echo $_output;

        return true;
    }

    public function _($lang, $vars = []) {
        return $this->lang($this->lang_file . '.' . $lang, array_merge($vars, $this->lang_vars));
    }

    public function setLangFile($file) {
        $this->lang_file = $file;
    }

    public function setLangVars($vars = []) {
        $this->lang_vars = $vars;
    }
    
    public function given_path($set = null) {
        return $set === null ? $this->given_path : $this->given_path = $set;
    }
    
    public function relative_path($path = "", $fallback_path) {
        return substr($path, 0, 1) !== '/' ? substr($this->given_path, 0, strrpos($this->given_path, '/'))."/".$path : $fallback_path;
    }

    /**
     *    This function handles custom tags placed into views and replaces
     *    them with the appropriate output.
     *
     *    The order of execution here is really important since comments override
     *    every other tags, and lang tags have the same beginning than php's tags
     */
    protected function _handle_custom_tags($html) {
        # handles comments "{# this is a comment #}"
        $html = preg_replace("#({$this->config['comment_open_tag']})(.*?)({$this->config['comment_close_tag']})#s", "", $html);
        
        # handles "{{_'langkey' .." and "{{__'langkey' " cases
        $html = preg_replace("/(?<!\{\\\})({$this->config['open_slang_tag']})\s*(.*)\s*\}\}/uU", '<?php echo $this->lang(\'' . $this->lang_file . '.\'.$2) ?>', $html); // Replace short lang tags
        $html = preg_replace("/(?<!\{\\\})({$this->config['open_lang_tag']})\s*(.*)\s*\}\}/uU", '<?php echo $this->lang($2) ?>', $html); // Replace lang tags
        
        # handles "{{ $code .." and "{{= $code .." cases
        $html = preg_replace("/(?<!\{\\\})({$this->config['open_echo_tag']})/u", '<?php echo ', $html); // Replace echo opening tag
        $html = preg_replace("/(?<!\{\\\})({$this->config['open_tag']})/u", '<?php ', $html);
        
        # handles closing tags ".. }}"
        $html = preg_replace("/(?<!\{\\\})({$this->config['close_tag']})/u", ' ?>', $html);
        
        # Remove escaping backslash
        $html = preg_replace("/(?<!\{\\\})(\{\\\})/u", "", $html);
        
        # handles functions "{% ... %}" 
        $html = preg_replace_callback("#({$this->config['func_open_tag']})(.*?)({$this->config['func_close_tag']})#s", function ($matches) { 
            list($func, $args) = array_pad( explode(" ", trim($matches[2]), 2), 2, "");
            
            $e = Event::instance()->trigger("Nex.view.function.$func", $this, [$args], true)->event_object();
            
            $tag = (substr($matches[1], -1) === '=') ? "<?php echo " : "<?php ";
            return isset($e['output']) ? $e['output'] : $tag."\$this->$func($args); ?>";
        }, $html);
        
        Event::instance()->trigger("Nex.view.custom_tags.done", $this, [ &$html ]);
        
        return $html;
    }

    ################################################################################
    ### Static general fonctions
    ################################################################################

    /**
     * Transform view path in a cache file name
     * using basic hashing algorithm
     * @param string path to view file
     * @return string
     */
    public static function cache_name($path, $view_path) {
        $max = 16;
        $boom = explode('/', $view_path);
        $count = count($boom);
        $keys = [];

        foreach ($boom as $i => $seg) {
            $keys[] = ord(strtoupper(substr($seg, 0, 1))) - 64;
            $boom[$i] = substr($seg, 0, 2) . (strlen($seg) > 4 ? substr($seg, -2) : '');
        }

        $sum = array_sum($keys) * 0.6180339887;
        $key = round(($sum - floor($sum)) * $max);
        $prefix = crc32($path);

        $path = $key . DIRECTORY_SEPARATOR . implode('_', $boom) . '_' . basename($path) . $prefix;

        return $path;
    }

}
