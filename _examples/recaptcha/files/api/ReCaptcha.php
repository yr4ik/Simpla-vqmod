<?php



require_once('Simpla.php');


class ReCaptcha extends Simpla 
{
	
	private $recaptha_object = null; 
	
	
	public function __construct(){
		
		spl_autoload_register(function ($class) {
			if (substr($class, 0, 10) !== 'ReCaptcha\\') {
			  /* If the class does not lie under the "ReCaptcha" namespace,
			   * then we can exit immediately.
			   */
			  return;
			}

			/* All of the classes have names like "ReCaptcha\Foo", so we need
			 * to replace the backslashes with frontslashes if we want the
			 * name to map directly to a location in the filesystem.
			 */
			$class = str_replace('\\', '/', $class);

			/* First, check under the current directory. It is important that
			 * we look here first, so that we don't waste time searching for
			 * test classes in the common case.
			 */
			$path = VQMod::getCwd() . 'captcha/' . $class.'.php';

			if (is_readable($path)) {
				require_once $path;
			}
		});
		
		$this->recaptha_object = new \ReCaptcha\ReCaptcha($this->settings->secret_code);
		
	}
	
	
	
	public function is_valid($code){
		return !$this->recaptha_object->verify($code)->getErrorCodes();
	}
	
	
	
	
}