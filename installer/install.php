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
$copied =  0;
$changes = 0;
$writes = 0;




/* index.php CHANGE */
$u->addFile('index.php');

$u->addPattern('~require_once\(\'view/IndexView.php\'\);~',  '
'.VQMOD_OPEN.'
require_once(\'./vqmod/vqmod.php\');
VQMod::bootup();

require_once(VQMod::modCheck(\'view/IndexView.php\'));
'.VQMOD_CLOSE.'
');

$result = $u->run();
echo "index.php was write {$result['changes']} changes<br>";

$writes += $result['writes'];
$changes += $result['changes'];

$u->clearPatterns();
$u->resetFileList();
/* END  index.php CHANGE */



/* simpla/index.php CHANGE */
$u->addFile(SIMPLA_ADMIN_DIR . '/index.php');

$u->addPattern('~require_once\(\''.SIMPLA_ADMIN_DIR.'/IndexAdmin.php\'\);~', '
'.VQMOD_OPEN.'
require_once(\'./vqmod/vqmod.php\');
VQMod::bootup();
require_once(VQMod::modCheck(\''.SIMPLA_ADMIN_DIR.'/IndexAdmin.php\'));
'.VQMOD_CLOSE.'
');

$result = $u->run();
echo SIMPLA_ADMIN_DIR."/IndexAdmin.php was write {$result['changes']} changes<br>";

$writes += $result['writes'];
$changes += $result['changes'];

$u->clearPatterns();
$u->resetFileList();
/* END simpla/index.php CHANGE */



/* api/Simpla.php CHANGE */
$u->addFile('api/Simpla.php');

$u->addPattern("~<\?php[$\s]+(?<!".VQMOD_OPEN.")\/\*~m", '<?php

'.VQMOD_OPEN.'
if(!class_exists(\'VQMod\')){
	require_once(dirname(dirname(__FILE__)).\'/vqmod/vqmod.php\');
	VQMod::bootup();
}
'.VQMOD_CLOSE.'

/*');

$u->addPattern('~include_once\(dirname\(__FILE__\)\.\'/\'\.\$class\.\'\.php\'\);~', '
'.VQMOD_OPEN.'
include_once(VQMod::modCheck(dirname(__FILE__).\'/\'.$class.\'.php\'));
'.VQMOD_CLOSE.'
');

$result = $u->run();
echo "api/Simpla.php was write {$result['changes']} changes<br>";

$writes += $result['writes'];
$changes += $result['changes'];

$u->clearPatterns();
$u->resetFileList();
/* END api/Simpla.php CHANGE */



/* .htaccess CHANGE */
$u->addFile('.htaccess');

$u->addPattern('~RewriteEngine on[\s$]+((?!'.VQMOD_OPEN.')#)~m', 'RewriteEngine on

'.VQMOD_OPEN.'
RewriteRule (js|design)/(.*)\.(js|css)$ '.$RESOURCES['minify.php'].' [L]
'.VQMOD_CLOSE.'

#');

$result = $u->run();
echo ".htaccess was write {$result['changes']} changes<br>";

$writes += $result['writes'];
$changes += $result['changes'];

$u->clearPatterns();
$u->resetFileList();
/* END  .htaccess CHANGE */


/* config/config.php CHANGE */
$u->addFile('config/config.php');

$u->addPattern('~(\[smarty\](?!;'.VQMOD_OPEN.'))([\s$]+)smarty_~m', '[smarty]
;'.VQMOD_OPEN.'
minify_js				= true			; сжимать javascript (true=да, false=нет)
minify_css				= true			; сжимать css (true=да, false=нет)
static_gzip_level		= 9				; уровень сжатия (gzip) от 0 до 9
static_expire_time 	= 172800		; время хранения в сек. (172800=2дня)
;'.VQMOD_CLOSE.'
smarty_');

$result = $u->run();
echo "config/config.php was write {$result['changes']} changes<br>";

$writes += $result['writes'];
$changes += $result['changes'];

$u->clearPatterns();
$u->resetFileList();
/* END config/config.php CHANGE */


if(!empty($RESOURCES)){
	foreach($RESOURCES as $resource_file => $resource_path){
		
		if(!$u->test_mode && file_exists(ROOT_DIR.$resource_path))
			unlink(ROOT_DIR.$resource_path);

		if(!$u->test_mode && !is_dir(dirname(ROOT_DIR.$resource_path)))
			mkdir(dirname(ROOT_DIR.$resource_path), 0755, true);
		
		$copy_result = ($u->test_mode || copy(INSTALLER_DIR.'resources/'.$resource_file, ROOT_DIR.$resource_path) ? 1:0);
		$copied += $copy_result;
		
		if($copy_result)
			echo $resource_path." was installed<br>";
		else
			echo $resource_path." can't installed<br>";
	}
}


// Write unistall protection
$protection = file_put_contents(ROOT_DIR . 'vqmod/installer/.htaccess', 'Allow From All
AddType application/octet-stream csv
AuthName "vqmod simpla installer"
AuthType Basic
AuthUserFile '.ROOT_DIR.SIMPLA_ADMIN_DIR.'/.passwd
require valid-user');

if($protection)
	echo "Installer protection was written<br>";
else
	echo "Can't write installer protection<br>";


// output result to user
if(!$changes) die('VQMOD ALREADY INSTALLED!');
if($writes != 5) die('ONE OR MORE FILES COULD NOT BE WRITTEN');
if($copied != count($RESOURCES)) die('ONE OR MORE FILES COULD NOT BE COPIED');
die('VQMOD HAS BEEN INSTALLED ON YOUR SYSTEM!');