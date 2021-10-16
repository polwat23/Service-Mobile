<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','contdata'],$dataComing)){
	if($func->check_permission_core($payload,'sms','constantsmswelfare',$conoracle)){
		$arrayGroup = array();
		$arrayChkG = array();
		$fetchConstant = $conoracle->prepare("SELECT
											id_smsconstantwelfare,
											welfare_itemtype_code,
											allow_smsconstantwelfare,
											allow_notify
										FROM
											 smsconstantwelfare
										ORDER BY welfare_itemtype_code ASC");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_SMSCONSTANTWELFARE"] = $rowMenuMobile["ID_SMSCONSTANTWELFARE"];
			$arrConstans["ITEM_CODE"] = $rowMenuMobile["WELFARE_ITEMTYPE_CODE"];
			$arrConstans["ALLOW_SMSCONSTANTWELFARE"] = $rowMenuMobile["ALLOW_SMSCONSTANTWELFARE"];
			$arrConstans["ALLOW_NOTIFY"] = $rowMenuMobile["ALLOW_NOTIFY"];
			$arrayChkG[] = $arrConstans;
		}
		$fetchDepttype = $conoracle->prepare("SELECT ITEM_CODE,ITEM_DESC FROM ASSUCFASSITEMCODE ORDER BY ITEM_CODE ASC");
		$fetchDepttype->execute();
		while($rowDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC)){
			$arrayDepttype = array();
				if(array_search($rowDepttype["ITEM_CODE"],array_column($arrayChkG,'ITEM_CODE')) === False){
						$arrayDepttype["ALLOW_SMSCONSTANTWELFARE"] = 0;
						$arrayDepttype["ALLOW_NOTIFY"] = 0;
				}else{
					$arrayDepttype["ALLOW_SMSCONSTANTWELFARE"] = $arrayChkG[array_search($rowDepttype["ITEM_CODE"],array_column($arrayChkG,'ITEM_CODE'))]["ALLOW_SMSCONSTANTWELFARE"];
					$arrayDepttype["ALLOW_NOTIFY"] = $arrayChkG[array_search($rowDepttype["ITEM_CODE"],array_column($arrayChkG,'ITEM_CODE'))]["ALLOW_NOTIFY"];
				}
				
			$arrayDepttype["ITEM_CODE"] = $rowDepttype["ITEM_CODE"];
			$arrayDepttype["ITEM_DESC"] = $rowDepttype["ITEM_DESC"];
			$arrayGroup[] = $arrayDepttype;
		}
		
			if($dataComing["contdata"] !== $arrayGroup){
				$resultUDiff = array_udiff($dataComing["contdata"],$arrayGroup,function ($loanChange,$loanOri){
					if ($loanChange === $loanOri){
						return 0;
					}else{
						return ($loanChange>$loanOri) ? 1 : -1;
					}
				});
				foreach($resultUDiff as $value_diff){
					if(array_search($value_diff["ITEM_CODE"],array_column($arrayChkG,'ITEM_CODE')) === False){
						$id_smsconstantwelfare = $func->getMaxTable('id_smsconstantwelfare' , 'smsconstantwelfare',$conoracle);
						$insertBulkCont[] = "('".$id_smsconstantwelfare."','".$value_diff["ITEM_CODE"]."','".$value_diff["ALLOW_SMSCONSTANTWELFARE"]."','".$value_diff["ALLOW_NOTIFY"]."')";
						$insertBulkContLog[]='ITEM_CODE=> '.$value_diff["ITEM_CODE"].' ALLOW_SMSCONSTANTWELFARE ='.$value_diff["ALLOW_SMSCONSTANTWELFARE"].' ALLOW_NOTIFY ='.$value_diff["ALLOW_NOTIFY"];
					}else{
						$updateConst = $conoracle->prepare("UPDATE  smsconstantwelfare SET allow_smsconstantwelfare = :ALLOW_SMSCONSTANTWELFARE, allow_notify = :ALLOW_NOTIFY WHERE welfare_itemtype_code = :ITEM_CODE");
						$updateConst->execute([
							':ALLOW_SMSCONSTANTWELFARE' => $value_diff["ALLOW_SMSCONSTANTWELFARE"],
							':ALLOW_NOTIFY' => $value_diff["ALLOW_NOTIFY"],
							':ITEM_CODE' => $value_diff["ITEM_CODE"]
						]);
						$updateConstLog = 'ITEM_CODE=> '.$value_diff["ITEM_CODE"].' ALLOW_SMSCONSTANTWELFARE='.$value_diff["ALLOW_SMSCONSTANTWELFARE"].' ALLOW_SMSCONSTANTWELFARE='.$value_diff["ALLOW_SMSCONSTANTWELFARE"];
					}
				}
				$insertConst = $conoracle->prepare("INSERT  smsconstantwelfare(id_smsconstantwelfare,welfare_itemtype_code,allow_smsconstantwelfare,allow_notify)
																VALUES".implode(',',$insertBulkCont));
				$insertConst->execute();
				$arrayStruc = [
					':menu_name' => "constantsmswelfare",
					':username' => $payload["username"],
					':use_list' =>"edit constant sms welfare",
					':details' => implode(',',$insertBulkContLog).' '.$updateConstLog
				];
				$log->writeLog('editsms',$arrayStruc);	
				$arrayResult['RESULT'] = TRUE;
				$arrayResult['arrayGroup'] = $arrayGroup;
				$arrayResult['resultUDiff'] = $resultUDiff;
				$arrayResult['contdata'] = $dataComing["contdata"];
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE'] = "ข้อมูลไม่มีการเปลี่ยนแปลง กรุณาเลือกทำรายการ";
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