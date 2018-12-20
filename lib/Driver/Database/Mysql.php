<?php

namespace Eckinox\Nex\Driver\Database;

use Eckinox\Exception;

use \mysql_connect,
    \mysql_real_escape_string,
    \mysql_error,
    \mysql_select_db;
    
use Eckinox\Nex,
    Eckinox\Nex\Model,
    Eckinox\Nex\arr;

class Mysql extends \Eckinox\Nex\Driver\Database {

    /**
     * Build sql FROM statement array
     * @param string|array $table_keys
     * @param bool $query if $table_keys is query
     * @return $this
     */
    public function from(array $table_keys, $is_query = false, $func = 'array_push') {
        
        # Add to instance's FROM array
        foreach ($table_keys as $table_key) {
            if (($table_key = trim($table_key)) === '')
                continue;

            if ($is_query) {
                $func($this->from, $table_key);
                continue;
            }
            
            $end = '';
            
            $table_key = explode('->', str_replace(' ', '', $table_key));
            
            if (isset($table_key[1])) {
                $alias = $table_key[1];
                $this->table_alias[] = $alias;
                $end = " AS $alias";
            }

            $func($this->from, array('type' => 'FROM', 'sql' => $this->table($table_key[0], TRUE) . $end));
        }

        // Return itself
        return $this;
    }

    /**
     * Build JOIN statement of a query.
     * @param string $table_key - String of table's key name.
     * @param string $fields - fields
     * @param string $type - type of join
     * @param bool $is_query if $table_key is a query
     */
    public function join($table_key, $fields, $type = '', $is_query = false) {
        // Check if $type is valid, if it is, add space
        $type = strtoupper(trim($type));
        if (!in_array($type, array('LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER'))) {
            $type = '';
        }

        $table_key = explode('->', $table_key);
        if (isset($table_key[1])) {
            $alias = trim($table_key[1]);
            $this->table_alias[] = $alias;
            $table = (($is_query == true) ? $table_key[0] : $this->table($table_key[0])) . " AS $alias";
        }
        else {
            $table = array_shift($table_key);
        
        }

        // Build condition
        $prefix = $this->config['prefix'];
        $tmp = [];
        foreach ($fields as $k => $v) {
            $tmp[] = $k . ' = ' . $v;
        }
        $fields = $tmp;
        
        $sql = ((empty($type)) ? 'JOIN ' : $type . ' JOIN ') . $table . " ON (" . implode(' AND ', $fields) . ')';
        $this->from[] = array('type' => 'JOIN', 'sql' => $sql);

        return $this;
    }

    /**
     * Build sql SET statement array
     * @param array key => value pairs
     * @return $this
     */
    public function set(array $pairs, $is_query = false) {
        foreach ($pairs as $field => $value) {
            $value = $is_query ? $value : $this::escapeVal($value);
            $this->set[] = $this::escapeField($field, $this->config['prefix'], $this->table_alias) . " = $value";
        }

        return $this;
    }

    /**
     * Private where that where() and orWhere() use.
     * @param array $pairs key => value pairs
     * @param array $operators =|!=|LIKE etc
     * @param string $main_operator - default : 'AND'
     * @uses $this::findOperator()
     * @uses $this::splitOnOperator()
     */
    public function where(array $pairs, $operators, $main_operator = 'AND', $type = Nex\DB_WHERE) {
        if ($pairs === [])
            return $this;

        if ($type == Nex\DB_HAVING)
            $w = & $this->having;
        else
            $w = & $this->where;

        $operators = (array) $operators;
        $enclose = $this->getEnclose('open');
        $strwhere = (!empty($w)) ? "$main_operator " . $enclose : $enclose;
        $op = array_shift($operators);
        foreach ($pairs as $field => $value) {
            if ($this::hasOperator($field)) {
                $strwhere .= $field;
            }
            elseif (is_array($value)) {
                $field = $this::escapeField($field, $this->config['prefix'], $this->table_alias);
                $strwhere .= "(";
                foreach ($value as $val) {
                    $val = $this::escapeVal($val, $op, NEX_COMPAT, true);
                    $strwhere .= $field . " $val OR ";
                }
                $strwhere = substr($strwhere, 0, -3) . ") ";
            }
            else {
                $value = $this::escapeVal($value, $op, NEX_COMPAT, true);
                $strwhere .= $this::escapeField($field, $this->config['prefix'], $this->table_alias) . " $value ";
            }

            $strwhere .= "AND ";

            $op = (isset($operator[0]) ? array_shift($operators) : $op);
        }

        $strwhere = substr($strwhere, 0, -4) . ' ';

        $w[] = $strwhere;

        return $this;
    }

