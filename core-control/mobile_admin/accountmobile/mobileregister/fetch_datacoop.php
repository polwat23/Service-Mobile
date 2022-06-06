<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','mobileregister')){
		$arrayAccount = array();
		$arrayExecute = array();
		$arrRegisterCoop = array();
		if(isset($dataComing["member_no"]) && $dataComing["member_no"] != ''){
			$arrayExecute[':member_no'] = $dataComing["member_no"];
		}
		if(isset($dataComing["memb_name"]) && $dataComing["memb_name"] != ''){
			$arrayExecute[':memb_name'] = "%".$dataComing["memb_name"]."%";
		}
		if(empty($dataComing["member_no"]) && empty($dataComing["memb_name"])){
			$arrayResult['RESPONSE'] = "ไม่สามารถค้นหาได้เนื่องจากไม่ได้ระบุค่าที่ต้องการค้นหา";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
		}
		
			$fetchAccount = $conmysql->prepare("SELECT member_no, prename_desc, memb_name, prename_edesc, memb_ename, remark ,service_status ,	suffname_desc
											FROM gcmembonlineregis 
											WHERE 1=1".(isset($dataComing["member_no"]) && $dataComing["member_no"] != '' ? " and member_no = :member_no" : null).
											(isset($dataComing["memb_name"]) && $dataComing["memb_name"] != '' ? " and TRIM(memb_name) LIKE :memb_name" : null));
			$fetchAccount->execute($arrayExecute);
			while($rowUser = $fetchAccount->fetch(PDO::FETCH_ASSOC)){
				$arrUserAcount = array();
				$arrUserAcount["REF_MEMNO"] = $rowUser["member_no"];
				$arrUserAcount["PRENAME_DESC"] = $rowUser["prename_desc"];
				$arrUserAcount["PRENAME_EDESC"] = $rowUser["prename_edesc"];
				$arrUserAcount["MEMB_NAME"] = $rowUser["memb_name"];
				$arrUserAcount["NAME_COOP"] = $rowUser["prename_desc"].$rowUser["memb_name"].' '.$rowUser["suffname_desc"];
				$arrUserAcount["MEMB_ENAME"] = $rowUser["memb_ename"];
				$arrUserAcount["REMARK"] = $rowUser["remark"];
				$arrUserAcount["SERVICE_STATUS"] = $rowUser["service_status"];
				$arrUserAcount["SUFFNAME_DESC"] = $rowUser["suffname_desc"];
				$arrUserAcount["CHECK_REGIS"] = true;
					
				$fetchContractTypeCheck = $conmysql->prepare("SELECT BALANCE_STATUS FROM gcconstantbalanceconfirm WHERE member_no = :member_no");
				$fetchContractTypeCheck->execute([':member_no' => $dataComing["member_no"]]);
				$rowContractnoCheck = $fetchContractTypeCheck->fetch(PDO::FETCH_ASSOC);
				$arrUserAcount["BALANCE_STATUS"] = $rowContractnoCheck["BALANCE_STATUS"] ?? 0;
				
				$arrRegisterCoop[] = $rowUser["member_no"];
				$arrayAccount[] = $arrUserAcount;
			}
		
			$fetchRegister = $conoracle->prepare("select mb.member_no,
                            mp.prename_desc,
                            mp.prename_edesc,
							mp.suffname_desc,
                            mb.memb_name,
                            mb.memb_ename,
							mb.sector_id
                            from mbmembmaster mb , mbucfprename mp 
                            where mb.resign_status = 0
							AND mb.prename_code = mp.prename_code
							".(count($arrRegisterCoop) > 0 ? ("and mb.member_no NOT IN(".implode(',',$arrRegisterCoop).")") : null)."
                            and 1=1".(isset($dataComing["member_no"]) && $dataComing["member_no"] != '' ? " and mb.member_no = :member_no " : null).
							(isset($dataComing["memb_name"]) && $dataComing["memb_name"] != '' ? " and TRIM(mb.memb_name) LIKE :memb_name" : null));
			$fetchRegister->execute($arrayExecute);
			while($rowRegis = $fetchRegister->fetch(PDO::FETCH_ASSOC)){
				$arrGroupRegis = array();
				$arrGroupRegis["REF_MEMNO"] = $rowRegis["MEMBER_NO"];
				$arrGroupRegis["PRENAME_DESC"] = $rowRegis["PRENAME_DESC"];
				$arrGroupRegis["PRENAME_EDESC"] = $rowRegis["PRENAME_EDESC"];
				$arrGroupRegis["SUFFNAME_DESC"] = $rowRegis["SUFFNAME_DESC"];
				$arrGroupRegis["MEMB_NAME"] = $rowRegis["MEMB_NAME"];
				$arrGroupRegis["NAME_COOP"] = $rowRegis["PRENAME_DESC"].$rowRegis["MEMB_NAME"].' '.$rowRegis["SUFFNAME_DESC"];
				$arrGroupRegis["MEMB_ENAME"] = $rowRegis["MEMB_ENAME"];
				$arrGroupRegis["SECTOR_ID"] = $rowRegis["SECTOR_ID"];
				$arrGroupRegis["CHECK_REGIS"] = false;
				$arrayAccount[] = $arrGroupRegis;
			}
			$arrayResult["REGISTER_DATA"] = $arrayAccount;	
			$arrayResult["arrRegisterCoop"]	 = $arrUserAcount["BALANCE_STATUS"];
			if(count($arrayAccount) == 0){
				$arrayResult['RESPONSE'] = "ไม่พบสหกรณ์ กรุณาลงทะเบียน";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
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