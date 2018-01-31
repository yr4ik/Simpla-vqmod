<?php


class installer_vqinstaller extends vqInstaller {

	

	private $xml_actions = array(
		'SQL' => array('xml_sql_action'),
		'COPYDIR' => array('xml_copy_action', 'src'=>'', 'dest'=>'', 'replace'=>'false'),
		'COPYFILE' => array('xml_copy_action', 'src'=>'', 'dest'=>'', 'replace'=>'false'),
		'CREATE' => array('xml_create_action', 'dir'=>'', 'file'=>'', 'mode'=>'0'),
		'DELETE' => array('xml_delete_action', 'dir'=>'', 'file'=>''),
		'PATCH' => array('xml_patch_action', 'file'=>''),
		'RENAME' => array('xml_rename_action', 'src'=>'', 'dest'=>''),
		'CHMOD' => array('xml_chmod_action', 'file'=>'', 'dir'=>'', 'mode'=>'0')
	);


	protected $counters = array(
		'query' => 0,
		'copied' => 0,
		'deleted_file' => 0,
		'deleted_dir' => 0,
		'create_file' => 0,
		'create_dir' => 0,
		'changes' => 0,
		'renamed' => 0,
		'writes' => 0,
		'permission' => 0
	);	
	
	
	protected $errors = array();
	protected $messages = array();
	private $_index_event = 0;
	
	
	protected $shortcuts = array(
		//[CFG:config_value],
		//[CONST:constant_name],
		//[MOD],
		//[ADMIN],
	);
	
	protected $disabled_delete_dirs = array(
		'', // root
		'ajax',
		'config',
		'payment',
		'vqmod',
		'vqmod/xml',
		'vqmod/mod',
		'vqmod/lib',
	);
	
	public function __construct(){
		
		$this->disabled_delete_dirs[] = SIMPLA_API_DIR;
		$this->disabled_delete_dirs[] = SIMPLA_DESIGN_DIR;
		$this->disabled_delete_dirs[] = SIMPLA_FILES_DIR;
		$this->disabled_delete_dirs[] = SIMPLA_VIEW_DIR;
		$this->disabled_delete_dirs[] = SIMPLA_ADMIN_DIR;
		$this->disabled_delete_dirs[] = SIMPLA_ADMIN_DIR.'/ajax';
		$this->disabled_delete_dirs[] = SIMPLA_ADMIN_DIR.'/design';

		$this->shortcuts['[MOD]'] = substr($this->mod->directory, 0, -1);
		$this->shortcuts['[ADMIN]'] =  ROOT_DIR . SIMPLA_ADMIN_DIR;
		
	}
	

	/* Execute xml manifest
	* exec(string $manifest, string $action_name);
	*
	* @param string $manifest name of xml file in MOD directory
	* @param string $action_name name of execute action
	* @return null
	*/
	public function exec($manifest, $action_name){
		
		$manifest_path = $this->mod->directory . $manifest;
	
		if(!file_exists($manifest_path))
			return $this->set_error('EXEC: FILE NOT FOUND: ' . $manifest_path);

		
		set_error_handler(array($this, 'handleXMLError'));
			
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->preserveWhiteSpace = false;
		
		$dom->load($manifest_path);
		$vqinstaller = $dom->getElementsByTagName('vqinstaller')->item(0);
		
		if(!$vqinstaller)
			$this->set_error('EXEC: FILE "' . $manifest . '" WAS WRONG');
		elseif(!($action_node = $vqinstaller->getElementsByTagName($action_name)->item(0)))
			$this->set_error('EXEC: FILE "' . $manifest . '" NOT HAVE ' . $action_name . ' ACTION');
		else{

			if($action_node->hasChildNodes()) {
				
				$this->set_message('EXEC: "' . $manifest . '" RUN <'.$action_name.'> ACTION');
				
				foreach($action_node->childNodes as $action) {
					
					$nodeName = strtoupper((string) $action->nodeName);
					
					if(!isset($this->xml_actions[$nodeName])){
						if($nodeName != '#COMMENT')
							$this->set_error('EXEC: UNSUPPORTED ACTION <' . $nodeName . '>');
						continue;
					}
					
					$attributes = $this->xml_actions[$nodeName];
					$xml_callback = array_shift($attributes);

					foreach($attributes as $attr_name=>$attr_default){
						$attr_value = (string) $action->getAttribute($attr_name);
						$attributes[$attr_name] = ($attr_value ? $attr_value:$attr_default);
					}
					
					$this->{$xml_callback}($action, (object) $attributes, (string) $action->nodeValue);
				}
	
				$counters_name = array(
					'query' => 'sql queries',
					'copied' => 'files copied',
					'deleted_file' => 'files deleted',
					'deleted_dir' => 'folders deleted',
					'create_dir' => 'new folders',
					'create_file' => 'new files',
					'changes' => 'files edited',
					'renamed' => 'renamed',
					'writes' => 'writes counted',
					'permission' => 'change permissions',
				);

				$counters = array();
				foreach($this->get_counter() as $name=>$value)
					if($value) $counters[] = $counters_name[$name].':'.$value;

				$this->set_message('EXEC END: ' . implode(', ', $counters));
			}
		}
		
		restore_error_handler();
	}
	
	

