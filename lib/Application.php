<?php

namespace Eckinox\Nex;

use Eckinox\{
	Component,
    config,
    reg
};

class Application extends Component {
    use config, reg;

    // Input object used by all child application
    protected $input;

    // Session object used by all child application
    protected $session;
    
    /**
     * Main controller of all applications. Everything in this constructor will be executed everytime
     * @param string $app - Application that is currently being run
     */
    public function __construct() {
		parent::__construct();
		
        $this->session = Session::instance();
        $this->input   = Input::instance();

        Event::trigger('eckinox.application.start');
		
        Event::addListener('system.error.404', array($this, 'error_404'));
        Event::addListener('system.error.403', array($this, 'error_403'));        
    }

    /**
     * Converted to app name
     */
    public function __toString() {
        return $this->get_name();
    }

    /**
     * This is executed right after constructor, before any method
     */
    public function _before() {}

    /**
     * This is executed after methods
     */
    public function _after() {}

    public function error_404() {
        header($this->input->server('SERVER_PROTOCOL') . ' 404 Not Found');

        $view = new View('error/404');
        $view->render(true);
    }

    public function error_403() {
        header($this->input->server('SERVER_PROTOCOL') . ' 403 Forbidden');

        $view = new View('error/403');
        $view->render(true);
    }
    
    public function get_name() {
        return $this->reflection['classname'];
    }

    /**
     * Return full path to file
     * @param string $relative sub directory in application directory
     */
    protected function getFilepath($relative) {
        debug_print_backtrace();
# 		return Nex::dispatcher()->app_versioned_file($this->getPath(), $relative);
    }

    /**
     * Return full path to application
     */
    protected function getPath() {
        debug_print_backtrace();
# 		return autoload::instance()->app_path( $this->name );
    }

    /**
     * Return a public/skin full url
     */
    protected function skin_url($relative_path) {
        return $this->public_url($relative_path);
    }

    protected function publicUrl($relative_path) {
        return Nex::publicUrl($relative_path, $this->_app);
    }
}
