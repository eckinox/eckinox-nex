<?php

namespace Eckinox\Nex\Driver\Database;

use Eckinox\Nex\arr;

/**
 * @author       Mikael Laforge <mikael.laforge@twikiconcept.com>
 * @version      1.0.0
 * @package      Nex
 * @subpackage   core
 *
 * @update 28/07/2014 [ML] - 1.0.0 - creation
 *
 * Database Mysqli Drivers
 */
class Mysqli extends \Eckinox\Nex\Driver\Database\Mysql {

    protected static $curr_mysql_con;

    /**
     * Execute Sql query with givin Sql command and store results
     * @param string $sql Complete sql query
     */
    public function query($sql) {
        try {
            mysqli_real_query(static::$connection[$this->database], $sql);
            if (mysqli_errno(static::$connection[$this->database])) {
                dump($sql);
                throw new \Exception(mysqli_error(static::$connection[$this->database]), mysqli_errno(static::$connection[$this->database]));
            }

            if ($this->unbuffed_query) {
                $this->result = mysqli_use_result(static::$connection[$this->database]);
            } else {
                $this->result = mysqli_store_result(static::$connection[$this->database]);
            }
        } catch (Exception $e) {
            Nex::exception($e);
            return false;
        }

        return true;
    }

################################################################################
## Global functions
################################################################################

    /**
     * Return number of object in list
     */
    public function nbr() {
        return is_object($this->result) && mysqli_num_rows($this->result);
    }

    /**
     * Return number of affected rows
     */
    public function affected() {
        return mysqli_affected_rows(static::$connection[$this->database]);
    }

    /**
     * Return last insert id
     */
    public function lastInsertId() {
        return mysqli_insert_id(static::$connection[$this->database]);
    }

    /**
     * Free mysql memory
     */
    public function free_memory() {
        if ($this->result && is_object($this->result)) {
            // Bug with php 5.3
            //$this->result->free();
        }
    }

    /**
     * Create an array of rows from mysql result that can be used with standard php functions
     * @return array
     */
    public function getRows() {
        if ($this->rows !== [])
            return $this->rows;

        if (is_object($this->result)) {
            if ($this->as_array) {
                while ($r = mysqli_fetch_assoc($this->result)) {
                    $this->rows[] = $r;
                }
            } else {
                while ($r = mysqli_fetch_object($this->result)) {
                    $this->rows[] = $r;
                }
            }
        }

        return $this->rows;
    }

################################################################################
### Global static functions
################################################################################

    /**
     * This function connects to the database using the Parameters or the default config
     * @param string $database key of database in config
     */
    public function connect() {
        // Make sure that connection wasn't established yet
        if (isset(static::$connection[$this->database])) {
            return (static::$last_db_name !== $this->config['name']) ? $this->selectDB() : true;
        }

        $user = $this->config['user'];
        $pass = $this->config['pass'];
        $host = $this->config['host'];
        $name = $this->config['name'];

        try {
            static::$connection[$this->database] = static::$curr_mysql_con = mysqli_connect($host, $user, $pass, $name);

            if (mysqli_connect_errno(static::$connection[$this->database])) {
                throw new Exception(mysqli_connect_error(static::$connection[$this->database]), mysqli_connect_errno(static::$connection[$this->database]));
            }

            if ($this->config['charset']) {
                mysqli_set_charset(static::$connection[$this->database], $this->config['charset']);
            }
        } catch (Exception $e) {
            Nex::exception($e);
        }
    }

    /**
     * This function select the database. If connection wasn't established yet,
     * it will try to connect with default param
     */
    public function selectDB() {
        $name = $this->config['name'];

        try {
            mysqli_select_db(static::$connection[$this->database], $name);

            if (mysqli_errno(static::$connection[$this->database])) {
                throw new Exception(mysqli_error(static::$connection[$this->database]), mysqli_errno(static::$connection[$this->database]));
            }
        } catch (Exception $e) {
            Nex::exception($e);
        }

        static::$last_db_name = $name;
    }

    /**
     * Escapes any input value.
     * @param mixed $value value to escape
     * @param string $operator arithmetic operator to return before the string
     * @param string $quotes quotes to use
     * @param bool $is_null IS NULL is used instead of NULL ( Used in where clause )
     * @return string
     */
    public static function escapeVal($value, $op = '', $quotes = NEX_COMPAT, $is_null = false) {
        switch (gettype($value)) {
            case 'string':
                $value = (( in_array(strtoupper($value), array('NOW()', 'NULL', 'IS NULL', 'RAND()')) ) ? $value : $quotes . mysqli_real_escape_string(static::$curr_mysql_con, $value) . $quotes );
                break;
            case 'boolean':
                $value = (int) $value;
                break;
            case 'array':
                $value = $quotes . mysqli_real_escape_string(static::$curr_mysql_con, arr::serialize($value)) . $quotes;
                break;
            case 'double':
                // Convert to non-locale aware float to prevent possible commas
                $value = sprintf('%F', $value);
                break;
            default:
                $value = (($value === NULL) ? ($is_null === true ? 'IS ' . ($op == '!=' ? 'NOT ' : '') : '') . 'NULL' : $value);
                break;
        }

        return (string) (strtoupper(substr($value, 0, 3)) != 'IS ') ? $op . $value : $value;
    }

}