	/* Delete file
	* delete_file(string $path);
	*
	* @param string $path string path to file
	* @return bool
	*/
	public function delete_file($path){
		
		$path = $this->prepare_path($path);

		return $this->_delete_file($path);
	}
	
	/* Delete folder with files
	* delete_dir(string $path);
	*
	* @param string $path string path to directory
	* @return bool
	*/
	public function delete_dir($path){
		
		$path = $this->prepare_path($path);
		
		return $this->_delete_dir($path);
	} 
	

	/* Create directory
	* new_dir(string $dir, int $mode = 0755);
	*
	* @param string $dir string  path to create dir
	* @param int|string $mode int:8 access permissions to dir
	* @return bool
	* @description mode is 0755 by default, which means the widest possible access. For more information on modes, read the details on the function chmod()
	*/
	public function new_dir($dir, $mode = '0755'){

		$dir = $this->prepare_path($dir);
		return $this->_new_dir($dir, $mode);
	}
	
	
	
	/* Copy file
	* copy_file(string $source, string $dest, bool $replace_file=false);
	*
	* @param string $source string full path to file
	* @param string $dest string path to file
	* @param string $replace_file bool if true existing file will be replaced
	* @return bool
	*/
	public function copy_file($source, $dest, $replace_file=false){
		
		$dest = $this->prepare_path($dest);
		$source = $this->prepare_path($source);

		return $this->_copy_file($source, $dest, $replace_file);
	}
		
		
		
	/* Copy files and dirs
	* resource_copy(string $source, string $dest, bool $replace_file=false);
	*
	* @param string $source string full path to file or dir
	* @param string $dest string path to file or dir
	* @param string $replace_file bool if true existing file will be replaced
	* @return bool
	*/
	public function resource_copy($source, $dest, $replace_file=false){
		
		$dest = $this->prepare_path($dest);
		$source = $this->prepare_path($source);
		
		return $this->_recurse_copy($source, $dest, $replace_file);
	}
	
	
	/* Rename file or dir
	* rename(string $source, string $dest);
	*
	* @param string $source string full path to file or dir
	* @param string $dest string path to file or dir
	* @return bool
	*/
	public function rename($source, $dest){
		
		$dest = $this->prepare_path($dest);
		$source = $this->prepare_path($source);
	
		return $this->_rename($source, $dest);
	}
	
	
	/* Apply patch to file
	* apply_patch(string $patch_file, array $patches){
	*
	* @param string $patch_file string of path to file
	* @param array $patches associative array (string regexp => string replace)
	* @return null
	*/
	public function apply_patch($patch_file, $patches){
	
		$patch_file = $this->prepare_path($patch_file);
		return $this->_apply_patch($patch_file, $patches);

	}
	
	
	/* Write content to file
	* write_file(string $file, string $content, string $append=false);
	*
	* @param string $file string path to the file
	* @param string $content the data to write
	* @param bool $append if true  $content will be appended
	* @return bool
	* @description if path not exist - will be created
	*/
	public function write_file($file, $content, $append=false){
		
		$file = $this->prepare_path($file);
		
		$this->_new_dir(dirname($file));
		
		$result = file_put_contents(ROOT_DIR . $file, $content, ($append ? FILE_APPEND | LOCK_EX : LOCK_EX));
		
		if(!$result)
			$this->set_error('WRITE FILE: FILE "/' . $file . '" COULD NOT BE WRITTEN');
		
		return $result;
	}
		
	
	/* Get counters value
	* get_counter(string $counter=null);
	*
	* @param string $counter string of (query|copied|deleted_file|deleted_dir|create_file|create_dir|changes|writes)
	* @return object, int
	*/
	public function get_counter($counter=null){
		if(is_null($counter))
			return (object) $this->counters;

		return (isset($this->counters[$counter]) ? $this->counters[$counter]:false);
	}
	
