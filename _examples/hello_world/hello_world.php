<?php

/**
 *
 * @Simpla HelloWorld Install/Uninstal Script
 * @author Polevik Yurii 2016 - https://vk.com/polevik_yuriy
 *
 */


class hello_world extends vqInstaller {
	


	public function __construct(){
		$this->form->addElement(new Element_HTML('<legend>Hello World</legend>'));
	}
	
	
	
	
	public function install(){

		if(!$this->is_confirmed()){
			
			$this->form->addElement(new Element_HTML('<p>Данный модуль служит для демонстрации функций.</p>
			<p>Установить?</p>'));
			
			$this->form->addElement(new Element_Hidden('confirmed', 'yes'));
			$this->form->addElement(new Element_Button('Да', 'submit'));
			
			$this->form->addElement(new Element_Button('Отмена', 'button', array(
				'class' => 'btn-default',
				'onclick' => "window.location='/'"
			)));


			
		}else{
			
			$this->installer->exec('manifest.xml', 'install');
			
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

