<?php
ini_set('display_errors', false);
ini_set('error_log', '../log/test.log');

header('Content-Type: application/json;charset=utf-8');
require_once('../include/connection.php');
require_once('../include/lib_util.php');
require_once('../extension/vendor/autoload.php');
use Connection\connection;
use ReallySimpleJWT\{Token,Parse,Jwt,Validate,Encode};
use ReallySimpleJWT\Exception\ValidateException;
$con = new connection();
$conmysql = $con->connecttomysql();
$conoracle = $con->connecttooracle();

use Utility\Library;

$lib = new library();
$jwt_token = new Token();
$jsonConfig = file_get_contents('../config/config_constructor.json');
$config = json_decode($jsonConfig,true);
$arrMembMysql = array();
$arrMembOra = array();
$arrGrp = array();
$arrTel = array();
$getListLoseMysql = $conmysql->prepare("SELECT member_no FROM logregisterelection");
$getListLoseMysql->execute();
while($rowListLose = $getListLoseMysql->fetch(PDO::FETCH_ASSOC)){
	$arrMembMysql[] = $rowListLose["member_no"];
}
$getListLoseOracle = $conoracle->prepare("SELECT MEMBER_NO FROM mbmembelection where post_no = '3' and election_year = '2564'");
$getListLoseOracle->execute();
while($rowListLoseOra = $getListLoseOracle->fetch(PDO::FETCH_ASSOC)){
	if(in_array($rowListLoseOra["MEMBER_NO"],$arrMembMysql)){
		
	}else{
		$arrMembOra[] = "'".$rowListLoseOra["MEMBER_NO"]."'";
		if(sizeof($arrMembOra) == 1000){
			$getTelMemb = $conoracle->prepare("SELECT MEMBER_NO,SMS_MOBILEPHONE FROM mbmembmaster WHERE member_no IN(".implode(',',$arrMembOra).")");
			$getTelMemb->execute();
			while($rowTelMemb = $getTelMemb->fetch(PDO::FETCH_ASSOC)){
				$arrGrp[$rowTelMemb["SMS_MOBILEPHONE"]] = $rowTelMemb["MEMBER_NO"];
				$arrTel[] = $rowTelMemb["SMS_MOBILEPHONE"];
			}
		}
	}
}
if(sizeof($arrMembOra) > 0) {
	$getTelMemb = $conoracle->prepare("SELECT MEMBER_NO,SMS_MOBILEPHONE FROM mbmembmaster WHERE member_no IN(".implode(',',$arrMembOra).")");
	$getTelMemb->execute();
	while($rowTelMemb = $getTelMemb->fetch(PDO::FETCH_ASSOC)){
		$arrGrp[$rowTelMemb["SMS_MOBILEPHONE"]] = $rowTelMemb["MEMBER_NO"];
		$arrTel[] = $rowTelMemb["SMS_MOBILEPHONE"];
	}
}
$arrVerifyToken['exp'] = time() + 300;
$arrVerifyToken['action'] = "fetch";
$arrVerifyToken["require"] = "tel";
$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["KEYCODE"]);
$arrSendData["dataRequest"] = $arrTel;
$arrSendData["custId"] = 'mhd';
$arrHeader[] = "version: v1";
$arrHeader[] = "OAuth: Bearer ".$verify_token;
$arrFetchDataSMS = $lib->posting_data($config["URL_SMS_ELECTION"].'/beta/navigator',$arrSendData,$arrHeader);
$data = json_decode($arrFetchDataSMS, true);
$insertBulk = array();
foreach($data["data"] as $data){
	if(!strpos($data["MSG"],'OTP')){
		$arrMsgExplode = explode(':',$data["MSG"]);
		$key_code = substr(trim($arrMsgExplode[1]),0,6);
		$insertBulk[] = "('".$arrGrp[$data["TEL"]]."','".$key_code."','".$data["TEL"]."','3')";
		/*if(sizeof($insertBulk) == 1000){
			$insertBulkSQL = $conmysql->prepare("INSERT INTO logregisterelection(member_no,keycode,tel_mobile,value_election)
																VALUES".implode(",",$insertBulk));
			$insertBulkSQL->execute();
			unset($insertBulk);
			$insertBulk = array();
		}*/
	}
}
/*if(sizeof($insertBulk) > 0){
	$insertBulkSQL = $conmysql->prepare("INSERT INTO logregisterelection(member_no,keycode,tel_mobile,value_election)
														VALUES".implode(",",$insertBulk));
	$insertBulkSQL->execute();
}*/
echo json_encode($arrTel);
?>