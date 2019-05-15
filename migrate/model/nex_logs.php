<?php namespace Eckinox\Nex\Migrate;

use Eckinox\Nex\Model\Log;

$this->model( new Log() )->create("2018-02-01", function($table) {
    
    return [
        "CREATE TABLE IF NOT EXISTS `$table` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `msg` text NOT NULL,
            `action` varchar(45) DEFAULT NULL,
            `data` text COMMENT 'serialized data',
            `application` varchar(45) DEFAULT NULL,
            `user` int(11) DEFAULT NULL,
            `module` varchar(45) DEFAULT NULL,
            `id_obj` int(11) DEFAULT NULL,
            `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8"
    ];

});