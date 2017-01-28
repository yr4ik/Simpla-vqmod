<?php

include_once(dirname(__FILE__).'/pfbc/Form.php');


class form_vqinstaller extends Form {
	
	
	public function __construct(){

		parent::__construct('vqform');
	
		$this->configure(array(
			//'prevent' => array('bootstrap', 'jQuery')
		));

	}
	
}