<?php

namespace Connection;

class connection {
	public $conmysql;
	public $conoracle;
	public $conmongo;
	
	public function connecttomysql() {
		$json = file_get_contents(__DIR__.'/../config/config_connection.json');
		$json_data = json_decode($json,true);
		$dbhost = $json_data["DBMOBILE_HOST"];
		$dbuser = $json_data["DBMOBILE_USERNAME"];
		$dbpass = $json_data["DBMOBILE_PASSWORD"];
		$dbname = $json_data["DBMOBILE_DATABASENAME"];
		try{
			$this->conmysql = new \PDO("mysql:dbname={$dbname};host={$dbhost}", $dbuser, $dbpass);
			$this->conmysql->exec("set names utf8mb4");
			return $this->conmysql;
		}catch(\Throwable $e){
			http_response_code(500);
			exit();
		}
	}
	public function connecttooracle() {
		$json = file_get_contents(__DIR__.'/../config/config_connection.json');
		$json_data = json_decode($json,true);
		try{
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
			$this->conoracle = new \PDO("oci:dbname=".$dbname.";charset=utf8", $dbuser, $dbpass);
			$this->conoracle->query("ALTER SESSION SET NLS_DATE_FORMAT = 'DD-MM-YYYY HH24:MI:SS'");
			$this->conoracle->query("ALTER SESSION SET NLS_DATE_LANGUAGE = 'AMERICAN'");
			return $this->conoracle;
		}catch(\Throwable $e){
			http_response_code(500);
			exit();
		}
	}
	public function connecttomongo() {
		$json = file_get_contents(__DIR__.'/../config/config_connection.json');
		$json_data = json_decode($json,true);
		$dbhost = $json_data["DBLOG_HOST"];
		$dbuser = $json_data["DBLOG_USERNAME"];
		$dbpass = $json_data["DBLOG_PASSWORD"];
		$dbname = $json_data["DBLOG_DATABASENAME"];
		$this->conmongo = new \MongoDB\Client("mongodb://{$dbhost}",[
			'username' => $dbuser,
			'password' => $dbpass,
			'authSource' => 'admin',
		]);
		return $this->conmongo->$dbname;
	}
}
?>