	/* Set error to log file and installer 
	* set_error(string $error_str);
	*
	* @param string $error_str string of error
	* @return null
	*/
	public function set_error($error_str) {
		$this->errors[$this->_index_event] = $error_str;
		$this->mod->log($error_str);
		$this->_index_event += 1;
	}	
	
	/* Set message to log file and installer 
	* set_message(string $message_str);
	*
	* @param string $message_str string of message
	* @return null
	*/
	public function set_message($message_str) {
		$this->messages[$this->_index_event] = $message_str;
		$this->mod->log($message_str);
		$this->_index_event += 1;
	}	
	
	/* Add value to counter
	* add_counter(string $counter, (int) $added=1)
	*
	* @param string $counter string counter name
	* @param int value of count
	* @return null
	*/
	public function add_counter($counter, $added=1) {
		$this->counters[$counter] += $added;
	}
	
	
	/* Get results messages
	* get_results(string $log_type=null);
	*
	* @param string $log_type string of type (errors|messages)
	* @return array
	* @description if $log_type is not set - returns all
	*/
	public function get_results($log_type=null) {

		switch($log_type){
			case 'errors':
				$result = $this->errors;
				break;			
			case 'messages':
				$result = $this->messages;
				break;
			default:
				$result = $this->messages + $this->errors;
				ksort($result);
				break;
		}
		
		return $result; 
	}	
	
	/* Get results messages
	* is_result_error(int $id);
	*
	* @param int $id event id
	* @return bool true if event is error
	*/
	public function is_result_error($id) {
		return isset($this->errors[$id]);
	}
	
	
	/*********************************
		******	XML ACTIONS SECTION ******
	/*********************************/
	
	/* 
	* Make query to DB
	* content: sql
	*/
	private function xml_sql_action($action, $attributes, $content){
		$this->db->query($content);
	}	
	
	
	/* 
	* Copy file or dir
	* attributes:
	*		src - path to the source file or directory
	* 		dest - the destination path
	*		replace - set true for replace exists file
	*/
	private function xml_copy_action($action, $attributes, $content){
		
		$nodeName = strtoupper((string) $action->nodeName);
		$attributes->src = $this->prepare_path($attributes->src);
		
		if(!$attributes->src)
			$this->set_error('EXEC ' . $nodeName . ': EMPTY ATTRIBUTE SRC');
		elseif($nodeName=='COPYDIR' && !is_dir(ROOT_DIR . $attributes->src))
			$this->set_error('EXEC COPYDIR: "/' . $attributes->src . '" IS NOT DIRECTORY');							
		elseif($nodeName=='COPYFILE' && !is_file(ROOT_DIR . $attributes->src))
			$this->set_error('EXEC COPYFILE: "/' . $attributes->src . '" IS NOT FILE');
		else{
			
			$replace = $this->is_true($attributes->replace);
			$attributes->dest = $this->prepare_path($attributes->dest);

			if($nodeName=='COPYFILE' && !$attributes->dest)
				$this->set_error('EXEC COPYFILE: DEST ATTRIBUTE IS EMPTY');
			else
				$this->_recurse_copy($attributes->src, $attributes->dest, $replace);
			
		}
		
	}		
	
	
	
