<?php
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/connection.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;

use Connection\connection;

$con = new connection();
$conmysql = $con->connecttomysql();
$conoracle = $con->connecttooracle();

$lib = new library();
$func = new functions();
/*
$columnArr = ['corepermissionmenu','corepermissionsubmenu','coresectionsystem','coresubmenu','coreuser','coreuserlogin','csbankdisplay','gcannounce',
'gcbankconstant','gcbankconstantmapping','gcbindaccount','gcconstant','gcconstantaccountdept','gcconstantbackground','gcconstantchangeinfo','gcconstanttypeloan',
'gcconstantwelfare','gcdeptalias','gcdeviceblacklist','gcfavoritelist','gcformatreqwelfare','gchistory','gclinenotify','gcmemberaccount','gcmemodept',
'gcmenu','gcmenuconstantmapping','gcnews','gcotp','gcpalettecolor','gctaskevent','gctoken','gctransaction','gcuserallowacctransaction','gcuserlogin',
'logacceptannounce','logbindaccount','logbuyshare','logchangepassword','logdepttransbankerror','logeditadmincontrol','logeditmobileadmin','logerrorusageapplication',
'loglockaccount','logrepayloan','logreqloan','logtransferinsidecoop','logunbindaccount','loguseapplication','logwithdrawtransbankerror','reconcilewithdrawktb','smsconstantdept',
'smsconstantinsure','smsconstantloan','smsconstantperson','smsconstantshare','smsconstantsystem','smsconstantwelfare','smsgroupmember','smslogmailsend','smslogwassent',
'smsquery','smssendahead','smssystemtemplate','smstemplate','smstopicmatchtemplate','smstranwassent','smswasnotsent'];
foreach($columnArr as $table){
	$tableSeq = '';
	$getColumnDataType = $conmysql->prepare("SHOW COLUMNS FROM ".$table." FROM mobile_rfsc");
	$getColumnDataType->execute();
	while($rowColumn = $getColumnDataType->fetch(PDO::FETCH_ASSOC)){
		if($rowColumn["Extra"] == "auto_increment"){
			$tableSeq = $rowColumn["Field"].'_SEQ';
			$dropSeqtable = $conoracle->prepare("DROP SEQUENCE ".$tableSeq);
			$dropSeqtable->execute();
		}
	}
	$droptable = $conoracle->prepare("DROP TABLE ".$table);
	$droptable->execute();
	
}*/
//$columnArr = ['smstopicmatchtemplate'];

foreach($columnArr as $table){
	$i = 0;
	$bulkInsertArr = array();
	$getColumnDataType = $conmysql->prepare("SHOW COLUMNS FROM ".$table." FROM mobile_rfsc");
	$getColumnDataType->execute();
	$arrColumn = array();
	$pk = null;
	//$length = $getColumnDataType->rowCount();
	while($rowColumn = $getColumnDataType->fetch(PDO::FETCH_ASSOC)){
		if($rowColumn["Extra"] == "auto_increment"){
			$pk = $rowColumn["Field"];
		}
		$arrColumn[] = $rowColumn["Field"];
	}
	$getDataTable = $conmysql->prepare("SELECT * FROM ".$table);
	$getDataTable->execute();
	while($rowData = $getDataTable->fetch(PDO::FETCH_ASSOC)){
		$bulkInsert = "INTO ".$table."(".implode(',',$arrColumn).") VALUES(";
		foreach($arrColumn as $key => $column){
			if($key == 0){
				if(DateTime::createFromFormat('Y-m-d H:i:s', $rowData[$column]) !== false) {
					$bulkInsert .= "TO_DATE('".$rowData[$column]."','yyyy-mm-dd hh24:mi:ss')";
				}else{
					$bulkInsert .= "'".$rowData[$column]."'";
				}
			}else{
				if (DateTime::createFromFormat('Y-m-d H:i:s', $rowData[$column]) !== false) {
					$bulkInsert .= ",TO_DATE('".$rowData[$column]."','yyyy-mm-dd hh24:mi:ss')";
				}else{
					$bulkInsert .= ",'".$rowData[$column]."'";
				}
			}
		}
		$bulkInsert .= ")";
		$bulkInsertArr[] = $bulkInsert;
		$i++;
	}
	$insertToOracle = $conoracle->prepare("INSERT ALL ".implode(' ',$bulkInsertArr)." SELECT * FROM DUAL");
	$insertToOracle->execute();
	echo "INSERT ALL ".implode(' ',$bulkInsertArr)." SELECT * FROM DUAL";
}
?>