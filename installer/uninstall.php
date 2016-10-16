<?php

/**
 *
 * @package Simpla vQmod Install Script
 * @author Jay Gilford - http://vqmod.com/
 * @ port Polevik Yurii 2016
 *
 */

 
require('installer.php');


// COUNTERS
$deleted =  0;
$changes = 0;
$writes = 0;




/* index.php CHANGE */
$u->addFile('index.php');

$u->addPattern('~'.VQMOD_OPEN.'(.+?)'.VQMOD_CLOSE.'~s', 'require_once(\'view/IndexView.php\');');

$result = $u->run();
echo "index.php was write {$result['changes']} changes<br>";

$writes += $result['writes'];
$changes += $result['changes'];

$u->clearPatterns();
$u->resetFileList();
/* END  index.php CHANGE */



/* simpla/index.php CHANGE */
$u->addFile(SIMPLA_ADMIN_DIR . '/index.php');

$u->addPattern('~'.VQMOD_OPEN.'(.+?)'.VQMOD_CLOSE.'~s', 'require_once(\''.SIMPLA_ADMIN_DIR.'/IndexAdmin.php\');');

$result = $u->run();
echo SIMPLA_ADMIN_DIR."/IndexAdmin.php was write {$result['changes']} changes<br>";

$writes += $result['writes'];
$changes += $result['changes'];

$u->clearPatterns();
$u->resetFileList();
/* END simpla/index.php CHANGE */



/* api/Simpla.php CHANGE */
$u->addFile('api/Simpla.php');


$u->addPattern('~'.VQMOD_OPEN.'\s+include_once(.+?)'.VQMOD_CLOSE.'~s',
'include_once(dirname(__FILE__).\'/\'.$class.\'.php\');');


$u->addPattern('~'.VQMOD_OPEN.'(.+?)'.VQMOD_CLOSE.'~s', '');

$result = $u->run();
echo "api/Simpla.php was write {$result['changes']} changes<br>";

$writes += $result['writes'];
$changes += $result['changes'];

$u->clearPatterns();
$u->resetFileList();
/* END api/Simpla.php CHANGE */



/* .htaccess CHANGE */
$u->addFile('.htaccess');

$u->addPattern('~'.VQMOD_OPEN.'(.+?)'.VQMOD_CLOSE.'~s', '');

$result = $u->run();
echo ".htaccess was write {$result['changes']} changes<br>";

$writes += $result['writes'];
$changes += $result['changes'];

$u->clearPatterns();
$u->resetFileList();
/* END  .htaccess CHANGE */


/* config/config.php CHANGE */
$u->addFile('config/config.php');

$u->addPattern('~\s+\['.VQMOD_OPEN.'(.+?)'.VQMOD_CLOSE.'\]~s', '');

$result = $u->run();
echo "config/config.php was write {$result['changes']} changes<br>";

$writes += $result['writes'];
$changes += $result['changes'];

$u->clearPatterns();
$u->resetFileList();
/* END config/config.php CHANGE */


if(!empty($RESOURCES)){
	foreach($RESOURCES as $resource_file => $resource_path){
		
		$delete = true;
		if(!$u->test_mode && file_exists(ROOT_DIR.$resource_path))
			$delete = unlink(ROOT_DIR.$resource_path);

		$deleted += ($delete?1:0);
		
		if($delete)
			echo $resource_path." was deleted<br>";
		else
			echo $resource_path." can not delete<br>";
	}
}

// Write unistall protection
$protection = file_put_contents(ROOT_DIR . 'vqmod/installer/.htaccess', 'Satisfy Any
Allow From All');

if($protection)
	echo "Installer protection was removed<br>";
else
	echo "Can't remove installer protection<br>";

// output result to user
if(!$changes) die('VQMOD ALREADY UNISTALLED!');
if($writes != 5) die('ONE OR MORE FILES COULD NOT BE WRITTEN');
if($deleted != count($RESOURCES)) die('ONE OR MORE FILES COULD NOT BE COPIED');
die('VQMOD HAS BEEN UNISTALLED ON YOUR SYSTEM!');