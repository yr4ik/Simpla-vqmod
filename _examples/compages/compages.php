<?php

/**
 *
 * @Simpla comments page Install/Uninstal Script
 * @author Polevik Yurii 2016 - https://vk.com/polevik_yuriy
 *
 */


class compages extends vqInstaller {
	
	private $resources = array(
		'compages.xml' => 'vqmod/xml/compages.xml'
	);
	
	
	private function fetch($result){
		return '<h1>Комментарии к страницам v.1.0</h1>
		' . nl2br($result) . '<br><br>
		<input type="button" onclick="window.location=\''.$this->config->root_url.'\'" value="Перейти на сайт">';
	}
	
	
	private function get_comments_types(){
		
		$types = array();
		
		$this->db->query("SHOW COLUMNS FROM __comments LIKE ?", 'type');
		$enum = $this->db->result('Type');
		
		if($enum && preg_match_all('~\'([^\']*)\'~', $enum, $matches)){
			foreach($matches[1] as $match)
				$types[$match] = $match;
		}
		return $types;
	}
	
	public function install(){
		$message = '';
		$types = $this->get_comments_types();
		
		if(!$types)
			$message .= 'Ошибка работы с базой данных';
		elseif(isset($types['page']))
			$message .= 'Модуль уже установлен';
		else{
			$types['page'] = 'page';
			$this->db->query("ALTER TABLE __comments CHANGE type type ENUM(?@) NOT NULL", $types);
			$this->db->query("ALTER TABLE __pages ADD allow_comment INT( 1 ) NOT NULL DEFAULT ?", '0');
				
			if(!empty($this->resources)){

				foreach($this->resources as $resource_file => $resource_path){
					
					if(file_exists(ROOT_DIR.$resource_path))
						unlink(ROOT_DIR.$resource_path);

					if(!is_dir(dirname(ROOT_DIR.$resource_path)))
						mkdir(dirname(ROOT_DIR.$resource_path), 0755, true);
					
					$copy_result = (copy(dirname(__FILE__).'/'.$resource_file, ROOT_DIR.$resource_path) ? 1:0);

					if($copy_result)
						$message .= "/{$resource_path} was installed\n";
					else
						$message .= "<font color=\"red\">/{$resource_path} can't installed</font>\n";
				}
			}
			
			
			$message .= "<font color=\"green\">Модуль установлен</font>";
		}
		return $this->fetch($message);
	}
	
	
	public function uninstall(){
		$message = '';
		$types = $this->get_comments_types();
		
		if(!$types)
			$message .= 'Ошибка работы с базой данных';
		elseif(!isset($types['page']))
			$message .= 'Модуль не установлен';
		else{
			unset($types['page']);
			$this->db->query("ALTER TABLE __comments CHANGE type type ENUM(?@) NOT NULL", $types);
			$this->db->query("ALTER TABLE __pages DROP allow_comment");
			
			if(!empty($this->resources)){
				foreach($this->resources as $resource_file => $resource_path){
					
					$delete = true;
					if(file_exists(ROOT_DIR.$resource_path))
						$delete = unlink(ROOT_DIR.$resource_path);

					if($delete)
						$message .= "/{$resource_path} was deleted\n";
					else
						$message .= "<font color=\"red\">/{$resource_path} can't delete</font>\n";

				}
			}
			
			$message .= "\n<font color=\"green\">Модуль удален</font>";
		}
		return $this->fetch($message);
	}

}






