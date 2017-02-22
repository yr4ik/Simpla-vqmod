<?php


include_once('installer.php');


class vqmodInstaller extends vqInstaller {
	
	public $action = 'mods';

	
	public function __construct(){
		
		
		if($action = $this->request->get('action', 'string'))
			$this->action = $action;

		$this->design->set_templates_dir(INSTALLER_DIR . 'design/html');
		$this->design->set_compiled_dir(INSTALLER_DIR . 'design/compiled');
		
		$this->design->assign('installer', $this);
	
		
	}
	
	
	/* Check installer htaccess */
	protected function protectionEnabled(){
		
		$htaccess = VQMOD_DIR . '.htaccess';
		
		if(!file_exists($htaccess))
			return false;
		
		$content = file_get_contents($htaccess);
		
		if(!preg_match('#AuthUserFile\s+(.+?)$#im', $content, $path_passwd))
			return false;
		
		if(!file_exists($path_passwd[1]))
			return false;
		
		return true;
	}
	
	protected function protection(){
		
		if($this->request->post('rewrite', 'boolean')){
			
			$htaccess = VQMOD_DIR . '.htaccess';
			
			if(file_exists($htaccess))
				$content = file_get_contents($htaccess);

			if(empty($content) || strpos($content, '#VQMOD_PROTECT')===false)
				throw new Exception('vqmod/.htaccess поврежден', 12);
				
			$protection = "#VQMOD_PROTECT\n";
			$protection .= "AddType application/octet-stream csv\n";
			$protection .= "AuthName 'Simpla vqmod'\n";
			$protection .= "AuthType Basic\n";
			$protection .= "AuthUserFile " . ROOT_DIR . SIMPLA_ADMIN_DIR . "/.passwd\n";
			$protection .= "require valid-user\n";
			$protection .= "#VQMOD_PROTECT";

			$content = preg_replace('~#VQMOD_PROTECT(.*?)#VQMOD_PROTECT~s', $protection, $content);
			
			if(!file_put_contents($htaccess, $content))
				throw new Exception('Ошибка записи защиты vqmod', 12);

			$location = $this->request->post('action', 'string');
			if($controller = $this->request->post('controller', 'string'))
				$location = $controller . '/' . $location;

			header('Location: '.$location);
			exit;
		}

		return $this->design->fetch('protection.tpl');
	}
	
	


	public function display(){
		
		if(!$this->protectionEnabled())
			return $this->protection();

		if(!$this->managers->access('settings'))
			throw new Exception('У вас нет доступа к настройкам сайта', 13);
	
		//Register mod api
		if(!$controller = trim($this->request->get('controller', 'string')))
			$controller = 'vqmod_control';
		
		self::$vqinstaller['mod'] = $this->mods->get($controller);

		$controller_object = $this->mod->get_controller();
	
		if(!method_exists($controller_object, $this->action))
			throw new Exception($this->mod->name . ' не поддерживает '.$this->action, 17);
				
		$content = call_user_func(array($controller_object, $this->action));
		
		if(in_array($this->action, array('install', 'uninstall'))){
			$time_var = $this->action.'_timestamp';
			$this->mod->$time_var = time();
		}
		
		return $content;
	}


}

session_start();
error_reporting(E_ALL ^ E_NOTICE);
header('Content-type: text/html; charset=utf-8'); 

try {

	$installer = new vqmodInstaller();
	$content = $installer->display();
	
} catch (Exception $e) {
    $content = '<div class="alert alert-danger"><b>[Ошибка ' . $e->getCode() . ']:</b>  ' . $e->getMessage() . '</div>';
}

$installer->design->assign('content',  $content);
echo $installer->design->fetch('index.tpl');



