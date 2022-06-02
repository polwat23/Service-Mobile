<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','suspendod')){
		$arrayGroupAll = array();
		$arrayExecute = array();
		if(isset($dataComing["member_no"]) && $dataComing["member_no"] != ''){
			$arrayExecute[':member_no'] = strtolower($lib->mb_str_pad($dataComing["member_no"]));
		}
		$fetchContract = $conmssql->prepare("SELECT LOANCONTRACT_NO,MEMBER_NO FROM lncontmaster where member_no = :member_no and loantype_code = '13' and contract_status > 0 and contract_status <> 8");
		$fetchContract->execute($arrayExecute);
		while($rowContract = $fetchContract->fetch(PDO::FETCH_ASSOC)){
			$arrayGroup = array();
			$arrayGroup["LOANCONTRACT_NO"] = $rowContract["LOANCONTRACT_NO"];
			$arrayGroup["MEMBER_NO"] = $rowContract["MEMBER_NO"];
			$fetchSuspend = $conmysql->prepare("SELECT suspendod_id, is_use FROM gcsuspendod WHERE loancontract_no = :loancontract_no");
			$fetchSuspend->execute([
				':loancontract_no' => $rowContract["LOANCONTRACT_NO"]
			]);
			$rowSuspend = $fetchSuspend->fetch(PDO::FETCH_ASSOC);
			if(isset($rowSuspend["is_use"])){
				$arrayGroup["IS_USE"] = $rowSuspend["is_use"] == 1;
			}else{
				$arrayGroup["IS_USE"] = false;
			}
			$arrayGroup["SUSPENDOD_ID"] = $rowSuspend["suspendod_id"];
			
			$arrayGroupAll[] = $arrayGroup;
		}
		$arrayResult["OD_CONTRACT"] = $arrayGroupAll;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../../include/exit_footer.php');
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