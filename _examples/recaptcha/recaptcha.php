<?php

/**
 *
 * @Simpla HelloWorld Install/Uninstal Script
 * @author Polevik Yurii 2016 - https://vk.com/polevik_yuriy
 *
 */


class recaptcha extends vqInstaller {
	


	public function __construct(){
		$this->form->addElement(new Element_HTML('<legend>ReCaptcha v2.0</legend>'));
	}
	
	
	
	
	public function install(){

		if(!$this->is_confirmed()){
			
			$this->form->addElement(new Element_Hidden('confirmed', 'yes'));
			$this->form->addElement(new Element_Button('Да', 'submit'));
			
			$this->form->addElement(new Element_Button('Отмена', 'button', array(
				'class' => 'btn-default',
				'onclick' => "window.location='/'"
			)));
			
		}else{
			
			$this->installer->exec('manifest.xml', 'install');
			
			$this->form->addElement(new Element_HTML('<br><p>Для завершения в шаблонах вашей темы где выводиться капча замените ее на</p>'));

			$this->form->addElement(new Element_Textbox("код отображения recaptcha: ", "short-code", array(
				'readonly' => 'readonly',
				'value' => '<div class="g-recaptcha" data-sitekey="{$settings->site_code|escape}"></div>'
			)));

			$this->form->addElement(new Element_HTML('<p class="small">Шаблоны для замены: cart.tpl, feedback.tpl, post.tpl, product.tpl, register.tpl</p>'));
			
			$this->form->addElement(new Element_HTML("<div class=\"alert alert-success\">Модуль установлен</div>"));
		
			$this->form->addElement(new Element_Button('Перейти на сайт', 'button', array(
				'class' => 'btn-default',
				'onclick' => "window.location='/'"
			)));
			
			
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
			
		}


		return $this->form->render(true);
	}
	
	

	private function is_confirmed(){
		return $this->request->post('confirmed', 'boolean');
	}
	
}

