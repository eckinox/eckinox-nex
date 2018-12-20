<?php

namespace Eckinox\Nex;

use Eckinox\config;

class Database {
    use config;

    public $driver = null;

    /**
     * @var array Databases instances
     */
    protected static $instances = [];

    /**
     * @var string Used database
     */
    protected static $database;

    /**
     * @var array Configuration detail loaded from configuration files
     */
    protected $config;

    // Log next query
    protected $log_next_query = false;

    // Clear next query params
    protected $clear_next_query = true;

    /**
     * A way to construct this class in a Static manner
     * @param string $database key of database in config
     */
    public static function instance($database = '_default') {
        if (isset(self::$instances[$database])) {
            return self::$instances[$database];
        }

        return new self($database);
    }

    /**
     * Constructor
     * @param string $database key of database in config
     */
    public function __construct($database = '_default') {

        $this->config = is_array($database) ? $database : $this->config("Nex.database.$database");

        $db_name = $this->config['name'];

        # should fix this mess ->  if ( empty( static::$instances[$db_name] ) )  {

            // Load Driver
            $classname = $this->config("Nex.database.drivers.{$this->config['driver']}");

            $this->driver = new $classname($this->config, $db_name);

            if ( static::$database != $database ) {
                // Initialize database
                $this->driver->connect();
            }

            static::$instances[$db_name] = $this;
       # }
    }

    /**
     * Build SELECT statement Array
     *
     * @param string|array $fields single or multiple fields separated by comma | array of fields
     * @return $this
     */
    public function field($fields = '*') {
        // Adapt to args received
        if (func_num_args() > 1) {
            $fields = func_get_args();
        } elseif (is_string($fields)) {
            $fields = explode(',', $fields);
        } else {
            $fields = (array) $fields;
        }

        $this->driver->field($fields);
        return $this;
    }

    public function fieldRaw($field) {
        $this->driver->fieldRaw($field);
        return $this;
    }

    /**
     * Build sql FROM statement array
     * @param string|array $table_keys
     * @param bool $query if $table_keys is query
     * @return $this
     */
    public function from($table_keys, $is_query = false, $func = 'array_push') {
        if ( ! $is_query ) {

            // Adapt to params received
            if (func_num_args() > 1) {
                $table_keys = func_get_args();
            } elseif (is_string($table_keys)) {
                $table_keys = explode(',', $table_keys);
            } else {
                $table_keys = (array) $table_keys;
            }
        }
        else {
            $table_keys = (array) $table_keys;
        }

        $this->driver->from($table_keys, $is_query, $func);
        return $this;
    }

    public function fromRaw($table, $func = 'array_push') {
        $this->driver->fromRaw($table, $func);
        return $this;
    }

    public function registerAlias($alias) {
        $this->driver->registerAlias($alias);
        return $this;
    }

    /**
     * Build JOIN statement of a query.
     * @param string $table_key - String of table's key name.
     * @param string $field1 - field of FROM table
     * @param string $field2 - field of this JOIN .... 'ON $field1 = $field2'
     * @param string $type - type of join
     * @param bool $is_query if $table_key is a query
     */
    public function join($table_key, $field1, $field2 = '', $type = '', $is_query = false) {
        if (is_array($field1)) {
            $fields = $field1;
            $is_query = $type;
            $type = $field2;
        } else {
            $fields = array($field1 => $field2);
        }

        $this->driver->join($table_key, $fields, $type, $is_query);
        return $this;
    }

    public function joinRaw($join) {
        $this->driver->joinRaw($join);
        return $this;
    }

    /**
     * Build sql SET statement array
     * @param string|array $key key name or array of key => value pairs
     * @param string $value value to match with key when $key is string
     * @return $this
     */
    public function set($key, $value = null, $is_query = false) {
        $this->driver->set(is_array($key) ? $key : array($key => $value), $is_query);
        return $this;
    }

    /* Broken function ??? */

    public function setRaw($set) {
        $this->driver->setRaw($keys);
        return $this;
    }