	/* 
	* Create file or dir
	* attributes:
	*		dir - path to new dir
	* 		file - path to new file
	*		mode - permissions to dir/file
	*/
	private function xml_create_action($action, $attributes, $content){
	
		if($attributes->dir){

			$this->new_dir($attributes->dir, $attributes->mode);
			
		}elseif($attributes->file){
			
			$attributes->file = $this->prepare_path($attributes->file);
			
			if(!file_exists(ROOT_DIR . $attributes->file)){
				
				if($this->write_file($attributes->file, $content)){
					
					if($mode = octdec($attributes->mode))
						$this->_chmod($attributes->file, $mode);
					
					$this->add_counter('create_file');
					$this->set_message('EXEC CREATE: FILE "/' . $attributes->file . '" WAS CREATED');

				}else
					$this->set_error('EXEC CREATE: FILE "/' . $attributes->file . '" CAN\'T WRITE');

			}else
				$this->set_error('EXEC CREATE: FILE "/' . $attributes->file . '" ALREADY EXISTS');

		}else
			$this->set_error('EXEC CREATE: EMPTY DIR AND FILE ATTRIBUTE');

		
	}
	

	/* 
	* Delete file or dir
	* attributes:
	*		dir - path to directory for delete
	* 		file - path to file for delete
	*/
	private function xml_delete_action($action, $attributes, $content){

		if($attributes->dir){
			
			$this->delete_dir($attributes->dir);
			
		}elseif($attributes->file){
			
			$this->delete_file($attributes->file);
			
		}else
			$this->set_error('EXEC DELETE: EMPTY DIR AND FILE ATTRIBUTE');

	}	
	
	
	/* 
	* Apply patch to file
	* attributes:
	* 		file - path to file for patching
	* sub properties:
	* 		<search> regexp string for search
	* 		<replace> string for replace
	*/
	private function xml_patch_action($action, $attributes, $content){

		$attributes->file = $this->prepare_path($attributes->file);
	
		$search = $this->replace_shortcuts((string) $action->getElementsByTagName('search')->item(0)->nodeValue);
		$replace = $this->replace_shortcuts((string) $action->getElementsByTagName('replace')->item(0)->nodeValue);
		
		if(!$attributes->file)
			$this->set_error('EXEC PATCH: EMPTY ATTRIBUTE FILE');
		elseif(!$search)
			$this->set_error('EXEC PATCH: EMPTY SEARCH CONTENT');
		else{
			
			$patches = array($search=>$replace);
			$this->_apply_patch($attributes->file, $patches);
			
		}

	}
	
	/* 
	* Change permissions for dir or file
	* attributes:
	*		dir - path to directory for delete
	* 		file - path to file for delete
	*		mode - permissions to dir/file
	*/
	private function xml_rename_action($action, $attributes, $content){	
	
		$attributes->src = $this->prepare_path($attributes->src);
		$attributes->dest = $this->prepare_path($attributes->dest);
		
		if(!$attributes->src)
			$this->set_error('EXEC RENAME: EMPTY ATTRIBUTE SRC');		
		elseif(!$attributes->src)
			$this->set_error('EXEC RENAME: EMPTY ATTRIBUTE DEST');
		else
			$this->_rename($attributes->src, $attributes->dest);
	}
	
	
	/* 
	* Change permissions for dir or file
	* attributes:
	*		dir - path to directory for delete
	* 		file - path to file for delete
	*		mode - permissions to dir/file
	*/
	private function xml_chmod_action($action, $attributes, $content){
		
		if($attributes->file)
			$chmod_path = $attributes->file;
		elseif($attributes->file)
			$chmod_path = $attributes->dir;
			
		$mode = octdec($attributes->mode);
			
		if(!isset($chmod_path))
			$this->set_error('EXEC CHMOD: EMPTY ATTRIBUTE FILE AND DIR');
		elseif(!$mode)
			$this->set_error('EXEC CHMOD: EMPTY ATTRIBUTE MODE');
		else
			$this->_chmod($this->prepare_path($chmod_path), $mode);
		
	}
	
	
	/*********************************
		*******	PROTECTED SECTION *******
	/*********************************/


	
	protected function prepare_path($path) {
		$path = $this->replace_shortcuts($path);
		return trim($this->clean_root_path($path), '/ ');
	}
	

