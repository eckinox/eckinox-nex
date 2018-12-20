<?php

namespace Eckinox\Nex\Driver\EAV;

use Eckinox\Nex\Model;

abstract class Attribute extends Model {

    protected $EAV_Value = array('key' => '', 'foreign_key' => '', 'model' => '');

    public function getDefaultValue() {
        return '';
    }

}
