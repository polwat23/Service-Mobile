<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','dept_type_code','dept_type_desc'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantdeptaccount',$conoracle)){
		$id_accountconstant = $func->getMaxTable('id_accountconstant' , 'gcconstantaccountdept',$conoracle);
		$insertConstants = $conoracle->prepare("INSERT gcconstantaccountdept(id_accountconstant,dept_type_code,member_cate_code,dept_type_desc,id_palette)
												VALUES(:id_accountconstant, :depttype_code,'AL',:depttype_desc,'2')");
		if($insertConstants->execute([
			':id_accountconstant' => $id_accountconstant,
			':depttype_code' => $dataComing["dept_type_code"],
			':depttype_desc' => $dataComing["dept_type_desc"]
		])){
			$arrayResult["RESULT"] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มค่าคงที่ประเภทบัญชีเงินฝากนี้ได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
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