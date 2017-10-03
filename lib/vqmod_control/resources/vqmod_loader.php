<?php


define('VQMOD_LOADER', 'FRONTEND');

require_once(dirname(__FILE__).'/vqmod/vqmod.php');
VQMod::bootup();


if(!empty($_GET['VQLOAD']))
	$module_vqload = $_GET['VQLOAD'];
elseif(($passed = getopt('', array('vq:'))) && !empty($passed['vq']))
	$module_vqload = trim($passed['vq'], '/');
else
	die('[vQmod Loader] not set VQLOAD parameter');

if(!preg_match('~^(index|yandex|sitemap|ajax/([\w-\.]+)|payment/\w+/callback|resize/resize)\.php$~', $module_vqload))
	die('[vQmod Loader] error: permission denied');


if(!is_file( VQMod::getCwd() . $module_vqload ))
	die('[vQmod Loader] wrong file: '.var_export($module_vqload, 1));


chdir(dirname( VQMod::getCwd() . $module_vqload ));
include_once(VQMod::modCheck( VQMod::getCwd() . $module_vqload ));