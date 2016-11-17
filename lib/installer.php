<?php

define('INSTALLER_DIR', dirname(__FILE__).'/');
define('VQMOD_DIR', dirname(INSTALLER_DIR).'/');
define('ROOT_DIR', dirname(VQMOD_DIR).'/');


require_once(ROOT_DIR.'/api/Simpla.php');

require_once(VQMOD_DIR.'vqmod.php');

require_once(INSTALLER_DIR.'config.php');



class vqInstaller extends Simpla {
	
	public $vqmod_version = '2.0';
	
	/* STATIC */
	protected static $ugrsr = null;

	
	//Create new UGRSR class
	protected function ugrsr(){
		
		if(is_null(self::$ugrsr)){
			
			require_once(INSTALLER_DIR.'ugrsr.class.php');
			
			self::$ugrsr = new UGRSR(ROOT_DIR);

			// Set file searching to off
			self::$ugrsr->file_search = false;
			
			// remove the # before this to enable debugging info
			#self::$ugrsr->debug = true;
			#self::$ugrsr->test_mode = true;
			
		}
		
		return self::$ugrsr;
	}
	
	
	
	protected function install(){
		return get_class() . ' не поддерживает install';
	}
	
	
	protected function uninstall(){
		return get_class() . ' не поддерживает uninstall';
	}
}




