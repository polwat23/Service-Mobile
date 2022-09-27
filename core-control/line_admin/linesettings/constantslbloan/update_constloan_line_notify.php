<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','contdata'],$dataComing)){
	if($func->check_permission_core($payload,'line','constantslinenotifyloan')){
		$arrayGroup = array();
		$arrayChkG = array();
		$fetchConstant = $conmysql->prepare("SELECT
												id_lbconstantloan,
												loan_itemtype_code,
												allow_lbconstantloan
											FROM
												lbconstantloan
											ORDER BY loan_itemtype_code ASC");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_LBCONSTANTLOAN"] = $rowMenuMobile["id_lbconstantloan"];
			$arrConstans["LOANITEMTYPE_CODE"] = $rowMenuMobile["loan_itemtype_code"];
			$arrConstans["ALLOW_LBCONSTANTLOAN"] = $rowMenuMobile["allow_lbconstantloan"];
			$arrayChkG[] = $arrConstans;
		}
		$fetchDepttype = $conoracle->prepare("SELECT LOANITEMTYPE_CODE,LOANITEMTYPE_DESC FROM LNUCFLOANITEMTYPE ORDER BY LOANITEMTYPE_CODE ASC");
		$fetchDepttype->execute();
		while($rowDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC)){
			$arrayDepttype = array();
				if(array_search($rowDepttype["LOANITEMTYPE_CODE"],array_column($arrayChkG,'LOANITEMTYPE_CODE')) === False){
						$arrayDepttype["ALLOW_LBCONSTANTLOAN"] = 0;
				}else{
					$arrayDepttype["ALLOW_LBCONSTANTLOAN"] = $arrayChkG[array_search($rowDepttype["LOANITEMTYPE_CODE"],array_column($arrayChkG,'LOANITEMTYPE_CODE'))]["ALLOW_LBCONSTANTLOAN"];
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
				foreach($resultUDiff as $value_diff){
					if(array_search($value_diff["LOANITEMTYPE_CODE"],array_column($arrayChkG,'LOANITEMTYPE_CODE')) === False){
						$insertBulkCont[] = "('".$value_diff["LOANITEMTYPE_CODE"]."','".$value_diff["ALLOW_LBCONSTANTLOAN"]."')";
						$insertBulkContLog[]='LOANITEMTYPE_CODE=> '.$value_diff["LOANITEMTYPE_CODE"].' ALLOW_LBCONSTANTLOAN ='.$value_diff["ALLOW_LBCONSTANTLOAN"];
					}else{
						$updateConst = $conmysql->prepare("UPDATE lbconstantloan SET allow_lbconstantloan = :ALLOW_LBCONSTANTLOAN WHERE loan_itemtype_code = :LOANITEMTYPE_CODE");
						$updateConst->execute([
							':ALLOW_LBCONSTANTLOAN' => $value_diff["ALLOW_LBCONSTANTLOAN"],
							':LOANITEMTYPE_CODE' => $value_diff["LOANITEMTYPE_CODE"]
						]);
						$updateConstLog = 'LOANITEMTYPE_CODE=> '.$value_diff["LOANITEMTYPE_CODE"].' ALLOW_LBCONSTANTLOAN='.$value_diff["ALLOW_LBCONSTANTLOAN"];
					}
				}
				$insertConst = $conmysql->prepare("INSERT lbconstantloan(loan_itemtype_code,allow_lbconstantloan)
																VALUES".implode(',',$insertBulkCont));
				$insertConst->execute();
				$arrayStruc = [
					':menu_name' => "constantsmsloan",
					':username' => $payload["username"],
					':use_list' =>"edit constant sms loan",
					':details' => implode(',',$insertBulkContLog).' '.$updateConstLog
				];
				$log->writeLog('editsms',$arrayStruc);	
				$arrayResult['RESULT'] = TRUE;
				$arrayResult['data'] = $insertBulkCont;
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