<?php

require_once('vqmod/vqmod.php');
VQMod::bootup();

$module_vqload = getenv('REDIRECT_VQLOAD');

if(empty($module_vqload) ||  !is_file($module_vqload))
	die('vqmod loader error: '.var_export($module_vqload, 1));

chdir(dirname($module_vqload));

include_once(VQMod::modCheck($module_vqload));
