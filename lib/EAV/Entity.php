<?php

namespace Eckinox\Nex\Driver\EAV;

use Eckinox\Nex\Model;

abstract class Entity extends Model {

    protected $EAV_Value = array('key' => '', 'foreign_key' => '', 'model' => '');
    protected $attributes = [];
    protected $filter_i = 0;
    protected $filter;
    protected $save_attributes = true;

    public function load_entity($id) {
        $this->load($id);

        if ($this[$this->current_primary_key]) {
            $this->attributes[$this->i] = $this->get_attributes_model(false);
        }

        return $this;
    }

    protected function get_attributes_model($all) {
        $model = Model::factory($this->EAV_Value['model']);

        $all ? $model->in('SELF.' . $this->EAV_Value['foreign_key'], $this->getListOf($this->current_primary_key)) :
                        $model->where('SELF.' . $this->EAV_Value['foreign_key'], $this[$this->current_primary_key]);

        $model->load_allAttributes();

        return $model;
    }

    public function load_all_entities($offset = null, $limit = null) {
        $this->load_all($offset, $limit);

        if ($this[$this->current_primary_key]) {
            $arr = $this->get_attributes_model(true)->get[];

            $tmp = [];
            foreach ($arr as $i => $r) {
                $id = $r[$this->EAV_Value['foreign_key']];
                if (!isset($tmp[$id])) {
                    $tmp[$id] = [];
                }
                $tmp[$id][] = $r;
                unset($arr[$i]); // free memory
            }

            while ($this->current()) {
                $id = $this[$this->current_primary_key];
                if (isset($tmp[$id])) {
                    $this->attributes[$this->key()] = Model::factory($this->EAV_Value['model']);
                    $this->attributes[$this->key()]->load_from($tmp[$id]);
                    unset($tmp[$id]); // free memory
                }
                $this->next();
            }
            $this->rewind();
        }

        return $this;
    }

    public function loadAttributes(EAV_Attribute & $objs) {
        $this->attributes[$this->i] = Model::factory($this->EAV_Value['model']);
        $this->attributes[$this->i]->load_fromAttributes($objs);
    }

    public function save($reload = false, $mode = null) {
        $create = empty($this[$this->current_primary_key]);

        parent::save($reload, $mode);

        if ($this->save_attributes && isset($this->attributes[$this->i])) {
            if ($create) {
                $this->attributes[$this->i]->setListOf($this->EAV_Value['foreign_key'], $this[$this->current_primary_key]);
            }
            $this->attributes[$this->i]->save_all();
        }

        return $this;
    }

    public function whereAttribute($id, $value, $op = '=') {
        $this->joinAttributeFilter();
        $this->linkAttributeFilter($id);
        $this->filter->where($this->currValFilterAlias() . '.' . $this->filter->valueField(), $value, $op);

        return $this;
    }

    public function likeAttribute($id, $value) {
        $this->joinAttributeFilter();
        $this->linkAttributeFilter($id);
        $this->filter->like($this->currValFilterAlias() . '.' . $this->filter->valueField(), $value);

        return $this;
    }

    public function inAttribute($id, array $values) {
        $this->joinAttributeFilter();
        $this->linkAttributeFilter($id);
        $this->filter->in($this->currValFilterAlias() . '.' . $this->filter->valueField(), $values);

        return $this;
    }

    protected function hasAttributes() {
        return isset($this->attributes[$this->i]);
    }

    public function getAttributes() {
        if (isset($this->attributes[$this->i])) {
            return $this->attributes[$this->i];
        } else {
            trigger_error('Attributes are not initialized for row ' . $this->i . '. Use load_entity() or load_all_entities()', E_USER_NOTICE);
        }
    }

    public function applyAttributeFilters() {
        if ($this->filter_i)
            $this->whereRaw('SELF.' . $this->current_primary_key . ' IN (' . $this->filter->getSelectQuery() . ')');

        return $this;
    }

    //
    //
    // Protected
    // -------------------------------------------------------------------------

    protected function joinAttributeFilter() {
        if (!$this->filter_i) {
            $this->filter_i++;
            $alias = $this->currValFilterAlias();
            $attr_alias = $this->currAttrFilterAlias();
            $this->filter = Model::factory($this->EAV_Value['model'] . '->' . $alias)
                    ->field($alias . '.' . $this->EAV_Value['foreign_key'])
                    ->registerAlias('SELF')
                    ->joinAttribute($alias, $attr_alias)
                    ->whereRaw($alias . '.' . $this->EAV_Value['foreign_key'] . ' = SELF.' . $this->EAV_Value['key']);
        } else {
            $prev_alias = 'VAL' . $this->filter_i;
            $this->filter_i++;
            $alias = $this->currValFilterAlias();
            $attr_alias = $this->currAttrFilterAlias();
            $this->filter
                    ->join($this->EAV_Value['model'] . '->' . $alias, $prev_alias . '.' . $this->EAV_Value['foreign_key'], $alias . '.' . $this->EAV_Value['foreign_key'], 'INNER')
                    ->joinAttribute($alias, $attr_alias);
        }
    }

    protected function linkAttributeFilter($id) {
        $this->filter->where($this->currAttrFilterAlias() . '.' . $this->filter->attributeField(), $id);
    }

    protected function currValFilterAlias() {
        return 'VAL' . $this->filter_i;
    }

    protected function currAttrFilterAlias() {
        return 'ATTR' . $this->filter_i;
    }

}