    /**
     * Build WHERE statement
     * operator LIKE is used.
     * @param array $fields field => match
     * @param string $match key word we're looking for
     * @return $this
     */
    public function like(array $fields, $operator = 'AND', $type = Nex\DB_WHERE) {
        if ($fields === [])
            return $this;

        if ($type == Nex\DB_HAVING)
            $w = & $this->having;
        else
            $w = & $this->where;

        $enclose = $this->getEnclose('open');
        $strwhere = (!empty($w)) ? $operator . ' ' . $enclose : $enclose;
        foreach ($fields as $field => $value) {
            $strwhere .= $this::escapeField($field, $this->config['prefix'], $this->table_alias) . ' LIKE "' . $this::escapeVal($value, null, NEX_NO_QUOTES) . '" AND ';
        }

        $strwhere = substr($strwhere, 0, -4) . ' ';

        $w[] = $strwhere;

        return $this;
    }

    /**
     * Build WHERE statement
     * MATCH is used.
     * @param array $fields field => array
     * @return $this
     */
    public function match(array $fields, $operator = 'AND', $mode = Nex\DB_MATCH_BOOLEAN, $type = Nex\DB_WHERE) {
        if ($fields === [])
            return $this;

        if ($type == Nex\DB_HAVING)
            $w = & $this->having;
        else
            $w = & $this->where;

        $mode_str = '';
        switch ($mode) {
            case Nex\DB_MATCH_BOOLEAN : $mode_str = ' IN BOOLEAN MODE';
            case Nex\DB_MATCH_NATURAL : $mode_str = ' IN NATURAL LANGUAGE MODE';
        }

        $enclose = $this->getEnclose('open');
        $strwhere = (!empty($w)) ? $operator . ' ' . $enclose : $enclose;
        foreach ($fields as $field => $value) {
            $strwhere .= "MATCH(" . $this::escapeField($field, $this->config['prefix'], $this->table_alias) . ') AGAINST ("' . $this::escapeVal($value, null, NEX_NO_QUOTES) . '"' . $mode_str . ') AND ';
        }

        $strwhere = substr($strwhere, 0, -4) . ' ';

        $w[] = $strwhere;

        return $this;
    }

    /**
     * Build WHERE statement
     * IN is used.
     * @param array $fields field => array
     * @return $this
     */
    public function in(array $fields, $operator = 'AND', $in = true, $type = Nex\DB_WHERE) {
        if ($fields === [])
            return $this;

        if ($type == Nex\DB_HAVING)
            $w = & $this->having;
        else
            $w = & $this->where;

        $enclose = $this->getEnclose('open');
        $strwhere = (!empty($w)) ? $operator . ' ' . $enclose : $enclose;

        foreach ($fields as $field => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $value[$k] = $this::escapeVal($v, null);
                }
                $value = implode(',', $value);
            }

