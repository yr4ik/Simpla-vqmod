<?PHP

require_once('api/Simpla.php');

class HelloAdmin extends Simpla
{
	function fetch()
	{	
	
		$helloworld = $this->helloworld->get_api();
		$this->design->assign('helloworld', $helloworld);
	
		return $this->design->fetch('hello.tpl');
	}
}
