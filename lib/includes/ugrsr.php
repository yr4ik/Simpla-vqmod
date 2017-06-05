<?php

include_once(dirname(__FILE__).'/ugrsr/ugrsr.class.php');


class ugrsr_vqinstaller extends UGRSR {
	
	
	public function __construct(){
		
		parent::__construct(ROOT_DIR);

		// Set file searching to off
		$this->file_search = false;
		
		// remove the # before this to enable debugging info
		#$this->debug = true;
		#$this->test_mode = true;

	}
	
}