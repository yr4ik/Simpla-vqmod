<?php

/*
*	@ name: ComPages
*	@ version: 1.3
*	@ description: Возможность оставлять комментарии к страницам
*	@ author: Polevik Yurii
*	@ author_url: http://vk.com/polevik_yuriy
*/



class compages extends vqInstaller {
	
	protected $types = null;

	public function __construct(){
		
		$this->db->query("SHOW COLUMNS FROM __comments LIKE ?", 'type');
		$enum = $this->db->result('Type');
		
		if($enum && preg_match_all('~\'([^\']*)\'~', $enum, $matches)){
			foreach($matches[1] as $match)
				$this->types[$match] = $match;
		}
		
		if(!$this->types)
			throw new Exception('Ошибка работы с базой данных', 41);
	
		
		$this->form->addElement(new Element_HTML('<legend>'.$this->mod->name . ' ' . $this->mod->version . '</legend>'));
	}
	

	public function install(){

		if(isset($this->types['page']))
			throw new Exception('Модуль уже установлен', 42);
	
		if(!$this->is_confirmed()){
			
			$this->form->addElement(new Element_HTML('<p>Данный модуль оставлять комментарии к страницам</p>
			<p>Установить?</p>'));
			
			$this->form->addElement(new Element_Hidden('confirmed', 'yes'));
			$this->form->addElement(new Element_Button('Да', 'submit'));
			
			$this->form->addElement(new Element_Button('Отмена', 'button', array(
				'class' => 'btn-default',
				'onclick' => "window.location='/'"
			)));


		}else{

			$this->types['page'] = 'page';
			$this->db->query("ALTER TABLE __comments CHANGE type type ENUM(?@) NOT NULL", $this->types);
			$this->db->query("ALTER TABLE __pages ADD allow_comment INT( 1 ) NOT NULL DEFAULT ?", '0');

			
			$this->installer->copy_file('[MOD]/compages.xml', 'vqmod/xml/compages.xml', true);
			

			$counters = $this->installer->get_counter();

			$result_log = "<p>Выполнено {$counters->query} sql к базе данных</p>";
			$result_log .= "<p>Установлено {$counters->copied} файлов</p>";
			
			
			foreach($this->installer->get_results('errors') as $error)
				$result_log .= "<div class=\"alert alert-danger\">{$error}</div>";

			$result_log .= "<div class=\"alert alert-success\">Модуль установлен</div>";
			
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


		if(!isset($this->types['page']))
			throw new Exception('Модуль не установлен', 42);
		
		if(!$this->is_confirmed()){
			
			$this->form->addElement(new Element_HTML('<p>Вы подтверждаете удаление?</p>'));

			$this->form->addElement(new Element_Hidden('confirmed', 'yes'));
			$this->form->addElement(new Element_Button('Удалить', 'submit'));
			
			$this->form->addElement(new Element_Button('Отмена', 'button', array(
				'class' => 'btn-default',
				'onclick' => "window.location='/'"
			)));

		}else{

			unset($this->types['page']);
			$this->db->query("ALTER TABLE __comments CHANGE type type ENUM(?@) NOT NULL", $this->types);
			$this->db->query("ALTER TABLE __pages DROP allow_comment");

			$this->installer->delete_file('vqmod/xml/compages.xml');

			$counters = $this->installer->get_counter();
			
			$result_log = "<p>Выполнено {$counters->query} sql к базе данных</p>";
			$result_log .= "<p>Удалено {$counters->deleted_file} файлов</p>";
									
			foreach($this->installer->get_results('errors') as $error)
				$result_log .= "<div class=\"alert alert-danger\">{$error}</div>";

			$result_log .= "<div class=\"alert alert-success\">Модуль удален</div>";
			
			$this->form->addElement(new Element_HTML($result_log));
			
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





