<?php

namespace Eckinox\Nex;

use Eckinox\reg,
    Eckinox\Event,
    Eckinox\Reflection,
    Eckinox\apps;

abstract class Model extends Database implements \Iterator, \ArrayAccess, \Countable, \JsonSerializable {
    use reg;

    public static $parentOf = [];
    public static $childOf = [];
    public static $friendOf = [];

    protected $reflection = null;
    public $tablename = "";

    // Model used
    protected $model_data;
    public $model_key;

    // Unique key used for request
    protected $current_primary_key = 'id';

    // Redefining primary key
    protected $primary_key = null;

    // Rows
    protected $rows = [];
    protected $unsaved = [];
    protected $i = 0;
    protected $original_rows = null;
    protected $filtered_rows = [];
    protected $pk_index = [];
    protected $as_array = false;
    protected $self_alias;

    protected static $next_self_alias = 'SELF';

    // Virtual fields allows unsavable fields to be added to models
    public static $virtual = [];

    /**
     * Static constructor
     * @param string|array $model
     */
    public static function factory($model) {

        if ( strpos($model, '->') !== false ) {
            list($model, static::$next_self_alias) = explode('->', str_replace(' ', '', $model));
        }

        $model = static::informations($model);

        if ( ! class_exists($model['class']) ) {
            throw new NEX_Exception("Model class {$model['class']} doesn\'t exist.", NEX_E_MODEL_LOAD);
        }

        return new $model['class']();
    }

    /**
     * Constructor.
     * @param array $model_data
     */
    public function __construct($database = '_default') {
        parent::__construct($database);

        $this->model_key = apps::iterable_apps_key($this);

        if ( ! $this->registry_has('Nex.model.'.$this->model_key) ) {
            $this->reflection( Reflection::instance()->reflect(static::class) );

            Event::instance()->on('Nex.model.register', function( $e, $model_key ) {
                $this->registry('Nex.model.'.$model_key, [ 'class' => static::class, 'tablename' => $this->tablename ] + get_class_vars(static::class));
            }, [], 'once')->trigger('Nex.model.register', $this, [ $this->model_key ])->off('Nex.model.register', 'once');
        }

        $this->self_alias = static::$next_self_alias;

        $this->from($this->model_key . '->' . $this->self_alias);

        static::$next_self_alias = 'SELF';

        $this->primary_key($this->primary_key);
    }

    public function __clone() {
        $this->driver = clone $this->driver;
    }

    public function get_model_path() {
        $path = array_diff( array_map('strtolower', explode("\\", $this->reflection['namespace']) ), [ 'model' ]);
        return $path;
    }

    public function __destruct() {
        $this->free_memory();
    }

    /**
     * Load row in database using id
     * @param int $id
     * @return $this
     */
    public function load($id) {
        return $this->where('SELF.' . $this->current_primary_key, $id)->_do_load(1);
    }

    public function set_table_name($name) {
        $this->tablename = $name;
        return $this;
    }

    /**
     * Load row in database using a specific field. It is recommended that you
     * use this function only with fields with a UNIQUE parameter.
     *
     * @param mixed $value field value
     * @param string $field in which field to search for given value
     * @return $this
     */
    public function load_using($field, $value, $limit = 1, $offset = null) {
        return $this->where("SELF.$field" , $value)->_do_load($limit, $offset);
    }

    /**
     * Load any number of rows in the database with an optionnal limit and offset
     * @param int $limit
     * @param int $offset
     * @return $this
     */
    public function load_all($limit = null, $offset = null) {
        return $this->_do_load($limit, $offset);
    }

    /**
     *  This function is used to call some code after loading. This allows an easy hook system that relies on overloading
     */
    public function loading_begin() {
        Event::instance()->trigger("{$this->model_key}.loading_begin", $this, [ $this->model_key ]);
    }

    /**
     *  This function is used to call some code after loading. This allows an easy hook system that relies on overloading
     */
    public function loading_done() {
        Event::instance()->trigger("{$this->model_key}.loading_done", $this, [ $this->model_key ]);
    }

