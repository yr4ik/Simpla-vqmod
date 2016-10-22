<?php


define('VQMOD_OPEN', '#VQMOD#');
define('VQMOD_CLOSE', '#VQMOD_END#');



define('INSTALLER_DIR', dirname(__FILE__).'/');
define('ROOT_DIR', dirname(dirname(INSTALLER_DIR)).'/');


require_once(ROOT_DIR.'/api/Simpla.php');
require_once(INSTALLER_DIR.'config.php');
require_once(INSTALLER_DIR.'ugrsr.class.php');


class vqmodInstaller extends Simpla {
	
	public $version = '1.5';

	protected $resources = array(
		'minify.php' => 'resize/minify.php',
		'jsmin.php' => 'resize/jsmin.php',
	);
	
	/* STATIC */
	protected static $ugrsr = null;
	
	static function init(){

		// Verify path is correct
		$write_errors = array();
		if(!is_writeable(ROOT_DIR . 'index.php')) {
			$write_errors[] = 'index.php not writeable';
		}
		if(!is_writeable(ROOT_DIR . '.htaccess')) {
			$write_errors[] = '.htaccess not writeable';
		}
		if(!is_writeable(ROOT_DIR . SIMPLA_ADMIN_DIR . '/index.php')) {
			$write_errors[] = 'Administrator '.SIMPLA_ADMIN_DIR.'/index.php not writeable';
		}
		if(!is_writeable(INSTALLER_DIR . '.htaccess')) {
			$write_errors[] = 'vqmod/installer/.htaccess not writeable';
		}
		if(!is_writeable(ROOT_DIR . '/config/config.php')) {
			$write_errors[] = 'config/config.php not writeable';
		}

		if(!empty($write_errors)) {
			die(implode('<br />', $write_errors));
		}


		$installer_classname = __CLASS__;
		
		return new $installer_classname();
	}
	
	
	// Create new UGRSR class
	protected function ugrsr(){
		
		if(is_null(self::$ugrsr)){
			
			self::$ugrsr = new UGRSR(ROOT_DIR);

			// Set file searching to off
			self::$ugrsr->file_search = false;
			
			// remove the # before this to enable debugging info
			#self::$ugrsr->debug = true;
			#self::$ugrsr->test_mode = true;
			
		}
		
		return self::$ugrsr;
	}
	
	
	/* Check installer htaccess */
	protected function protectionEnabled(){
		
		$htaccess = INSTALLER_DIR.'.htaccess';
		
		if(!file_exists($htaccess))
			return false;
		
		$content = file_get_contents($htaccess);
		
		if(!preg_match('#AuthUserFile\s+(.+?)$#im', $content, $path_passwd))
			return false;
		
		if(!file_exists($path_passwd[1]))
			return false;
		
		return true;
	}
	
	
	public function display(){
		
		if($this->protectionEnabled()){
			$action = $this->request->get('action', 'string');
			
			if(!$this->managers->access('settings'))
				die('You not have access to change site settings');
			
		}else
			$action = 'protection';

		if(!file_exists(INSTALLER_DIR . 'controllers/' . $action . '.php'))
			die('Controller not exists');
		
		include_once(INSTALLER_DIR . 'controllers/' . $action . '.php');
		
		$controller_name = 'vqmod'.$action;
		
		if(!class_exists($controller_name))
			die('Wrong controller');
		
		$controller = new $controller_name();
		
		return $controller->fetch();
	}
	
	
	
}



header('Content-type: text/html; charset=utf-8'); 
error_reporting(E_ALL ^ E_NOTICE);

$installer = vqmodInstaller::init();
$content = $installer->display();

?><html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf8" />
  <meta http-equiv="Content-Language" content="ru" />
  <title>Установка Simpla vQmod</title>
</head>
<style>
  h1{font-size:26px; font-weight:normal}
  p{font-size:19px;}
  input{font-size:18px;}
  td{padding-right:15px;font-size:18px; font-family:tahoma, verdana;}
  p.error{color:red;}
  div.maindiv{width: 600px; height: 300px; position: relative; left: 50%; top: 100px; margin-left: -300px; }
</style>
<body>
<div style="width:100%; height:100%;"> 
  <div class="maindiv">
    <h1>Установка Simpla vQmod v.<?php echo $installer->version; ?></h1>
    <?php echo $content; ?>
  </div>
</div>


</body>
</html>

