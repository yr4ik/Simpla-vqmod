<?php

/*
*	@ name: Recaptcha
*	@ version: 1.1
*	@ description: Google Recaptcha v2.0
*	@ author: Polevik Yurii
*	@ author_url: http://vk.com/polevik_yuriy
*/


class recaptcha extends vqInstaller {
	


	public function __construct(){
		$this->form->addElement(new Element_HTML('<legend>ReCaptcha v2.0</legend>'));
	}
	
	
	
	
	public function install(){

		if(!$this->is_confirmed()){
			
			$this->form->addElement(new Element_Hidden('confirmed', 'yes'));
			
			$this->form->addElement(new Element_HTML('<p>Установить?</p>'));
			$this->form->addElement(new Element_Button('Да', 'submit'));
			
			$this->form->addElement(new Element_Button('Отмена', 'button', array(
				'class' => 'btn-default',
				'onclick' => "window.location='/'"
			)));
			
		}else{
			
			$this->installer->exec('manifest.xml', 'install');
			
			$captha_code = htmlentities('<div class="g-recaptcha" data-sitekey="{$settings->site_code|escape}"></div>');
			
			$html = <<<HTML
			<br><p>Для завершения в шаблонах вашей темы где выводиться капча замените ее на</p>
			<p>код recaptcha: <code>{$captha_code}</code></p>
			<p class="small text-warning">Шаблоны для замены: cart.tpl, feedback.tpl, post.tpl, product.tpl, register.tpl</p>
			<div class="alert alert-success">Модуль установлен</div>
			<button onclick="window.location='/'" class="btn btn-default">Перейти на сайт</button>
HTML;

			$this->form->addElement(new Element_HTML($html));

			$this->mod->status = 'installed';
		}

		return $this->form->render(true);
	}
	
	
	public function uninstall(){


		if(!$this->is_confirmed()){
			
			$this->form->addElement(new Element_HTML('<p>Вы подтверждаете удаление?</p>'));
			
			$this->form->addElement(new Element_Hidden('confirmed', 'yes'));
			$this->form->addElement(new Element_Button('Удалить', 'submit'));
			
			$this->form->addElement(new Element_Button('Отмена', 'button', array(
				'class' => 'btn-default',
				'onclick' => "window.location='/'"
			)));

		}else{
			
			$this->installer->exec('manifest.xml', 'uninstall');
			
			$this->form->addElement(new Element_HTML("<div class=\"alert alert-success\">Модуль удален</div>"));
			
			$this->form->addElement(new Element_Button('Перейти на сайт', 'button', array(
				'class' => 'btn-default',
				'onclick' => "window.location='/'"
			)));
			
			$this->mod->status = 'uninstalled';
			
		}


		return $this->form->render(true);
	}
	
	

	private function is_confirmed(){
		return $this->request->post('confirmed', 'boolean');
	}
	
}

