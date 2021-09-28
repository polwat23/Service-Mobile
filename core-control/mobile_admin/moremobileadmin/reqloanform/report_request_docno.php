<?php
require_once('../../../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','loanrequestform')){
		$arrData = array();
		if(isset($dataComing["reqdoc_no"]) && isset($dataComing["reqdoc_no"]) != ""){
			$getAllReqDocno = $conmysql->prepare("SELECT reqloan_doc, member_no, loantype_code, request_amt, period_payment, 
												period, req_status, loanpermit_amt, diff_old_contract, receive_net, int_rate_at_req, salary_at_req, 
												request_date, salary_img, citizen_img, remark, deptaccount_no_bank, bank_desc, objective
												FROM gcreqloan WHERE reqloan_doc = :reqdoc_no");		
			$getAllReqDocno->execute([
						':reqdoc_no' => $dataComing["reqdoc_no"]
			]);
			$rowDocno = $getAllReqDocno->fetch(PDO::FETCH_ASSOC);
			if($getAllReqDocno->rowCount() > 0){
				$member_no = $rowDocno["member_no"];
				$fetchPrefix = $conoracle->prepare("SELECT prefix, loantype_desc FROM lnloantype where loantype_code = :loantype_code");
				$fetchPrefix->execute([
					':loantype_code' => $rowDocno["loantype_code"] 
				]);
				$rowPrefix = $fetchPrefix->fetch(PDO::FETCH_ASSOC);
				$fetchData = $conoracle->prepare("SELECT mp.prename_desc,mb.memb_name,mb.memb_surname,mb.birth_date,mb.card_person,mb.salary_amount,
													mb.member_date,mb.position_desc,mg.membgroup_desc,mt.membtype_desc,mb.membgroup_code,
													mb.ADDR_NO as ADDR_NO,
													mb.ADDR_MOO as ADDR_MOO,
													mb.ADDR_SOI as ADDR_SOI,
													mb.ADDR_VILLAGE as ADDR_VILLAGE,
													mb.ADDR_ROAD as ADDR_ROAD,
													MBT.TAMBOL_DESC AS TAMBOL_DESC,
													MBD.DISTRICT_DESC AS DISTRICT_DESC,
													MB.PROVINCE_CODE AS PROVINCE_CODE,
													MBP.PROVINCE_DESC AS PROVINCE_DESC,
													MB.ADDR_POSTCODE AS ADDR_POSTCODE,(sh.sharestk_amt * 10) AS SHAREBEGIN_AMT,sh.sharestk_amt,(sh.periodshare_amt * 10) as periodshare_amt,
													mb.addr_email as email,mb.addr_phone as MEM_TELMOBILE, mariage_status
													FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													LEFT JOIN MBUCFMEMBGROUP mg ON mb.MEMBGROUP_CODE = mg.MEMBGROUP_CODE
													LEFT JOIN MBUCFMEMBTYPE mt ON mb.MEMBTYPE_CODE = mt.MEMBTYPE_CODE
													LEFT JOIN MBUCFTAMBOL MBT ON mb.TAMBOL_CODE = MBT.TAMBOL_CODE
													LEFT JOIN MBUCFDISTRICT MBD ON mb.AMPHUR_CODE = MBD.DISTRICT_CODE
													LEFT JOIN MBUCFPROVINCE MBP ON mb.PROVINCE_CODE = MBP.PROVINCE_CODE
													LEFT JOIN shsharemaster sh ON mb.member_no = sh.member_no
													WHERE mb.member_no = :member_no");
				$fetchData->execute([
					':member_no' => $member_no
				]);
				$rowData = $fetchData->fetch(PDO::FETCH_ASSOC);
				
				$memberInfoMobile = $conmysql->prepare("SELECT phone_number,email FROM gcmemberaccount WHERE member_no = :member_no");
				$memberInfoMobile->execute([':member_no' => $member_no]);
				$rowInfoMobile = $memberInfoMobile->fetch(PDO::FETCH_ASSOC);
		
				$rowGroupAddr = [];
				if(isset($rowData["MEMBGROUP_CODE"]) && $rowData["MEMBGROUP_CODE"] != ""){
					$fetchGroupAddr = $conoracle->prepare("select 
													mb.ADDR_PHONE,
													MBT.TAMBOL_DESC AS TAMBOL_DESC,
													MBD.DISTRICT_DESC AS DISTRICT_DESC,
													MBP.PROVINCE_DESC AS PROVINCE_DESC from MBUCFMEMBGROUP mb
													LEFT JOIN MBUCFTAMBOL MBT ON mb.ADDR_TAMBOL = MBT.TAMBOL_CODE
													LEFT JOIN MBUCFDISTRICT MBD ON mb.ADDR_AMPHUR = MBD.DISTRICT_CODE
													LEFT JOIN MBUCFPROVINCE MBP ON mb.ADDR_PROVINCE = MBP.PROVINCE_CODE
													where MEMBGROUP_CODE = :membgroup_code");
					$fetchGroupAddr->execute([
						':membgroup_code' => $rowData["MEMBGROUP_CODE"]
					]);
					$rowGroupAddr = $fetchGroupAddr->fetch(PDO::FETCH_ASSOC);
				}
				
				$rowMate = [];
				if(isset($rowData["MARIAGE_STATUS"]) && $rowData["MARIAGE_STATUS"] == "1"){
					$fetchMate = $conoracle->prepare("SELECT 
													mb.mateaddr_no as ADDR_NO,
													mb.mateaddr_moo as ADDR_MOO,
													mb.mateaddr_soi as ADDR_SOI,
													mb.mateaddr_village as ADDR_VILLAGE,
													mb.mateaddr_road as ADDR_ROAD,
													MBT.TAMBOL_DESC AS TAMBOL_DESC,
													MBD.DISTRICT_DESC AS DISTRICT_DESC,
													MB.MATEPROVINCE_CODE AS PROVINCE_CODE,
													MBP.PROVINCE_DESC AS PROVINCE_DESC,
													MB.MATEADDR_POSTCODE AS ADDR_POSTCODE,
													mate_name,mate_cardperson,mateaddr_phone
													FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													LEFT JOIN MBUCFTAMBOL MBT ON mb.MATETAMBOL_CODE = MBT.TAMBOL_CODE
													LEFT JOIN MBUCFDISTRICT MBD ON mb.MATEAMPHUR_CODE = MBD.DISTRICT_CODE
													LEFT JOIN MBUCFPROVINCE MBP ON mb.MATEPROVINCE_CODE = MBP.PROVINCE_CODE
													WHERE mb.member_no = :member_no");
					$fetchMate->execute([
						':member_no' => $member_no
					]);
					$rowMate = $fetchMate->fetch(PDO::FETCH_ASSOC);
				}
				$arrData = array();
				$arrData["birth_date"] = $lib->convertdate($rowData["BIRTH_DATE"],"D M Y");
				$arrData["birth_date_raw"] = $rowData["BIRTH_DATE"];
				$arrData["requestdoc_no"] = $rowDocno["reqloan_doc"];
				$arrData["loan_prefix"] = $rowPrefix["PREFIX"];
				$arrData["loantype_desc"] = $rowPrefix["LOANTYPE_DESC"];
				$arrData["full_name"] = $rowData["PRENAME_DESC"].$rowData["MEMB_NAME"].' '.$rowData["MEMB_SURNAME"];
				$arrData["name"] = $rowData["MEMB_NAME"].' '.$rowData["MEMB_SURNAME"];
				$arrData["member_no"] = $member_no;
				$arrData["position"] = $rowData["POSITION_DESC"];
				$arrData["pos_group"] = $rowData["MEMBGROUP_DESC"];
				$arrData["pos_group_code"] = $rowData["MEMBGROUP_CODE"];
				
				$arrData["group_district_desc"] = $rowGroupAddr["DISTRICT_DESC"];
				$arrData["group_tambol_desc"] = $rowGroupAddr["TAMBOL_DESC"];
				$arrData["group_province_desc"] = $rowGroupAddr["PROVINCE_DESC"];
				$arrData["group_phone"] = $rowGroupAddr["ADDR_PHONE"];
				
				$arrData["district_desc"] = $rowData["DISTRICT_DESC"];
				$arrData["tambol_desc"] = $rowData["TAMBOL_DESC"];
				$arrData["province_desc"] = $rowData["PROVINCE_DESC"];
				$arrData["addr_moo"] = $rowData["ADDR_MOO"];
				$arrData["addr_road"] = $rowData["ADDR_ROAD"];
				$arrData["addr_no"] = $rowData["ADDR_NO"];
				$arrData["mem_telmobile"] = $rowInfoMobile["phone_number"];
				$arrData["card_person"] = $rowData["CARD_PERSON"];
				
				$arrData["mate_name"] = $rowMate["MATE_NAME"];
				$arrData["mateaddr_no"] = $rowMate["ADDR_NO"];
				$arrData["mateaddr_moo"] = $rowMate["ADDR_MOO"];
				$arrData["mateaddr_soi"] = $rowMate["ADDR_SOI"];
				$arrData["mateaddr_village"] = $rowMate["ADDR_VILLAGE"];
				$arrData["mateaddr_road"] = $rowMate["ADDR_ROAD"];
				$arrData["matetambol_desc"] = $rowMate["TAMBOL_DESC"];
				$arrData["matedistrict_desc"] = $rowMate["DISTRICT_DESC"];
				$arrData["mateprovince_desc"] = $rowMate["PROVINCE_DESC"];
				$arrData["mate_cardperson"] = $rowMate["MATE_CARDPERSON"];
				$arrData["mateaddr_phone"] = $rowMate["MATEADDR_PHONE"];
				
				$arrData["salary_amount"] = number_format($rowData["SALARY_AMOUNT"],2);
				$arrData["share_bf"] = number_format($rowData["SHAREBEGIN_AMT"],2);
				$arrData["sharestk_amt"] = number_format($rowData["SHARESTK_AMT"],2);
				$arrData["periodshare_amt"] = number_format($rowData["PERIODSHARE_AMT"],2);
				
				$fetchEmerLoan = $conoracle->prepare("SELECT LM.STARTCONT_DATE,LM.LOANCONTRACT_NO,LM.PRINCIPAL_BALANCE,LT.LOANTYPE_CODE,LT.LOANTYPE_DESC,LT.LOANGROUP_CODE
												FROM LNCONTMASTER LM 
												JOIN LNLOANTYPE LT ON LT.LOANTYPE_CODE = LM.LOANTYPE_CODE 
												WHERE LM.MEMBER_NO = :member_no 
												AND LM.CONTRACT_STATUS > 0 AND LM.CONTRACT_STATUS <> 8");
				$fetchEmerLoan->execute([
					':member_no' => $member_no
				]);
				$arrData["emer_contract"] = array();
				$arrData["common_contract"] = array();
				while($rowEmerLoan = $fetchEmerLoan->fetch(PDO::FETCH_ASSOC)){
					if($rowEmerLoan["LOANGROUP_CODE"] == '01'){
						$tempArr = array();
						$tempArr["STARTCONT_DATE"] = $lib->convertdate($rowEmerLoan["STARTCONT_DATE"],"D m Y");
						$tempArr["LOANCONTRACT_NO"] = $rowEmerLoan["LOANCONTRACT_NO"];
						$tempArr["PRINCIPAL_BALANCE"] = number_format($rowEmerLoan["PRINCIPAL_BALANCE"],2);
						$arrData["emer_contract"][] = $tempArr;
					}else{
						$tempArr = array();
						$tempArr["STARTCONT_DATE"] = $lib->convertdate($rowEmerLoan["STARTCONT_DATE"],"D m Y");
						$tempArr["LOANCONTRACT_NO"] = $rowEmerLoan["LOANCONTRACT_NO"];
						$tempArr["PRINCIPAL_BALANCE"] = number_format($rowEmerLoan["PRINCIPAL_BALANCE"],2);
						$arrData["common_contract"][] = $tempArr;
					}
				}
				
				$fetchGuarantee = $conoracle->prepare("SELECT
										LCC.LOANCONTRACT_NO AS LOANCONTRACT_NO,
										LNTYPE.loantype_desc as TYPE_DESC,
										PRE.PRENAME_DESC,MEMB.MEMB_NAME,MEMB.MEMB_SURNAME,
										LCM.MEMBER_NO AS MEMBER_NO,
										NVL(LCM.LOANAPPROVE_AMT,0) as LOANAPPROVE_AMT,
										NVL(LCM.principal_balance,0) as LOAN_BALANCE
										FROM
										LNCONTCOLL LCC LEFT JOIN LNCONTMASTER LCM ON  LCC.LOANCONTRACT_NO = LCM.LOANCONTRACT_NO
										LEFT JOIN MBMEMBMASTER MEMB ON LCM.MEMBER_NO = MEMB.MEMBER_NO
										LEFT JOIN MBUCFPRENAME PRE ON MEMB.PRENAME_CODE = PRE.PRENAME_CODE
										LEFT JOIN lnloantype LNTYPE  ON LCM.loantype_code = LNTYPE.loantype_code
										WHERE
										LCM.CONTRACT_STATUS > 0 and LCM.CONTRACT_STATUS <> 8
										AND LCC.LOANCOLLTYPE_CODE = '01'
										AND LCC.REF_COLLNO = :member_no");
				$fetchGuarantee->execute([
					':member_no' => $member_no
				]);
				$arrData["guarantee"] = array();
				while($rowGuarantee = $fetchGuarantee->fetch(PDO::FETCH_ASSOC)){
						$tempArr = array();
						$tempArr["LOANCONTRACT_NO"] = $rowGuarantee["LOANCONTRACT_NO"];
						$tempArr["MEMBER_NO"] = $rowGuarantee["MEMBER_NO"];
						$arrData["guarantee"][] = $tempArr;
				}
				$i = sizeof($arrData["guarantee"]);
				while($i <= 3) {
					$tempArr = array();
					$tempArr["LOANCONTRACT_NO"] = '';
					$tempArr["MEMBER_NO"] = '';
					$arrData["guarantee"][] = $tempArr;
					$i++;
				}
				
				$arrData["request_amt"] = $rowDocno["request_amt"];
				$arrData["period_payment"] = $rowDocno["period_payment"];
				$arrData["period"] = $rowDocno["period"];
				$arrData["recv_account"] = $deptaccount_no_bank;
				
				$pathFile = $config["URL_SERVICE"].'/resource/pdf/request_loan/'.$rowDocno["reqloan_doc"].'.pdf?v='.time();
				if($rowDocno["loantype_code"] == '23'){
					if(file_exists('form_request_loan_'.$rowDocno["loantype_code"].'.php')){
						include('form_request_loan_'.$rowDocno["loantype_code"].'.php');
						$arrayPDF = GeneratePDFContract($arrData,$lib);
						$updatepathFile = $conmysql->prepare("UPDATE gcreqloan SET contractdoc_url = :pathFile
										WHERE reqloan_doc = :reqloan_doc");
						if($updatepathFile->execute([
								':pathFile' => $pathFile,
								':reqloan_doc' => $rowDocno["reqloan_doc"]
						])){

						}else{
							$arrayResult['RESULT'] = FALSE;
							require_once('../../../../include/exit_footer.php');
						}
					}else{
						$arrayPDF["RESULT"] = FALSE;
					}
				}else{
					if(file_exists('form_request_loan_02.php')){
						include('form_request_loan_02.php');
						$arrayPDF = GeneratePDFContract($arrData,$lib);
						$updatepathFile = $conmysql->prepare("UPDATE gcreqloan SET contractdoc_url = :pathFile
										WHERE reqloan_doc = :reqloan_doc");
						if($updatepathFile->execute([
								':pathFile' => $pathFile,
								':reqloan_doc' => $rowDocno["reqloan_doc"]
						])){

						}else{
							$arrayResult['RESULT'] = FALSE;
							require_once('../../../../include/exit_footer.php');
						}
					}else{
						$arrayPDF["RESULT"] = FALSE;
					}
				}
				
				if($arrayPDF["RESULT"]){
					$arrayResult['RESULT'] = TRUE;
					$arrayResult['PATH'] = $config["URL_SERVICE"].$arrayPDF["PATH"];
					require_once('../../../../include/exit_footer.php');
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS1038";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');	
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS1038";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');	
			}
		}else{
			$arrayResult['RESULT'] = FALSE;
			http_response_code(204);
			require_once('../../../../include/exit_footer.php');
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
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
	require_once('../../../../include/exit_footer.php');
}
?>