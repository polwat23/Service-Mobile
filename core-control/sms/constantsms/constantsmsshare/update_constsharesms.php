<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','contdata'],$dataComing)){
	if($func->check_permission_core($payload,'sms','constantsmsshare')){
		$arrayGroup = array();
		$arrayChkG = array();
		$fetchConstant = $conmysql->prepare("SELECT
																		id_smsconstantshare,
																		share_itemtype_code,
																		allow_smsconstantshare,
																		allow_notify
																	FROM
																		smsconstantshare
																	ORDER BY share_itemtype_code ASC");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_SMSCONSTANTSHARE"] = $rowMenuMobile["id_smsconstantshare"];
			$arrConstans["SHRITEMTYPE_CODE"] = $rowMenuMobile["share_itemtype_code"];
			$arrConstans["ALLOW_SMSCONSTANTSHARE"] = $rowMenuMobile["allow_smsconstantshare"];
			$arrConstans["ALLOW_NOTIFY"] = $rowMenuMobile["allow_notify"];
			$arrayChkG[] = $arrConstans;
		}
		$fetchDepttype = $conoracle->prepare("SELECT SHRITEMTYPE_CODE,SHRITEMTYPE_DESC FROM SHUCFSHRITEMTYPE ORDER BY SHRITEMTYPE_CODE ASC");
		$fetchDepttype->execute();
		while($rowDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC)){
			$arrayDepttype = array();
				if(array_search($rowDepttype["SHRITEMTYPE_CODE"],array_column($arrayChkG,'SHRITEMTYPE_CODE')) === False){
						$arrayDepttype["ALLOW_SMSCONSTANTSHARE"] = 0;
						$arrayDepttype["ALLOW_NOTIFY"] = 0;
				}else{
					$arrayDepttype["ALLOW_SMSCONSTANTSHARE"] = $arrayChkG[array_search($rowDepttype["SHRITEMTYPE_CODE"],array_column($arrayChkG,'SHRITEMTYPE_CODE'))]["ALLOW_SMSCONSTANTSHARE"];
					$arrayDepttype["ALLOW_NOTIFY"] = $arrayChkG[array_search($rowDepttype["SHRITEMTYPE_CODE"],array_column($arrayChkG,'SHRITEMTYPE_CODE'))]["ALLOW_NOTIFY"];
				}
				
			$arrayDepttype["SHRITEMTYPE_CODE"] = $rowDepttype["SHRITEMTYPE_CODE"];
			$arrayDepttype["SHRITEMTYPE_DESC"] = $rowDepttype["SHRITEMTYPE_DESC"];
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
					$value_diff["ALLOW_NOTIFY"] = '0';
					if(array_search($value_diff["SHRITEMTYPE_CODE"],array_column($arrayChkG,'SHRITEMTYPE_CODE')) === False){
						$insertBulkCont[] = "('".$value_diff["SHRITEMTYPE_CODE"]."','".$value_diff["ALLOW_SMSCONSTANTSHARE"]."','".$value_diff["ALLOW_NOTIFY"]."')";
						$insertBulkContLog[]='SHRITEMTYPE_CODE=> '.$value_diff["SHRITEMTYPE_CODE"].' ALLOW_SMSCONSTANTSHARE ='.$value_diff["ALLOW_SMSCONSTANTSHARE"].' ALLOW_NOTIFY ='.$value_diff["ALLOW_NOTIFY"];
					}else{
						$updateConst = $conmysql->prepare("UPDATE smsconstantshare SET allow_smsconstantshare = :ALLOW_SMSCONSTANTSHARE, allow_notify = :ALLOW_NOTIFY WHERE share_itemtype_code = :SHRITEMTYPE_CODE");
						$updateConst->execute([
							':ALLOW_SMSCONSTANTSHARE' => $value_diff["ALLOW_SMSCONSTANTSHARE"],
							':ALLOW_NOTIFY' => $value_diff["ALLOW_NOTIFY"],
							':SHRITEMTYPE_CODE' => $value_diff["SHRITEMTYPE_CODE"]
						]);
						$updateConstLog = 'SHRITEMTYPE_CODE=> '.$value_diff["SHRITEMTYPE_CODE"].' ALLOW_SMSCONSTANTSHARE='.$value_diff["ALLOW_SMSCONSTANTSHARE"].' ALLOW_SMSCONSTANTSHARE='.$value_diff["ALLOW_SMSCONSTANTSHARE"];
					}
				}
				$insertConst = $conmysql->prepare("INSERT smsconstantshare(share_itemtype_code,allow_smsconstantshare,allow_notify)
																VALUES".implode(',',$insertBulkCont));
				$insertConst->execute();
				$arrayStruc = [
					':menu_name' => "constantsmsshare",
					':username' => $payload["username"],
					':use_list' =>"edit constant sms share",
					':details' => implode(',',$insertBulkContLog).' '.$updateConstLog
				];
				$log->writeLog('editsms',$arrayStruc);	
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