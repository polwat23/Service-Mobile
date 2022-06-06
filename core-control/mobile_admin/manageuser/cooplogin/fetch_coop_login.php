<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','cooplogin')){
		$arrayGroup = array();
		
		if(isset($dataComing["sector"]) && $dataComing["sector"] != ''){
			$fetchUserlogin = $conmysql->prepare("SELECT gc.ref_memno as member_no,
												gr.prename_desc,
												gr.memb_name,
												gr.suffname_desc,
												gr.sector_id,
												gu.channel,
												gu.login_date
												FROM gcuserlogin gu LEFT JOIN gcmemberaccount gc ON gu.member_no = gc.member_no 
												LEFT JOIN gcmembonlineregis gr ON gc.ref_memno = gr.member_no
												WHERE gr.sector_id = :sector AND is_login ='1' AND login_date > '2022-01-01 00:00:00'  AND gu.member_no not in('dev@mode','etnmode1','etnmode2','etnmode3','etnmode4','salemode','0650','0060','1174','0572','0128','0014','0623','0033','0287','0356','0498','0294','0025','0254','0119','1075','0195','0358')
												GROUP BY gu.member_no ORDER BY gu.login_date ASC");
			$fetchUserlogin->execute([':sector' => $dataComing["sector"]]);
		}else {		
			$fetchUserlogin = $conmysql->prepare("SELECT gc.ref_memno as member_no,
												gr.prename_desc,
												gr.memb_name,
												gr.suffname_desc,
												gr.sector_id,
												gu.channel,
												gu.login_date
												FROM gcuserlogin gu LEFT JOIN gcmemberaccount gc ON gu.member_no = gc.member_no 
												LEFT JOIN gcmembonlineregis gr ON gc.ref_memno = gr.member_no
												WHERE is_login ='1' AND login_date > '2022-01-01 00:00:00'  AND gu.member_no not in('dev@mode','etnmode1','etnmode2','etnmode3','etnmode4','salemode','0650','0060','1174','0572','0128','0014','0623','0033','0287','0356','0498','0294','0025','0254','0119','1075','0195','0358')
												GROUP BY gu.member_no ORDER BY gu.login_date ASC");
			$fetchUserlogin->execute();
		}
		while($rowUserlogin = $fetchUserlogin->fetch(PDO::FETCH_ASSOC)){
			$arrGroupRootUserlogin = array();
			if(isset($rowUserlogin["member_no"])){
				$arrGroupRootUserlogin["LOGIN_DATE"] =  $lib->convertdate($rowUserlogin["login_date"],'d M Y');
				$arrGroupRootUserlogin["CHANNEL"] = $rowUserlogin["channel"];
				$arrGroupRootUserlogin["MEMBER_NO"] = $rowUserlogin["member_no"];
				$arrGroupRootUserlogin["FULLNAME"] = $rowUserlogin["prename_desc"].$rowUserlogin["memb_name"]." ".$rowUserlogin["suffname_desc"];
				$arrayGroup[] = $arrGroupRootUserlogin;
			}
		}
		$arrayResult["COOP_LOGIN"] = $arrayGroup;
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