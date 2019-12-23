<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no','id_card','api_token','unique_id','menu_component'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		if($lang_locale == 'th'){
			$arrayResult['RESPONSE_MESSAGE'] = "มีบางอย่างผิดพลาดกรุณาติดต่อสหกรณ์ #WS0001";
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "Something wrong please contact cooperative #WS0001";
		}
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		echo json_encode($arrayResult);
		exit();
	}
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'AppRegister')){
		$member_no = str_pad($dataComing["member_no"],8,0,STR_PAD_LEFT);
		$checkMember = $conmysql->prepare("SELECT id_account FROM gcmemberaccount WHERE member_no = :member_no");
		$checkMember->execute([':member_no' => $member_no]);
		if($checkMember->rowCount() > 0){
			$arrayResult['RESPONSE_CODE'] = "WS0020";
			if($lang_locale == 'th'){
				$arrayResult['RESPONSE_MESSAGE'] = "คุณเป็นสมาชิกอยู่แล้ว";
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "You has been registered";
			}
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}else{
			$checkValid = $conoracle->prepare("SELECT memb_name,memb_surname FROM mbmembmaster 
												WHERE member_no = :member_no and card_person = :card_person and resign_status = 0");
			$checkValid->execute([
				':member_no' => $member_no,
				':card_person' => $dataComing["id_card"]
			]);
			$rowMember = $checkValid->fetch();
			if($rowMember){
				$arrayResult['MEMBER_NO'] = $member_no;
				$arrayResult['CARD_PERSON'] = $dataComing["id_card"];
				$arrayResult['MEMBER_FULLNAME'] = $rowMember["MEMB_NAME"].' '.$rowMember["MEMB_SURNAME"];
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0003";
				if($lang_locale == 'th'){
					$arrayResult['RESPONSE_MESSAGE'] = "ไม่พบข้อมูลผู้ใช้";
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "Not found membership";
				}
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		if($lang_locale == 'th'){
			$arrayResult['RESPONSE_MESSAGE'] = "ท่านไม่มีสิทธิ์ใช้งานเมนูนี้";
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "You not have permission for this menu";
		}
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	if($lang_locale == 'th'){
		$arrayResult['RESPONSE_MESSAGE'] = "มีบางอย่างผิดพลาดกรุณาติดต่อสหกรณ์ #WS4004";
	}else{
		$arrayResult['RESPONSE_MESSAGE'] = "Something wrong please contact cooperative #WS4004";
	}
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>