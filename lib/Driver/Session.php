<?php

namespace Eckinox\Nex\Driver;

/**
 * Session driver interface
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */

interface Session {
    /**
     * Opens a session.
     * @param   string   save path
     * @param   string   session name
     * @return  boolean
     */
    public function open($path, $name);

    /**
     * Closes a session.
     * @return  boolean
     */
    public function close();

    /**
     * Reads a session.
     * @param   string  session id
     * @return  string
     */
    public function read($id);

    /**
     * Writes a session.
     * @param   string   session id
     * @param   string   session data
     * @return  boolean
     */
    public function write($id, $data);

    /**
     * Destroys a session.
     * @param   string   session id
     * @return  boolean
     */
    public function destroy($id);

    /**
     * Regenerates the session id.
     * @return  string
     */
    public function regenerate();

    /**
     * Garbage collection.
     * @param   integer  session expiration period
     * @return  boolean
     */
    public function gc($maxlifetime);
}