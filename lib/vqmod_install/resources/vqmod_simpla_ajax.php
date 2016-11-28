<?php

require_once('../../vqmod/vqmod.php');
VQMod::bootup();


$ajax_module = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$subdir = substr(VQMod::getCwd(), strlen($_SERVER['DOCUMENT_ROOT'].'/'));
$module_path = VQMod::getCwd().trim(substr($ajax_module, strlen($subdir)), '/');

if(!file_exists($module_path))
	die('Module not exists');

include(VQMod::modCheck($module_path));