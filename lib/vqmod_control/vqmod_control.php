<?php

/*
	@ name: vQmod for Simpla CMS
	@ version: 2.5
	@ description: Установочный компонент vqmod
	@ author: Polevik Yurii
	@ author_url: http://vk.com/polevik_yuriy
*/


class vqmod_control extends vqInstaller {

	private $resources = array();
	

	public function __construct(){
		
		// Verify path is correct
		$_errors = array();
		if(!is_writeable(ROOT_DIR . '.htaccess'))
			$_errors[] = '.htaccess not writeable';

		if(!is_writeable(ROOT_DIR . '/config/config.php'))
			$_errors[] = 'config/config.php not writeable';

		if(version_compare(PHP_VERSION, '5.1.2', '<'))
			$_errors[] = 'Need php version 5.1.2 or higher';
		
		if(!class_exists("DOMDocument"))
			$_errors[] = 'DOMDocument extension needs to be loaded for work';		
		
		
		if(!empty($_errors))
			throw new Exception(implode('<br />', $_errors), 103);
		
		
		$this->resources = array(
			array('src' => '[MOD]/resources/minify.php', 'dest' => 'resize/minify.php'),
			array('src' => '[MOD]/resources/jsmin.php', 'dest'  => 'resize/jsmin.php'),
			array('src' => '[MOD]/resources/vqmod_loader.php', 'dest'  => 'vqmod_loader.php'),
			array('src' => '[MOD]/resources/simpla_vqmod_loader.php', 'dest'  => SIMPLA_ADMIN_DIR . '/vqmod_loader.php'),
		);
	}


	
	
	
	public function install(){
		
		$this->form->addElement(new Element_HTML('<h1>Установка vQmod '.$this->mod->version.'</h1>'));
		
		if(!$this->is_confirmed()){
			
			$this->form->addElement(new Element_HTML('<p>Вы подтверждаете начало установки?</p><hr>'));
			
			$this->form->addElement(new Element_Hidden('confirmed', 'yes'));
			$this->form->addElement(new Element_Button('Да', 'submit'));
			
			$this->form->addElement(new Element_Button('Отмена', 'button', array(
				'class' => 'btn-default',
				'onclick' => "window.location='/'"
			)));

		}else{
	
			$patches = array(			
				/* .htaccess CHANGE */
				'.htaccess' => array(
					'~RewriteEngine on[\s$]+((?!'.preg_quote(VQMOD_OPEN).')(#|Rewrite))~mi' =>
							"RewriteEngine on\n\n".
							VQMOD_OPEN . "\n".
							
							//File is exist or skip 3 RewriteRule
							"RewriteCond %{REQUEST_FILENAME} !-f\n".
							"RewriteRule . - [S=3]\n".
							
							//Cath css and js
							"RewriteRule ^(js|design)/(.*)\.(js|css)$ {$this->resources[0]['dest']} [L]\n".
							//Cath view modules
							"RewriteRule ^(index|yandex|sitemap|ajax/([\w-\.]+)|payment/\w+/callback|resize/resize)\.php$ {$this->resources[2]['dest']}?VQLOAD=%{REQUEST_FILENAME} [QSA,L]\n".
							//Cath admin protected modules
							"RewriteRule ^" . SIMPLA_ADMIN_DIR . "/(index|ajax(/stats)?/([\w-\.]+)|cml/1c_exchange)\.php$ {$this->resources[3]['dest']}?VQLOAD=%{REQUEST_FILENAME} [QSA,L]\n".
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
			
			$result_log = '';
			
			foreach($patches as $patch_file=>$patch){
				$result = $this->installer->apply_patch($patch_file, $patch);
				$result_log .= "<div>{$patch_file} was write {$result->changes} changes</div>";
			}
			
			foreach($this->resources as $copy){
				
				if($this->installer->copy_file($copy['src'], $copy['dest'], true))
					$result_log .= "<div>{$copy['dest']} was installed</div>";
				else
					$result_log .= "<div class=\"text-danger\">{$copy['dest']} can't installed</div>";
			}


			$xmlDirs = array();
			$xmlDirlist = ROOT_DIR . VQMod::$xmlDirlist;
			if(file_exists($xmlDirlist))
				$xmlDirs = file($xmlDirlist, FILE_SKIP_EMPTY_LINES);
			
			$xmlDirs[0] = SIMPLA_DESIGN_DIR ."/{$this->settings->theme}/xml\n";
			$this->installer->write_file($xmlDirlist, implode($xmlDirs));
			
			$result_log = "<div class=\"text-left\">{$result_log}</div><br>";
			
			$result_counts = $this->installer->get_counter();
			if(!$result_counts->changes) $result_log .= "<div class=\"alert alert-success\">VQMOD ALREADY INSTALLED!</div>";
			elseif($result_counts->writes != count($patches)) $result_log .= "<div class=\"alert alert-danger\">ONE OR MORE FILES COULD NOT BE WRITTEN</div>";
			elseif($result_counts->copied != count($this->resources)) $result_log .= "<div class=\"alert alert-danger\">ONE OR MORE FILES COULD NOT BE COPIED</div>";
			else{
				$result_log .= "<div class=\"alert alert-success\">VQMOD HAS BEEN INSTALLED ON YOUR SYSTEM!</div>";
				$this->mod->status = 'installed';
			}
			$this->form->addElement(new Element_HTML($result_log));
			
			$this->form->addElement(new Element_Button('Перейти на сайт', 'button', array(
				'class' => 'btn-default',
				'onclick' => "window.location='/'"
			)));
		}
		
		
		return $this->form->render(true);
	}
	
	

	public function uninstall(){
		

		$this->form->addElement(new Element_HTML('<h1>Удаление vQmod '.$this->mod->version.'</h1>'));
		
		if(!$this->is_confirmed()){
			
			$this->form->addElement(new Element_HTML('<p>Компоненты vqmod могут быть все еще активны.</p>
			<p>После удаления они не смогут нормально функционировать</p>
			<p>Вы подтверждаете удаление?</p><hr>'));
			
			$this->form->addElement(new Element_Hidden('confirmed', 'yes'));
			$this->form->addElement(new Element_Button('Да', 'submit'));
			
			$this->form->addElement(new Element_Button('Отмена', 'button', array(
				'class' => 'btn-default',
				'onclick' => "window.location='/'"
			)));

		}else{
			

			
			$patches = array(
				/* .htaccess CHANGE */
				'.htaccess' => array(
					'~'.preg_quote(VQMOD_OPEN).'(.+?)'.preg_quote(VQMOD_CLOSE).'~s' => ''
				),
				
				/* config/config.php CHANGE */
				'config/config.php' => array(
					'~\s+;'.preg_quote(VQMOD_OPEN).'(.+?);'.preg_quote(VQMOD_CLOSE).'~s' => ''
				)
			);


			
			$result_log = '';
			
			foreach($patches as $patch_file=>$patch){
				$result = $this->installer->apply_patch($patch_file, $patch);
				$result_log .= "<div>{$patch_file} was write {$result->changes} changes</div>";
			}
			
			
			foreach($this->resources as $delete){
				if($this->installer->delete_file($delete['dest']))
					$result_log .= "<div>{$delete['dest']} was deleted</div>";
				else
					$result_log .= "<div class=\"text-danger\">{$delete['dest']} can't delete</div>";
			}
			
			$result_log = "<div class=\"text-left\">{$result_log}</div><br>";
			
			$result_counts = $this->installer->get_counter();

			// output result to user
			if(!$result_counts->changes) $result_log .= "<div class=\"alert alert-success\">VQMOD ALREADY UNINSTALLED!</div>";
			elseif($result_counts->writes != count($patches)) $result_log .= "<div class=\"alert alert-danger\">ONE OR MORE FILES COULD NOT BE WRITTEN</div>";
			elseif($result_counts->deleted_file != count($this->resources)) $result_log .= "<div class=\"alert alert-danger\">ONE OR MORE FILES COULD NOT BE DELETED</div>";
			else{
				$result_log .= "<div class=\"alert alert-success\">VQMOD HAS BEEN UNINSTALLED ON YOUR SYSTEM!</div>";
				$this->mod->status = 'uninstalled';
			}
			
			$this->form->addElement(new Element_HTML($result_log));
			
			$this->form->addElement(new Element_Button('Перейти на сайт', 'button', array(
				'class' => 'btn-default',
				'onclick' => "window.location='/'"
			)));
		}
		
		return $this->form->render(true);
	}
	
	
	
	
	public function mods(){
		
	
		$mods = glob(MODS_DIR. '*', GLOB_ONLYDIR);
		

		foreach($mods as &$_mod)
			$_mod =  $this->mods->get(basename($_mod));

		array_unshift($mods, $this->mods->get('vqmod_control'));
		
		$this->design->assign('mods', $mods);
		
		return $this->design->fetch('mods.tpl');
	}
	
	
	
	
	public function manager(){
		
 		if($xml_turn = trim($this->request->get('turn'))){
			
			if(is_file(VQMOD_DIR. "xml/{$xml_turn}.xml")){
				
				$is_off = (substr($xml_turn, 0, 1)=='_');
				
				if($is_off)
					$new_xml = substr($xml_turn, 1);
				else
					$new_xml = '_'.$xml_turn;

				if(rename(VQMOD_DIR. "xml/{$xml_turn}.xml", VQMOD_DIR. "xml/{$new_xml}.xml"))
					$this->design->assign('turn_xml', $this->get_vqxml_data(VQMOD_DIR. "xml/{$new_xml}.xml"));
					
			}else{
				header('Location: '.$this->request->url(array('turn'=>null)));
				exit;
			}
		}


		$xmls = glob(VQMOD_DIR. 'xml/*.xml');
		
		if(!$xmls)
			throw new Exception('xml-модули отсутствуют', 21);
			
		foreach($xmls as &$_xml)
			$_xml =  $this->get_vqxml_data($_xml);

		$this->design->assign('xmls', $xmls);
		
		return $this->design->fetch('manager.tpl');
	}
	
	
	
	
	
	private function get_vqxml_data($xml_path){
		
		$data = array(
			'id' => basename($xml_path, '.xml'),
			'version' => '',
			'author' => ''
		);
		
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->preserveWhiteSpace = false;
		
		$dom->load($xml_path);
		$modification = $dom->getElementsByTagName('modification')->item(0);
		

		foreach($data as $tag=>$value){
			$node = $modification->getElementsByTagName($tag)->item(0);
			if($node && ($nodeValue = trim((string) $node->nodeValue)))
				$data[$tag] = $nodeValue;
		}
		
		$data['xml_file'] = basename($xml_path, '.xml');
		$data['active'] = !(substr($data['xml_file'], 0,1)=='_');
		
		return (object) $data;
	}
		

		
	private function is_confirmed(){
		return $this->request->post('confirmed', 'boolean');
	}
	
}


