<?php

namespace Eckinox\Nex\Driver\Session;

class File {

	/**
	 * Path to sessions
	 * @var string
	 */
	protected $path ;

	/**
	 * Log instance
	 * @var Log
	 */
	protected $log ;

	/**
	 * Log filename
	 * @var string
	 */
	protected $log_filename = 'session.log' ;

    public function __construct() {
		$this->log = Log::instance();
	}

    /**
	 * Opens a session.
	 * @param   string   save path
	 * @param   string   session name
	 * @return  boolean
	 */
	public function open($path, $name)
	{
		$this->path = $path.(substr($path, -1) != DIRECTORY_SEPARATOR ? DIRECTORY_SEPARATOR : '').$name ;

		// Make sure directory exist
		if ( !is_dir($this->path) ) {
			mkdir($this->path, 0755, true);
			$this->log('Session directory "'.$this->path.'" didn\'t exist. System created it.');
		}

		return true ;
	}

	/**
	 * Closes a session.
	 * @return  boolean
	 */
	public function close()
	{
		return true ;
	}

	/**
	 * Reads a session.
	 * @param   string  session id
	 * @return  string
	 */
	public function read($id)
	{
		$path = $this->path.DIRECTORY_SEPARATOR.'sess_'.$id ;

		if ( !is_file($path) ) {
			$this->log('Session file "'.$path.'" doesn\'t exist. Session data will be empty');
			return '' ;
		}

		if ( !is_readable($path) ) {
			$this->log('Session file "'.$path.'" can\'t be read. Session data will be empty');
			return '' ;
		}

		$this->log('Session file "'.$path.'" was read');

		return file_get_contents($path);
	}

	/**
	 * Writes a session.
	 * @param   string   session id
	 * @param   string   session data
	 * @return  boolean
	 */
	public function write($id, $data)
	{
		$path = $this->path.DIRECTORY_SEPARATOR.'sess_'.$id ;

		if ( $handle = fopen($path, "w") ) {
			$return = fwrite($handle, $data);
			fclose($handle);
			$this->log('Session data was saved to "'.$path.'"');
		} else {
			$return = false ;
			$this->log('Could not use "'.$path.'" to write session data');
		}

		return $return;
	}

	/**
	 * Destroys a session.
	 *
	 * @param   string   session id
	 * @return  boolean
	 */
	public function destroy($id)
	{
		$path = $this->path.DIRECTORY_SEPARATOR.'sess_'.$id ;

		if ( $return = @unlink($path) ) {
			$this->log('Session was destroyed, file "'.$path.'" was destroyed');
		} else {
			$this->log('Session was destroyed, file "'.$path.'" could not be destroyed');
		}

		return $return ;
	}

	/**
	 * Regenerates the session id.
	 * @return  string
	 */
	public function regenerate()
	{
		// Regenerate the session id
		session_regenerate_id();

		return session_id();
	}

	/**
	 * Garbage collection.
	 * @param   integer  session expiration period
	 * @return  boolean
	 */
	public function gc($maxlifetime)
	{
		$nbr = 0 ;
		foreach ( glob($this->path.DIRECTORY_SEPARATOR.'sess_*') as $filename ) {
			if (filemtime($filename) + $maxlifetime < time()) {
				@unlink($filename);
				$nbr++;
			}
		}

		$this->log('Garbage collector was called. '.$nbr.' files were destroyed');

		return true;
	}

	/**
	 * Logging
	 */
	protected function log ( $message )
	{
		$this->log->toFile($this->log_filename, date('Y-m-d H:i:s').' - '.$message.NEX_EOL);
	}
}
