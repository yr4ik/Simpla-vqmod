<?php

/**
 *
 * @package Simpla vQmod Uninstall Script
 * @author Jay Gilford - http://vqmod.com/
 * @ port Polevik Yurii 2016
 *
 */

 


class vqmodUninstall extends vqmodInstaller {
	
	// COUNTERS
	public $deleted =  0;
	public $changes = 0;
	public $writes = 0;
	
	public function fetch(){
		return '<textarea onclick="this.focus();this.select()" readonly="readonly" cols="60" rows="12">' . $this->uninstall() . '</textarea>'.
		'<br><input type="button" onclick="window.location=\''.$this->config->root_url.'\'" value="Перейти на сайт">';
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
			
			/* api/Simpla.php CHANGE */
			'api/Simpla.php' => array(
				'~'.preg_quote(VQMOD_OPEN).'\s+include_once(.+?)'.preg_quote(VQMOD_CLOSE).'~s' => "include_once(dirname(__FILE__).'/'.\$class.'.php');",
				'~'.preg_quote(VQMOD_OPEN).'(.+?)'.preg_quote(VQMOD_CLOSE).'~s' => ''
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
					$result_log .= "/{$resource_path} can't delete\n";

			}
		}
		
		if(is_link(ROOT_DIR . 'vqmod/xml/xmltheme.lnk'))
			unlink(ROOT_DIR . 'vqmod/xml/xmltheme.lnk');
		
		// output result to user
		if(!$this->changes) $result_log .= "\nVQMOD ALREADY UNINSTALLED!";
		elseif($this->writes != 5) $result_log .= "\nONE OR MORE FILES COULD NOT BE WRITTEN";
		elseif($this->deleted != count($this->resources)) $result_log .= "\nONE OR MORE FILES COULD NOT BE DELETED";
		else $result_log .= "\nVQMOD HAS BEEN UNINSTALLED ON YOUR SYSTEM!";
		
		return $result_log;
	}
	
	
}

