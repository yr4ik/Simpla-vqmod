<?php

/**
 *
 * @Simpla translit Install/Uninstal Script
 * @author Polevik Yurii 2016 - https://vk.com/polevik_yuriy
 *
 */


class translit extends vqInstaller {
	
	

	public function __construct(){
		$this->form->addElement(new Element_HTML('<legend>Translit</legend>'));
	}
	

	public function install(){

		if(!$this->is_confirmed()){
			
			$this->form->addElement(new Element_HTML('<p>Данный модуль позволит транслитерировать любой текст</p>
			<p>Установить?</p>'));
			
			$this->form->addElement(new Element_Hidden('confirmed', 'yes'));
			$this->form->addElement(new Element_Button('Да', 'submit'));
			
			$this->form->addElement(new Element_Button('Отмена', 'button', array(
				'class' => 'btn-default',
				'onclick' => "window.location='/'"
			)));


		}else{
			
			
			$this->installer->exec('manifest.xml', 'install');

			$counters = $this->installer->get_counter();

			$result_log = "<div class=\"text-left\"><div>Установлено {$counters->copied} файлов</div></div><br>";
			
			
			foreach($this->installer->get_results('errors') as $error)
				$result_log .= "<div class=\"alert alert-danger\">{$error}</div>";

			$result_log .= "<div class=\"alert alert-success\">Модуль установлен</div>";
			
			$this->form->addElement(new Element_HTML($result_log));

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


			$counters = $this->installer->get_counter();
			$result_log = "<div class=\"text-left\"><div>Удалено {$counters->deleted_file} файлов</div></div><br>";
									
			foreach($this->installer->get_results('errors') as $error)
				$result_log .= "<div class=\"alert alert-danger\">{$error}</div>";

			$result_log .= "<div class=\"alert alert-success\">Модуль удален</div>";
			
			$this->form->addElement(new Element_HTML($result_log));
			
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

