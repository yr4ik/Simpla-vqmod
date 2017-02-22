<?php

/*
*	@ name: Simple Resize
*	@ version: 1.2
*	@ description: Мульти-ресайз изображений
*	@ author: Polevik Yurii
*	@ author_url: http://vk.com/polevik_yuriy
*/



class simple_resize extends vqInstaller {


	const resized_dir = 'files/resize/';
	const products_images_dir = 'files/products/';
	
	const resized_images_dir = 'files/products/';
	const original_images_dir = 'files/originals/';



	public function __construct(){
		
		if(!is_writeable(ROOT_DIR . '.htaccess'))
			$write_errors[] = '.htaccess not writeable';

		
		if(!is_writeable(ROOT_DIR . 'config/config.php'))
			$write_errors[] = 'config/config.php not writeable';

		if(!empty($write_errors))
			throw new Exception(implode('<br />', $write_errors), 103);


		$this->form->addElement(new Element_HTML('<legend>'.$this->mod->name . ' ' . $this->mod->version . '</legend>'));
		
	}
	
	
	

	
	public function install(){
		
		
		if($this->is_installed())
			throw new Exception('Модуль уже установлен', 303);

		
		if(!is_dir(ROOT_DIR . $this->config->original_images_dir))
			throw new Exception('Can\'t find original_images_dir directory', 304);
		
		
		if(!$this->is_confirmed()){
			
			$this->form->addElement(new Element_HTML('<p>Данный модуль улучшает функции стандартного ресайза</p>
			<p>Установить?</p>'));
			
			$this->form->addElement(new Element_Hidden('confirmed', 'yes'));
			$this->form->addElement(new Element_Button('Да', 'submit'));
			
			$this->form->addElement(new Element_Button('Отмена', 'button', array(
				'class' => 'btn-default',
				'onclick' => "window.location='/'"
			)));


		}else{
		
			
			$result_log = '';
			

			if($this->installer->copy_file('[MOD]/simple_resize.xml', 'vqmod/xml/simple_resize.xml', true))
				$result_log .= "<div>simple_resize.xml was installed</div>";
			else
				$result_log .= "<div class=\"text-warning\">simple_resize.xml can't installed</div>";


			$patches = array(
				'config/config.php' => array(
					'~original_images_dir\s+=\s+'.$this->config->original_images_dir.'\s*;~s' => 'products_images_dir = '.self::products_images_dir.';',
					'~resized_images_dir\s+=\s+'.$this->config->resized_images_dir.'\s*;~s' => 'resized_dir = '.self::resized_dir.';'
				),
				'.htaccess' => array(
					'~RewriteRule\s+\^files/products/\(\.\+\)\s+resize/resize\.php\?file=\$1&token=%{QUERY_STRING}~is' => 'RewriteRule ^files/resize/([a-z]+)/(.+)$ resize/resize.php?file=\$2&type=\$1'
				)
			);
		
			
			foreach($patches as $patch_file=>$patch){
				$result = $this->installer->apply_patch($patch_file, $patch);
				$result_log .= "<div>{$patch_file} was write {$result->changes} changes</div>";
			}
			
			if(!is_dir(ROOT_DIR . $this->config->resized_images_dir) || $this->installer->delete_dir($this->config->resized_images_dir))
				$result_log .= "<div>/{$this->config->resized_images_dir} was removed<div>";	
			else
				$result_log .= "<div color=\"text-warning\">/{$this->config->resized_images_dir} can't removed</div>";
			
			
			if(!is_dir(ROOT_DIR . self::products_images_dir) && $this->installer->rename($this->config->original_images_dir, self::products_images_dir))
				$result_log .= "<div>/{$this->config->original_images_dir} was renamed to /".self::products_images_dir."</div>";	
			else
				$result_log .= "<div class=\"text-warning\">/{$this->config->original_images_dir} can't rename to /".self::products_images_dir."</div>";
			

			$result_log .= "<br><div class=\"alert alert-success\">Модуль установлен</div>";
			
			$this->form->addElement(new Element_HTML($result_log));
			
			$this->form->addElement(new Element_Button('Перейти на сайт', 'button', array(
				'class' => 'btn-default',
				'onclick' => "window.location='/'"
			)));
			
			$this->mod->status = 'installed';
			
		}
		
		return $this->form->render(true);
	}
	
	
	public function uninstall(){
		

		if(!$this->is_installed())
			throw new Exception('Модуль не установлен', 303);

		
		if(!is_dir(ROOT_DIR . $this->config->products_images_dir))
			throw new Exception('Can\'t find products_images_dir directory', 304);

		if(!$this->is_confirmed()){
			
			$this->form->addElement(new Element_HTML('<p>Вы подтверждаете удаление?</p>'));

			$this->form->addElement(new Element_Hidden('confirmed', 'yes'));
			$this->form->addElement(new Element_Button('Удалить', 'submit'));
			
			$this->form->addElement(new Element_Button('Отмена', 'button', array(
				'class' => 'btn-default',
				'onclick' => "window.location='/'"
			)));

		}else{
				
			$result_log = '';
			
			
			if($this->installer->delete_file('vqmod/xml/simple_resize.xml'))
				$result_log .= "<div>simple_resize.xml was deleted</div>";
			else
				$result_log .= "<div class=\"text-warning\">simple_resize.xml can't delete</div>";


			$patches = array(
				'config/config.php' => array(
					'~products_images_dir\s+=\s+'.$this->config->products_images_dir.'\s*;~s' => 'original_images_dir = '.self::original_images_dir.';',
					'~resized_dir\s+=\s+'.$this->config->resized_dir.'\s*;~s' => 'resized_images_dir = '.self::resized_images_dir.';'
				),
				'.htaccess' => array(
					'~RewriteRule\s\^files/resize/\(\[a-z\]\+\)/\(\.\+\)\$\s+resize/resize\.php\?file=\$2&type=\$1~is' => 'RewriteRule ^files/products/(.+) resize/resize.php?file=\$1&token=%{QUERY_STRING}'
				)
			);
			
			foreach($patches as $patch_file=>$patch){
				$result = $this->installer->apply_patch($patch_file, $patch);
				$result_log .= "<div>{$patch_file} was write {$result->changes} changes</div>";
			}
			

			if(!is_dir(ROOT_DIR . $this->config->resized_dir) || $this->installer->delete_dir($this->config->resized_dir))
				$result_log .= "<div>/{$this->config->resized_dir} was removed</div>";	
			else
				$result_log .= "<div class=\"text-warning\">/{$this->config->resized_dir} can't removed</div>";
			
			
			if($this->installer->rename($this->config->products_images_dir, self::original_images_dir))
				$result_log .= "<div>/{$this->config->products_images_dir} was renamed to /".self::original_images_dir."</div>";	
			else
				$result_log .= "<div class=\"text-warning\">/{$this->config->products_images_dir} can't rename to /".self::original_images_dir."</div>";
			
			
			$this->installer->new_dir(self::resized_images_dir);
			
			$result_log .= "<br><div class=\"alert alert-success\">Модуль удален</div>";
			
			$this->form->addElement(new Element_HTML($result_log));
			
			$this->form->addElement(new Element_Button('Перейти на сайт', 'button', array(
				'class' => 'btn-default',
				'onclick' => "window.location='/'"
			)));
			
			$this->mod->status = 'uninstalled';
			
		}
		
		return $this->form->render(true);
	}

	
	
	
	private function is_installed(){
		return !is_null($this->config->resized_dir);
	}


	private function is_confirmed(){
		return $this->request->post('confirmed', 'boolean');
	}
	
	
}






