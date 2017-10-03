<?php

define('VQMOD_LOADER', 'BACKEND');

require_once(dirname(dirname(__FILE__)).'/vqmod/vqmod.php');
VQMod::bootup();

if(!empty($_GET['VQLOAD']))
	$module_vqload = $_GET['VQLOAD'];
elseif(($passed = getopt('', array('vq:'))) && !empty($passed['vq']))
	$module_vqload = trim($passed['vq'], '/');
else
	die('[vQmod Loader] not set VQLOAD parameter');

if(!preg_match('~^[\w\d]+/(index|ajax(/stats)?/([\w-\.]+)|cml/1c_exchange)\.php$~', $module_vqload))
	die('[vQmod Loader] error: permission denied');


if(!is_file( VQMod::getCwd() . $module_vqload ))
	die('[vQmod Loader] wrong file: '.var_export($module_vqload, 1));


chdir(dirname( VQMod::getCwd() . $module_vqload ));
include_once(VQMod::modCheck( VQMod::getCwd() . $module_vqload ));
