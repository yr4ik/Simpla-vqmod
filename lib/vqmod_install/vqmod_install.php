<?php

/**
 *
 * @Simpla vQmod Install/Uninstal Script
 * @author Polevik Yurii 2016 - https://vk.com/polevik_yuriy
 *
 */

class vqmod_install extends vqInstaller {

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
		
		if(getenv("REDIRECT_VQLOAD") !== 'true')
			$_errors[] = 'Web-server can\'t not pass environment variables';
		
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
		
		$form = $this->form;
		
		$form->addElement(new Element_HTML('<h1>Установка vQmod '.$this->vqmod_version.'</h1>'));
		
		if(!$this->is_confirmed()){
			
			$form->addElement(new Element_HTML('<p>Вы подтверждаете начало установки?</p><hr>'));
			
			$form->addElement(new Element_Hidden('confirmed', 'yes'));
			$form->addElement(new Element_Button('Да', 'submit'));
			
			$form->addElement(new Element_Button('Отмена', 'button', array(
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
							//Cath REDIRECT_VQLOAD and stop it
							"RewriteCond %{ENV:REDIRECT_VQLOAD} ^(.+)$\n".
							"RewriteRule .* - [E=VQLOAD:%1,L]\n".
							
							//File is exist or skip 3 RewriteRule
							"RewriteCond %{REQUEST_FILENAME} !-f\n".
							"RewriteRule . - [S=3]\n".
							
							//Cath css and js
							"RewriteRule ^(js|design)/(.*)\.(js|css)$ {$this->resources[0]['dest']} [L]\n".
							//Cath view modules
							"RewriteRule ^(index|yandex|sitemap|ajax/([\w-\.]+)|payment/\w+/callback|resize/resize)\.php$ {$this->resources[2]['dest']} [QSA,E=VQLOAD:%{SCRIPT_FILENAME},L]\n".
							//Cath admin protected modules
							"RewriteRule ^" . SIMPLA_ADMIN_DIR . "/(index|ajax(/stats)?/([\w-\.]+)|cml/1c_exchange)\.php$ {$this->resources[3]['dest']} [QSA,E=VQLOAD:%{SCRIPT_FILENAME},L]\n".
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
			else $result_log .= "<div class=\"alert alert-success\">VQMOD HAS BEEN INSTALLED ON YOUR SYSTEM!</div>";
			
			$form->addElement(new Element_HTML($result_log));
			
			$form->addElement(new Element_Button('Перейти на сайт', 'button', array(
				'class' => 'btn-default',
				'onclick' => "window.location='/'"
			)));
		}
		
		
		return $form->render(true);
	}
	
	

	public function uninstall(){
		
		$form = $this->form;
		
		$form->addElement(new Element_HTML('<h1>Удаление vQmod '.$this->vqmod_version.'</h1>'));
		
		if(!$this->is_confirmed()){
			
			$form->addElement(new Element_HTML('<p>Компоненты vqmod могут быть все еще активны.</p>
			<p>После удаления они не смогут нормально функционировать</p>
			<p>Вы подтверждаете удаление?</p><hr>'));
			
			$form->addElement(new Element_Hidden('confirmed', 'yes'));
			$form->addElement(new Element_Button('Да', 'submit'));
			
			$form->addElement(new Element_Button('Отмена', 'button', array(
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
			else $result_log .= "<div class=\"alert alert-success\">VQMOD HAS BEEN UNINSTALLED ON YOUR SYSTEM!</div>";

			$form->addElement(new Element_HTML($result_log));
			
			$form->addElement(new Element_Button('Перейти на сайт', 'button', array(
				'class' => 'btn-default',
				'onclick' => "window.location='/'"
			)));
		}
		
		return $form->render(true);
	}
	
	
	private function is_confirmed(){
		return $this->request->post('confirmed', 'boolean');
	}
	
}













