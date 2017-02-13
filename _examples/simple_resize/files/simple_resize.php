<?php

require_once('../vqmod/vqmod.php');
VQMod::bootup();

require_once(VQMod::modCheck(VQMod::getCwd().'api/Simpla.php'));

$simpla = new Simpla();

$filename = $simpla->request->get('file');
$type = $simpla->request->get('type', 'string');

$resized_filename =  $simpla->image->resize($filename, $type);

if(is_readable($resized_filename))
{
	header('Content-type: image');
	print file_get_contents($resized_filename);
}