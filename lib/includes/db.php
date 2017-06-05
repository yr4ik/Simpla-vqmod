<?php


class db_vqinstaller extends vqInstaller {

	private $tables = array();
	private $db_object = null;

	public function __construct(){

		//get default database object
		$simpla = new Simpla();
		$this->db_object = $simpla->db;
		
		//not loging query
		$this->db_object->query("SHOW TABLES LIKE '__%' ");
		
		$prefix_len =  strlen($this->config->db_prefix);
		foreach($this->results() as $result)
			$this->tables[] = substr(reset($result), $prefix_len);

	}
	
	public function __call($method, $arguments){
		return call_user_func_array(array($this->db_object, $method), $arguments);
	}
	

	/* Make SQL query to database
	* query(string $sql);
	*
	* @param string $sql sql query
	* @return null
	*/
	public function query(){
		
		$args = func_get_args();
		$sql = trim(call_user_func_array(array($this->db_object, 'placehold'), $args));		
		$q = $this->db_object->query($sql);
		
		$len = strlen($sql);
		if($len >150){
			
			if($len>400)
				$sql = preg_replace("/\(([^\)]{7})([^\)]+)([^\)]{7}?)\)/", '($1...$3)', $sql);

			$sql_log = $sql . PHP_EOL;
			
		}else
			$sql_log = implode(' ', array_map('trim', explode("\n", $sql)));

		if($q){
			$this->installer->set_message('SQL QUERY: ' . $sql_log);
			$this->installer->add_counter('query');
		}else
			$this->installer->set_error('SQL QUERY ERROR: ' . $sql_log);
	}
	
	
	
	public function table_exists($table){
		return in_array($table, $this->tables);
	}
	
	
	public function get_table_keys($table, $filter=array(), $field=null){
		
		if(!$this->table_exists($table)){
			$this->installer->set_error("DB GET_TABLE_KEYS ERROR: TABLE {$table} NOT EXISTS");
			return false;
		}
		
		$key_filter = '';
		if(!empty($filter['key']))
			$key_filter = $this->db->placehold(' AND Key_name IN (?@)', (array) $filter['key']);		
		
		$unique_filter = '';
		if(isset($filter['unique']))
			$unique_filter = $this->db->placehold(' AND Non_unique = ?', $filter['unique'] ? 0:1);
				
		$column_filter = '';
		if(!empty($filter['column']))
			$column_filter = $this->db->placehold(' AND Column_name IN (?@)', (array) $filter['column']);

		
		$this->db_object->query("SHOW KEYS FROM __{$table} WHERE 1 {$key_filter} {$column_filter} {$unique_filter}");
		return $this->db_object->results($field);
	}
		
	public function get_field($table, $field){
		
		if(!$this->table_exists($table)){
			$this->installer->set_error("DB GET_FIELD ERROR: TABLE {$table} NOT EXISTS");
			return false;
		}
		
		$this->db_object->query("SHOW COLUMNS FROM __{$table} WHERE Field=?", $field);
		return $this->db_object->result();
	}
	
	
	public function field_exists($table, $field){
		
		if(!$this->table_exists($table)){
			$this->installer->set_error("DB EXISTS_FIELDS ERROR: TABLE  {$table} NOT EXISTS");
			return false;
		}
		
		$this->db_object->query("SHOW COLUMNS FROM __{$table}");
		$fields = $this->db_object->results('Field');
		return in_array($field, $fields);
	}	
	
	
	public function exists_fields($table, $fields){
		
		if(!$this->table_exists($table)){
			$this->installer->set_error("DB EXISTS_FIELDS ERROR: TABLE  {$table} NOT EXISTS");
			return false;
		}
		
		$this->db_object->query("SHOW COLUMNS FROM __{$table}");
		$exist_fields = $this->db_object->results('Field');
		return array_intersect($fields, $exist_fields);
	}	
	
	
	public function missing_fields($table, $fields){
		
		if(!$this->table_exists($table)){
			$this->installer->set_error("DB MISSING_FIELDS ERROR: TABLE  {$table} NOT EXISTS");
			return false;
		}
		
		return array_diff($fields, $this->exists_fields($table, $fields));
	}
	
	
}








