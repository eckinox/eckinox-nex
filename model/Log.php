<?php namespace Eckinox\Nex\Model;

class Log extends \Eckinox\Nex\Model {

    public $tablename = 'nex_logs' ;

    public function save_all_logs($encoding_options = 0) {

        foreach($this as $item) {
            if ( is_array($this['data']) ) {
                $this['data'] = json_encode( $this['data'], $encoding_options );
            }

            $this['date_created'] || ( $this['date_created'] = date('Y-m-d H:i:s'));
        }

        return $this->save_all();
    }

    public function log($msg, $action, $data = [], $obj_id = null, $application = null, $module = null) {
        $this->load_row([
            'msg'    => $msg,
            'action' => $action,
            'data'   => $data,
            'id_obj' => $obj_id,
            'application' => $application,
            'module' => $module,
#            'user'   => $user,
        ]);

        return $this;
    }
}
