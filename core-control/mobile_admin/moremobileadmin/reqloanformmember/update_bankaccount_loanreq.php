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
		
		if(count($arrayGroup) > 0){
			$insertIntoInfo = $conmysql->prepare("UPDATE gcallowmemberreqloan SET bank_account_no=:bank_account_no,update_username=:username WHERE member_no = :member_no");
			if($insertIntoInfo->execute([
				':bank_account_no' => $dataComing["bank_account_no"],
				':username' => $payload["username"],
				':member_no' => $member_no
			])){
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "เเก้ไขเลขบัญชีไม่สำเร็จ";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}else{
				$insertIntoInfo = $conmysql->prepare("INSERT INTO gcallowmemberreqloan(member_no,is_allow,bank_account_no,update_username) VALUES (:member_no,'0',:bank_account_no,:username)");
				if($insertIntoInfo->execute([
						':member_no' => $member_no,
						':bank_account_no' => $dataComing["bank_account_no"],
						':username' => $payload["username"]
				])){
					$arrayResult['RESULT'] = TRUE;
					require_once('../../../../include/exit_footer.php');
				}else{
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