<?php  // Moodle configuration file

include "redirect.php";

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mariadb';
//$CFG->dbtype    = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'moodlestud';
//$CFG->dbuser    = 'wordpress_user';
//$CFG->dbpass    = 'kTM-gHkgJ8Npft';
$CFG->dbuser    = 'moodlestud';
$CFG->dbpass    = '9Y6fnJIwAO';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => 3306,
//  'dbsocket' => '/opt/bitnami/mysql/tmp/mysql.sock',
//  'dbcollation' => 'utf8_general_ci',
//'dbcollation' => 'utf8mb4_0900_ai_ci'
'dbcollation' => 'utf8mb4_unicode_ci'

);
          
if (empty($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = '127.0.0.1:80';
};

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
    $CFG->wwwroot   = 'https://' . $_SERVER['HTTP_HOST'];
} else {
    $CFG->wwwroot   = 'http://' . $_SERVER['HTTP_HOST'];
};
$CFG->dataroot  = __dir__.'/moodledata';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 02775;

$CFG->passwordsaltalt1 = 'f25cda3e0747f8e956c0fba6ff7a03c811c49bfacd332353dd36cffe7c4bcbc9';
$CFG->passwordsaltmain = 'cad77bb85e83716ca542be5a03580506b9cb58c6049567fb2493e726dbc40915';

$CFG->defaultblocks_override = ':news_items,teaching_team,messageteacher,calendar_upcoming,recent_activity,navigation,settings,dedication';
require_once(__DIR__ . '/lib/setup.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
$CFG->wwwroot   = 'https://moodle.studujkdychces.cz';
//$CFG->localcachedir = '/tmp/cache';
//$CFG->tempdir = '/tmp'; 

//$CFG->debug = 2047; 
//$CFG->debugdisplay = 1;


//@error_reporting(E_ALL | E_STRICT);
//@ini_set('display_errors', '1');
//$CFG->debug = (E_ALL | E_STRICT)

//ini_set('display_errors', '0');;

//echo $CFG->auth='manual';