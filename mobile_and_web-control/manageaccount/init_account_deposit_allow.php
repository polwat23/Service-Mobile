<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrDeptAllowed = array();
		$arrAccAllowed = array();
		$arrAllowAccGroup = array();
		
		/*$getDeptTypeAllow = $conmysql->prepare("SELECT dept_type_code FROM gcconstantaccountdept
												WHERE allow_withdraw_outside = '1' OR allow_withdraw_inside = '1' OR allow_deposit_outside = '1' OR allow_deposit_inside = '1'");
		$getDeptTypeAllow->execute();
		while($rowDeptAllow = $getDeptTypeAllow->fetch(PDO::FETCH_ASSOC)){
			$arrDeptAllowed[] = "'".$rowDeptAllow["dept_type_code"]."'";
		}*/
		$InitDeptAccountAllowed = $conmysql->prepare("SELECT deptaccount_no FROM gcuserallowacctransaction WHERE member_no = :member_no and is_use <> '-9'");
		$InitDeptAccountAllowed->execute([':member_no' => $payload["member_no"]]);
		while($rowAccountAllowed = $InitDeptAccountAllowed->fetch(PDO::FETCH_ASSOC)){
			$arrAccAllowed[] = $rowAccountAllowed["deptaccount_no"];
		}
		$getDeptAtm = $conoracle->prepare("SELECT COOP_ACC FROM atmdept WHERE member_no = :member_no");
		$getDeptAtm->execute([':member_no' => $member_no]);
		$rowDeptAtm = $getDeptAtm->fetch(PDO::FETCH_ASSOC);
		if(sizeof($arrAccAllowed) > 0){
			if(isset($rowDeptAtm["COOP_ACC"])){
				$getAccountAllinCoop = $conoracle->prepare("select (select concat(concat(m.memb_name,' '),m.memb_surname) from mbmembmaster m 
														where d.member_no = m.member_no ) as DEPTACCOUNT_NAME,DEPTACCOUNT_NO,d.DEPTTYPE_CODE,dt.DEPTTYPE_DESC,PRNCBAL
														from   atmdept a LEFT JOIN  dpdeptmaster d  ON d.member_no = a.member_no 
														AND  d.deptaccount_no = a.coop_acc 
														AND d.depttype_code = a.depttype_code
														LEFT JOIN dpdepttype dt ON  d.depttype_code = dt.depttype_code
														where d.depttype_code in ( '01') AND  trim(d.member_no) = :member_no
														and d.deptaccount_no NOT IN(".implode(',',$arrAccAllowed).")
														and d.deptclose_status= '0'
														order by deptaccount_no desc");
			}else{
				$getDeptmaster= $conoracle->prepare("SELECT  deptaccount_no FROM dpdeptmaster WHERE member_no = :member_no");	
				$getDeptmaster->execute([':member_no' => $member_no]);
				while($rowAccIncoop = $getDeptmaster->fetch(PDO::FETCH_ASSOC)){
					$arrDept[] = $rowAccIncoop["DEPTACCOUNT_NO"];
				}
						
				$getAccountAllinCoop = $conoracle->prepare("select (select concat(concat(m.memb_name,' '),m.memb_surname) from mbmembmaster m 
														where d.member_no = m.member_no ) as DEPTACCOUNT_NAME,DEPTACCOUNT_NO,d.DEPTTYPE_CODE,dt.DEPTTYPE_DESC,PRNCBAL
														from   dpdeptmaster d LEFT JOIN  atmdept a  ON d.member_no = a.member_no 
														AND  d.deptaccount_no = a.coop_acc 
														AND d.depttype_code = a.depttype_code
														LEFT JOIN dpdepttype dt ON  d.depttype_code = dt.depttype_code
														where d.depttype_code in ( '01')
														and d.deptaccount_no IN(".implode(',',$arrDept).")
														and d.deptclose_status= '0'
														order by deptaccount_no desc");
			}
		}else{
			if(isset($rowDeptAtm["COOP_ACC"])){
				$getAccountAllinCoop = $conoracle->prepare("select (select concat(concat(m.memb_name,' '),m.memb_surname) from mbmembmaster m 
														where d.member_no=m.member_no ) as DEPTACCOUNT_NAME,DEPTACCOUNT_NO,d.DEPTTYPE_CODE,dt.DEPTTYPE_DESC,PRNCBAL
														from   atmdept a LEFT JOIN  dpdeptmaster d  ON d.member_no = a.member_no 
														AND  d.deptaccount_no = a.coop_acc 
														AND d.depttype_code = a.depttype_code
														LEFT JOIN dpdepttype dt ON  d.depttype_code = dt.depttype_code
														where d.depttype_code in ( '01') AND trim(d.member_no) = :member_no
														and d.deptclose_status= '0'
														order by deptaccount_no desc");	
			}else{
				$getDeptmaster= $conoracle->prepare("SELECT  deptaccount_no FROM dpdeptmaster WHERE member_no = :member_no");	
				$getDeptmaster->execute([':member_no' => $member_no]);
				while($rowAccIncoop = $getDeptmaster->fetch(PDO::FETCH_ASSOC)){
					$arrDept[] = $rowAccIncoop["DEPTACCOUNT_NO"];
				}
				
				$getAccountAllinCoop = $conoracle->prepare("select (select concat(concat(m.memb_name,' '),m.memb_surname) from mbmembmaster m 
														where d.member_no = m.member_no ) as DEPTACCOUNT_NAME,DEPTACCOUNT_NO,d.DEPTTYPE_CODE,dt.DEPTTYPE_DESC,PRNCBAL
														from   dpdeptmaster d LEFT JOIN  atmdept a  ON d.member_no = a.member_no 
														AND  d.deptaccount_no = a.coop_acc 
														AND d.depttype_code = a.depttype_code
														LEFT JOIN dpdepttype dt ON  d.depttype_code = dt.depttype_code
														where d.depttype_code in ( '01')
														and d.deptaccount_no IN(".implode(',',$arrDept).")
														and d.deptclose_status= '0'
														order by deptaccount_no desc");	
			}	
		}
		$getAccountAllinCoop->execute([':member_no' => $member_no]);
		while($rowAccIncoop = $getAccountAllinCoop->fetch(PDO::FETCH_ASSOC)){
			$arrAccInCoop["DEPTACCOUNT_NO"] = $rowAccIncoop["DEPTACCOUNT_NO"];
			$arrAccInCoop["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowAccIncoop["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
			$arrAccInCoop["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($rowAccIncoop["DEPTACCOUNT_NO"],$func->getConstant('hidden_dep'));
			$arrAccInCoop["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',trim($rowAccIncoop["DEPTACCOUNT_NAME"]));
			$arrAccInCoop["DEPT_TYPE"] = $rowAccIncoop["DEPTTYPE_DESC"];
			$getIDDeptTypeAllow = $conmysql->prepare("SELECT id_accountconstant FROM gcconstantaccountdept
													WHERE dept_type_code = :depttype_code");
			$getIDDeptTypeAllow->execute([
				':depttype_code' => $rowAccIncoop["DEPTTYPE_CODE"]
			]);
			$rowIDDeptTypeAllow = $getIDDeptTypeAllow->fetch(PDO::FETCH_ASSOC);
			$arrAccInCoop["ID_ACCOUNTCONSTANT"] = $rowIDDeptTypeAllow["id_accountconstant"];
			$getDeptTypeAllow = $conmysql->prepare("SELECT allow_withdraw_outside,allow_withdraw_inside,allow_deposit_outside
																	FROM gcconstantaccountdept
																	WHERE dept_type_code = :depttype_code");
			$getDeptTypeAllow->execute([
				':depttype_code' => $rowAccIncoop["DEPTTYPE_CODE"]
			]);
			$rowDeptTypeAllow = $getDeptTypeAllow->fetch(PDO::FETCH_ASSOC);
			if(($rowDeptTypeAllow["allow_withdraw_outside"] == '0' && $rowDeptTypeAllow["allow_deposit_outside"] == '0') && 
			$rowDeptTypeAllow["allow_withdraw_inside"] == '1'){
				$arrAccInCoop["ALLOW_DESC"] = $configError['ALLOW_TRANS_INSIDE_FLAG_ON'][0][$lang_locale];
			}else if($rowDeptTypeAllow["allow_withdraw_outside"] == '1' || $rowDeptTypeAllow["allow_deposit_outside"] == '1'){
				$arrAccInCoop["ALLOW_DESC"] = $configError['ALLOW_TRANS_ALL_MENU'][0][$lang_locale];
			}else{
				$arrAccInCoop["FLAG_NAME"] = $configError['ACC_TRANS_FLAG_OFF'][0][$lang_locale];
			}
			if($rowDeptTypeAllow["allow_withdraw_inside"] == '0'){
				$arrAccInCoop["FLAG_NAME"] = $configError['ACC_TRANS_FLAG_OFF'][0][$lang_locale];
			}
			$arrAllowAccGroup[] = $arrAccInCoop;
		}
		$arrayResult['ACCOUNT_ALLOW'] = $arrAllowAccGroup;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
		
	}
}else{
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>