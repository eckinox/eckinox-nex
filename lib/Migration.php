<?php

namespace Eckinox\Nex;

use Eckinox\{
    config,
    singleton,
    iterate,
    Eckinox,
    Reflection
};

class Migration {
    use config, singleton;

    /**
     * @var string Currently loaded table
     */
    protected $current_table;
    protected $current_model;

    protected static $migration = [];
    protected static $tables = [];
    protected static $models = [];
    protected static $component_migration = [];

    protected static $sql_create = [];
    protected static $sql_update = [];
    protected static $fill_data  = [];

    protected $reflection;

    protected function __construct() {
        $this->build();
    }

    public function build() {
        $this->reflection = Reflection::instance()->reflect(static::class, $this);
        static::$migration = array_merge(static::$migration, $this->load_migration_history());
        return $this;
    }

    /**
     * Autoload based on default configuration
     */
    public function autoload() {
        $base_dir = $this->reflection["dir"]."/";

        foreach((array)$this->config('Nex.migration.autoload_dir') as $folder) {
            foreach(iterate::files($base_dir.$folder, 'php') as $file) {
                $this->add_file($file);
            }
        }

        return $this;
    }

    /**
     * Launch migration / creation process here
     *
     * @return Type    Description
     */
    public function migrate() {
        echo "Launching migration process from " . static::class;

        $create = $this->_create_table();
        $update = $this->_update_table();
        $fill   = $this->_fill_table();

        if ( $create || $update || $fill ) {
            $stack = [];

            foreach(static::$migration as $key => $item) {
                $stack[ static::$tables[$key] ][$key] = $item;
            }

            foreach($stack as $component => $tables) {
                file_put_contents($this->get_migration_filepath($component), json_encode( $tables, \JSON_PRETTY_PRINT ));
            }

        }

        return $this;
    }

    protected function _create_table() {
        $sql = [];

        foreach($this->_order_array(static::$sql_create) as $item) {

            # Do we have to create this instance ?
            if (! $this->_component_table_migration($item['table']) ) {
                $sql = array_merge($sql, (array) $item['callback']( $item['table'], $item['model'] ));
                $this->latest_migration($item['table'], $item['order']);
            }

        }

        if ( $sql ) {
            $this->_query_sql($sql);
        }

        return $sql;
    }

    public function _update_table() {
        $sql = [];

        foreach($this->_order_array(static::$sql_update) as $item) {

            # Do we have to create this instance ?
            if ( $this->_component_table_migration($item['table']) < $item['order'] ) {
                $sql = array_merge($sql, (array) $item['callback']( $item['table'], $item['model'] ));
                $this->latest_migration($item['table'], $item['order']);
            }

        }

        if ( $sql ) {
            $this->_query_sql($sql);
        }

        return $sql;
    }

    public function _fill_table() {
        $retval = false;
        $data = [];

        foreach($this->_order_array(static::$fill_data) as $item) {

            # Do we have to fill this instance ?
            if ( $this->_component_table_migration($item['table']) < $item['order'] ) {
                $callback_value = (array) $item['callback']( $item['table'], $item['model'] );
                $data[ $item['table'] ] = array_merge( isset($data[$item['table']]) ? $data[$item['table']] : [], $callback_value);
                $this->latest_migration($item['table'], $item['order']);

                $retval = true;
            }

        }

        foreach($data as $table => $content) {
            if ( $content ) {
                Database::instance()->replace_all(static::$models[$table]->model_key, $content);
            }
        }

        return $retval;
    }

    /**
     * Loading indiviual files containing SQL's code
     *
     * @param string $filepath  Full filepath
     */
    public function add_file($filepath) {
        include($filepath);
    }

    public function table($table = null) {
        $component = $this->reflection['component'][0];

        if ( !isset(static::$component_migration[$component]) ) {
            static::$component_migration[$component] = [];
        }

        $this->current_model = null;
        $this->current_table = $table;

        if ( empty(static::$component_migration[$component][$table]) ) {
            static::$component_migration[$component][$table] = $this->latest_migration($table);
        }

        static::$tables[$table] = $component;

        return $this;
    }

    public function model($model) {
        $table = $model->tablename;

        $this->table($table);
        $this->current_model = $model;

        static::$models[$table] = $model;

        return $this;
    }

    public function create($order, $callback) {
        static::$sql_create[] = $this->_handle_callback($order, $callback);

        return $this;
    }

    public function update($order, $callback) {
        static::$sql_update[] = $this->_handle_callback($order, $callback);

        return $this;
    }

    public function fill($order, $callback) {
        static::$fill_data[] = $this->_handle_callback($order, $callback);

        return $this;
    }

    protected function _handle_callback($order, $callback) {
        return [
            'order'     => $this->_handle_priority($order),
            'callback'  => $callback,
            'table'     => $this->current_table,
            'model'     => $this->current_model
        ];
    }

    public function latest_migration($table, $set = null) {
        /* TMP! */ if ( ! isset(static::$migration[$table]) ) {
            static::$migration[$table] = null;
         /* /TMP! */ }

        if ( $set !== null ) {
            if ( $set > $this->latest_migration($table) ) {
                static::$migration[$table] = $set;
            }
        }

        return (int)static::$migration[$table] /*?? null*/ ;
    }

    public function load_migration_history() {
        $path = $this->get_migration_filepath($this->reflection['component'][0]);

        if ( ! file_exists($path) ) {
            file_put_contents($path, "{}");
        }

        # Check writing before starting the migration process
        if ( ! is_writable( $path ) ) {
            trigger_error("var path ($path) is not writable, therefore it's impossible to apply a proper migration process.", \E_USER_ERROR );
        }

        return json_decode(file_get_contents($path), true);
    }

    public function get_migration_filepath($component) {
        return Eckinox::path_migration()."$component.json";
    }

    protected function _query_sql($sql) {
        $db = Database::instance();

        foreach($sql as $item) {
            dump($item);
            $db->query($item);
        }
    }

    protected function _handle_priority($order) {
        if ( ($date = \DateTime::createFromFormat('Y-m-d', $order)) !== FALSE ) {
            $date->setTime(0,0,0);
            $order = $date->getTimestamp();
        }

        return $order;
    }

    protected function _order_array(&$array) {
        uasort($array, function($a1, $a2) {
            return $a1['order'] === $a2['order'] ? 0 : ( $a1['order'] > $a2['order'] ? 1 : -1 );
        });

        return $array;
    }

    protected function _data_path($file = "") {
        return $this->reflection["dir"]."/data/$file";
    }

    protected function _component_table_migration($table) {
        return static::$component_migration[ static::$tables[$table] ][$table];
    }

    protected function _fill_from_csv($filename) {
        return converter::csvToArray( $this->_data_path($filename), null, '"', true );
    }
}
