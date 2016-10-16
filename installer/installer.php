<?php

/*
	CHANGE THIS IF YOU EDIT YOUR ADMIN FOLDER NAME
	Имя папки админпанели (меняем если это не папка simpla)
*/

define('SIMPLA_ADMIN_DIR', 'simpla');





/* 
	NOT CHANGE SECTION 
	Дальше ничего не меняем
*/

$RESOURCES = array(
	'minify.php' => 'resize/minify.php',
	'jsmin.php' => 'resize/jsmin.php',
);


define('VQMOD_OPEN', '#VQMOD#');
define('VQMOD_CLOSE', '#VQMOD_END#');


define('INSTALLER_DIR', dirname(__FILE__).'/');
define('ROOT_DIR', dirname(dirname(INSTALLER_DIR)).'/');

require_once(dirname(__FILE__).'/ugrsr.class.php');



// Verify path is correct
$write_errors = array();
if(!is_writeable(ROOT_DIR . 'index.php')) {
	$write_errors[] = 'index.php not writeable';
}
if(!is_writeable(ROOT_DIR . '.htaccess')) {
	$write_errors[] = '.htaccess not writeable';
}
if(!is_writeable(ROOT_DIR . SIMPLA_ADMIN_DIR . '/index.php')) {
	$write_errors[] = 'Administrator '.SIMPLA_ADMIN_DIR.'/index.php not writeable';
}
if(!is_writeable(ROOT_DIR . 'config/config.php')) {
	$write_errors[] = 'config/config.php not writeable';
}
if(!is_writeable(ROOT_DIR . 'vqmod/installer/.htaccess')) {
	$write_errors[] = 'vqmod/installer/.htaccess not writeable';
}



if(!empty($write_errors)) {
	die(implode('<br />', $write_errors));
}

// Create new UGRSR class
$u = new UGRSR(ROOT_DIR);


// remove the # before this to enable debugging info
#$u->debug = true;
#$u->test_mode = true;

// Set file searching to off
$u->file_search = false;


/* NOT CHANGE SECTION  END */

