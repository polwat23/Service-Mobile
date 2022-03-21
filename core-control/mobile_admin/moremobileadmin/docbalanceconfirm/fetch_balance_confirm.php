<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','docbalanceconfirm')){
		$arrayGroupAll = array();

		$fetchDocbalance= $conmysql->prepare("SELECT  id_confirm, member_no, confirm_date, confirmdep_list, confirmlon_list, confirmshr_list, balance_date, url_path FROM gcconfirmbalancelist");
		$fetchDocbalance->execute();
		while($rowDocbalance = $fetchDocbalance->fetch(PDO::FETCH_ASSOC)){
			$arrayData = array();
			$fetchMember = $conoracle->prepare("SELECT MP.PRENAME_SHORT,MB.MEMB_NAME,MB.MEMB_SURNAME
											FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
											WHERE mb.member_no = :member_no");
			$fetchMember->execute(['member_no' => $rowDocbalance["member_no"]]);
			$rowMember = $fetchMember->fetch(PDO::FETCH_ASSOC);
			$arrayData["id_confirm"] = $rowDocbalance["id_confirm"];
			$arrayData["MEMBER_NO"] = $rowDocbalance["member_no"];
			$arrayData["FULL_NAME"] = $rowMember["PRENAME_SHORT"].$rowMember["MEMB_NAME"].' '.$rowMember["MEMB_SURNAME"];
			$arrayData["CONFIRM_DATE"] = $lib->convertdate($rowDocbalance["confirm_date"],'d m Y'); 
			$arrayData["balance_date"] = $rowDocbalance["balance_date"];
			$arrayData["CONFIRMDEP_LIST"] = json_decode(($rowDocbalance["confirmdep_list"]),true);
			$arrayData["CONFIRMLON_LIST"] = json_decode(($rowDocbalance["confirmlon_list"]),true);
			$arrayData["CONFIRMSHR_LIST"] = json_decode(($rowDocbalance["confirmshr_list"]),true);
			$arrayData["URL_PATH"] = $rowDocbalance["url_path"];
			
			//$arrayData["BALANCE_DATE_FORMAR"] = $rowDocbalance["balance_date"];
			$arrayGroupAll[] = $arrayData;
		}
		$arrayResult["BALANCE"] = $arrayGroupAll;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		$arrayResult['RESULT'] = TRUE;
		require_once('../../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
}
?>