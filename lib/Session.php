<?php

namespace Eckinox\Nex;

use Eckinox\{
	singleton,
	config
};

class Session {
	use singleton, config;

    // These variables cannot be set by user
    protected static $protects = [
        'session_id',
        'user_agent',
        'ip_address',
        'hit_count',
		'last_activity',
	];

	// Session configuration
    protected $config = [] ;

	// Driver
	protected $driver ;

	// Session status
	protected $status = 0 ;

	// Input librairie
    protected $input ;

	// Session data
	protected $data = [];

	const INVALID_USER_AGENT = 50 ;
	const INVALID_IP_ADDRESS = 51 ;
	const EXPIRED = 52 ;

    public function __construct() {
        $this->input = Input::instance();

		$this->config = $this->config('Nex.session');

		$this->apply_config();

		// Create session
		$this->create();

		// Close the session just before sending the headers, so that
		// the session cookie(s) can be written.
		Event::register('system.send_headers', array($this, 'write_close'));

		// Make sure that sessions are closed before exiting
		register_shutdown_function(array($this, 'write_close'));
    }

    /**
     * Create and open sessions
     */
    public function create( $id = null )
    {
		if ($this->config['driver'] !== '_default')
		{
			// Set driver name
			$driver = 'Session_'.ucfirst($this->config['driver']).'_Driver';

			// Initialize the driver
			self::$driver = new $driver();

			// Register non-native driver as the session handler
			session_set_save_handler
			(
				array(self::$driver, 'open'),
				array(self::$driver, 'close'),
				array(self::$driver, 'read'),
				array(self::$driver, 'write'),
				array(self::$driver, 'destroy'),
				array(self::$driver, 'gc')
			);
		}

        // Name the session, this will also be the name of the cookie
		session_name($this->config['name']);

        if ( $id ) session_id($id);

        session_start();

		$this->data =& $_SESSION ;

		$this->update_protected();

		// Refresh session cookie if exist
		if ( isset($_COOKIE[$this->config['name']]) ) {
			cookie::set($this->config['name'], $_COOKIE[$this->config['name']], time() + $this->config['expiration']);
		}
    }

	/**
     * Regenerates the global session id.
     */
    public function regenerate()
    {
		if ($this->config['driver'] === '_default')
		{
			// Regenerate the session id
			session_regenerate_id();

			$id = session_id();
		}
		else {
			$id = self::$driver->regenerate();
		}

        $this->data['session_id'] = $id ;

        return $id ;
    }

    /**
     * Check validity of session
     * @return Bool                 False if session is not valid, true if it is
     */
    public function isValid() { return $this->checkValid(); }
    public function checkValid()
    {
        if ( $this->data['hit_count'] <= 1 ) return true ;

		$validates = $this->config['validate'] ;
		$validates = explode(',', $validates);

		foreach ( $validates as $valid )
		{
			switch(trim($valid))
			{
				// Check user agent for consistency
				case 'user_agent':
					if ($this->data[$valid] !== request::user_agent()){
						$this->status = self::INVALID_USER_AGENT;
						return false;
					}
				break;

				// Check ip address for consistency
				case 'ip_address':
					if ($this->data[$valid] !== $this->input->ipAddress()) {
						$this->status = self::INVALID_IP_ADDRESS;
						return false;
					}
				break;

				// Check expiration time to prevent users from manually modifying it
				case 'expiration':
					if (time() - $this->data['last_activity'] > ini_get('session.gc_maxlifetime')){
                        dump("this the reason why session are so freaking short !!"); die();
						$this->status = self::EXPIRED ;
						return false;
					}
				break;
			}
		}

        return true;
    }


    /**
     * Save session's data in instance's variable
     * and destroy current session if it exist
     */
    public function destroy()
    {
        if (session_id() !== '')
		{
			// Get the session name
			$name = session_name();

			// Destroy the session
			session_destroy();

			// Re-initialize the array
			$this->data = [];

			 // Destroy cookie's session
			cookie::delete($name);
        }
    }

	/**
	 * Runs the system.session_write event, then calls session_write_close.
	 * @return  void
	 */
	public function write_close()
	{
		static $run;

		if ($run === NULL)
		{
			$run = TRUE;

			// Run the events that depend on the session being open
			Event::trigger('system.session_write');

			// Close the session
			session_write_close();
		}
	}

    /**
     * Get a session variable using Input librairie
     *
     * @param String $key - key of value in $_SESSION variable
     * @param Bool $xss_clean - Return a clean value. FALSE by default
     * @param mixed $default default value to return when key doesnt exist
     * @return String Value found in $_SESSION
     */
    public function get($key, $xss_clean = FALSE, $default = '')
    {
        return $this->input->searchArray($this->data, $key , $xss_clean, $default);
    }

	/**
	 * Return the session ID
	 */
	public function id() { return $this->getId(); }
	public function getId(){
		return $this->data['session_id'];
    }

	/**
	 * Return session status
	 */
	public function getStatus() { return $this->status; }

    /**
     * Set a session variable
     *
     * @param String/Array          $keys- key in array $_SESSION or array $key => $value
     * @param String                $value - value of $key in array
     */
    public function set($keys, $value = '')
    {
        if (empty($keys))
            return false;

        if (! is_array($keys)){
			$keys = array($keys => $value);
		}

		foreach ($keys as $key => $value){
			if (in_array($key, self::$protects))
				continue;

			arr::set($this->data, $key, $value);
		}

    }

    public function exist($key) { return array_key_exists($key, $this->data); }

	/**
	 * Apply session configuration
	 * This method should only be called once
	 */
	protected function apply_config( )
	{
		// Do not allow PHP to send Cache-Control headers by default
		session_cache_limiter(FALSE);

		// Use only cookie or not
		ini_set('session.use_only_cookies', $this->config['use_only_cookies']);

		// Use transparent sid support or not. ( Session id passed by url )
		ini_set('session.use_trans_sid ', $this->config['use_trans_sid']);

		// Set the session cookie parameters
		session_set_cookie_params
		(
			$this->config['expiration'],
			$this->config('Nex.cookie.path'),
			$this->config('Nex.cookie.domain')
		);

		// Configure garbage collection
		ini_set('session.gc_probability', (int) $this->config['gc_probability']);
		ini_set('session.gc_divisor', 100);
		ini_set('session.gc_maxlifetime', $expire = $this->config['expiration'] ?: 86400);
        ini_set('session.cookie_lifetime', $expire);
	}

	/**
	 * Initialize session protected data
	 */
	protected function update_protected()
	{
		// Put session_id in the session variable
        $this->data['session_id'] = session_id();

        // Set last activity
		$this->data['last_activity'] = time() ;

        // set the session validators if they arent set
        if( !isset($this->data['hit_count']) ) {
            $this->data['ip_address'] = $this->input->ipAddress() ;
            $this->data['user_agent'] = request::user_agent() ;
            $this->data['hit_count']  = 0 ;
        }

		// Increase hit count
        $this->data['hit_count']++;
	}
}
