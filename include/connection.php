<?php

namespace Connection;

class connection {
	public $conmysql;
	public $conoldmysql;
	public $conmssql;
	public $conmssqlcmt;

	public function connecttooldmysql() {
		$json = file_get_contents(__DIR__.'/../config/config_connection.json');
		$json_data = json_decode($json,true);
		$dbhost = $json_data["DBMOBILE_HOST_OLD"];
		$dbuser = $json_data["DBMOBILE_USERNAME_OLD"];
		$dbpass = $json_data["DBMOBILE_PASSWORD_OLD"];
		$dbname = $json_data["DBMOBILE_DATABASENAME_OLD"];
		try{
			$this->conoldmysql = new \PDO("mysql:dbname={$dbname};host={$dbhost}", $dbuser, $dbpass);
			$this->conoldmysql->exec("set names utf8mb4");
			return $this->conoldmysql;
		}catch(\Throwable $e){
			$arrayError = array();
			$arrayError["ERROR"] = $e->getMessage();
			$arrayError["RESULT"] = FALSE;
			$arrayError["MESSAGE"] = "Can't connect To MySQL Old Server";
			return $arrayError;
			http_response_code(200);
			exit();
		}
	}

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
			$arrayError = array();
			$arrayError["ERROR"] = $e->getMessage();
			$arrayError["RESULT"] = FALSE;
			$arrayError["MESSAGE"] = "Can't connect To MySQL";
			return $arrayError;
			http_response_code(200);
			exit();
		}
	}
	public function connecttosqlserver() {
		$json = file_get_contents(__DIR__.'/../config/config_connection.json');
		$json_data = json_decode($json,true);
		$dbhost = $json_data["DBSQLSVR_HOST"];
		$dbuser = $json_data["DBSQLSVR_USERNAME"];
		$dbpass = $json_data["DBSQLSVR_PASSWORD"];
		$dbname = $json_data["DBSQLSVR_DATABASENAME"];
		try{
			$this->conmssql = new \PDO("sqlsrv:server=".$dbhost." ; Database = ".$dbname, $dbuser, $dbpass);
			return $this->conmssql;
		}catch(\Throwable $e){
			$arrayError = array();
			$arrayError["ERROR"] = $e->getMessage();
			$arrayError["RESULT"] = FALSE;
			$arrayError["MESSAGE"] = "Can't connect To SQLServer";
			return $arrayError;
			http_response_code(200);
			exit();
		}
	}
	public function connecttosqlservercmt() {
		$json = file_get_contents(__DIR__.'/../config/config_connection.json');
		$json_data = json_decode($json,true);
		$dbhost = $json_data["DBSQLSVR_HOST_CMT"];
		$dbuser = $json_data["DBSQLSVR_USERNAME_CMT"];
		$dbpass = $json_data["DBSQLSVR_PASSWORD_CMT"];
		$dbname = $json_data["DBSQLSVR_DATABASENAME_CMT"];
		try{
			$this->conmssqlcmt = new \PDO("sqlsrv:server=".$dbhost." ; Database = ".$dbname, $dbuser, $dbpass);
			return $this->conmssqlcmt;
		}catch(\Throwable $e){
			$arrayError = array();
			$arrayError["ERROR"] = $e->getMessage();
			$arrayError["RESULT"] = FALSE;
			$arrayError["MESSAGE"] = "Can't connect To SQLServer";
			return $arrayError;
			http_response_code(200);
			exit();
		}
	}
}
?>