	protected function clean_root_path($path) {
		return str_replace(array('\\', '//', ROOT_DIR), '/', $path);
	}	
	
	
	protected function replace_shortcuts($string) {
		$string = strtr($string, $this->shortcuts);
		return preg_replace_callback('~\[(CFG|CONST):(\w+)(?:\:(regex|html))?\]~', array($this, 'replace_shortcuts_callback'), $string);
	}
	
	
	protected function is_true($value) {	
		return filter_var($value, FILTER_VALIDATE_BOOLEAN);
	}
	

	protected function IsOcta($string){
		return decoct(octdec($x)) == $x;
	} 
	
	/*********************************
		******** PRIVATE SECTION ********
	/*********************************/

	
	private function _new_dir($dir, $mode = '0755'){
		
		$created = true;
		
		if (!is_dir(ROOT_DIR . $dir)){
			
			if(!($mode = octdec($mode))) $mode = 0755;
			$created = mkdir(ROOT_DIR . $dir, $mode, true);	

			if($created){
				$this->add_counter('create_dir');
				$this->set_message('NEW FOLDER: "/' . $dir . '" [0' . decoct($mode) . ']');
			}else
				$this->set_error('CAN\'T CREATE DIRECTORY "/' . $dir . '"[0' . decoct($mode) . ']');
			
		}

		return $created;
	}
	
	
	
	private function _delete_file($path, $loging=true){
		
		$delete = !is_file(ROOT_DIR . $path);
		if(!$delete){
			$delete = unlink(ROOT_DIR . $path);
			
			if($delete){
				$this->add_counter('deleted_file');
				
				if($loging==true) 
					$this->set_message('DELETE FILE: "/' . $path . '"');
			}else{
				$this->set_error('CAN\'T DELETE FILE: "/' . $path . '"');
			}
		}
		
		return $delete;
	}
	
	private function _delete_dir($path){
		
		$full_path = ROOT_DIR . $path;
		
		$dir_deleted = !is_dir($full_path);
		if(!$dir_deleted)
		{
			if(!in_array(strtolower($path), $this->disabled_delete_dirs))
			{
				if(!$dh = @opendir($full_path)) 
					return false; 
				
				while (false !== ($obj = readdir($dh)))
				{ 
					if($obj == '.' || $obj == '..') 
						continue; 
			
					if (is_dir($full_path . '/' . $obj)) 
						$this->_delete_dir($path . '/' . $obj); 
					else
						$this->_delete_file($path . '/' . $obj, false);
				} 
				closedir($dh);
				
				$dir_deleted = @rmdir($full_path);

				if($dir_deleted)
				{
					$this->add_counter('deleted_dir');
					$this->set_message('DELETE DIR: "/' . $path . '"');
				}else
					$this->set_error('DELETE DIR: CAN\'T REMOVE "/' . $path . '"');
			}else
				$this->set_error('DELETE DIR: FORBIDDEN DIRECTORY "/' . $path . '"');
		}
		
		return $dir_deleted;
	} 
	
	
	private function _copy_file($source, $dest, $replace_file=false){
		
		$exist = file_exists(ROOT_DIR . $dest);
		
		if($exist){
			
			if($replace_file==true)
				$exist = !unlink(ROOT_DIR . $dest);

		}else
			$this->_new_dir(dirname($dest));
		
		if($exist){
			$this->set_error('COPY: SOURCE FILE "/' . $dest . '" WAS EXISTS');
			return false;
		}

		$copied = copy(ROOT_DIR . $source, ROOT_DIR . $dest);

		if($copied){
			$this->add_counter('copied');
			$this->set_message('COPY: "/' . $source . '" TO "/' . $dest . '" ');
		}else
			$this->set_error('COPY: CAN\'T COPY "/' . $source . '" TO "/' . $dest . '"');
		
		return $copied;
	}
	
	
	
	
	private function _recurse_copy($source, $dest, $replace_file=false)
	{
		if(is_file(ROOT_DIR . $source))
		{
			return $this->_copy_file($source, $dest, $replace_file);
		}
		elseif(is_dir(ROOT_DIR . $source))
		{
			$this->_new_dir($dest);
		
			$dir = dir(ROOT_DIR . $source);
			
			if($dest) $dest .= '/';
			
			while (false !== $entry = $dir->read())
			{
				if ($entry == '.' || $entry == '..')
					continue;

				$this->_recurse_copy($source . '/' . $entry, $dest . $entry, $replace_file);
			}

			$dir->close();
			
			return true;
		}else
			$this->set_error('UNKNOWN RESOURCE: "/' . $source . '"');
		
		return false;
	}
	
	
	private function _rename($source, $dest)
	{
		$result = false;
		
		if(!$source)
		{
			$this->set_error('RENAME: CAN\'T RENAME ROOT DIRECTORY');
		}
		elseif(!file_exists(ROOT_DIR . $source))
		{
			$this->set_error('RENAME: SOURCE "/' . $source . '" IS NOT EXIST');
		}
		elseif(file_exists(ROOT_DIR . $dest))
		{
			$this->set_error('RENAME: DEST "/' . $dest . '" WAS EXIST');
		}
		else
		{
			$result = @rename(ROOT_DIR . $source, ROOT_DIR . $dest);
			if($result)
			{
				$this->add_counter('renamed');
				$this->set_message('RENAME: "/' . $source . '" WAS  RENAMED TO "/' . $dest . '"');
			}else
				$this->set_error('RENAME: CAN\'T RENAME "/' . $source . '" TO "/' . $dest . '"');
		}
		
		return $result;
	}
	