    /**
     * Build sql WHERE statement array
     *
     * @param string|array          $key - key name or array of key => value pairs
     * @param string                $value - value to match with key when $key is string
     * @param string|array			$operator - Default : '='
     * @return Sql             This intance
     */
    public function where($key, $value = null, $operator = '=', $type = DB_WHERE) {
        $this->driver->where(is_array($key) ? $key : array($key => $value), is_string($operator) ? explode(',', $operator) : (array) $operator, 'AND', $type);
        return $this;
    }

    public function whereRaw($where, $type = DB_WHERE) {
        $this->driver->whereRaw($where, 'AND', $type);
        return $this;
    }

    /**
     * Build sql WHERE statement array using OR instead of AND
     * @param string|array $key key name or array of key => value pairs
     * @param string $value value to match with key when $key is string
     * @param string|array $operator - Default : '='
     * @return $this
     */
    public function orWhere($key, $value = null, $operator = '=', $type = DB_WHERE) {
        $this->driver->where(is_array($key) ? $key : array($key => $value), is_string($operator) ? explode(',', $operator) : (array) $operator, 'OR', $type);
        return $this;
    }

    public function orWhereRaw($where, $type = DB_WHERE) {
        $this->driver->whereRaw($where, 'OR', $type);
        return $this;
    }

    /**
     * Build WHERE statement with IN
     * @param string|array $field string or array like array('field' => array('val1', 'val2', 'val3'))
     * @param array $values
     */
    public function in($field, $values = [], $type = DB_WHERE) {
        if (!$values || !count($values))
            return $this;

        $this->driver->in(is_array($field) ? $field : array($field => $values), 'AND', true, $type);
        return $this;
    }

    public function orIn($field, $values = [], $type = DB_WHERE) {
        if (!$values || !count($values))
            return $this;

        $this->driver->in(is_array($field) ? $field : array($field => $values), 'OR', true, $type);
        return $this;
    }

    /**
     * Build WHERE statement with NOT IN
     * @param string|array $field string or array like array('field' => array('val1', 'val2', 'val3'))
     * @param array $values
     */
    public function notIn($field, $values = [], $type = DB_WHERE) {
        if (!$values || !count($values))
            return $this;

        $this->driver->in(is_array($field) ? $field : array($field => $values), 'AND', DB_NOT, $type);
        return $this;
    }

    public function orNotIn($field, $values = [], $type = DB_WHERE) {
        if (!$values || !count($values))
            return $this;

        $this->driver->in(is_array($field) ? $field : array($field => $values), 'OR', DB_NOT, $type);
        return $this;
    }

    /**
     * Build WHERE statement
     * operator LIKE is used.
     *
     * @param string|array $key - key name or array of key => value pairs
     * @param string $match - key word we're looking for
     * @return $this
     */
    public function like($field, $match = '', $type = DB_WHERE) {
        $this->driver->like(is_array($field) ? $field : array($field => $match), 'AND', $type);
        return $this;
    }

    /**
     * Build WHERE statement
     * operator LIKE is used with 'OR'.
     *
     * @param string|array  		$key - key name or array of key => value pairs
     * @param string        		$match - key word we're looking for
     * @return $this
     */
    public function orLike($field, $match = '', $type = DB_WHERE) {
        $this->driver->like(is_array($field) ? $field : array($field => $match), 'OR', $type);
        return $this;
    }

    /**
     * Build WHERE statement
     * MATCH is used.
     *
     * @param string|array $key - key name or array of key => value pairs
     * @param string $match - key word we're looking for
     * @return $this
     */
    public function match($key, $value = null, $mode = DB_MATCH_BOOLEAN, $type = DB_WHERE) {
        $this->driver->match(is_array($key) ? $key : array($key => $value), 'AND', $mode, $type);
        return $this;
    }

    /**
     * Build WHERE statement
     * OR MATCH is used.
     *
     * @param string|array $key - key name or array of key => value pairs
     * @param string $match - key word we're looking for
     * @return $this
     */
    public function orMatch($key, $value = null, $mode = DB_MATCH_BOOLEAN, $type = DB_WHERE) {
        $this->driver->match(is_array($key) ? $key : array($key => $value), 'OR', $mode, $type);
        return $this;
    }

