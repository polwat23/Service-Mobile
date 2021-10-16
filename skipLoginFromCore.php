<?php
try{
	$dbIncome = $dataComing["connection_string"];
	$dbArr = explode('@',$dbIncome);
	$dbArrInstant = explode('_',$dbArr[1]);
	$dbuser = $dbArr[0];
	$dbpass = $dbArr[0];
	$dbname = "(DESCRIPTION =
				(ADDRESS_LIST =
				  (ADDRESS = (PROTOCOL = TCP)(HOST = ".$dbArrInstant[0].")(PORT = 1521))
				)
				(CONNECT_DATA =
				  (SERVICE_NAME = ".$dbArrInstant[1].")
				)
			  )";
	$conoracle = new PDO("oci:dbname=".$dbname.";charset=utf8", $dbuser, $dbpass);
	$conoracle->query("ALTER SESSION SET NLS_DATE_FORMAT = 'DD-MM-YYYY HH24:MI:SS'");
	$conoracle->query("ALTER SESSION SET NLS_DATE_LANGUAGE = 'AMERICAN'");
}catch(Throwable $e){
	$arrayError = array();
	$arrayError["ERROR"] = $e->getMessage();
	$arrayError["RESULT"] = FALSE;
	$arrayError["MESSAGE"] = "Can't connect To Oracle";
	echo json_encode($arrayError);
	http_response_code(200);
	exit();
}
?>