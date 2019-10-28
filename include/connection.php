<?php

namespace Connection;

class connection {
	public $conmysql;
	public $conoracle;
	public $conmongo;
	
	public function connecttomysql($is_test=false) {
		$json = file_get_contents(__DIR__.'/../json/config_connection.json');
		$json_data = json_decode($json,true);
		$dbhost = $json_data["DBMOBILE_HOST"];
		$dbuser = $json_data["DBMOBILE_USERNAME"];
		$dbpass = $json_data["DBMOBILE_PASSWORD"];
		if($is_test){
			$dbname = $json_data["DBMOBILE_DATABASENAME_TEST"];
		}else{
			$dbname = $json_data["DBMOBILE_DATABASENAME"];
		}
		try{
			$this->conmysql = new \PDO("mysql:dbname={$dbname};host={$dbhost}", $dbuser, $dbpass);
			$this->conmysql->exec("set names utf8mb4");
			return $this->conmysql;
		}catch(PDOException $e){
			http_response_code(203);
			exit();
		}
	}
	public function connecttooracle($is_test=false) {
		$json = file_get_contents(__DIR__.'/../json/config_connection.json');
		$json_data = json_decode($json,true);
		if($is_test){
			$dbuser = $json_data["DBORACLE_USERNAME_TEST"];
			$dbpass = $json_data["DBORACLE_PASSWORD_TEST"];
			$dbname = "(DESCRIPTION =
					(ADDRESS_LIST =
					  (ADDRESS = (PROTOCOL = TCP)(HOST = ".$json_data["DBORACLE_HOST_TEST"].")(PORT = 1521))
					)
					(CONNECT_DATA =
					  (SERVICE_NAME = ".$json_data["DBORACLE_SERVICE_TEST"].")
					)
				  )";
		}else{
			$dbuser = $json_data["DBORACLE_USERNAME"];
			$dbpass = $json_data["DBORACLE_PASSWORD"];
			$dbname = "(DESCRIPTION =
					(ADDRESS_LIST =
					  (ADDRESS = (PROTOCOL = TCP)(HOST = ".$json_data["DBORACLE_HOST"].")(PORT = 1521))
					)
					(CONNECT_DATA =
					  (SERVICE_NAME = ".$json_data["DBORACLE_SERVICE"].")
					)
				  )";
		}
		try{
			$this->conoracle = new \PDO("oci:dbname=".$dbname.";charset=utf8", $dbuser, $dbpass);
			$this->conoracle->query("ALTER SESSION SET NLS_DATE_FORMAT = 'DD-MM-YYYY HH24:MI:SS'");
			$this->conoracle->query("ALTER SESSION SET NLS_DATE_LANGUAGE = 'AMERICAN'");
			return $this->conoracle;
		}catch(PDOException $e){
			http_response_code(203);
			exit();
		}
	}
	public function connecttomongo($is_test=false) {
		$json = file_get_contents(__DIR__.'/../json/config_connection.json');
		$json_data = json_decode($json,true);
		$dbhost = $json_data["DBLOG_HOST"];
		$dbuser = $json_data["DBLOG_USERNAME"];
		$dbpass = $json_data["DBLOG_PASSWORD"];
		if($is_test){
			$dbname = $json_data["DBLOG_DATABASENAME_TEST"];
		}else{
			$dbname = $json_data["DBLOG_DATABASENAME"];
		}
		$this->conmongo = new \MongoDB\Client("mongodb://{$dbhost}");
		return $this->conmongo->$dbname;
	}
}
?>