<?php namespace Eckinox\Nex;
   
const ERRORHANDLER_LEVEL_ERROR   = 5;
const ERRORHANDLER_LEVEL_WARNING = 4;
const ERRORHANDLER_LEVEL_NOTICE  = 3;
const ERRORHANDLER_LEVEL_DEBUG   = -1;

const LOG_DIR    = 'log/';
const VIEW_DIR   = 'view/' ;
const LAYOUT_DIR = 'view/layout/' ;

const MODEL_NS   = 'Model';

const DB_NOT = false;
const DB_MATCH_NATURAL = 4;
const DB_MATCH_BOOLEAN = 8;
const DB_HAVING = 16;
const DB_WHERE = 32;

const MODEL_FORCE_CREATE = 2;
const MODEL_FORCE_UPDATE = 4;

// Url base without domain
// Ex: /dir/ or /dir1/dir2/
define('NEX_BASE_URL', (isset($_SERVER['SCRIPT_NAME']) ? substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], "/") + 1) : ''));

// Set up Subdomain and Domain variables
$_nex_parts = url::domain($_SERVER['HTTP_HOST'] ?? '');

define('NEX_DOMAIN', $_nex_parts['domain']);
define('NEX_SUBDOMAIN', $_nex_parts['subdomain']);