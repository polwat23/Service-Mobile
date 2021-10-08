<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_regelection'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','keycodeelectionlist')){
		$arrGrp = array();
		
		$getAllReqDocno = $conmysql->prepare("SELECT id_regelection, member_no, keycode, tel_mobile, register_date FROM logregisterelection WHERE id_regelection = :id_regelection");
		$getAllReqDocno->execute([
			':id_regelection' => $dataComing["id_regelection"]
		]);
		$rowDocno = $getAllReqDocno->fetch(PDO::FETCH_ASSOC);
		
		if(isset($rowDocno["keycode"])){
			$arrVerifyToken['exp'] = time() + 300;
			$arrVerifyToken['action'] = "sendmsg";
			$arrVerifyToken["mode"] = "eachmsg";
			$arrVerifyToken['typeMsg'] = 'OTP';
			$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["KEYCODE"]);
			$arrMsg[0]["msg"] = 'รหัสของท่านคือ : '.$rowDocno["keycode"].' รหัสผ่านชุดนี้ใช้ในการลงคะแนนสรรหา วันที่ 13-16 ธันวาคม 2564';
			$arrMsg[0]["to"] = $rowDocno["tel_mobile"];
			$arrSendData["dataMsg"] = $arrMsg;
			$arrSendData["custId"] = 'mhd';
			$arrHeader[] = "version: v1";
			$arrHeader[] = "OAuth: Bearer ".$verify_token;
			$arraySendSMS = $lib->posting_data($config["URL_SMS_ELECTION"].'/navigator',$arrSendData,$arrHeader);
			if($arraySendSMS["result"]){
				$arrayResult['DDD'] = $arrSendData;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else {
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE'] = "ไม่สามารถส่ง Key Code ได้ กรุณาติดต่อผู้พัฒนา";
				require_once('../../../../include/exit_footer.php');
			}
		}else{
			$arrayResult['RESULT'] = FALSE;
			$arrayResult['RESPONSE'] = "ไม่สามารถส่ง Key Code ได้ กรุณาติดต่อผู้พัฒนา";
			require_once('../../../../include/exit_footer.php');
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
}
?>