    /**
     *  This private function allows us to overload load(), load_using() and load_all
     *  values
     */
    protected function _do_load($limit = null, $offset = null) {
        $this->loading_begin();

        $this->rows = $this->limit($limit, $offset)->unbuffed_select()->toArray()->getRows();

        $this->free_memory();
        $this->rewind();
        $this->loading_done();

        // if $this->from was cleared, add it back
        if ($this->getQueryComponent('from') === []) {
            $this->from($this->model_key . '->' . $this->self_alias);
        }

        return $this;
    }

    public function load_count() {
        return (int) $this->field('count(*) as count')->load_all()->getP('count');
    }

    /**
     * Load from rows
     * @param array $rows
     * @return $this
     */
    public function load_from($rows) {
        $this->rows = is_object($rows) ? $rows->getArray() : $rows;

        $this->rewind();

        return $this;
    }

    /**
     * Load a row
     * @param array $row
     * @param bool $toSave default is false, for retrocompatibility
     */
    public function load_row($row) {
        $row = is_object($row) ? $row->getRow() : $row;

        $this->rows[] = $row;
        $count = count($this->rows);
        $this->unsaved[count($this->rows) - 1] = $row;

        $this->i = key($this->rows);

        return $this;
    }

    public function load_into($row) {
        foreach($row as $key => $value) {
            $this[$key] = $value;
        }

        return $this;
    }


    /**
     *  Merge with another model or array
     *  and make sure primary key stays unique
     *
     * @param array | Model  $rows      Data to merged with
     * @param bool $unsaved_item        Set items as unsaved
     * @return \Itremma_Nex_App_Model
     */
    public function merge_with($rows, $unsaved_item = false) {
        if (is_object($rows)) {
            foreach ($rows as $r) {
                $this->rows[] = $r->getRow();
            }
        } else {
            foreach ($rows as $r) {
                $this->rows[] = $r;
            }
        }

        $keys = [];
        foreach ($this->rows as $i => $r) {
            if (isset($r[$this->current_primary_key])) {
                if (in_array($r[$this->current_primary_key], $keys)) {
                    unset($this->rows[$i]);
                }
                $keys[] = $r[$this->current_primary_key];
            }

            if ($unsaved_item) {
                $this->unsaved[$i] = $r;
            }
        }

        return $this;
    }

    /**
     * Unload and delete row from database
     * If id is null, $this->current() will be used
     * @param int $id
     */
    public function remove($id = null) {
        if ($id === '' || $id === null) {
            $id = $this->p($this->current_primary_key);
        }

        // If id is still null, Error
        if ($id === '' || $id === null) {
            return false;
        }

        $this->unload($id);

        // Delete from database
        $this->delete($this->model_key, [ $this->current_primary_key => $id ], 1);

        // if $this->from was cleared, add it back
        if ($this->getQueryComponent('from') === []) {
            $this->from($this->model_key . '->' . $this->self_alias);
        }

        return $this;
    }

    /**
     * Unload row from database
     * If id is null, $this->current() will be used
     * @param int $id
     */
    public function unload($id = null) {
        if ( $id !== false && $this->find($id) ) {
            unset($this->rows[$this->key()], $this->find_idx[$this->primary_key][$id]);
        }

        return $this;
    }

    /**
     * Unload and Delete all from database
     * If id is null, all loaded rows will be deleted
     * @param int|array $id
     * @param int ...
     */
    public function remove_all($id = null) {
        // Create id from loaded rows
        if ($id === '' || $id === null) {
            $ids = [];
            $this->rewind();

            while ($this->current()) {
                $ids[] = $this->getP($this->current_primary_key);
                $this->next();
            }
        } elseif (func_num_args() > 1) {
            $ids = func_get_args();
        } elseif (is_string($id)) {
            $ids = explode(',', $id);
        } else {
            $ids = (array) $id;
        }

        $this->unload_all($id);

        if (count($ids)) {
            foreach ($ids as $id) {
                $this->orWhere($this->current_primary_key, $id);
            }

            $limit = count($ids);
            $this->delete($this->model_key, null, $limit);
        }

        // if $this->from was cleared, add it back
        if ($this->getQueryComponent('from') === [])
            $this->from($this->model_key . '->' . $this->self_alias);

        return $ids;
    }