    /**
     * Set the sql ORDER statement array
     * @param string|array $key key name or array of key => order pairs
     * @param string $type  Order
     * @param bool $cache keep order in cache
     * @return $this
     */
    public function orderBy($key, $type = 'ASC', $cache = false) {
        $this->driver->orderBy(is_array($key) ? $key : array($key => $type), $cache);
        return $this;
    }

    public function orderByRaw($orderBy) {
        $this->driver->orderByRaw($orderBy);
        return $this;
    }

    /**
     * Set the sql ORDER statement by using cache
     * @param string $key
     */
    public function orderByCache($key) {
        $this->driver->orderByCache($key);
        return $this;
    }

    /**
     * Build sql GROUP BY statement array
     * @param string|array $fields string or array of field's names.
     * @return $this
     */
    public function groupBy($fields) {

        // Adapt to params received
        if (func_num_args() > 1) {
            $fields = func_get_args();
        } elseif (is_string($fields)) {
            $fields = explode(',', $fields);
        } else {
            $fields = (array) $fields;
        }

        $this->driver->groupBy($fields);

        return $this;
    }

    public function groupByRaw($orderBy) {
        $this->driver->groupByRaw($orderBy);
        return $this;
    }

    /**
     * Set the sql LIMIT statement variables
     * @param int $limit Maximum number of rows returned by query
     * @param int $offset Offset of returned rows
     * @return $this
     */
    public function limit($limit, $offset = null) {
        $limit = $limit >= 0 ? $limit : 0;
        $offset = $offset >= 0 ? $offset : 0;

        $this->driver->limit($limit, $offset);
        return $this;
    }

    public function ignore($ignore = true) {
        $this->driver->ignore($ignore);
        return $this;
    }

    public function distinct($distinct = true) {
        $this->driver->distinct($distinct);
        return $this;
    }

    /**
     * Clear all query segments
     */
    public function clearQuery() {
        $this->driver->clearQuery();
    }

    public function clearFields() {
        $this->driver->clearFields();
    }

    public function clearFrom() {
        $this->driver->clearFrom();
    }

    public function clearWhere() {
        $this->driver->clearWhere();
    }

    public function clearJoin() {
        $this->driver->clearJoin();
    }

    public function clearOrderBy() {
        $$this->driver->clearOrderBy();
    }

    public function clearGroupBy() {
        $this->driver->clearGroupBy();
    }

    /**
     * Compiles an update string and runs the query.
     * @param string $table_key - String of table's key name.
     * @param array $set - associative array of update values
     * @param array $where - associative array where clause
     * @return bool
     */
    public function update($table_key = null, $set = null, $where = null) {
        is_array($set) && $this->set($set);
        is_array($where) && $this->where($where);

        $this->query($this->driver->update($table_key));
        return $this;
    }

    /**
     * Compiles a delete string and runs the query.
     * @param string $table_key string of table's key name.
     * @param array $where associative array where clause.
     * @param int $limit Maximum number of row to be deleted.
     * @return bool
     */
    public function delete($table_key = null, $where = null, $limit = 1) {
        is_array($where) && $this->where($where);
        ( $limit != '*' ) && $this->limit($limit);

        $this->query($this->driver->delete($table_key));
        return $this;
    }

    /**
     * Compiles a truncate-like query (delete without limit)
     *
     * @param type $table_key
     * @param type $reset_auto_increment
     * @return $this
     */
    public function delete_all($table_key, $reset_auto_increment = true) {
        if ($reset_auto_increment) {
            $this->query($this->driver->reset_autoincrement_value($table_key, 1));
        }

        $this->delete($table_key, [], 0);
        return $this;
    }

    /**
     * Compile an Insert sql string and runs Query
     * @param string $table_key String of table's key name.
     * @param array $set associative array of update values
     * @return bool
     */
    public function insert($table_key = null, $set = null) {
        if (is_array($set)) {
            $this->set($set);
        }

        $this->query($this->driver->insert($table_key));
        return $this;
    }

    /**
     * Compile an Insert sql string for multiple rows
     * @param string $table_key
     * @param array $sets multidimensional array
     * @return bool
     */
    public function insert_all($table_key = null, $sets = []) {
        $this->query($this->driver->insert_all($table_key, $sets));
        return $this;
    }

