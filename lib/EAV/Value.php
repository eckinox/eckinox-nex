<?php

namespace Eckinox\Nex\Driver\EAV;

use Eckinox\Nex\Model;

abstract class Value extends Model {

    protected $EAV_Entity = array('key' => '', 'foreign_key' => '', 'model' => '');
    protected $EAV_Attribute = array('key' => '', 'foreign_key' => '', 'model' => '');
    protected $fields = array('value'); // Value should always be first

    public function attributeField() {
        return $this->EAV_Attribute['key'];
    }

    public function valueField() {
        return $this->fields[0];
    }

    public function getValue() {
        return $this[$this->fields[0]];
    }

    public function setValue($val) {
        $this[$this->fields[0]] = $val;
    }

    public function load_allAttributes() {
        $this->joinAttribute();

        return $this->load_all();
    }

    public function joinAttribute($alias = 'SELF', $attr_alias = 'EAV_Attribute') {
        return $this->join($this->EAV_Attribute['model'] . '->' . $attr_alias, $alias . '.' . $this->EAV_Attribute['key'], $attr_alias . '.' . $this->EAV_Attribute['foreign_key'], 'RIGHT');
    }

    public function save($reload = false, $mode = null) {
        $set = array(
            $this->EAV_Entity['key'] => $this[$this->EAV_Entity['key']],
            $this->EAV_Attribute['key'] => $this[$this->EAV_Attribute['key']],
        );

        foreach ($this->fields as $f) {
            $set[$f] = $this[$f];
        }

        $this->replace($this->model_key, $set);

        return $this;
    }

    public function save_all() {
        $sets = [];
        foreach ($this->rows as $r) {
            $set = array(
                $this->EAV_Entity['key'] => $r[$this->EAV_Entity['key']],
                $this->EAV_Attribute['key'] => $r[$this->EAV_Attribute['key']],
            );

            foreach ($this->fields as $f) {
                $set[$f] = $r[$f];
            }

            $sets[] = $set;
        }

        if (count($sets))
            $this->replace_all($this->model_key, $sets);

        return $this;
    }

    public function load_fromAttributes(EAV_Attribute & $objs) {
        $this->rows = [];
        foreach ($objs as $r) {
            $set = $r->getRow();

            $set[$this->EAV_Entity['key']] = null; // can't stay null
            $set[$this->EAV_Attribute['key']] = $r[$this->EAV_Attribute['foreign_key']];

            foreach ($this->fields as $i => $f) {
                $set[$f] = !$i ? $r->getDefaultValue() : null;
            }

            $this->rows[] = $set;
        }


        $this->rewind();

        return $this;
    }

}
