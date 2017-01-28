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



		$this->form->addElement(new Element_Hidden('action', $this->request->get('action', 'string')));
		$this->form->addElement(new Element_Hidden('controller', $this->request->get('controller', 'string')));
		$this->form->addElement(new Element_Hidden('rewrite', 'yes'));
		
		$this->form->addElement(new Element_HTML('<h1>vQmod Protection</h1>
			<p>Здравствуйте!</p>
			<p>Для продолжения необходима авторизация<br>
			<small>(примечание: учетная запись должна иметь доступ к настрокам сайта)</small></p>
			<hr>'));
		
		
		$this->form->addElement(new Element_Button('Продолжить', 'submit'));
		
		$this->form->addElement(new Element_Button('Отмена', 'button', array(
			'class' => 'btn-default',
			'onclick' => "window.location='/'"
		)));


		return $this->form->render(true);
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
		
		define('MOD_NAME',  $controller);
		define('MOD_DIR', $component_dir . $controller . '/');
		
		if(!file_exists(MOD_DIR . $controller . '.php'))
			throw new Exception('Компонент ' . $controller . ' не найден', 14);
		
		include_once(MOD_DIR . $controller . '.php');

		if(!class_exists($controller))
			throw new Exception('Ошибка контроллера', 15);
		
		$controller_object = new $controller();
		
		if(!is_subclass_of($controller_object, 'vqInstaller'))
			throw new Exception('Ошибка контроллера', 16);
				
		
		return $controller_object->$action();
		
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


?><html>
<head>
	<base href="<?php echo $installer->config->root_url;?>/"/>
	<title>Simpla vQmod <?php echo $installer->vqmod_version;?></title>
	
	<meta http-equiv="Content-Type" content="text/html; charset=utf8" />
	<meta http-equiv="Content-Language" content="ru" />

	<meta http-equiv="cache-control" content="max-age=0" />
	<meta http-equiv="cache-control" content="no-cache" />
	<meta http-equiv="expires" content="0" />
	<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
	<meta http-equiv="pragma" content="no-cache" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	
	<link rel="stylesheet" href="vqmod/lib/bootstrap/css/bootstrap.min.css"/>
	<link rel="stylesheet" href="vqmod/lib/bootstrap/css/bootstrap-theme.min.css"/>
	<link rel="stylesheet" href="vqmod/lib/bootstrap/style.css"/>

	<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
	
	<style>
		.lead {font-size: 16px}
	</style>
	
</head>
<body>
    <div class="site-wrapper">
      <div class="site-wrapper-inner">
        <div class="cover-container">
		
          <div class="masthead clearfix">
            <div class="inner">
              <h3 class="masthead-brand">Simpla vQmod <?php echo $installer->vqmod_version;?></h3>
              <nav>
                <ul class="nav masthead-nav">
					<li><a href="https://github.com/yr4ik/Simpla-vqmod" target="_blank">GitHub</a></li>
					<li><a href="http://forum.simplacms.ru/topic/11871-237-vqmod-simplacms/" target="_blank">Forum</a></li>
                </ul>
              </nav>
            </div>
          </div>

          <div class="inner cover">
            <!--<h1 class="cover-heading">Cover your page.</h1>-->
			<div class="lead">
				<?php echo $content; ?>
			</div>
          </div>

          <div class="mastfoot">
            <div class="inner">
              <p>Simpla vQmod v<?php echo $installer->vqmod_version;?> &copy; <a href="http://vk.com/polevik_yuriy">Polevik Yurii</a>.</p>
            </div>
          </div>
		  
        </div>
      </div>
    </div>
	<script type="text/javascript">
	$(function(){
		$('button,input[type="button"]').filter(':not([class])').addClass('btn btn-default');
	});
	</script>
</body>
</html>