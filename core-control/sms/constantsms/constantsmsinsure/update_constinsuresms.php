<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','contdata'],$dataComing)){
	if($func->check_permission_core($payload,'sms','constantsmsinsure')){
		$arrayGroup = array();
		$arrayChkG = array();
		$fetchConstant = $conoracle->prepare("SELECT
											id_smsconstantinsure,
											insure_itemtype_code,
											allow_smsconstantinsure,
											allow_notify
										FROM
											smsconstantinsure
										ORDER BY insure_itemtype_code ASC");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_SMSCONSTANTINSURE"] = $rowMenuMobile["ID_SMSCONSTANTINSURE"];
			$arrConstans["INSITEMTYPE_CODE"] = $rowMenuMobile["INSURE_ITEMTYPE_CODE"];
			$arrConstans["ALLOW_SMSCONSTANTINSURE"] = $rowMenuMobile["ALLOW_SMSCONSTANTINSURE"];
			$arrConstans["ALLOW_NOTIFY"] = $rowMenuMobile["ALLOW_NOTIFY"];
			$arrayChkG[] = $arrConstans;
		}
		$fetchDepttype = $conoracle->prepare("SELECT INSITEMTYPE_CODE,INSITEMTYPE_DESC FROM INSUCFINSITEMTYPE ORDER BY INSITEMTYPE_CODE ASC");
		$fetchDepttype->execute();
		while($rowDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC)){
			$arrayDepttype = array();
				if(array_search($rowDepttype["INSITEMTYPE_CODE"],array_column($arrayChkG,'INSITEMTYPE_CODE')) === False){
						$arrayDepttype["ALLOW_SMSCONSTANTINSURE"] = 0;
						$arrayDepttype["ALLOW_NOTIFY"] = 0;
				}else{
					$arrayDepttype["ALLOW_SMSCONSTANTINSURE"] = $arrayChkG[array_search($rowDepttype["INSITEMTYPE_CODE"],array_column($arrayChkG,'INSITEMTYPE_CODE'))]["ALLOW_SMSCONSTANTINSURE"];
					$arrayDepttype["ALLOW_NOTIFY"] = $arrayChkG[array_search($rowDepttype["INSITEMTYPE_CODE"],array_column($arrayChkG,'INSITEMTYPE_CODE'))]["ALLOW_NOTIFY"];
				}
				
			$arrayDepttype["INSITEMTYPE_CODE"] = $rowDepttype["INSITEMTYPE_CODE"];
			$arrayDepttype["INSITEMTYPE_DESC"] = $rowDepttype["INSITEMTYPE_DESC"];
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
					if(array_search($value_diff["INSITEMTYPE_CODE"],array_column($arrayChkG,'INSITEMTYPE_CODE')) === False){
						$id_smsconstantinsure = $func->getMaxTable('id_smsconstantinsure' , 'smsconstantinsure');
						$insertBulkCont[] = "('".$id_smsconstantinsure."','".$value_diff["INSITEMTYPE_CODE"]."','".$value_diff["ALLOW_SMSCONSTANTINSURE"]."','".$value_diff["ALLOW_NOTIFY"]."')";
						$insertBulkContLog[]='INSITEMTYPE_CODE=> '.$value_diff["INSITEMTYPE_CODE"].' ALLOW_SMSCONSTANTINSURE ='.$value_diff["ALLOW_SMSCONSTANTINSURE"].' ALLOW_NOTIFY ='.$value_diff["ALLOW_NOTIFY"];
					}else{
						$updateConst = $conoracle->prepare("UPDATE smsconstantinsure SET allow_smsconstantinsure = :ALLOW_SMSCONSTANTINSURE, allow_notify = :ALLOW_NOTIFY WHERE insure_itemtype_code = :INSITEMTYPE_CODE");
						$updateConst->execute([
							':ALLOW_SMSCONSTANTINSURE' => $value_diff["ALLOW_SMSCONSTANTINSURE"],
							':ALLOW_NOTIFY' => $value_diff["ALLOW_NOTIFY"],
							':INSITEMTYPE_CODE' => $value_diff["INSITEMTYPE_CODE"]
						]);
						$updateConstLog = 'INSITEMTYPE_CODE=> '.$value_diff["INSITEMTYPE_CODE"].' ALLOW_SMSCONSTANTINSURE='.$value_diff["ALLOW_SMSCONSTANTINSURE"].' ALLOW_SMSCONSTANTINSURE='.$value_diff["ALLOW_SMSCONSTANTINSURE"];
					}
				}
				$insertConst = $conoracle->prepare("INSERT smsconstantinsure(id_smsconstantinsure,insure_itemtype_code,allow_smsconstantinsure,allow_notify)
																VALUES".implode(',',$insertBulkCont));
				$insertConst->execute();
				$arrayStruc = [
					':menu_name' => "constantsmsinsure",
					':username' => $payload["username"],
					':use_list' =>"edit constant sms insure",
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