    /**
     * Unload all from database
     * If id is null, all loaded rows will be deleted
     * @param int|array $id
     * @param int ...
     */
    public function unload_all($id = null) {
        $this->rewind();

        // Create id from loaded rows
        if ($id === '' || $id === null) {
            $ids = [];
            while ($this->next()) {
                $this->unload();
            }

            $this->rewind();
            return $this; // Return now
        } elseif (func_num_args() > 1) {
            $ids = func_get_args();
        } elseif (is_string($id)) {
            $ids = explode(',', $id);
        } else {
            $ids = (array) $id;
        }

        while ($this->current()) {
            if (in_array($this->getP($this->current_primary_key), $ids)) {
                $this->unload();
            }
            $this->next();
        }

        $this->rewind();

        return $ids;
    }

    public function truncate($reset_auto_increment = true) {
        return $this->delete_all($this->model_key, $reset_auto_increment);
    }

    /**
     * Save changes made at current Iterator position
     * Insert new row if no primary_key value found
     * @param bool $reload reload row after saving if its a new row
     */
    public function save($reload = false, $mode = null) {
        // Try to get id from loaded iterator
        $id = $this->getP($this->current_primary_key);
        $unsaved = $this->getUnsaved();

        // If $id is null insert new row
        if ($mode != MODEL_FORCE_UPDATE && ($id === '' || $id === null || $mode == MODEL_FORCE_CREATE)) {
            if ( $this->insert($this->model_key, $unsaved) ) {
                // Reload data
                if ( $reload == true ) {
                    $this->load($this->lastInsertId());
                }

                if ( $id === '' || $id === null ) {
                    $this->setP($this->current_primary_key, $this->lastInsertId());
                }
            }
        }
        // Update
        elseif (count($unsaved)) {
            $this->limit(1)->update($this->model_key, $unsaved, array($this->current_primary_key => $id));
        }

        // Clear unsaved work
        $this->clearUnsaved();

        // if $this->from was cleared, add it back
        if ($this->getQueryComponent('from') === []) {
            $this->from($this->model_key . '->' . $this->self_alias);
        }

        return $this;
    }

    /**
     * Full Save update all fields
     * Insert new row if no primary_key value found
     * @param bool $reload reload row after saving if its a new row
     */
    public function full_save($reload = false, $mode = null) {
        // Try to get id from loaded iterator
        $id = $this->getP($this->current_primary_key);

        // If $id is null insert new row
        if ($mode != MODEL_FORCE_UPDATE && ($id === '' || $id === null || $mode == MODEL_FORCE_CREATE)) {
            $this->clearFields();
            if ($this->insert($this->model_key, $this->getRow())) {
                // Reload data
                if ($reload == true) {
                    $this->load($this->lastInsertId());
                }

                if ($id === '' || $id === null)
                    $this->setP($this->current_primary_key, $this->lastInsertId());
            }
        }
        // Update
        else {
            $this->clearFields();
            $this->limit(1)->update($this->model_key, $this->getRow(), array($this->current_primary_key => $id));
        }

        // if $this->from was cleared, add it back
        if ($this->getQueryComponent('from') === [])
            $this->from($this->model_key . '->' . $this->self_alias);

        return $this;
    }

    /**
     * Save change made to all rows
     */
    public function save_all() {
        $this->rewind();

        while ($this->current()) {
            $this->save(false);
            $this->next();
        }

        return $this;
    }

    /**
     * Full Save all rows
     */
    public function full_save_all() {
        $this->rewind();
        while ($this->current()) {
            $this->full_save(false);
            $this->next();
        }

        return $this;
    }

