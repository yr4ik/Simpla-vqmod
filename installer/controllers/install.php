<?php

/**
 *
 * @package Simpla vQmod Install Script
 * @author Jay Gilford - http://vqmod.com/
 * @ port Polevik Yurii 2016
 *
 */

 

class vqmodInstall extends vqmodInstaller {

	// COUNTERS
	public $copied =  0;
	public $changes = 0;
	public $writes = 0;
	
	public function fetch(){
		return '<textarea onclick="this.focus();this.select()" readonly="readonly" cols="60" rows="12">' . $this->install() . '</textarea>'.
		'<br><input type="button" onclick="window.location=\''.$this->config->root_url.'\'" value="Перейти на сайт">';
	}
	
	public function install(){
		
		$result_log = '';
		
		$patches = array(
			/* index.php CHANGE */
			'index.php' => array(
				'~require_once\(\'view/IndexView.php\'\);~' => 
						"\n" . VQMOD_OPEN . "\n".
						"require_once('./vqmod/vqmod.php');\n".
						"VQMod::bootup();\n".
						"require_once(VQMod::modCheck('view/IndexView.php'));\n".
						VQMOD_CLOSE . "\n"
			),
			
			/* simpla/index.php CHANGE */
			SIMPLA_ADMIN_DIR . '/index.php' => array(
				'~require_once\(\''.SIMPLA_ADMIN_DIR.'/IndexAdmin.php\'\);~' => 
						"\n" . VQMOD_OPEN . "\n".
						"require_once('./vqmod/vqmod.php');\n".
						"VQMod::bootup();\n".
						"require_once(VQMod::modCheck('".SIMPLA_ADMIN_DIR."/IndexAdmin.php'));\n".
						VQMOD_CLOSE . "\n" 
			),
			
			/* api/Simpla.php CHANGE */
			'api/Simpla.php' => array(
				"~<\?php[$\s]+(?<!".preg_quote(VQMOD_OPEN).")\/\*~m" => 
						"<?php\n".
						VQMOD_OPEN . "\n".
						"if(!class_exists('VQMod')){\n".
						"	require_once(dirname(dirname(__FILE__)).'/vqmod/vqmod.php');\n".
						"	VQMod::bootup();\n".
						"}\n".
						VQMOD_CLOSE."\n\n/*",
				'~include_once\(dirname\(__FILE__\)\.\'/\'\.\$class\.\'\.php\'\);~' =>
						"\n" . VQMOD_OPEN . "\n".
						"include_once(VQMod::modCheck(dirname(__FILE__).'/'.\$class.'.php'));\n".
						VQMOD_CLOSE . "\n"
			),
			/* .htaccess CHANGE */
			'.htaccess' => array(
				'~RewriteEngine on[\s$]+((?!'.preg_quote(VQMOD_OPEN).')#)~m' =>
						"RewriteEngine on\n\n".
						VQMOD_OPEN . "\n".
						"RewriteRule (js|design)/(.*)\.(js|css)$ {$this->resources['minify.php']} [L]\n".
						VQMOD_CLOSE . "\n\n#"
			),
			/* config/config.php CHANGE */
			'config/config.php' => array(
				'~(\[smarty\](?!;'.preg_quote(VQMOD_OPEN).'))([\s$]+)smarty_~m' =>
						"[smarty]\n".
						";" . VQMOD_OPEN . "\n".
						"minify_js				= true			; сжимать javascript (true=да, false=нет)\n".
						"minify_css			= true			; сжимать css (true=да, false=нет)\n".
						"static_gzip_level	= 9				; уровень сжатия (gzip) от 0 до 9\n".
						"static_expire_time	= 172800		; время хранения в сек. (172800=2дня)\n".
						";" . VQMOD_CLOSE . "\nsmarty_"
			)
		);
		
		$u = $this->ugrsr();
		
		foreach($patches as $patch_file=>$actions){
			$u->addFile($patch_file);
			
			foreach($actions as $regex => $replace)
				$u->addPattern($regex, $replace);

			$result = $u->run();
			$result_log .= "/{$patch_file} was write {$result['changes']} changes\n";

			$this->writes += $result['writes'];
			$this->changes += $result['changes'];

			$u->clearPatterns();
			$u->resetFileList();
		}
		
		

		if(!empty($this->resources)){
			foreach($this->resources as $resource_file => $resource_path){
				
				if(!$u->test_mode && file_exists(ROOT_DIR.$resource_path))
					unlink(ROOT_DIR.$resource_path);

				if(!$u->test_mode && !is_dir(dirname(ROOT_DIR.$resource_path)))
					mkdir(dirname(ROOT_DIR.$resource_path), 0755, true);
				
				$copy_result = ($u->test_mode || copy(INSTALLER_DIR.'resources/'.$resource_file, ROOT_DIR.$resource_path) ? 1:0);
				$this->copied += $copy_result;
				
				if($copy_result)
					$result_log .= "/{$resource_path} was installed\n";
				else
					$result_log .= "/{$resource_path} can't installed\n";
			}
		}
		
		
		// output result to user
		if(!$this->changes) $result_log .= "\nVQMOD ALREADY INSTALLED!";
		elseif($this->writes != 5) $result_log .= "\nONE OR MORE FILES COULD NOT BE WRITTEN";
		elseif($this->copied != count($this->resources)) $result_log .= "\nONE OR MORE FILES COULD NOT BE COPIED";
		else $result_log .= "\nVQMOD HAS BEEN INSTALLED ON YOUR SYSTEM!";
		
		return $result_log;
	}
	
	
	
}













