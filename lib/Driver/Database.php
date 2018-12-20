<?php

namespace Eckinox\Nex\Driver;

use Eckinox\Eckinox,
    Eckinox\Nex\caches;

abstract class Database {
    use caches;

    // Database connexion instance
    protected static $connection = [];

    // Name of last selected database
    protected static $last_db_name = null;

    // Key of current database config
    protected $database = '';

    // Database Config
    protected $config = [];

	protected $delay_remember = false ;
	protected $delay_remember_ttl = 0 ;

    // Sql result of select
    protected $result = null;
    protected $rows = [];

    // Last executed query
    protected $last_query = null;

    // Parts of Sql query
    protected $distinct = false;
    protected $ignore = false;
    protected $from = [];
    protected $group_by = [];
    protected $having = [];
    protected $join = [];
    protected $limit = null;
    protected $offset = null;
    protected $order_by = [];
    protected $select = [];
    protected $set = [];
    protected $where = [];

    // Order by to cache
    protected $order_by_cache = [];

    // Fields that have been used in order by already
    protected $order_by_used = [];

    // Flag to know if SELECT query has 'WHERE' statement
    // Used by system
    protected $has_where = false;

    // Flag to know is enclose is on (parenthesis)
    // 'open' | 'close'
    protected $enclose = 0;

    protected $unbuffed_query = false ;

    // Table alias must be static because self::table() use it
    protected $table_alias = [];

    // Return rows as array
    protected $as_array = false;

    protected $current_application = null;

    /**
     * Constructor
     */
    public function __construct($config, $database) {
        $this->config = $config;
		$this->database = $database ;
        $this->current_application( Eckinox::instance()->current_application() );
    }

    public function field( array $fields ) { $this->select = array_merge($this->select, $fields); }

	public function fieldRaw($field) { $this->select[] = $field; }

	public function fromRaw($table, $func) { $func($this->from, $table); }

	public function joinRaw($join) { $this->join[] = $join; }

	public function setRaw($set) { $this->set[] = $set; }

    public function whereRaw($where, $op = 'AND', $type = \Eckinox\Nex\DB_WHERE)
    {
        if ( $type == \Eckinox\Nex\DB_HAVING ) $w = & $this->having ;
        else $w = & $this->where ;

        $enclose = $this->getEnclose('open');

        $where = (!empty($w) ? $op.' '.$enclose.$where : $enclose.$where) ;

        $w[] = $where.' ' ;
    }

    public function distinct($distinct = true) {
        $this->distinct = $distinct;
    }

    public function ignore($ignore = true) {
        $this->ignore = $ignore;
    }

    /**
     * Return last executed query
     */
    public function lastQuery() {
        return $this->last_query;
    }

    /**
     * Return rows as array
     */
    public function asArray($asArray = true) {
        $this->as_array = $asArray;
    }

    public function orderByRaw($orderBy) {
         $this->order_by[] = $orderBy;
    }

    public function groupByRaw($groupBy) {
        $this->group_by[] = $groupBy;
    }

    public function registerAlias( $alias ) {
        $this->table_alias[] = $alias;
    }

	/**
	 * Return mysql result
	 */
    public function getResult() { return $this->result; }

    /**
     * Save orderby stack in unique identifier
     * @param string $key
     */
    public function saveOrderCache($key) {
        $cachevar = new Cachevar();
        $cachevar->support('session');
        $cachevar->set('database.' . $key, $this->order_by_cache, 0);
    }

    /**
     * Set Sql statement ORDER using cache
     * @param string $key
     */
    public function orderByCache($key) {
        $cachevar = new Cachevar();
        $orders = $cachevar->get('database.' . $key);

        if (is_array($orders)) {
            $this->orderBy($orders, true);
        }
    }

    /**
     * Set enclose
     * @param string $state 'open' | 'close'
     */
    public function enclose($state) {
        if ($state == 'open') {
            $this->enclose++;
        } elseif ($state == 'close') {
            if (($count = count($this->where)) && $this->enclose < 1)
                $this->where[$count - 1] .= ') ';
        }
    }

    /**
     * Return enclose
     */
    public function getEnclose($state) {
        $return = '';

        if ($state == 'open')
            while ($this->enclose > 0) {
                $return .= '( ';
                $this->enclose--;
            } elseif ($state == 'close')
            while ($this->enclose < 0) {
                $return .= ') ';
                $this->enclose++;
            }

        return $return;
    }

	public function runQuery($sql)
	{
		$this->last_query = $sql ;
		$key = 'db-'.$this->current_application().'-'.md5($sql);

		if ( $rows = $this->cache($key) ) {
			$this->rows = $rows;
		}
		else {
			$this->rows = [];
			$this->query($sql);

			if ( $this->delay_remember ) {
				$this->remember($this->delay_remember_ttl);
				$this->delay_remember = false ;
			}
		}
	}

	public function remember($ttl)
	{
		if ( !$this->last_query ) {
			$this->delay_remember = true ;
			$this->delay_remember_ttl = $ttl ;
		}
		else {
			$key = 'db-'.$this->current_application().'-'.md5($this->last_query);
			$this->cache($key, $this->getRows(), $ttl);
		}

		return $this;
	}

    /**
     * Clear all query segments
     */
    public function clearQuery() {
        $this->select = [];
        $this->set = [];
        $this->join = [];
        $this->where = [];
        $this->order_by = [];
        $this->group_by = [];
        $this->having = [];
        $this->distinct = FALSE;
        $this->limit = null;
        $this->offset = null;

        //$this->from = []; // Was commented, why ? don't know
        //$this->table_alias = [];
    }

    public function clearFields() {
        $this->set = [];
        $this->select = [];
    }

    public function clearFrom() {
        $this->from = [];
    }

    public function clearWhere() {
        $this->where = [];
    }

    public function clearJoin() {
        $this->join = [];
    }

    public function clearOrderBy() {
        $this->order_by = [];
    }

    public function clearGroupBy() {
        $this->group_by = [];
    }

	public function getQueryComponent($comp)
	{
		switch($comp)
		{
			case 'from': return $this->from ;
			case 'join': return $this->join ;
			case 'where': return $this->where ;
			case 'orderBy': return $this->order_by ;
			case 'group_by': return $this->group_by ;
			case 'having' : return $this->having ;
			case 'limit' : return $this->limit ;
			case 'offset' : return $this->offset ;
		}
	}

    public function current_application($set = null) {
        return $set === null ? $this->current_application : $this->current_application = $set;
    }
}
