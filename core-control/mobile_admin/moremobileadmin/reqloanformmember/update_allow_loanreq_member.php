<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','loanrequestformmember')){
		$member_no = strtolower($lib->mb_str_pad($dataComing["member_no"]));
		$fetchAllow = $conmysql->prepare("SELECT member_no, create_date, update_date, update_username, is_allow
											FROM gcallowmemberreqloan WHERE member_no = :member_no");
		$fetchAllow->execute([
				':member_no' => $member_no
		]);
		
		$arrayGroupMemb = array();
		
		while($rowAllow = $fetchAllow->fetch(PDO::FETCH_ASSOC)){
			$arrayGroup = array();
			$arrayGroup["CREATE_DATE"] = $rowAllow["create_date"] ?? null;
			$arrayGroup["UPDATE_DATE"] = $rowAllow["update_date"] ?? null;
			$arrayGroup["UPDATE_USERNAME"] = $rowAllow["update_username"] ?? null;
			$arrayGroup["IS_ALLOW"] = $rowAllow["is_allow"] ?? 0;
			$arrayGroupMemb[] = $arrayGroup;
		}
		
		$checkAccBankBeenbind = $conmysql->prepare("SELECT id_bindaccount FROM gcbindaccount WHERE member_no = :member_no and bindaccount_status IN('0','1')");
		$checkAccBankBeenbind->execute([':member_no' => $member_no]);
		

		$fetchMember = $conoracle->prepare("SELECT mp.prename_short,mb.memb_name,mb.memb_surname,mb.member_no ,mb.card_person
										FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
										WHERE mb.member_no = :member_no");
		$fetchMember->execute([':member_no' => $member_no]);
		$rowMember = $fetchMember->fetch(PDO::FETCH_ASSOC);
		$arrPayloadverify["ACCOUNT_NAME"] = $rowMember["PRENAME_SHORT"].$rowMember["MEMB_NAME"]." ".$rowMember["MEMB_SURNAME"];
		$arrPayloadverify["CARD_PERSON"] = $rowMember["CARD_PERSON"];
		$sigma_key = $lib->generate_token();

		
	
		if(count($arrayGroup) > 0 && $checkAccBankBeenbind->rowCount() > 0){
			$conmysql->beginTransaction();
			$insertIntoInfo = $conmysql->prepare("UPDATE gcallowmemberreqloan SET is_allow=:is_allow WHERE member_no = :member_no");
			if($insertIntoInfo->execute([
				':is_allow' => $dataComing["is_allow"],
				':member_no' => $member_no
			])){
				$insertbindaccount = $conmysql->prepare("UPDATE gcbindaccount SET bindaccount_status=:is_allow WHERE member_no = :member_no");
				if($insertbindaccount->execute([
					':is_allow' => $dataComing["is_allow"],
					':member_no' => $member_no
				])){	
					$conmysql->commit();
					$arrayResult['RESULT'] = TRUE;	
					require_once('../../../../include/exit_footer.php');
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE_MESSAGE'] = "เเก้ไขเลขบัญชีไม่สำเร็จ";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
				}
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE_MESSAGE'] = "เเก้ไขเลขบัญชีไม่สำเร็จ";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}else{
			$conmysql->beginTransaction();
			$insertIntoInfo = $conmysql->prepare("INSERT INTO gcallowmemberreqloan(member_no,is_allow,update_username) VALUES (:member_no,:is_allow,:username)");
			if($insertIntoInfo->execute([
					':member_no' => $member_no,
					':is_allow' => $dataComing["is_allow"],
					':username' => $payload["username"]
			])){
				$insertbindaccount = $conmysql->prepare("INSERT INTO gcbindaccount(sigma_key,member_no,deptaccount_no_coop,citizen_id,mobile_no,bank_account_name,bank_account_name_en,bank_code,id_token,bindaccount_status) 
													  VALUES(:sigma_key,:member_no,:coop_account_no,:citizen_id,:mobile_no,:bank_account_name,:bank_account_name_en,'006',:id_token,'1')");
				if($insertbindaccount->execute([
					':sigma_key' => $sigma_key,
					':member_no' => $member_no,
					':coop_account_no' => $member_no,
					':citizen_id' => $arrPayloadverify["CARD_PERSON"],
					':mobile_no' => 'mobile_app',
					':bank_account_name' => $arrPayloadverify["ACCOUNT_NAME"],
					':bank_account_name_en' => $arrPayloadverify["ACCOUNT_NAME"],
					':id_token' => '999'
				])){
					$conmysql->commit();
					$arrayResult['RESULT'] = TRUE;
					require_once('../../../../include/exit_footer.php');
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE_MESSAGE'] = "เพิ่มเลขบัญชีไม่สำเร็จ";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
				}	
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE_MESSAGE'] = "เพิ่มเลขบัญชีไม่สำเร็จ";
				$arrayResult['insertIntoInfo'] = $insertIntoInfo;
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
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