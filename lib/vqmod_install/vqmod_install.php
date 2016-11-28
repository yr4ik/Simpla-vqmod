<?php

/**
 *
 * @Simpla vQmod Install/Uninstal Script
 * @author Polevik Yurii 2016 - https://vk.com/polevik_yuriy
 *
 */

class vqmod_install extends vqInstaller {

	protected $resources = array();

	// COUNTERS
	public $copied =  0;
	public $deleted =  0;
	public $changes = 0;
	public $writes = 0;
	
	
	public function __construct(){
		
		$this->resources = array(
			'minify.php' => 'resize/minify.php',
			'jsmin.php' => 'resize/jsmin.php',
			'vqmod_ajax.php' => 'ajax/vqmod_ajax.php',
			'vqmod_simpla_ajax.php' => SIMPLA_ADMIN_DIR . '/ajax/vqmod_ajax.php',
		);
		
		// Verify path is correct
		$write_errors = array();
		if(!is_writeable(ROOT_DIR . 'index.php')) {
			$write_errors[] = 'index.php not writeable';
		}
		if(!is_writeable(ROOT_DIR . '.htaccess')) {
			$write_errors[] = '.htaccess not writeable';
		}
		if(!is_writeable(ROOT_DIR . SIMPLA_ADMIN_DIR . '/index.php')) {
			$write_errors[] = 'Administrator '.SIMPLA_ADMIN_DIR.'/index.php not writeable';
		}
		if(!is_writeable(ROOT_DIR . '/config/config.php')) {
			$write_errors[] = 'config/config.php not writeable';
		}

		if(!empty($write_errors)) {
			throw new Exception(implode('<br />', $write_errors), 103);
		}
		
	}

	
	public function fetch($content){
		return '<h1>Simpla vQmod v.'.$this->vqmod_version.'</h1>
		<div style="height: 200px; overflow: auto; border: 1px solid grey; padding: 5px;">' . nl2br($content) . '</div>'.
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
			
			/* .htaccess CHANGE */
			'.htaccess' => array(
				'~RewriteEngine on[\s$]+((?!'.preg_quote(VQMOD_OPEN).')(#|Rewrite))~mi' =>
						"RewriteEngine on\n\n".
						VQMOD_OPEN . "\n".
						"RewriteCond %{REQUEST_FILENAME} -f\n".
						"RewriteRule ^(js|design)/(.*)\.(js|css)$ {$this->resources['minify.php']} [L]\n".
						"RewriteCond %{REQUEST_FILENAME} -f\n".
						"RewriteRule ^(" . SIMPLA_ADMIN_DIR . "/)?ajax/([\w_-]+)\.php$ \\$1{$this->resources['vqmod_ajax.php']} [L,QSA]\n".
						VQMOD_CLOSE . "\n\n\\2"
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
				
				$copy_result = ($u->test_mode || copy(INSTALLER_DIR.'vqmod_install/resources/'.$resource_file, ROOT_DIR.$resource_path) ? 1:0);
				$this->copied += $copy_result;
				
				if($copy_result)
					$result_log .= "/{$resource_path} was installed\n";
				else
					$result_log .= "<font color=\"red\">/{$resource_path} can't installed</font>\n";
			}
		}
		
		$xmlDirs = array();
		$xmlDirlist = ROOT_DIR . VQMod::$xmlDirlist;
		if(file_exists($xmlDirlist))
			$xmlDirs = file($xmlDirlist, FILE_SKIP_EMPTY_LINES);
		
		$xmlDirs[0] = SIMPLA_DESIGN_DIR ."/{$this->settings->theme}/xml\n";
		
		file_put_contents($xmlDirlist, implode($xmlDirs));
		
		// output result to user
		if(!$this->changes) $result_log .= "\n<font color=\"green\">VQMOD ALREADY INSTALLED!</font>";
		elseif($this->writes != 4) $result_log .= "\n<font color=\"red\">ONE OR MORE FILES COULD NOT BE WRITTEN</font>";
		elseif($this->copied != count($this->resources)) $result_log .= "\n<font color=\"red\">ONE OR MORE FILES COULD NOT BE COPIED</font>";
		else $result_log .= "\n<font color=\"green\">VQMOD HAS BEEN INSTALLED ON YOUR SYSTEM!</font>";
		
		return $this->fetch($result_log);
	}
	
	

	public function uninstall(){
		
		$result_log = '';
		
		$patches = array(
			/* index.php CHANGE */
			'index.php' => array(
				'~'.preg_quote(VQMOD_OPEN).'(.+?)'.preg_quote(VQMOD_CLOSE).'~s' => "require_once('view/IndexView.php');"
			),
			
			/* simpla/index.php CHANGE */
			SIMPLA_ADMIN_DIR . '/index.php' => array(
				'~'.preg_quote(VQMOD_OPEN).'(.+?)'.preg_quote(VQMOD_CLOSE).'~s' => "require_once('" . SIMPLA_ADMIN_DIR . "/IndexAdmin.php');"
			),
			
			/* .htaccess CHANGE */
			'.htaccess' => array(
				'~'.preg_quote(VQMOD_OPEN).'(.+?)'.preg_quote(VQMOD_CLOSE).'~s' => ''
			),
			
			/* config/config.php CHANGE */
			'config/config.php' => array(
				'~\s+;'.preg_quote(VQMOD_OPEN).'(.+?);'.preg_quote(VQMOD_CLOSE).'~s' => ''
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
				
				$delete = true;
				if(!$u->test_mode && file_exists(ROOT_DIR.$resource_path))
					$delete = unlink(ROOT_DIR.$resource_path);

				$this->deleted += ($delete?1:0);

				if($delete)
					$result_log .= "/{$resource_path} was deleted\n";
				else
					$result_log .= "<font color=\"red\">/{$resource_path} can't delete</font>\n";

			}
		}
		
		
		// output result to user
		if(!$this->changes) $result_log .= "\n<font color=\"green\">VQMOD ALREADY UNINSTALLED!</font>";
		elseif($this->writes != 4) $result_log .= "\n<font color=\"red\">ONE OR MORE FILES COULD NOT BE WRITTEN</font>";
		elseif($this->deleted != count($this->resources)) $result_log .= "\n<font color=\"red\">ONE OR MORE FILES COULD NOT BE DELETED</font>";
		else $result_log .= "\n<font color=\"green\">VQMOD HAS BEEN UNINSTALLED ON YOUR SYSTEM!</font>";
		
		return $this->fetch($result_log);
	}
	
	
	
	
}













