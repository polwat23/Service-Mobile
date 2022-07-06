<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','contdata'],$dataComing)){
	if($func->check_permission_core($payload,'sms','constantsmsloan',$conoracle)){
		$arrayGroup = array();
		$arrayChkG = array();
		$fetchConstant = $conoracle->prepare("SELECT
																		id_smsconstantloan,
																		loan_itemtype_code,
																		allow_smsconstantloan,
																		allow_notify
																	FROM
																		smsconstantloan
																	ORDER BY loan_itemtype_code ASC");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_SMSCONSTANTLOAN"] = $rowMenuMobile["ID_SMSCONSTANTLOAN"];
			$arrConstans["LOANITEMTYPE_CODE"] = $rowMenuMobile["LOAN_ITEMTYPE_CODE"];
			$arrConstans["ALLOW_SMSCONSTANTLOAN"] = $rowMenuMobile["ALLOW_SMSCONSTANTLOAN"];
			$arrConstans["ALLOW_NOTIFY"] = $rowMenuMobile["ALLOW_NOTIFY"];
			$arrayChkG[] = $arrConstans;
		}
		$fetchDepttype = $conoracle->prepare("SELECT LOANITEMTYPE_CODE,LOANITEMTYPE_DESC FROM LNUCFLOANITEMTYPE ORDER BY LOANITEMTYPE_CODE ASC");
		$fetchDepttype->execute();
		while($rowDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC)){
			$arrayDepttype = array();
				if(array_search($rowDepttype["LOANITEMTYPE_CODE"],array_column($arrayChkG,'LOANITEMTYPE_CODE')) === False){
						$arrayDepttype["ALLOW_SMSCONSTANTLOAN"] = 0;
						$arrayDepttype["ALLOW_NOTIFY"] = 0;
				}else{
					$arrayDepttype["ALLOW_SMSCONSTANTLOAN"] = $arrayChkG[array_search($rowDepttype["LOANITEMTYPE_CODE"],array_column($arrayChkG,'LOANITEMTYPE_CODE'))]["ALLOW_SMSCONSTANTLOAN"];
					$arrayDepttype["ALLOW_NOTIFY"] = $arrayChkG[array_search($rowDepttype["LOANITEMTYPE_CODE"],array_column($arrayChkG,'LOANITEMTYPE_CODE'))]["ALLOW_NOTIFY"];
				}
				
			$arrayDepttype["LOANITEMTYPE_CODE"] = $rowDepttype["LOANITEMTYPE_CODE"];
			$arrayDepttype["LOANITEMTYPE_DESC"] = $rowDepttype["LOANITEMTYPE_DESC"];
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
				$id_smsconstantloan = $func->getMaxTable('id_smsconstantloan' , 'smsconstantloan',$conoracle);
				foreach($resultUDiff as $value_diff){
					if(array_search($value_diff["LOANITEMTYPE_CODE"],array_column($arrayChkG,'LOANITEMTYPE_CODE')) === False){
						$insertBulkCont[] =  "INTO smsconstantloan(id_smsconstantloan, loan_itemtype_code,allow_smsconstantloan,allow_notify) VALUES(".$id_smsconstantloan.",'".$value_diff["LOANITEMTYPE_CODE"]."','".$value_diff["ALLOW_SMSCONSTANTLOAN"]."','".$value_diff["ALLOW_NOTIFY"]."')";
						$insertBulkContLog[]='LOANITEMTYPE_CODE=> '.$value_diff["LOANITEMTYPE_CODE"].' ALLOW_SMSCONSTANTLOAN ='.$value_diff["ALLOW_SMSCONSTANTLOAN"].' ALLOW_NOTIFY ='.$value_diff["ALLOW_NOTIFY"];
						$id_smsconstantloan++;
					}else{
						$updateConst = $conoracle->prepare("UPDATE smsconstantloan SET allow_smsconstantloan = :ALLOW_SMSCONSTANTLOAN, allow_notify = :ALLOW_NOTIFY WHERE loan_itemtype_code = :LOANITEMTYPE_CODE");
						$updateConst->execute([
							':ALLOW_SMSCONSTANTLOAN' => $value_diff["ALLOW_SMSCONSTANTLOAN"],
							':ALLOW_NOTIFY' => $value_diff["ALLOW_NOTIFY"],
							':LOANITEMTYPE_CODE' => $value_diff["LOANITEMTYPE_CODE"]
						]);
						$updateConstLog = 'LOANITEMTYPE_CODE=> '.$value_diff["LOANITEMTYPE_CODE"].' ALLOW_SMSCONSTANTLOAN='.$value_diff["ALLOW_SMSCONSTANTLOAN"].' ALLOW_NOTIFY='.$value_diff["ALLOW_NOTIFY"];
					}
				}
				$insertConst = $conoracle->prepare("INSERT ALL ".implode(' ',$insertBulkCont)."SELECT *  FROM DUAL");
				$insertConst->execute();
				$arrayStruc = [
					':menu_name' => "constantsmsloan",
					':username' => $payload["username"],
					':use_list' =>"edit constant sms loan",
					':details' => implode(',',$insertBulkContLog).' '.$updateConstLog
				];
				//$log->writeLog('editsms',$arrayStruc);	
				$arrayResult['RESULT'] = TRUE;
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