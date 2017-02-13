<?php

define('INSTALLER_DIR', str_replace('\\', '/', dirname(__FILE__).'/'));
define('VQMOD_DIR', dirname(INSTALLER_DIR).'/');
define('ROOT_DIR', dirname(VQMOD_DIR).'/');


require_once(ROOT_DIR.'/api/Simpla.php');

require_once(VQMOD_DIR.'vqmod.php');

require_once(INSTALLER_DIR.'config.php');



class vqInstaller extends Simpla {
	
	public $vqmod_version = '2.4';
	
	/* STATIC */
	protected static $vqinstaller = array();

	
	//Create new UGRSR class (old version v1 - 2.2)
	protected function ugrsr(){
		return $this->ugrsr;
	}

	
	public function __get($var){
		
		if(isset(self::$vqinstaller[$var]))
			return self::$vqinstaller[$var];
		

		if(file_exists(INSTALLER_DIR . 'includes/'.$var.'.php')){
			//API VQMOD INSTALLER
			
			include_once(INSTALLER_DIR . 'includes/'.$var.'.php');
			
			$class_name = $var.'_vqinstaller';

			self::$vqinstaller[$var] = new $class_name();
			
		}else{
			
			//SIMPLA API
			self::$vqinstaller[$var] = parent::__get($var);
		}
		
		return self::$vqinstaller[$var];
	}
	
	

	protected function install(){
		return get_class() . ' не поддерживает install';
	}
	
	
	protected function uninstall(){
		return get_class() . ' не поддерживает uninstall';
	}
}