	private function _apply_patch($patch_file, $patches)
	{
		$result = false;
		if(file_exists(ROOT_DIR . $patch_file))
		{
			$this->ugrsr->addFile($patch_file);
			
			foreach($patches as $regex => $replace)
				$this->ugrsr->addPattern($regex, $replace);

			$result = (object) $this->ugrsr->run();

			if(!$result->writes)
			{
				$this->set_error('PATCH: CAN\'T WRITE "/' . $patch_file . '"');
			}
			elseif(!$result->changes)
			{
				$this->set_error('PATCH: CAN\'T WRITE CHANGES TO "/' . $patch_file . '"');
			}
			else
			{
				
				$this->add_counter('writes', $result->writes);
				$this->add_counter('changes', $result->changes);
				
				$this->set_message('PATCH: WAS WRITE ' . $result->changes . ' CHANGES TO "/' . $patch_file . '"');
			}
				
			$this->ugrsr->clearPatterns();
			$this->ugrsr->resetFileList();
		}else
			$this->set_error('PATCH: FILE "' . $patch_file . '" IS NOT EXIST');
		
		return $result;
	}
	
	
	private function _chmod($path, $mode)
	{
		$result = false;
		if(file_exists(ROOT_DIR . $path))
		{
			$result = @chmod(ROOT_DIR . $path, $mode);
			if($result)
			{
				$this->add_counter('permission');
				$this->set_message('CHMOD: "/' . $path . '" SET MODE [0' . decoct($mode) . '] ');
			}else
				$this->set_error('CHMOD: CAN\'T SET MODE [0' . decoct($mode) . '] FOR  "/' . $path . '"');
		}else
			$this->set_error('CHMOD: CAN\'T FIND "/' . $path . '" FOR SET MODE [0' . decoct($mode) . ']');

		return $result;
	}
	
	/*********************************
		******** CALLBACK SECTION ********
	/*********************************/	
	
		
	public function replace_shortcuts_callback($matches) {

		list(, $shortcut, $value, $modifier) = $matches;
		
		switch($shortcut){
			case 'CONST':
				if(defined($value))
					$value = constant($value);

				break;
			case 'CFG':
				if($this->config->{$value})
					$value = $this->config->{$value};
				break;
		}
		
		switch($modifier){
			case 'regex':
				$value = preg_quote($value);
				break;
			case 'html':
				$value = htmlentities($value);
				break;
		}

		return $value;
	}
		
	public function handleXMLError($errno, $errstr, $errfile, $errline) {
		if ($errno == E_WARNING && (substr_count($errstr, 'DOMDocument::load()') > 0)) {
			throw new DOMException(str_replace('DOMDocument::load()', '', $errstr), $errno);
		} else {
			return false;
		}
	}

	
}











