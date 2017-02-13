<?php




class logger_vqinstaller {
	
	private $_sep = null;
	private $_log_file = null;
	private $_logs = array();


	public function __construct() {
		$this->_sep = str_repeat('-', 70);
		
		$this->_log_file = ROOT_DIR . VQMod::$logFolder . MOD_NAME .'.log';
	}

	
	public function __destruct() {
		
		if(empty($this->_logs)) {
			return;
		}

		$txt = array();

		$txt[] = str_repeat('-', 15) . ' Date: ' . date('Y-m-d H:i:s') . ' ' . str_repeat('-', 15);

		foreach($this->_logs as  $log) {
			$txt[] = $log;
		}

		$txt[] = $this->_sep;
		$txt[] = str_repeat(PHP_EOL, 2);
		$append = true;

		if(!file_exists($this->_log_file)) {
			$append = false;
		} else {
			$content = file_get_contents($this->_log_file);
			if(!empty($content) && strpos($content, ' Date: ' . date('Y-m-d ')) === false) {
				$append = false;
			}
		}

		$result = file_put_contents($this->_log_file, implode(PHP_EOL, $txt), ($append ? FILE_APPEND | LOCK_EX : LOCK_EX));
		if(!$result) {
			die('vqInstaller::logger - LOG FILE "' . $this->_log_file . '" COULD NOT BE WRITTEN');
		}
	}

	
	public function write($data) {

		$this->_logs[] = $data;

	}
}