            $strwhere .= $this::escapeField($field, $this->config['prefix'], $this->table_alias) . (!$in ? ' NOT ' : '') . "IN (" . $value . ") AND ";
        }

        $strwhere = substr($strwhere, 0, -4) . ' ';

        $w[] = $strwhere;

        return $this;
    }

    /**
     * Set the sql ORDER statement array
     * @param array $pairs field => order
     * @param bool $cache keep order in cache
     * @return $this
     */
    public function orderBy(array $pairs, $cache = false) {
        // Add to instance's ORDERBY array
        foreach ($pairs as $field => $type) {
            if (in_array($field, $this->order_by_used))
                continue;

            $this->order_by_used[] = $field;

            $type = strtoupper($type);

            if ($type != 'ASC' && $type != 'DESC') {
                $type = 'ASC';
            }

            // Keep that in cache
            if ($cache) {
                $this->order_by_cache[$field] = $type;
            }

            if (($field = trim($field)) === '')
                continue;
            $this->order_by[] = $field . ' ' . $type;
        }

        // Return itself
        return $this;
    }

    /**
     * Build sql GROUP BY statement array
     * @param array $fields string or array of field's names.
     * @return $this
     */
    public function groupBy(array $fields) {
        // Add fields to instance's GROUP BY array
        foreach ($fields as $field) {
            // Skip empty vars
            if (($field = trim($field)) === '')
                continue;

            $this->group_by[] = $this::escapeField($field, $this->config['prefix'], $this->table_alias);
        }

        return $this;
    }

    /**
     * Set the sql LIMIT statement variables
     * @param int $limit - Number of maximum row returned by query
     * @param int $offset - Offset of returned rows
     * @return $this
     */
    public function limit($limit, $offset = null) {
        if (func_num_args() == 1) {
            $limit = (array) $limit;
            $this->limit = array_shift($limit);
            $this->offset = array_shift($limit);
        }
        else {
            $params = func_get_args();
            $this->limit = array_shift($params);
            $this->offset = array_shift($params);
        }

        return $this;
    }

    /**
     * Compiles an update string and runs the query.
     * @param string $table_key String of table's key name.
     * @return bool
     */
    public function update($table_key = null) {
        if (empty($this->set)) {
            throw new Exception('SET statement is empty for UPDATE query.', \Eckinox\NEX_E_DATABASE_QUERY_COMPILE);
            return false;
        }

        if ($table_key === null) {
            $table = $this->build_from();
        }
        else {
            $table = $this->table($table_key);
        }

        $sql = 'UPDATE ' . ($this->ignore ? 'IGNORE ' : '') . $table . ' SET ' . implode(', ', $this->set) . ' ';
        $sql .= (!empty($this->where)) ? 'WHERE ' . implode('', $this->where) . ' ' : '';

        return $sql;
    }

    /**
     * Compiles a Delete string and runs the query.
     * @param string $table_key String of table's key name.
     * @return bool
     */
    public function delete($table_key = null) {
        if ($table_key === null) {
            $table = $this->build_from();
        }
        else {
            $table = $this->table($table_key);
        }

        $sql = 'DELETE ' . ($this->ignore ? 'IGNORE ' : '') . 'FROM ' . $table . ' ';
        $sql .= (!empty($this->where)) ? 'WHERE ' . implode('', $this->where) . ' ' : '';
        $sql .= (!empty($this->limit)) ? 'LIMIT ' . $this->limit . ' ' : '';

        return $sql;
    }

    /**
     * Compile an Insert sql string and runs Query
     * @param string $table_key String of table's key name.
     * @return bool
     */
    public function insert($table_key) {
        $table = $this->table($table_key);

        $sql = 'INSERT ' . ($this->ignore ? 'IGNORE ' : '') . 'INTO ' . $table . ' ';
        $sql .= (!empty($this->set)) ? "SET " . implode(', ', $this->set) . ' ' : "VALUES () ";

        return $sql;
    }

    /**
     * Compile an Insert sql string for multiple rows
     * @param string $table_key
     * @param array $sets multidimensional array
     */
    public function insert_all($table_key, $sets = []) {
        if (count($sets) == 0)
            return false;

        $table = $this->table($table_key);

        $keys = array_keys($sets[0]);

        $values = [];
        foreach ($sets as $set) {
            $row = [];
            foreach ($set as $v) {
                $row[] = $this::escapeVal($v);
            }
            $values[] = '(' . implode(',', $row) . ')';
        }

        $sql = 'INSERT ' . ($this->ignore ? 'IGNORE ' : '') . 'INTO ' . $table . ' (`' . implode('`, `', $keys) . '`) VALUES ' . implode(',', $values);

        return $sql;
    }

    /**
     * Compile a Replace sql string and runs Query
     *
     * @param String                $table_key - String of table's key name.
     * @param Array                 $set - associative array of update values
     * @return  Database_Result     Query result
     */
    public function replace($table_key, $set = null) {
        $table = $this->table($table_key);

        $sql = "REPLACE INTO $table ";
        $sql .= (!empty($this->set)) ? "SET " . implode(', ', $this->set) . ' ' : "VALUES () ";

        return $sql;
    }

    /**
     * Compile a Replace sql string for multiple rows
     * @param string $table_key
     * @param array $sets multidimensional array
     */
    public function replace_all($table_key, $sets = []) {
        if (count($sets) == 0)
            return false;

        $table = $this::table($table_key);

        $keys = array_keys($sets[0]);

        $values = [];
        foreach ($sets as $set) {
            $row = [];
            foreach ($set as $v) {
                $row[] = $this::escapeVal($v);
            }
            $values[] = '(' . implode(',', $row) . ')';
        }

        
        $sql = 'REPLACE INTO ' . $table . ' (`' . implode('`, `', $keys) . '`) VALUES ' . implode(',', $values);

        return $sql;
    }

    /**
     * Compile an autoincrement reset sql query
     * 
     * @param table_key  Table to reset autoincrement
     * @param value      New value to set autoincrement
     */
    public function reset_autoincrement_value($table_key, $value = 1) {
        $table = $this->table($table_key);
        return "ALTER TABLE $table AUTO_INCREMENT = $value;";
    }

    /**
     * Build a final Sql SELECT string with instance's variables. Execute query with
     * @param bool $force_resource force return value to be the raw database result resource
     */
    public function select() {
        $sql = $this->select_build_query();

        // Set flag $this->has_where
        if ($this->where !== []) {
            $this->has_where = true;
        }

        return $sql;
    }

    public function unbuffed_select() {
        $this->unbuffed_query = true;

        return $this->select();
    }

    /**
     * Build select query and return it
     */
    public function select_build_query() {
        $sql = [];
        
        $sql[] =  'SELECT ' . ($this->distinct ? 'DISTINCT ' : '') . (($this->select === []) ? '*' : implode(', ', $this->select));
        $sql[] = ' FROM ' . $this->build_from();
        $sql[] = implode(' ', $this->join) . ' ';
        $sql[] = (!empty($this->where)) ? 'WHERE ' . implode('', $this->where) . ' ' : '';
        $sql[] = (!empty($this->group_by)) ? 'GROUP BY ' . implode(', ', $this->group_by) . ' ' : '';
        $sql[] = (!empty($this->having)) ? 'HAVING ' . implode('', $this->having) . ' ' : '';
        $sql[] = (!empty($this->order_by)) ? 'ORDER BY ' . implode(', ', $this->order_by) . ' ' : '';

        if (!empty($this->limit)) {
            $sql[] = 'LIMIT ' . ((!empty($this->offset)) ? intval($this->offset) . ', ' : ' ') . intval($this->limit);
        }

        return implode("", $sql);
    }

    public function build_from() {
        $sql = [];
        
        foreach ($this->from as $arr) {
            if (is_string($arr)) {
                $sql[] = $arr . ' ';
            }
            elseif (!$sql) {
                $sql[] = $arr['sql'] . ' ';
            }
            else {
                $sql[] = ($arr['type'] == 'JOIN' ? $arr['sql'] : ', ' . $arr['sql']) . ' ';
            }
        }
        
        return implode('', $sql);
    }

    /**
     * Execute Sql query with givin Sql command and store results
     * @param string $sql Complete sql query
     */
    public function query($sql) {
        $func = ($this->unbuffed_query ? 'mysql_unbuffered_query' : 'mysql_query');

        // Execute Query and Trow Fatal if there is an error
        if (!($result = $func($sql, $this::$connection[$this->database]))) {
            debug_print_backtrace();
            throw new Exception('Database query error : "' . $sql . '" : ' . mysql_error(), NEX_E_DATABASE_QUERY_EXECUTE . '.' . mysql_errno());
            return false;
        }

        $this->result = $result;

        return true;
    }

    /**
     * Return number of object in list
     */
    public function nbr() {
        return is_resource($this->result) && mysql_num_rows($this->result);
    }

    /**
     * Return number of affected rows
     */
    public function affected() {
        return mysql_affected_rows($this::$connection[$this->database]);
    }

    /**
     * Return last insert id
     */
    public function lastInsertId() {
        return mysql_insert_id($this::$connection[$this->database]);
    }

    /**
     * Return next ID to be inserted into a table which has an auto-increment field
     * 
     * @return string Next id, or False if no auto-increment field were found
     */
    public function nextInsertId($table_name) {
        $this->query('SHOW TABLE STATUS WHERE name = "' . $table_name . '"');
        return arr::get($this->getRows(), "0.Auto_increment") ? : false;
    }

    /**
     * Free mysql memory
     */
    public function free_memory() {
        if (is_resource($this->result)) {
            mysql_free_result($this->result);
        }
    }

    /**
     * Create an array of rows from mysql result that can be used with standard php functions
     * @return array
     */
    public function getRows() {
        $tmp = [];

        if ($this->rows !== [])
            return $this->rows;

        if (is_resource($this->result)) {
            if (!$this->unbuffed_query)
                mysql_data_seek($this->result, 0);

            if ($this->as_array == false) {
                while ($r = mysql_fetch_object($this->result)) {
                    $this->rows[] = $r;
                }
            }
            else {
                while ($r = mysql_fetch_assoc($this->result)) {
                    $this->rows[] = $r;
                }
            }

            if (!$this->unbuffed_query)
                mysql_data_seek($this->result, 0);
        }

        return $this->rows;
    }
    
    /**
     * This function connects to the database using the Parameters or the default config
     * @param string $database key of database in config
     */
    public function connect() {
        // Make sure that connection wasn't established yet
        if (isset($this::$connection[$this->database])) {
            return ($this::$last_db_name !== $this->config['name']) ? $this->selectDB() : true;
        }

        $user = $this->config['username'];
        $pass = $this->config['password'];
        $host = $this->config['host'];

        if (!($this::$connection[$this->database] = mysql_connect($host, $user, $pass))) {
            throw new Exception(mysql_error(), E_USER_ERROR);
            return false;
        }
        
        if ($this->config['charset']) {
            mysql_set_charset($this->config['charset'], $this::$connection[$this->database]);
        }

        return $this->selectDB();
    }

    /**
     * This function select the database. If connection wasn't established yet,
     * it will try to connect with default param
     */
    public function selectDB() {
        $name = $this->config['name'];

        if (!mysql_select_db($name, $this::$connection[$this->database])) {
            throw new Exception(mysql_error(), NEX_E_DATABASE_SELECT);
            return false;
        }

        $this::$last_db_name = $name;
    }

    /**
     * This function will return the requested database table name with the prefix or not
     * It will check if dot is found meaning we have tablekey.field or db.tablekey.field
     *
     * @param string|array          $arr_key - key or array of key of a table in database config
     * @param bool                  $prefixed - return the name prefixed or not
     * @param string				$quotes -  type of quotes to use
     * @return string|array         $arr_table - table name with prefix or not
     *
     * @uses $this::escape_field()
     */
    public function table($arr_key, $prefixed = TRUE, $quotes = NEX_BACKTICK) {
        // INIT
        $arr_table = [];
        $prefix = $prefixed ? $this->config['prefix'] : '';
        $arr_key = (array) $arr_key;

        foreach ($arr_key as $key) {
            
            if ( $model = Model::informations(is_object($key) ? $key->model_key : $key) ) {
                $arr_table[] = isset($model['tablename']) ? $model['tablename'] : "";
            }
            
            #$arr_table[] = 
            #$arr_table[] = static::escapeField($key, $prefix, $this->table_alias, TRUE, $quotes);
        }

        // return a string if $arr_table has only 1 row
        return (count($arr_table) == 1 ) ? $arr_table[0] : $arr_table;
    }

    /**
     * Split a string on query delimiter
     * This is used to separate many query into array
     * @param string $str
     * @return array
     */
    public static function splitOnDelimiter($str) {
        $boom = preg_split("/;[\r?\n]+/i", $str);
        foreach ($boom as $key => $cell) {
            $cell = trim(ltrim($cell));
            if ($cell == '') {
                unset($boom[$key]);
            }
        }
        return $boom;
    }

    /**
     * Determines if the string has an arithmetic operator in it.
     * @param string $str  		str to check
     * @return bool
     */
    public static function hasOperator($str) {
        return (bool) preg_match('/[<>!=]|\sIS(?:\s+NOT\s+)?\b/i', trim($str));
    }

    /**
     * Split a string on arithmetic operator
     * @param string $str  		str to split
     * @return bool
     */
    public static function splitOnOperator($str) {
        return preg_split('/[<>!=]|\sIS(?:\s+NOT\s+)?\b/i', trim($str));
    }

    /**
     * Return arithmetic operator in a string
     * @param string $str
     * @param bool $as_array return first match or all in an array
     */
    public static function findOperator($str, $as_array = FALSE) {
        if (($count = preg_match_all('/[<>!=]|\sIS(?:\s+NOT\s+)?\b/i', trim($str), $matches)) > 0) {
            return ($as_array == FALSE ) ? $matches[0] : $matches;
        }
        return false;
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
                $value = (( in_array(strtoupper($value), array('NOW()', 'NULL', 'IS NULL', 'RAND()')) ) ? $value : $quotes . mysql_real_escape_string($value) . $quotes );
                break;
            case 'boolean':
                $value = (int) $value;
                break;
            case 'array':
                $value = $quotes . mysql_real_escape_string(arr::serialize($value)) . $quotes;
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

    /**
     * Escapes fields. Support bd name, table name/table key/table alias , field name
     * It can take db.table.field, field, table, table.field, alias.field etc...
     *
     * @param string|array $value field to escape
     * @param string $prefixed prefix table or not
     * @param bool $consider_table when no '.' is found, consider that value as a Table or Field
     * @return string
     */
    public static function escapeField($value, $prefix = '', $alias = [], $consider_table = FALSE, $quotes = NEX_BACKTICK) {
        $table_trick = ($consider_table == FALSE) ? 1 : 0;
        
        
        $was_array = (is_array($value)) ? true : false;
        $value = (array) $value;
        $tmp = [];
        
        foreach ($value as $val) {
            if ($val == '*') {
                $tmp[] = $val;
                continue;
            }

            $val = trim(str_replace($quotes, '', strval($val)));

            $db = $table = $field = '';

            // Retrieve fields with tables
            if (preg_match_all('/((([a-z_0-9]+)\.)?([a-z_0-9]+)\.)?([a-z_0-9]+)/i', $val, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $i => $match) {
                    // db.table.field
                    if ($match[3] && $match[4] && $match[5]) {
                        $db = $quotes . $match[3] . $quotes . '.';
                        $table = (in_array($match[4], $alias) ? $match[4] . '.' : $quotes . $prefix . Model::informations($match[4] . '.table') . $quotes . '.');
                        $field = $quotes . $match[5] . $quotes;
                    }
                    // table.field || db.table
                    elseif ($match[4] && $match[5]) {
                        if ($consider_table) {
                            $db = $quotes . $match[4] . $quotes . '.';
                            $table = (in_array($match[5], $alias) ? $match[5] . '.' : $quotes . $prefix . Model::informations($match[5] . '.table') . $quotes);
                        }
                        else {
                            $table = (in_array($match[4], $alias) ? $match[4] . '.' : $quotes . $prefix . Model::informations($match[4] . '.table') . $quotes . '.');
                            $field = $quotes . $match[5] . $quotes;
                        }
                    }
                    // table || field
                    elseif ($match[5]) {
                        if ($consider_table) {
                            $table = (in_array($match[5], $alias) ? $match[5] . '.' : $quotes . $prefix . Model::informations($match[5] . '.table') . $quotes);
                        }
                        else {
                            $field = $quotes . $match[5] . $quotes;
                        }
                    }

                    $val = str_replace($match[0], $db . $table . $field, $val);
                }
            }

            
            $tmp[0] = $val;

            /* $boom = explode('.', trim(str_replace($quotes, '', strval($val))));
              $count = count($boom);
              $dot = ($count > 1) ? '.' : '';

              $bd = ($count > 2) ? $quotes.array_shift($boom).$quotes.'.' : '' ;

              if(count($boom) > $table_trick){
              $boom_shift = array_shift($boom);
              $table = ((in_array($boom_shift, $alias) == FALSE)
              ? (( ($_table = Model::informations($boom_shift.'.table')) != FALSE )
              ? $quotes.$prefix.$_table.$quotes.$dot // Table key
              : $quotes.$prefix.$boom_shift.$quotes.$dot) // Table name
              : $boom_shift.$dot ); // Table alias
              }
              // No table
              else{
              $table = '' ;
              }
              $field = ($count == 1 && $consider_table != FALSE) ? '' : $quotes.$boom[0].$quotes ;
              $tmp[] = $bd.$table.$field ; */
        }

        return ($was_array == false) ? $tmp[0] : $tmp;
    }

}
