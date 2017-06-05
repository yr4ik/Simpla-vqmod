<?php

require_once(dirname(dirname(__FILE__)).'/vqmod/vqmod.php');
VQMod::bootup();

if(!empty($_GET['VQLOAD']))
	$module_vqload = $_GET['VQLOAD'];
elseif(($passed = getopt('', array('vq:'))) && !empty($passed['vq']))
	$module_vqload = dirname(__FILE__) . '/' . trim($passed['vq'], '/');
else
	die('[vQmod Loader] not set VQLOAD parameter');

if(!is_file($module_vqload))
	die('[vQmod Loader] wrong file: '.var_export($module_vqload, 1));


if(!preg_match('~^'.preg_quote(dirname(__FILE__)).'/(index|ajax(/stats)?/([\w-\.]+)|cml/1c_exchange)\.php$~', $module_vqload))
	die('[vQmod Loader] error: permission denied');

chdir(dirname($module_vqload));
include_once(VQMod::modCheck($module_vqload));