    /**
     * Make a copy of current object, in database
     */
    public function copy() {
        $row = $this->getRow();
        unset($row[$this->current_primary_key]);

        $this->insert($this->model_key, $row);

        // if $this->from was cleared, add it back
        if ($this->getQueryComponent('from') === [])
            $this->from($this->model_key . '->' . $this->self_alias);

        return $this->lastInsertId();
    }

    /**
     * Make a copy of all loaded objects
     */
    public function copy_all() {
        $ids = [];

        $this->rewind();
        while ($this->current()) {
            $old_id = $this->getP($this->current_primary_key);
            $ids[$old_id] = $this->copy();
            $this->next();
        }

        return $ids;
    }

    public function filter($field, $search) {
        if ($this->original_rows === null) {
            $this->original_rows = $this->getArray();
        }

        if (!is_array($search)) {
            $search = array($search);
        }

        $stack = [];

        foreach ($this->rows as $i => $row) {
            $found = false;
            $value = Nex_App_String::create($row[$field]);

            foreach ($search as $item) {
                $item = Nex_App_String::create($item);

                if ($item->startsWith('%') && $item->endsWith('%')) {
                    $found = $value->search($item->trim('%')->toString());
                } else if ($item->startsWith('%')) {
                    $found = $value->endsWith($item->strafter('%'));
                } else if ($item->endsWith('%')) {
                    $found = $value->startsWith($item->strbefore('%'));
                } else {
                    $found = $item->strcasecmp($value);
                }

                if ($found)
                    break;
            }

            if ($found) {
                $stack[] = $row;

                # Keeping traces of alteration in case we needs it (catalogs do!)
                $this->filtered_rows[count($stack) - 1] = $i;
            }
        }

        $this->rows = $stack;
        $this->rewind();

        return $this;
    }

    public function reset_filters() {
        if ($this->original_rows) {
            $this->rows = $this->original_rows;
            $this->original_rows = null;
            $this->filtered_rows = [];
            $this->rewind();
        }

        return $this;
    }

    public function asArray($bool = true) {
        $this->as_array = $bool;
    }

    public function find($id, $field = null) {
        $field || ( $field = $this->current_primary_key );

        if (isset($this->find_idx[$field][$id])) {
            return $this->at($this->find_idx[$field][$id]);
        } else {
            $this->rewind();

            while ($this->current()) {
                $pk = $this->getP($field);
                $this->find_idx[$field][$pk] = $this->i;

                if ($pk == $id) {
                    return $this;
                }

                $this->next();
            }
        }

        return false;
    }

    /**
     * Find every occurences of searched $needle from given $field and return a new self() model containing those rows.
     *
     * @param string|array $needle
     * @param string $field
     * @param mixed $limit   ignored if null
     * @param mixed $offset  ignored if null
     * @param bool $strict
     */
    public function find_all($needle, $field, $limit = null, $offset = null, $strict = false) {
        $retval = [];

        if ($field && ( $needle = (array) $needle)) {
            $this->rewind();

            while ($this->current() && ( $limit !== 0 )) {
                if (in_array($this->getP($field), $needle, $strict)) {
                    if ($offset) {
                        $offset--;
                    } else {
                        $retval[] = $this->getRow();
                        $limit && $limit--;
                    }
                }

                $this->next();
            }

            $this->rewind();
        }

        return static::factory($this->model_key)->load_from($retval);
    }

    public static function informations($key) {
        static::registry()->has( "Nex.model.$key" ) || static::instanciate_from_key($key);
        return static::registry( "Nex.model.$key" );
    }

    public function at($i) {
        $this->i = $i;
        return $this;
    }

    // Iterator
    public function next() {
        $this->i++;
    }

    public function rewind() {
        $this->i = 0;
    }

    public function key() {
        return $this->i;
    }

    public function valid() {
        return isset($this->rows[$this->i]);
    }

    public function exist() {
        return isset($this->rows[$this->i]);
    }

