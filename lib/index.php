<?php


include_once('installer.php');


class vqmodInstaller extends vqInstaller {
	
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
			if($controller = $this->request->post('controller', 'boolean'))
				$location .= '/'.$this->request->post('controller', 'string');

			header('Location: '.$location);
			exit;
		}

		return '<h1>Simpla vQmod v.'.$this->version.'</h1>
		<form method="post">
			<input type="hidden" name="action" value="'.$this->request->get('action', 'string').'">
			<input type="hidden" name="controller" value="'.$this->request->get('controller', 'string').'">
			<p>Здравствуйте!</p>
			<p>Для продолжения необходимо авторизоватся<br>
			<small>(примечание: учетная запись должна иметь доступ к настрокам сайта)</small></p>
			<p><input type="checkbox" name="rewrite" value="1"> У меня есть доступ</p>
			<input type="submit" value="Продолжить">
			<input type="button" onclick="window.location=\''.$this->config->root_url.'\'" value="Отмена">
		</form>';
	}
	
	


	public function display(){
		
		if(!$this->protectionEnabled())
			return $this->protection();

		
		if(!$this->managers->access('settings'))
			throw new Exception('У вас нет доступа к настройкам сайта', 13);
	
		$action = $this->request->get('action', 'string');
		$controller = $this->request->get('controller', 'string');
		
		$component_dir = VQMOD_DIR.'mod/';
		if(empty($controller)){
			$controller = 'vqmod_install';
			$component_dir = INSTALLER_DIR;
		}

		if(!file_exists($component_dir . $controller . '/' . $controller . '.php'))
			throw new Exception('Компонент ' . $controller . ' не найден', 14);
		
		include_once($component_dir . $controller . '/' . $controller . '.php');

		if(!class_exists($controller))
			throw new Exception('Ошибка контроллера', 15);
		
		$controller_object = new $controller();
		
		if(!is_subclass_of($controller_object, 'vqInstaller'))
			throw new Exception('Ошибка контроллера', 16);
				
		
		return $controller_object->$action();
		
	}


}



error_reporting(E_ALL ^ E_NOTICE);
header('Content-type: text/html; charset=utf-8'); 

try {
	$installer = new vqmodInstaller();
	$content = $installer->display();
	
} catch (Exception $e) {
    $content = '<div><b>[Ошибка ' . $e->getCode() . ']:</b>  ' . $e->getMessage() . '</div>';
}


?><html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf8" />
	<meta http-equiv="Content-Language" content="ru" />
	<meta http-equiv="cache-control" content="max-age=0" />
	<meta http-equiv="cache-control" content="no-cache" />
	<meta http-equiv="expires" content="0" />
	<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
	<meta http-equiv="pragma" content="no-cache" />
	<title>Simpla vQmod <?php echo $installer->version;?></title>
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
		<?php echo $content; ?>
	  </div>
	</div>
</body>
</html>