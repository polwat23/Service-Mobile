<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_confirm'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','docconfirmballist')){
		$getBalStatus = $conmysql->prepare("SELECT id_confirm, confirm_date,confirm_flag,confirmlon_list, confirmshr_list, balance_date, remark, url_path, member_no 
						FROM gcconfirmbalancelist 
						WHERE id_confirm = :id_confirm");
		$getBalStatus->execute([
			':id_confirm' => $dataComing["id_confirm"]
		]);
		$rowBalStatus = $getBalStatus->fetch(PDO::FETCH_ASSOC);
		
		$getMemberInfo = $conmssql->prepare("SELECT mp.PRENAME_DESC,mb.MEMB_NAME,mb.MEMB_SURNAME,mb.BIRTH_DATE,mb.CARD_PERSON,
													mb.MEMBER_DATE,mup.POSITION_DESC,mg.MEMBGROUP_DESC,mt.MEMBTYPE_DESC
													FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													LEFT JOIN MBUCFPOSITION mup ON mb.POSITION_CODE = mup.POSITION_CODE
													LEFT JOIN MBUCFMEMBGROUP mg ON mb.MEMBGROUP_CODE = mg.MEMBGROUP_CODE
													LEFT JOIN MBUCFMEMBTYPE mt ON mb.MEMBTYPE_CODE = mt.MEMBTYPE_CODE
													WHERE mb.member_no = :member_no");
		$getMemberInfo->execute([':member_no' => $rowBalStatus["member_no"]]);
		$rowMemberInfo = $getMemberInfo->fetch(PDO::FETCH_ASSOC);
		
		$conmysql->beginTransaction();
		$insertSettlement = $conmysql->prepare("UPDATE gcconfirmbalancelist SET is_use = '0' WHERE id_confirm = :id_confirm and is_use = '1'");
		if($insertSettlement->execute([
			':id_confirm' => $dataComing["id_confirm"],
		])){
			$pathConfirmbal = __DIR__.'/../../../../resource/pdf/docbalconfirm/confirmed'.'/'.$rowMemberInfo["MEMBGROUP_DESC"].'/'.date('Ymd',strtotime($rowBalStatus["balance_date"])).$rowBalStatus["member_no"].'.pdf';
			unlink($pathConfirmbal);
			$arrayStruc = [
				':menu_name' => "docconfirmballist",
				':username' => $payload["username"],
				':use_list' => "delete firmlon",
				':details' => "id_confirm : ".$dataComing["id_confirm"]
			];
			
			$log->writeLog('manageuser',$arrayStruc);
		}else{
			$conmysql->rollback();
			$arrayResult['RESULT'] = FALSE;
			$arrayResult['RESPONSE'] = "ไม่สามารถยกเลิกรายการหักได้ กรุณาติดต่อผู้พัฒนา";
			echo json_encode($arrayResult);
			exit();
		}
		$conmysql->commit();
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>