<?php namespace Eckinox\Nex;

trait log {
    
    public function log($msg, $action, $data = [], $obj_id = null, $application = null, $module = null, $user = null) {
        $this->log_instance()->log($msg, $action, $data, $obj_id, $application, $module, $user);
    }
    
    public function log_build_action($function) {
        static $class = null;
        $class || ( $class = get_called_class() );
        
        return $class."::$function";
    }
    
    protected static function log_instance() {
        static $object = null;
        
        if ( $object === null ) {
            $object = new \Eckinox\Nex\Model\Log();
            
            register_shutdown_function(function() use ($object) {
                $object->save_all_logs();
            });
        }
        
        return $object;
    }
}