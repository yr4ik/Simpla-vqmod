<?php







class mods_vqinstaller  {
	
	/* STATIC */
	private static $_mods = array();
	
	
	public function __get($var){
		return $this->get($var);
	}
	
	
	public function get($mod_name){
		
		if(!isset(self::$_mods[$mod_name])){

			$mods_dir = ($mod_name == 'vqmod_control' ? INSTALLER_DIR:MODS_DIR);
			if(!is_dir($mods_dir . $mod_name))
				return;
			
			self::$_mods[$mod_name] = new vqinstallerModObject($mod_name, $mods_dir . $mod_name . '/');
		}

		
		return self::$_mods[$mod_name];
	}

}






class vqinstallerModObject {
	
	
	private $_mod = array();
	private $_mod_data = null;
	private $_mod_object = null;
	
	private $_sep = null;
	private $_logs = array();
	
	private $_mod_data_file = 'data.ini';
	private $_comments_data_vars = array('name', 'version', 'description', 'author', 'author_url');

	
	
	public function __construct($mod, $mod_path){
		
		$this->_sep = str_repeat('-', 70);
		

		$this->_mod['id'] = $mod;
		$this->_mod['directory'] = $mod_path;
		$this->_mod['controller'] = $mod_path . $mod . '.php';
		$this->_mod['log_file'] = VQMod::$logFolder . $mod .'.log';
		$this->_mod['log_exist'] = is_file(ROOT_DIR . $this->_mod['log_file']);
		

		
		//Берем файл инициализации и ищем в нем нужные комментарии
		$fp = fopen($this->_mod['controller'], 'r');
		if($fp){
			$file_data = fread($fp, 8192);
			fclose($fp);
			$file_data = str_replace("\r", "\n", $file_data);
			foreach ($this->_comments_data_vars as $field){
				if(preg_match( '/^[ \t\/*#@]*' . preg_quote( $field, '/' ) . ':(.*)$/mi', $file_data, $match) && $match[1])
					$this->_mod[$field] = trim(preg_replace("/\s*(?:\*\/|\?>).*/", '', $match[1]));
			}
		}

		if(empty($this->_mod['name']))
			$this->_mod['name'] = $mod;		
		
		
	}
	
	
	public function __destruct(){
		
		//Если были какие то операции с данными
		if(!is_null($this->_mod_data)){
		
			$save_data = array();
			foreach($this->_mod_data as $var=>$value){
				if(preg_match('~[^\w\d\.\-]~', $value))
					$value = "'{$value}'";
				
				$save_data[] = $var . ' = '.$value; 
			}

			$datafile = $this->_mod['directory'] . $this->_mod_data_file;
				
			if($save_data){
				
				array_unshift($save_data, '; Information file for '.$this->_mod['id'], '; '.$this->_sep, PHP_EOL);
				$save_data[] = PHP_EOL;
				
				file_put_contents($datafile, implode(PHP_EOL, $save_data), LOCK_EX);
				
			}elseif(is_file($datafile))//данных нет. Удаляем файл 
				unlink($datafile);
				
		}
		
			
		if(empty($this->_logs))
			return;

		$txt = array();
		$txt[] = str_repeat('-', 15) . ' Date: ' . date('Y-m-d H:i:s') . ' ' . str_repeat('-', 15);

		foreach($this->_logs as  $log)
			$txt[] = $log;

		$txt[] = $this->_sep;
		$txt[] = str_repeat(PHP_EOL, 2);

		if(!file_put_contents(ROOT_DIR . $this->_mod['log_file'], implode(PHP_EOL, $txt), ($this->_mod['log_exist'] ? FILE_APPEND | LOCK_EX : LOCK_EX)))
			die('vqInstaller::mod - LOG FILE "' . $this->_mod['log_file'] . '" COULD NOT BE WRITTEN');

	}
	
	
	public function __get($var){
		
		if(isset($this->_mod[$var]))
			return $this->_mod[$var];
		
		return $this->modData($var);
	}
	
	
	public function __set($var, $value){
		$this->modData($var, $value);
	}
	
	
	
	public function get_controller(){
		
		if(!$this->_mod_object){

			if(!file_exists($this->_mod['controller']))
				throw new Exception('Компонент ' . $this->_mod['name'] . ' не найден', 14);
			
			include_once($this->_mod['controller']);
			
			if(!class_exists($this->_mod['id']))
				throw new Exception('Ошибка контроллера', 15);
			
			$this->_mod_object = new $this->_mod['id']();
			
			if(!is_subclass_of($this->_mod_object, 'vqInstaller'))
				throw new Exception('Ошибка контроллера', 16);
			
		}
		return $this->_mod_object;	
	}
	
	
	
	public function log($data) {
		$this->_logs[] = $data;
	}
	
	
	
	public function unsetData($var){
		
		if(is_null($this->_mod_data))
			$this->initData();
		
		unset($this->_mod_data[$var]);
	}
	
	
	public function modData($var, $value=null){
		
		if(is_null($this->_mod_data))
			$this->initData();
		
		if(!is_null($value))
			$this->_mod_data[$var] = $value;
		elseif(isset($this->_mod_data[$var]))
			return $this->_mod_data[$var];
		
		return null;
	}
	
	private function initData(){
		
		$this->_mod_data = array();
		if(is_file($this->_mod['directory'] . $this->_mod_data_file) && ($dataini=parse_ini_file($this->_mod['directory'] . $this->_mod_data_file)))
			$this->_mod_data = $dataini;
		
	}
	
}