<?php
require_once('../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'line',null)){
		$groupRespondMessage = array();
		$fetchLineRespondMessage = $conmysql->prepare("SELECT COUNT(title) as C_TITLE,DATE_FORMAT(date,'%m') as MONTH,DATE_FORMAT(date,'%Y') as YEAR
				FROM lbrespondmessage
				WHERE date <= DATE_SUB(date,INTERVAL -6 MONTH) 
				GROUP BY DATE_FORMAT(date,'%m') ORDER BY date ASC");
		$fetchLineRespondMessage->execute();
		while($rowResponeMessage = $fetchLineRespondMessage->fetch(PDO::FETCH_ASSOC)){
			$arrGroupResponeMessage = array();
			$arrGroupResponeMessage["MONTH"] = $rowResponeMessage["MONTH"];
			$arrGroupResponeMessage["YEAR"] = $rowResponeMessage["YEAR"] + 543;
			$arrGroupResponeMessage["AMT"] = $rowResponeMessage["C_TITLE"];
			$groupRespondMessage[] = $arrGroupResponeMessage;
		}
		$fetchSumRespondMessage = $conmysql->prepare("SELECT COUNT(title)AS c_reply FROM lbrespondmessage");
		$fetchSumRespondMessage->execute();
		$arrReplay = $fetchSumRespondMessage->fetch(PDO::FETCH_ASSOC);
		
		$fetchSumTextIncome = $conmysql->prepare("SELECT COUNT(text) AS c_text FROM lbincometext");
		$fetchSumTextIncome->execute();
		$arrTextIncom = $fetchSumTextIncome->fetch(PDO::FETCH_ASSOC);
		
		$fetchUserBindAccount = $conmysql->prepare("SELECT
    COUNT(member_no) AS c_meber_no
FROM
    gcmemberaccount
WHERE
    line_token IS NOT NULL
    AND member_no != 'etnmode1' AND member_no != 'etnmode2' AND member_no !='etnmode3' AND member_no !='etnmode4'");
		$fetchUserBindAccount->execute();
		$arrUserBindAccount = $fetchUserBindAccount->fetch(PDO::FETCH_ASSOC);

		
		$total_message  = intval($arrTextIncom["c_text"]);
		$total_replay =   intval($arrReplay["c_reply"]);
		$arrayResult["RESPOND_MESSAGE_DATA"] = $groupRespondMessage;
		$arrayResult["TOTAL_RESPOND"] = $arrReplay["c_reply"];
		$arrayResult["TOTAL_MESSAGE_INCOME"] = $arrTextIncom["c_text"];
		$arrayResult["TOTAL_NOT_RESPOND"] = $total_message - $total_replay;
		$arrayResult["USER_BIND_ACCOUNT_DATA"] = $arrUserBindAccount["c_meber_no"];
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../include/exit_footer.php');

	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
}
?>
