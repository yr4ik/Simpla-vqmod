<?php

require_once('../vqmod/vqmod.php');
VQMod::bootup();

$ajax_module = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$module_path = $_SERVER['DOCUMENT_ROOT'].$ajax_module;

if(!file_exists($module_path))
	die('Module not exists');

include(VQMod::modCheck($module_path));