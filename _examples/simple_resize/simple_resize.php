<?php

/**
 *
 * @Simpla simple_resize Install/Uninstal Script
 * @author Polevik Yurii 2016 - https://vk.com/polevik_yuriy
 *
 */


class simple_resize extends vqInstaller {


	const resized_dir = 'files/resize/';
	const products_images_dir = 'files/products/';
	
	const resized_images_dir = 'files/products/';
	const original_images_dir = 'files/originals/';

	private $resources = array(
		'simple_resize.php' => 'resize/simple_resize.php',
		'simple_resize.xml' => 'vqmod/xml/simple_resize.xml'
	);
	

	public function __construct(){
		
		if(!is_writeable(ROOT_DIR . '.htaccess')) {
			$write_errors[] = '.htaccess not writeable';
		}
		
		if(!is_writeable(ROOT_DIR . '/config/config.php')) {
			$write_errors[] = 'config/config.php not writeable';
		}

		if(!empty($write_errors)) {
			throw new Exception(implode('<br />', $write_errors), 103);
		}

		
	}
	
	
	
	private function fetch($result){
		return '<h1>Simple resize v.1.0</h1>
		' . nl2br($result) . '<br><br>
		<input type="button" onclick="window.location=\''.$this->config->root_url.'\'" value="Перейти на сайт">';
	}
	
	
	public function install(){
		$message = '';

		if($this->is_installed())
			return $this->fetch("<font color=\"green\">Модуль уже установлен</font>");
		
		if(!is_dir(ROOT_DIR . $this->config->original_images_dir))
			return $this->fetch("<font color=\"red\">Can't find original_images_dir directory</font>");
		
		$copy_counts = 0;
		$resources_dir = dirname(__FILE__).'/files/';
		foreach($this->resources as $resource_file => $resource_path){
			
			if(file_exists(ROOT_DIR.$resource_path))
				unlink(ROOT_DIR.$resource_path);
			
			$copy_result = (copy($resources_dir.$resource_file, ROOT_DIR.$resource_path) ? 1:0);
			$copy_counts += $copy_result;
			
			if($copy_result)
				$message .= "/{$resource_path} was installed\n";
			else
				$message .= "<font color=\"red\">/{$resource_path} can't installed</font>\n";
		}
		
		if($copy_counts!==count($this->resources))
			return $this->fetch($message . "<font color=\"red\">Ошибка установки компонентов</font>\n");

		
		$patches = array(
			'config/config.php' => array(
				'~original_images_dir\s+=\s+'.$this->config->original_images_dir.'\s*;~s' => 'products_images_dir = '.self::products_images_dir.';',
				'~resized_images_dir\s+=\s+'.$this->config->resized_images_dir.'\s*;~s' => 'resized_dir = '.self::resized_dir.';'
			),
			'.htaccess' => array(
				'~RewriteRule\s+\^files/products/\(\.\+\)\s+resize/resize\.php\?file=\$1&token=%{QUERY_STRING}~is' => 'RewriteRule ^files/resize/([a-z]+)/(.+)$ resize/resize.php?file=\$2&type=\$1'
			)
		);
		
		$message .= $this->apply_patches($patches);
		
		if(!is_dir(ROOT_DIR . $this->config->resized_images_dir) || $this->delete_directory(ROOT_DIR . $this->config->resized_images_dir))
			$message .= "/{$this->config->resized_images_dir} was removed\n";	
		else
			$message .= "<font color=\"red\">/{$this->config->resized_images_dir} can't removed</font>\n";
		
		
		if(!is_dir(ROOT_DIR . self::products_images_dir) && rename(ROOT_DIR . $this->config->original_images_dir, ROOT_DIR . self::products_images_dir))
			$message .= "/{$this->config->original_images_dir} was renamed to ".self::products_images_dir."\n";	
		else
			$message .= "<font color=\"red\">/{$this->config->original_images_dir} can't rename to ".self::products_images_dir."</font>\n";
		
		
		$message .= "<font color=\"green\">Модуль установлен</font>";

		return $this->fetch($message);
	}
	
	
	public function uninstall(){
		$message = '';

		if(!$this->is_installed())
			return $this->fetch("<font color=\"green\">Модуль не установлен</font>");

		if(!is_dir(ROOT_DIR . $this->config->products_images_dir))
			return $this->fetch("<font color=\"red\">Can't find products_images_dir directory</font>");
		
		$delete_counts = 0;
		foreach($this->resources as $resource_file => $resource_path){
			
			$delete = true;
			if(file_exists(ROOT_DIR.$resource_path))
				$delete = unlink(ROOT_DIR.$resource_path);

			$delete_counts += ($delete?1:0);
			
			if($delete)
				$message .= "/{$resource_path} was deleted\n";
			else
				$message .= "<font color=\"red\">/{$resource_path} can't delete</font>\n";

		}

		if($delete_counts!==count($this->resources))
			return $this->fetch($message . "<font color=\"red\">Ошибка удаления компонентов</font>\n");
		
		$patches = array(
			'config/config.php' => array(
				'~products_images_dir\s+=\s+'.$this->config->products_images_dir.'\s*;~s' => 'original_images_dir = '.self::original_images_dir.';',
				'~resized_dir\s+=\s+'.$this->config->resized_dir.'\s*;~s' => 'resized_images_dir = '.self::resized_images_dir.';'
			),
			'.htaccess' => array(
				'~RewriteRule\s\^files/resize/\(\[a-z\]\+\)/\(\.\+\)\$\s+resize/resize\.php\?file=\$2&type=\$1~is' => 'RewriteRule ^files/products/(.+) resize/resize.php?file=\$1&token=%{QUERY_STRING}'
			)
		);
		
		$message .= $this->apply_patches($patches);
		
		if(!is_dir(ROOT_DIR . $this->config->resized_dir) || $this->delete_directory(ROOT_DIR . $this->config->resized_dir))
			$message .= "/{$this->config->resized_dir} was removed\n";	
		else
			$message .= "<font color=\"red\">/{$this->config->resized_dir} can't removed</font>\n";
		
		
		if(rename(ROOT_DIR . $this->config->products_images_dir, ROOT_DIR . self::original_images_dir))
			$message .= "/{$this->config->products_images_dir} was renamed to ".self::original_images_dir."\n";	
		else
			$message .= "<font color=\"red\">/{$this->config->products_images_dir} can't rename to ".self::original_images_dir."</font>\n";
		
		$message .= "\n<font color=\"green\">Модуль удален</font>";

		return $this->fetch($message);
	}

	
	public function is_installed(){
		return !is_null($this->config->resized_dir);
	}
	
	private function apply_patches($patches){
		
		$message = '';
		
		$u = $this->ugrsr();
		
		foreach($patches as $patch_file=>$actions){
			$u->addFile($patch_file);
			
			foreach($actions as $regex => $replace)
				$u->addPattern($regex, $replace);

			$result = $u->run();
			
			if(!$result['writes'] || $result['changes']!==count($actions))
				$message .= "<font color=\"red\">/{$patch_file} ".($result['writes'] ? "can't write changes":"was write {$result['changes']} changes")."</font>\n";
			else
				$message .= "/{$patch_file} was write {$result['changes']} changes\n";	
			
			$u->clearPatterns();
			$u->resetFileList();
		}
		
		return $message;
	}
	
	
	private function delete_directory($path){
		
		if(!$dh = @opendir($path)) 
	 		return false; 
		
		while (false !== ($obj = readdir($dh))){ 
			if($obj == '.' || $obj == '..') 
	            continue; 
	
	        if (!@unlink($path . '/' . $obj) && is_dir($path . '/' . $obj)) 
				$this->delete_directory($path . '/' . $obj); 
		} 
		closedir($dh);
		
		return @rmdir($path); 
	} 
	
	
	
	
	
}






