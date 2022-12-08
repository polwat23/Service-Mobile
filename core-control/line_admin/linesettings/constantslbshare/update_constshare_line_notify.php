<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','contdata'],$dataComing)){
	if($func->check_permission_core($payload,'line','constantslinenotifyshare')){
		$arrayGroup = array();
		$arrayChkG = array();
		$fetchConstant = $conmysql->prepare("SELECT
																		id_lbconstantshare,
																		share_itemtype_code,
																		allow_lbconstantshare
																	FROM
																		lbconstantshare
																	ORDER BY share_itemtype_code ASC");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_LBCONSTANTSHARE"] = $rowMenuMobile["id_lbconstantshare"];
			$arrConstans["SHRITEMTYPE_CODE"] = $rowMenuMobile["share_itemtype_code"];
			$arrConstans["ALLOW_LBCONSTANTSHARE"] = $rowMenuMobile["allow_lbconstantshare"];
			$arrayChkG[] = $arrConstans;
		}
		$fetchDepttype = $conmssql->prepare("SELECT SHRITEMTYPE_CODE,SHRITEMTYPE_DESC FROM SHUCFSHRITEMTYPE ORDER BY SHRITEMTYPE_CODE ASC");
		$fetchDepttype->execute();
		while($rowDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC)){
			$arrayDepttype = array();
				if(array_search($rowDepttype["SHRITEMTYPE_CODE"],array_column($arrayChkG,'SHRITEMTYPE_CODE')) === False){
						$arrayDepttype["ALLOW_LBCONSTANTSHARE"] = 0;
				}else{
					$arrayDepttype["ALLOW_LBCONSTANTSHARE"] = $arrayChkG[array_search($rowDepttype["SHRITEMTYPE_CODE"],array_column($arrayChkG,'SHRITEMTYPE_CODE'))]["ALLOW_LBCONSTANTSHARE"];
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
					if(array_search($value_diff["SHRITEMTYPE_CODE"],array_column($arrayChkG,'SHRITEMTYPE_CODE')) === False){
						$insertBulkCont[] = "('".$value_diff["SHRITEMTYPE_CODE"]."','".$value_diff["ALLOW_LBCONSTANTSHARE"]."')";
						$insertBulkContLog[]='SHRITEMTYPE_CODE=> '.$value_diff["SHRITEMTYPE_CODE"].' ALLOW_LBCONSTANTSHARE ='.$value_diff["ALLOW_LBCONSTANTSHARE"];
					}else{
						$updateConst = $conmysql->prepare("UPDATE lbconstantshare SET ALLOW_LBCONSTANTSHARE = :ALLOW_LBCONSTANTSHARE WHERE share_itemtype_code = :SHRITEMTYPE_CODE");
						$updateConst->execute([
							':ALLOW_LBCONSTANTSHARE' => $value_diff["ALLOW_LBCONSTANTSHARE"],
							':SHRITEMTYPE_CODE' => $value_diff["SHRITEMTYPE_CODE"]
						]);
						$updateConstLog = 'SHRITEMTYPE_CODE=> '.$value_diff["SHRITEMTYPE_CODE"].' ALLOW_LBCONSTANTSHARE='.$value_diff["ALLOW_LBCONSTANTSHARE"];
					}
				}
				$insertConst = $conmysql->prepare("INSERT lbconstantshare(share_itemtype_code,allow_lbconstantshare)
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
				$arrayResult['data'] = $resultUDiff;
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