    public function current() {
        if ($this->valid()) {
            if ($this->as_array) {
                return Model::factory($this->model_key)->load_row($this->rows[$this->i]);
            } else {
                return $this;
            }
        }
        return false;
    }

    // Array Access
    public function offsetSet($offset, $value) {
        $this->setP($offset, $value);
    }

    public function offsetExists($offset) {
        return isset($this->rows[$this->i][$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->rows[$this->i][$offset]);
        unset($this->unsaved[$this->i][$offset]);
    }

    public function offsetGet($offset) {
        return $this->getP($offset);
    }

    // Countable
    public function count() {
        return count($this->rows);
    }

    //
    //
    // Direct properties methods
    // -------------------------------------------------------------------------

    public function jsonSerialize () {
        return $this->rows;
    }

    /**
     * Return row at current index position
     */
    public function getRow() {
        return $this->rows[$this->i];
    }

    public function getModelRow() {
        return static::factory($this->model_key)->load_row($this->getRow());
    }

    public function duplicate($unsaved = false, $rows = []) {
        return static::factory($this->model_key)->merge_with($rows ?: $this->rows, $unsaved);
    }

    public static function instanciate_from_key($model_key) {
        $dir = [ MODEL_NS ];
        $ns = [];

        foreach(explode('.', $model_key) as $key) {
            if ($apps = apps::keyname($key)) {
                $ns[] = $apps;
            }
            else {
                if ($dir) {
                    $ns[] = array_pop($dir);
                }
                $ns[] = ucfirst($key);
            }
        }

        $class = array_pop($ns);
        $classname = implode("\\", array_merge($ns, [ $class ]));
        new $classname();
    }

    /**
     * Return full array
     */
    public function getArray() {
        return $this->rows;
    }

    /**
     * Return a list of properties
     */
    public function getListOf($p, $pk_as_key = false, $use_model = false) {
        $rows = $use_model ? $this->duplicate() : $this->rows;
        $list = [];

        if ($pk_as_key) {
            foreach ($rows as $r) {
                $list[$r[$this->primary_key()]] = $r[$p];
            }
        } else {
            foreach ($rows as $r) {
                $list[] = $r[$p];
            }
        }

        return $list;
    }

    public function setListOf($p, $val, $to_save = true) {
        foreach ($this->rows as $k => $r) {
            $this->rows[$k][$p] = $val;
            if ($to_save) {
                $this->unsaved[$k][$p] = $val;
            }
        }
    }

    public function setListOfAssoc($p, $assoc, $to_save = true) {
        foreach ($this->rows as $k => $r) {
            if (isset($r[$p]) && isset($assoc[$p])) {
                $this->rows[$k][$p] = $assoc[$p];
                if ($to_save) {
                    $this->unsaved[$k][$p] = $assoc[$p];
                }
            }
        }
    }

    public function getArrayList($field_value, $field_key = null) {
        if ( $field_key === null ) {
            $field_key = $this->primary_key();
        }

        return array_combine($this->getListOf($field_key), $this->getListOf($field_value));
    }

    /**
     * return a variable/object in rows at current pointer position
     * @param string $name
     */
    public function getP($name) {
        // Check if variable is found in rows at current pointer position
        if ( $this->valid() && array_key_exists($name, $this->rows[$this->i]) ) {
            return $this->rows[$this->i][$name];
        }
        // If it's not found, maybe we're calling a relation, check for it
        elseif ($infos = $this->getRelation($name)) {
            $this->loadRelation($name, $infos);
            return $this->rows[$this->i][$name];
        }

        return null;
    }

    /**
     * Modify and register variable value $this->rows at current pointer position.
     * If target is an object, return it.
     * @param string $name - name of variable we're trying to call
     * @param string $value - value of variable $name
     */
    public function setP($name, $value, $to_save = true) {
        if (!$this->valid())
            $this->rows[$this->i] = [];

        // some validation if $name is already set in $row
        if (isset($this->rows[$this->i][$name])) {
            // Maybe we're calling a relation, check for it
            if (empty($this->rows[$this->i][$name]) && $infos = $this->getRelation($name)) {
                $this->loadRelation($name, $infos);
            }

            // If found value is object, return it
            if (is_object($this->rows[$this->i][$name]))
                return $this->rows[$this->i][$name];
        }

        // Register modification
        if ($to_save) {
            $this->unsaved[$this->i][$name] = $value;
        }

        $this->rows[$this->i][$name] = $value;
    }

    /**
     * Set or return property value
     * @param string $name
     * @param mixed $value
     */
    public function p($name) {
        $args = func_get_args();

        if (count($args) == 1) {
            return $this->getP($name);
        } else {
            if (isset($args[2])) {
                return $this->setP($name, $args[1], $args[2]);
            } else {
                return $this->setP($name, $args[1]);
            }
        }
    }

    /**
     * Check if value isset
     * @param string $name
     */
    public function __isset($name) {
        return isset($this->rows[$this->i][$name]);
    }

    protected function load_relationParentOf($name, $id, $foreign_field, $model, $function = null, $load_function = "load_all", $order_by = null) {
        switch( strtolower($function) ) {
            case "find_in_set":
                $this[$name]->whereRaw("FIND_IN_SET($foreign_field, '$id')");
            break;

            default:
                $this[$name]->where($foreign_field, $id);
        }

        if ($order_by) {
            foreach((array) $order_by as $field => $order) {
                $this[$name]->orderBy("SELF.".( ! is_numeric($field) ? $field : $order), is_numeric($field) ? 'ASC' : $order);
            }
        }

        $this[$name]->$load_function();
        return $this;
    }

    protected function load_relationChildOf($name, $id, $foreign_field, $model, $function = null, $load_function = "load_all") {
        switch( strtolower($function) ) {
            default:
                $this[$name]->where($foreign_field, $id);
        }

        $this[$name]->$load_function(1);
        return $this;
    }

    protected function load_relationFriendOf($name, $id, $foreign_field, $bridge_field, $bridge_foreign_field, $bridge_model, $model, $function = null, $load_function = "load_all", $order_by = null, $bridge_field_list = [], $bridge_field_prepend = "bridge_") {
        $fields = [ "SELF.*" ];

        foreach ($bridge_field_list as $field) {
            $fields[] = "B.$field as bridge_$field";
        }

        $this[$name]->field(implode(',', $fields))
            ->join($bridge_model . '->B', 'SELF.' . $foreign_field, 'B.' . $bridge_foreign_field, 'INNER')
            ->where("B.$bridge_field", $id);

        if ($order_by) {
            $this[$name]->orderBy("SELF.".( $order_by['field'] ?? $order_by ), $order_by['order'] ?? 'ASC');
        }

        $this[$name]->$load_function();

        return $this;
    }

    //
    //
    // Global methods
    // -------------------------------------------------------------------------

    /**
     * Slice rows array
     * @param see array_slice()
     */
    public function slice($offset, $length = null) {
        $this->rows = array_slice($this->rows, $offset, $length, false);
        return $this;
    }

    public function each($callback) {
        $pointer = $this->key();
        $this->rewind();

        while ($this->current()) {
            $callback($this);
            $this->next();
        }

        $this->at($pointer);
        return $this;
    }

    /**
     * Check if rows are loaded
     */
    public function loaded() {
        return count(array_filter( $this->rows )) > 0 ;
    }

    /**
     * Set unique key
     * @param string $new_key
     */
    public function primary_key($new_key = null) {
        if (!$new_key)
            return $this->current_primary_key;

        $this->current_primary_key = $new_key;
        return $this;
    }

    /**
     * Return unsaved data at current pointer position
     */
    public function getUnsaved($i = null) {
        $pointer = !$i ? $this->key() : $i;

        return array_diff_key($this->unsaved[$pointer] ?? [], array_fill_keys(static::$virtual, "") );
    }

    /**
     * Clear current unsaved data at current pointer position
     */
    public function clearUnsaved($i = null) {
        $pointer = !$i ? $this->key() : $i;
        unset($this->unsaved[$pointer]);
    }

    public function getModelKey() {
        return $this->model_key;
    }

    public function reflection($set = null) {
        return $set !== null ? $this->reflection = $set : $this->reflection;
    }

    /**
     * Check for relations
     * @param string $name name of variable called
     * @param array $relation_infos array('type' => x, 'table_key' => y, 'bridge' => z, 'key' => 's', 'f_key' = 's', 'bridge_key' => 's', 'bridge_f_key' => 's')
     */
    protected function loadRelation($name, $relation_infos) {
        // return right away if $type is false

        if (! $relation_infos || ! $relation_infos['model'])
            return null;

        $this->rows[$this->i][$name] = Model::factory($relation_infos['model']);

        if (! $this->valid() || empty($this->rows[$this->i][$relation_infos['key']]))
            return false;

        // Action depending of type or relation
        switch ($relation_infos['type']) {
            case 'child' :
                $this->load_relationChildOf($name, $this->rows[$this->i][$relation_infos['key']], $relation_infos['f_key'], $relation_infos['model'], $relation_infos['function'], $relation_infos['load_function']);
                break;

            case 'parent':
                $this->load_relationParentOf($name, $this->rows[$this->i][$relation_infos['key']], $relation_infos['f_key'], $relation_infos['model'], $relation_infos['function'], $relation_infos['load_function'], $relation_infos['order_by']);
                break;

            case 'friend':
                $this->load_relationFriendOf($name, $this->rows[$this->i][$relation_infos['key']], $relation_infos['f_key'], $relation_infos['bridge_key'], $relation_infos['bridge_f_key'], $relation_infos['bridge'], $relation_infos['model'], $relation_infos['function'], $relation_infos['load_function'], $relation_infos['order_by'], $relation_infos['bridge_field_list'], $relation_infos['bridge_field_prepend']);
                break;
        }

        return $this;
    }

    /**
     * Check for a relation in all instance's relation tables
     *
     * @param String                $table_key - key of a table in database config
     * @return String or False      Type of relation found or false is none was found
     */
    public function getRelation($model_key) {
        $model_data = $this->informations($this->model_key);

        if ( $infos = $model_data['childOf'][$model_key] ?? false ) {
            return array(
                'type' => 'child',
                'table_key' => $this->model_key,
                'key' => $infos['key'],
                'f_key' => $infos['foreign_key'],
                'model' => $infos['model'] ?? null,
                'function' => $infos['function'] ?? null,
                'load_function' => $infos['load_function'] ?? "load_all",
            );
        }

        if ( $infos = $model_data['parentOf'][$model_key] ?? false ) {
            return array(
                'type' => 'parent',
                'table_key' => $this->model_key,
                'key' => $infos['key'],
                'f_key' => $infos['foreign_key'],
                'model' => $infos['model'] ?? null,
                'function' => $infos['function'] ?? null,
                'load_function' => $infos['load_function'] ?? "load_all",
                'order_by' => $infos['order_by'] ?? null,
            );
        }

        if ( $infos = $model_data['friendOf'][$model_key] ?? false ) {
            return array(
                'type' => 'friend',
                'table_key' => $this->model_key,
                'key' => $infos['key'],
                'f_key' => $infos['foreign_key'],
                'bridge' => $infos['bridge'],
                'bridge_key' => $infos['bridge_key'],
                'bridge_f_key' => $infos['bridge_f_key'],
                'model' => $infos['model'] ?? null,
                'function' => $infos['function'] ?? null,
                'load_function' => $infos['load_function'] ?? "load_all",
                'order_by' => $infos['order_by'] ?? null,
                'bridge_field_list' => $infos['bridge_field_list'] ?? [],
                'bridge_field_prepend' => $infos['bridge_field_prepend'] ?? 'bridge_',
            );
        }

        // If no relation was found, return false
        return false;
    }

}