    /**
     * Compile a Replace sql string and runs Query
     * @param string $table_key String of table's key name.
     * @param array $set associative array of update values
     * @return bool
     */
    public function replace($table_key = null, $set = null) {
        if (is_array($set)) {
            $this->set($set);
        }

        $this->query($this->driver->replace($table_key));
        return $this;
    }

    /**
     * Compile a Replace sql string for multiple rows
     * @param string $table_key
     * @param array $sets multidimensional array
     */
    public function replace_all($table_key = null, $sets = []) {
        $this->query($this->driver->replace_all($table_key, $sets));
        return $this;
    }

    /**
     * Build a final Sql SELECT string with instance's variables. Execute query with
     * @param bool $force_resource force return value to be the raw database result resource
     */
    public function select() {
        $this->query($this->driver->select());
        return $this;
    }

    public function unbuffed_select() {
        $this->query($this->driver->unbuffed_select());
        return $this;
    }

    /**
     * Build select query and return it
     */
    public function select_build_query() {
        return $this->driver->select_build_query();
    }

    public function getSelectQuery() {
        return $this->driver->select_build_query();
    }

    /**
     * Execute Sql query with givin Sql command and store results
     * @param string $sql Complete sql query
     */
    public function query($sql) {
        if ($this->log_next_query) {
            Log::instance()->toFile($this->config['type'] . '.log', $sql);
            $this->log_next_query = false;
        }

        $this->driver->runQuery($sql);

        if ($this->clear_next_query) {
            $this->driver->clearQuery();
        }

        return $this;
    }

    public function remember($ttl = 0) {
        $this->driver->remember($ttl);
        return $this;
    }

    /**
     * Save order cache
     * @param string $key
     */
    public function saveOrderCache($key) {
        $this->driver->saveOrderCache($key);
        return $this;
    }

    /**
     * Return number of object in list
     */
    public function nbr() {
        return $this->driver->nbr();
    }

    /**
     * Return number of affected rows
     */
    public function affected() {
        return $this->driver->affected();
    }

    /**
     * Return last insert id
     */
    public function lastInsertId() {
        return $this->driver->lastInsertId();
    }

    /**
     * Return last executed query
     */
    public function lastQuery() {
        return $this->driver->lastQuery();
    }

    public function setClearQuery($val = true) {
        $this->clear_next_query = $val;
    }

    /**
     * Log next query
     */
    public function logQuery() {
        $this->log_next_query = true;
    }

    /**
     * Create an array of rows from sql result that can be used with standard php functions
     * @param bool $as_array false by default
     * @return array
     */
    public function toArray($as_array = true) {
        $this->driver->asArray($as_array);
        return $this;
    }

    public function asArray($as_array = true) {
        return $this->toArray($as_array);
    }

    /**
     * Return sql result
     */
    public function getResult() {
        return $this->driver->getResult();
    }

    /**
     * Return rows
     */
    public function getRows() {
        return $this->driver->getRows();
    }

    /**
     * Set enclose
     * @param string $state 'open' | 'close'
     */
    public function enclose($state) {
        $this->driver->enclose($state);
        return $this;
    }

    /**
     * Free resources
     */
    public function free_memory() {
        return $this->driver->free_memory();
    }

    /**
     * This function will return the requested database table name with the prefix or not
     * It will check if dot is found meaning we have tablekey.field or db.tablekey.field
     *
     * @param string|array $arr_key key or array of key of a table in database config
     * @param bool $prefixed return the name prefixed or not
     * @param string $quotes type of quotes to use
     * @return string|array $arr_table
     */
    public function table($arr_key, $prefixed = true, $quotes = null) {
        return $this->driver->table($arr_key, $prefixed, $quotes);
    }

    public function getQueryComponent($comp) {
        return $this->driver->getQueryComponent($comp);
    }

    /**
     * Return order cache as it would be used in orderBy() method
     * @param string $key
     */
    public function getOrderCache($key) {
        $cachevar = new Cachevar();
        return $cachevar->get('database.' . $key);
    }

}
