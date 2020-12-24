<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','assignadmin')){
		$arrayAdmin = array();
		$fetchAdmin = $conmysql->prepare("SELECT member_no FROM gcmemberaccount WHERE user_type = '1'");
		$fetchAdmin->execute();
		while($rowAdmin = $fetchAdmin->fetch(PDO::FETCH_ASSOC)){
			$arr_data = array();
			$arr_data["member_no"] = $rowAdmin["member_no"];
			$arr_data["fullname"] = null;
			
			$fetchUserInfo = $conoracle->prepare("SELECT mb.member_no,mp.prename_desc,mb.memb_name,mb.memb_surname,mb.member_date
													,mb.mem_tel as MEM_TEL, mb.mem_telmobile as MEM_TELMOBILE,mb.email_address as email,
													mb.MEMBGROUP_CODE,
													mg.membgroup_desc
													FROM mbmembmaster mb
													LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													LEFT JOIN MBUCFMEMBGROUP mg ON mb.MEMBGROUP_CODE = mg.MEMBGROUP_CODE
													WHERE mb.resign_status = '0' AND member_no = :member_no");
			$fetchUserInfo->execute([
				'member_no' => $rowAdmin["member_no"]
			]);
			
			while($rowUserInfo = $fetchUserInfo->fetch(PDO::FETCH_ASSOC)){
				$arr_data["fullname"] = $rowUserInfo["PRENAME_DESC"].$rowUserInfo["MEMB_NAME"]." ".$rowUserInfo["MEMB_SURNAME"];
				$arr_data["MEMBGROUP_CODE"] = $rowUserInfo["MEMBGROUP_CODE"];
				$arr_data["MEMBGROUP_DESC"] = $rowUserInfo["MEMBGROUP_DESC"];
			}
			$arrayAdmin[] = $arr_data;
		}
		$arrayResult['ADMIN'] = $arrayAdmin;
		$arrayResult['RESULT'] = TRUE;
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
