<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','reportmembereditdata')){
		$arrayGroup = array();
		
		$arrayGroup["address"] = "ที่อยู่";
		$arrayGroup["addr_moo"] = "หมู่";
		$arrayGroup["addr_no"] = "บ้านเลขที่";
		$arrayGroup["addr_postcode"] = "รหัสไปรษณีย์";
		$arrayGroup["addr_road"] = "ถนน";
		$arrayGroup["addr_soi"] = "ซอย";
		$arrayGroup["addr_village"] = "หมู่บ้าน";
		$arrayGroup["district_code"] = "รหัสอำเภอ";
		$arrayGroup["district"] = "อำเภอ";
		$arrayGroup["province_code"] = "รหัสจังหวัด";
		$arrayGroup["province"] = "จังหวัด";
		$arrayGroup["tambol_code"] = "รหัสตำบล";
		$arrayGroup["tambol"] = "ตำบล";
		$arrayGroup["email"] = "อีเมลล์";
		$arrayGroup["tel"] = "เบอร์โทรศัพท์";
		$arrayGroup["addr_fax"] = "โทรสาร";
		$arrayGroup["website"] = "เว็บไซต์";
		$arrayGroup["coopregis_date"] = "วันที่จดทะเบียน";
		$arrayGroup["coopregis_no"] = "เลขทะเบียนสหกรณ์";
		$arrayGroup["memb_regno"] = "เลข 13 หลักสหกรณ์";
		$arrayGroup["tax_id"] = "เลขประจำตัวผู้เสียภาษีอากร";
		$arrayGroup["accyearclose_date"] = "วันสิ้นปีทางบัญชี";
		$arrayGroup["share_stk"] = "ทุนเรือนหุ้น";
		
		$arrayGroup["BOARD"] = "คณะกรรมการ";
		$arrayGroup["BUSINESS"] = "ผู้ตรวจสอบกิจการ";
		$arrayGroup["COOP_OFFICER"] = "จำนวนเจ้าหน้าที่";
		$arrayGroup["MEMBER_COUNT"] = "จำนวนสมาชิก";
		
		$arrayResult["EDIT_MEMBER_LABEL"] = $arrayGroup;

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