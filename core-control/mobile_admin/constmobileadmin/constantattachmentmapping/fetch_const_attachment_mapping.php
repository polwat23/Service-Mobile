<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantattachmentmapping')){
		$arrayGroup = array();
		$arrayLoanGroup = array();
		
		$arrConst = array();
		$arrConst["LOANGROUP_CODE"] = '01';
		$arrConst["LOANGROUP_DESC"] = 'ฉุกเฉินพร้อมเปย์';
		$arrayLoanGroup[] = $arrConst;
		$arrConst = array();
		$arrConst["LOANGROUP_CODE"] = '02';
		$arrConst["LOANGROUP_DESC"] = 'เงินกู้สามัญ';
		$arrayLoanGroup[] = $arrConst;
		$arrConst = array();
		$arrConst["LOANGROUP_CODE"] = '03';
		$arrConst["LOANGROUP_DESC"] = 'ฉุกเฉินทั่วไป';
		$arrayLoanGroup[] = $arrConst;
		
		
		$arrayFile = array();
		$fetchConstFile = $conmysql->prepare("SELECT file_id, file_name, file_desc, update_date, update_user FROM gcreqfileattachment WHERE is_use = '1'");
		$fetchConstFile->execute();
		while($rowConstFile = $fetchConstFile->fetch(PDO::FETCH_ASSOC)){
			$arrConst = array();
			$arrConst["FILE_ID"] = $rowConstFile["file_id"];
			$arrConst["FILE_NAME"] = $rowConstFile["file_name"];
			$arrConst["FILE_DESC"] = $rowConstFile["file_desc"];
			$arrConst["UPDATE_DATE"] = $rowConstFile["update_date"];
			$arrConst["UPDATE_USER"] = $rowConstFile["update_user"];
			$arrayFile[] = $arrConst;
		}
		
		$fetchConstChangeInfo = $conmysql->prepare("SELECT fmap.filemapping_id, fmap.file_id, fmap.loangroup_code, fmap.max, fmap.is_require, fmap.update_date,
											fatt.file_name
											FROM gcreqfileattachmentmapping fmap 
											LEFT JOIN gcreqfileattachment fatt ON fmap.file_id = fatt.file_id
											WHERE fmap.is_use = '1'");
		$fetchConstChangeInfo->execute();
		while($rowConst = $fetchConstChangeInfo->fetch(PDO::FETCH_ASSOC)){
			$arrConst = array();
			$arrConst["FILEMAPPING_ID"] = $rowConst["filemapping_id"];
			$arrConst["FILE_ID"] = $rowConst["file_id"];
			$arrConst["FILE_NAME"] = $rowConst["file_name"];
			$arrConst["LOANGROUP_CODE"] = $rowConst["loangroup_code"];
			$arrConst["MAX"] = $rowConst["max"];
			$arrConst["IS_REQUIRE"] = $rowConst["is_require"];
			$arrConst["UPDATE_DATE"] = $rowConst["update_date"];
			$arrayGroup[] = $arrConst;
		}
		$arrayResult["CONST_FILE"] = $arrayGroup;
		$arrayResult["LOAN_GROUP"] = $arrayLoanGroup;
		$arrayResult["FILE_TYPE"] = $arrayFile;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>