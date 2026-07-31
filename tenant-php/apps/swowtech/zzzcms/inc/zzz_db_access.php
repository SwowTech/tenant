<?php
class db_pdo_access{	
	public $conf = array(); // 配置，可以支持主从
	public $showsql = '';
	public $wlink = NULL;  // 写连接
	public $link = NULL;   // 最后一次使用的连接
	public $errno = 0;
	public $errstr = '';
	public $sqls = array();
	public $innodb_first = TRUE;// 优先 InnoDB
	
	public function __construct($conf) {
		$this->conf = $conf;
		$this->showsql  = $conf['showsql'];
	}
	
	// 根据配置文件连接
	public function connect() {
		$this->wlink = $this->connect_master();
		return $this->wlink;
	}
		
	// 连接写服务器
	public function connect_master() {
		if($this->wlink) return $this->wlink;
		$conf = $this->conf;
		$this->wlink = $this->real_connect($conf['accesspath'], $conf['accessname']);
		return $this->wlink;
	}
	public function real_connect($dbpath, $dbname) {
		$accessdb = SITE_DIR. $dbpath. $dbname;
		try {			
		 	$link = new PDO("odbc:driver={Microsoft Access Driver(*.mdb, *.accdb)};dbq=".realpath($accessdb )) or die ("PDO Connection faild.");
		} catch (Exception $e) {
			// 传递完整的错误信息，让error方法处理调用位置
			$error_msg = '连接数据库服务器失败:'.togbk($e->getMessage());
			$this->error($e->getCode(), $error_msg);
			return FALSE;
	        }		
		return $link;
	}
		
	public function sql_find_one($sql) {
		$query = $this->query($sql);	
		if(!$query) return $query;
		$query->setFetchMode(PDO::FETCH_ASSOC);
		return togbk($query->fetch());
	}
	
	public function sql_find($sql, $key = NULL) {
		$query = $this->query($sql);
		if(!$query) return $query;
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$arrlist = togbk($query->fetchAll());
		$key AND $arrlist = arrlist_change_key($arrlist, $key);
		return $arrlist;
	}
	public function sql_query($sql) {
		$query = $this->query($sql);
		$query->setFetchMode(PDO::FETCH_NUM);
		$arrlist = togbk($query->fetchAll());
		return $arrlist;
	}
	public function find($table, $where = array(), $orderby = array(), $page = 1, $pagesize = 10, $key = '', $col = array()) {
		$page = max(1, $page);
		$table= db_tableby_to_sqladd($table);
		$where = db_cond_to_sqladd($where);
		$orderby = db_orderby_to_sqladd($orderby);
		$limit='';
		//$offset = ($page - 1) * $pagesize;
		$size = $pagesize>0 ? 'top '.$pagesize : '';
		if (empty($col)){
			$cols='*';
		}else if(is_array($col)){
			$cols= implode(',', $col);
		}else{
			$cols = $col;
		}	
		if ($page>1){
			$tableid=table_id($table);
			!empty($where) ?: $where=' where 1=1 ';
			$where .='and ('.$tableid.' not in  (SELECT TOP  '.($page - 1) * $pagesize.' '.$tableid.'  FROM '. $table.' '.$where.$orderby.'))';	
		}
		//echop ("SELECT $size $cols FROM $table $where$orderby");
		return $this->sql_find("SELECT $size $cols FROM $table $where $orderby", $key);
		
	}
		
	public function find_one($table, $where = array(), $orderby = array(), $col = array()) {
		$table= db_tableby_to_sqladd($table);
		$where = db_cond_to_sqladd($where);
		$orderby = db_orderby_to_sqladd($orderby);
		if (empty($col)){
			$cols='*';
		}else if(is_array($col)){
			$cols= implode(',', $col);
		}else{
			$cols = $col;
		}		
		//echop ("SELECT $cols FROM $table $where$orderby");
		return $this->sql_find_one("SELECT $cols FROM $table $where$orderby");
	}
	
	
	public function query($sql) {
		if(!$this->wlink) return FALSE;
		$link = $this->link = $this->wlink;
		$query = $link->query($sql);
		if($query === FALSE) {
			// 优化错误信息，添加SQL语句
			$error_msg = '数据库查询失败: '.$sql;
			$this->error($query , $error_msg);
		 	$this->sqls[] = $sql;
		}
		if($this->showsql) echop($sql,1);
		return $query;
	}
	
	public function exec($sql) {
		if(!$this->wlink) return FALSE;
		$link = $this->link = $this->wlink;		
		try {
			$n = $link->exec($sql); // 返回受到影响的行，插入的 id ?
		} catch (Exception $e) {  
			// 优化错误信息，包含异常消息和SQL语句
			$error_msg = '数据库执行失败: '.$e->getMessage().' [SQL: '.$sql.']';
			$this->error($e->getCode(), $error_msg);
			return FALSE;
	    }
		if($this->showsql) echop($sql,1);
		return $n;
	}
	
	// innoDB 通过 information_schema 读取大致的行数
	// SELECT TABLE_ROWS FROM information_schema.tables WHERE TABLE_SCHEMA = '$table' AND TABLE_NAME = '$table';
	// SELECT TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '$table';
	public function count($table, $where = array()) {		
		$table= db_tableby_to_sqladd($table);
		$where = db_cond_to_sqladd($where);
		$sql = "SELECT COUNT(*) AS num FROM `$table` $where";
		$arr = $this->sql_find_one($sql);
		return !empty($arr) ? intval($arr['num']) : $arr;
	}
	
	public function maxid($table, $field, $where) {
		$table= db_tableby_to_sqladd($table);
		$where = db_cond_to_sqladd($where);
		$sql = "SELECT MAX($field) AS maxid FROM `$table` $where";
		$arr = $this->sql_find_one($sql);
		return !empty($arr) ? intval($arr['maxid']) : $arr;
	}
	
	public function istable($table) {
		$table= db_tableby_to_sqladd($table);
		$arr = $this->sql_find_one("SELECT COUNT(*) FROM `$table`");
		 return  $arr === FALSE ? 0 : 1;	
	}
	
	public function truncate($table) {
		$table= db_tableby_to_sqladd($table);
		return $this->exec("TRUNCATE $table");
	}
	
	public function version() {
		$r = $this->sql_find_one("SELECT VERSION() AS v");
		return $r['v'];
	}
	
	// 设置错误。
	public function error($errno = 0, $errstr = '',$sql='') {
		// 获取调用栈信息，找到调用error方法的位置
		$trace = debug_backtrace();
		$caller_info = '';
		if (isset($trace[1])) {
			$caller = $trace[1];
			$caller_info = ' [调用位置: ' . basename($caller['file']) . ' 第' . $caller['line'] . '行]';
		}
		
		$this->errno = $errno ? $errno : (isset($error[1]) ? $error[1] : 0);
		$this->errstr = $errstr ? ($errstr . $caller_info) : (isset($error[2]) ? $error[2] : '');
		DEBUG AND trigger_error('Database Error:'.$this->errstr.$sql);
	}
	public function close() {
		$this->wlink = NULL;
		return TRUE;
	}
	
	public function __destruct() {
		if($this->wlink) $this->wlink = NULL;
	}
	public function getsubsort($sid){
		if(stripos($sid,',') === false) {
		  $data=$this->sql_find("select sid from ".DB_PRE."sort where s_onoff=1 and ','+s_path+',' like '%,$sid,%'");
		  $r= array();
		  foreach ($data as $value){
			array_push($r,$value['sid']);
		  }	
		}else{
			$r=explode(",",$sid);
		}
		return $r;
	}
}
?>