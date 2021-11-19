<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','arr_id_editdata'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','reportmembereditdata')){
		foreach($dataComing["arr_id_editdata"] as $id_edit){
			$fetchUserAccount = $conmssql->prepare("SELECT member_no, incoming_data, inputgroup_type 
												FROM gcmembereditdata WHERE id_editdata = :id_editdata");
			$fetchUserAccount->execute([
				':id_editdata' => $id_edit
			]);
			$rowUserAcc = $fetchUserAccount->fetch(PDO::FETCH_ASSOC);
		}
		if($rowUserAcc["inputgroup_type"] == 'tel'){
			$update_edit = $conmssqlcoop->prepare("EXEC sp_Mobile_UpdAddInf
													@Member_Id = ?,@Telephone = ?");
			$update_edit->bindParam(1, TRIM($rowUserAcc["member_no"]));
			$update_edit->bindParam(2, $rowUserAcc["incoming_data"]);
			if($update_edit->execute()){
				$arrayStruc = [
					':menu_name' => "reportmembereditdata",
					':username' => $payload["username"],
					':use_list' => "update member edit data",
					':details' => implode(',',$dataComing["arr_id_editdata"])
				];
				$log->writeLog('manageuser',$arrayStruc);
				$updateSuccess = $conmssql->prepare("UPDATE gcmembereditdata SET is_updateoncore = '1' WHERE id_editdata = :id_editdata");
				$updateSuccess->execute([':id_editdata' => $id_edit]);
				$arrayResult["RESULT"] = TRUE;
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขข้อมูลได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}else if($rowUserAcc["inputgroup_type"] == 'email'){
			$update_edit = $conmssqlcoop->prepare("EXEC sp_Mobile_UpdAddInf
													@Member_Id = ?,@Email = ?");
			$update_edit->bindParam(1, TRIM($rowUserAcc["member_no"]));
			$update_edit->bindParam(2, $rowUserAcc["incoming_data"]);
			if($update_edit->execute()){
				$arrayStruc = [
					':menu_name' => "reportmembereditdata",
					':username' => $payload["username"],
					':use_list' => "update member edit data",
					':details' => implode(',',$dataComing["arr_id_editdata"])
				];
				$log->writeLog('manageuser',$arrayStruc);
				$updateSuccess = $conmssql->prepare("UPDATE gcmembereditdata SET is_updateoncore = '1' WHERE id_editdata = :id_editdata");
				$updateSuccess->execute([':id_editdata' => $id_edit]);
				$arrayResult["RESULT"] = TRUE;
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขข้อมูลได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}else if($rowUserAcc["inputgroup_type"] == 'address'){
			$dataRawAddr = json_decode(($rowUserAcc["incoming_data"]), true);
			$address = (isset($dataRawAddr["addr_no"]) ? $dataRawAddr["addr_no"] : null);
			$getTambol = $conmssql->prepare("SELECT TAMBOL_DESC FROM MBUCFTAMBOL WHERE TAMBOL_CODE = :tambol_code");
			$getTambol->execute([':tambol_code' => $dataRawAddr["tambol_code"]]);
			$rowTambol = $getTambol->fetch(PDO::FETCH_ASSOC);
			$getDistrict = $conmssql->prepare("SELECT DISTRICT_DESC,POSTCODE FROM MBUCFDISTRICT WHERE DISTRICT_CODE = :district_code");
			$getDistrict->execute([':district_code' => $dataRawAddr["district_code"]]);
			$rowDistrict = $getDistrict->fetch(PDO::FETCH_ASSOC);
			$getProvince = $conmssql->prepare("SELECT PROVINCE_DESC FROM MBUCFPROVINCE WHERE DISTRICT_CODE = :province_code");
			$getProvince->execute([':province_code' => $dataRawAddr["province_code"]]);
			$rowProvince = $getProvince->fetch(PDO::FETCH_ASSOC);
			if(isset($dataRawAddr["province_code"]) && $dataRawAddr["province_code"] == '10'){
				$address .= (isset($dataRawAddr["addr_moo"]) ? ' ม.'.$dataRawAddr["addr_moo"] : null);
				$address .= (isset($dataRawAddr["addr_soi"]) ? ' ซอย'.$dataRawAddr["addr_soi"] : null);
				$address .= (isset($dataRawAddr["addr_village"]) ? ' หมู่บ้าน'.$dataRawAddr["addr_village"] : null);
				$address .= (isset($dataRawAddr["addr_road"]) ? ' ถนน'.$dataRawAddr["addr_road"] : null);
				$address .= (isset($rowTambol["TAMBOL_DESC"]) ? ' แขวง'.$rowTambol["TAMBOL_DESC"] : null);
				$address .= (isset($rowDistrict["DISTRICT_DESC"]) ? ' เขต'.$rowDistrict["DISTRICT_DESC"] : null);
				$address .= (isset($rowProvince["PROVINCE_DESC"]) ? ' '.$rowProvince["PROVINCE_DESC"] : null);
				$address .= (isset($dataRawAddr["addr_postcode"]) ? ' '.$dataRawAddr["addr_postcode"] : null);
			}else{
				$address .= (isset($dataRawAddr["addr_moo"]) ? ' ม.'.$dataRawAddr["addr_moo"] : null);
				$address .= (isset($dataRawAddrs["addr_soi"]) ? ' ซอย'.$dataRawAddr["addr_soi"] : null);
				$address .= (isset($dataRawAddr["addr_village"]) ? ' หมู่บ้าน'.$dataRawAddr["addr_village"] : null);
				$address .= (isset($dataRawAddr["addr_road"]) ? ' ถนน'.$dataRawAddr["addr_road"] : null);
				$address .= (isset($rowTambol["TAMBOL_DESC"]) ? ' ต.'.$rowTambol["TAMBOL_DESC"] : null);
				$address .= (isset($rowDistrict["DISTRICT_DESC"]) ? ' อ.'.$rowDistrict["DISTRICT_DESC"] : null);
				$address .= (isset($rowProvince["PROVINCE_DESC"]) ? ' จ.'.$rowProvince["PROVINCE_DESC"] : null);
				$address .= (isset($dataRawAddr["addr_postcode"]) ? ' '.$dataRawAddr["addr_postcode"] : null);
			}
			$update_edit = $conmssqlcoop->prepare("EXEC sp_Mobile_UpdAddInf
													@Member_Id = ?,@Address1 = ?");
			$update_edit->bindParam(1, TRIM($rowUserAcc["member_no"]));
			$update_edit->bindParam(2, $address);
			if($update_edit->execute()){
				$arrayStruc = [
					':menu_name' => "reportmembereditdata",
					':username' => $payload["username"],
					':use_list' => "update member edit data",
					':details' => implode(',',$dataComing["arr_id_editdata"])
				];
				$log->writeLog('manageuser',$arrayStruc);
				$updateSuccess = $conmssql->prepare("UPDATE gcmembereditdata SET is_updateoncore = '1' WHERE id_editdata = :id_editdata");
				$updateSuccess->execute([':id_editdata' => $id_edit]);
				$arrayResult["RESULT"] = TRUE;
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขข้อมูลได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}
		$arrayResult["RESULT"] = $rowUserAcc;
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
