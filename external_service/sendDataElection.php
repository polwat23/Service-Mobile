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
$mobile_no = array();
$mobile_noRaw = array();
$getMobileNotsend = $conmysql->prepare("SELECT mobile_no FROM importsmstemp");
$getMobileNotsend->execute();
while($rowMobile = $getMobileNotsend->fetch(PDO::FETCH_ASSOC)){
	if(strlen($rowMobile["mobile_no"]) == 11){
		if(!in_array( '0'.substr($rowMobile["mobile_no"],2),$mobile_no)){
			$mobile_no[] = "'".'0'.substr($rowMobile["mobile_no"],2)."'";
			$mobile_noRaw[] = '0'.substr($rowMobile["mobile_no"],2);
		}
	}
}


$arrMsg = array();
$getMember = $conmysql->prepare("SELECT keycode,tel_mobile  FROM logregisterelection WHERE tel_mobile IN(".implode(',',$mobile_no).") LIMIT 2");
$getMember->execute();
while($rowMember = $getMember->fetch(PDO::FETCH_ASSOC)){
	$arr = array(); 
	$arr["msg"]= 'รหัสของท่านคือ : '.$rowMember["keycode"].' รหัสผ่านชุดนี้ใช้ในการลงคะแนนสรรหา วันที่ 13-16 ธันวาคม 2564';
	$arr["to"]= '0820161367';
	$arrMsg[] = $arr;
}

$arrVerifyToken2['exp'] = time() + 300;
$arrVerifyToken2['action'] = "sendmsg";
$arrVerifyToken2["mode"] = "eachmsg";
$arrVerifyToken2['typeMsg'] = 'OTP';
$verify_token2 =  $jwt_token->customPayload($arrVerifyToken2, $config["KEYCODE"]);
$arrSendData2["dataMsg"] = $arrMsg;
$arrSendData2["custId"] = 'mhd';
$arrHeader2[] = "version: v1";
$arrHeader2[] = "OAuth: Bearer ".$verify_token2;
$arraySendSMS = $lib->posting_data($config["URL_SMS_ELECTION"].'/beta/navigator',$arrSendData2,$arrHeader2);
echo json_encode($arraySendSMS);
?>