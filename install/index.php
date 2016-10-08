<?php

/**
 *
 * @package Simpla vQmod Install Script
 * @author Jay Gilford - http://vqmod.com/
 * @ port Polevik Yurii 2016
 *
 */

// CHANGE THIS IF YOU EDIT YOUR ADMIN FOLDER NAME
$admin_folder = 'simpla';

// Counters
$changes = 0;
$writes = 0;

// Load class required for installation
require('ugrsr.class.php');

include_once('../../api/Simpla.php');

$simpla = new Simpla();

// Verify path is correct

$write_errors = array();
if(!is_writeable($simpla->config->root_dir . 'index.php')) {
	$write_errors[] = 'index.php not writeable';
}
if(!is_writeable($simpla->config->root_dir . $admin_folder . '/index.php')) {
	$write_errors[] = 'Administrator index.php not writeable';
}

if(!empty($write_errors)) {
	die(implode('<br />', $write_errors));
}

// Create new UGRSR class
$u = new UGRSR($simpla->config->root_dir);

// remove the # before this to enable debugging info
#$u->debug = true;

// Set file searching to off
$u->file_search = false;


// Add catalog index files to files to include
$u->addFile('index.php');

// Pattern to add vqmod include
$u->addPattern('~require_once\(\'view/IndexView.php\'\);~', '// vQmod Startup
require_once(\'./vqmod/vqmod.php\');
VQMod::bootup();
//vQmod Startup END

require_once(VQMod::modCheck(\'view/IndexView.php\'));');

$result = $u->run();
$writes += $result['writes'];
$changes += $result['changes'];

$u->clearPatterns();
$u->resetFileList();

// Add Admin index file
$u->addFile($admin_folder . '/index.php');

// Pattern to add vqmod include
$u->addPattern('~require_once\(\''.$admin_folder.'/IndexAdmin.php\'\);~', '// vQmod Startup
require_once(\'./vqmod/vqmod.php\');
VQMod::bootup();
//vQmod Startup END

require_once(VQMod::modCheck(\''.$admin_folder.'/IndexAdmin.php\'));');


$result = $u->run();
$writes += $result['writes'];
$changes += $result['changes'];

$u->addFile('api/Simpla.php');

// Pattern to run required files through vqmod
$u->addPattern('~class Simpla~', '

// vQmod Startup
require_once(dirname(dirname(__FILE__)).\'/vqmod/vqmod.php\');
VQMod::bootup();
//vQmod Startup END

class Simpla');

$u->addPattern('~include_once\(dirname\(__FILE__\).\'/\'.\$class.\'.php\'\);~', 'include_once(VQMod::modCheck(dirname(__FILE__).\'/\'.$class.\'.php\'));');

// Get number of changes during run
$result = $u->run();
$writes += $result['writes'];
$changes += $result['changes'];

// output result to user
if(!$changes) die('VQMOD ALREADY INSTALLED!');
if($writes != 3) die('ONE OR MORE FILES COULD NOT BE WRITTEN');
die('VQMOD HAS BEEN INSTALLED ON YOUR SYSTEM!');