<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','reportmembereditdata')){
		$arrayGroup = array();
		$fetchUserAccount = $conmssql->prepare("SELECT id_editdata, member_no, edit_date, old_data, incoming_data,update_date, inputgroup_type 
											FROM gcmembereditdata WHERE is_updateoncore = '0'");
		$fetchUserAccount->execute();
		while($rowUser = $fetchUserAccount->fetch(PDO::FETCH_ASSOC)){
			$arrGroupUserAcount = array();
			$arrGroupUserAcount["ID_EDITDATA"] = $rowUser["id_editdata"];
			$arrGroupUserAcount["MEMBER_NO"] = $rowUser["member_no"];
			$arrGroupUserAcount["EDIT_DATE"] = $lib->convertdate($rowUser["edit_date"],'d m Y H-i-s',true);
			$arrGroupUserAcount["OLD_DATA_JSON"] = $rowUser["old_data"];
			if($rowUser["inputgroup_type"] == 'address'){
				$dataRawAddr = json_decode(($rowUser["incoming_data"]), true);
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
				$arrGroupUserAcount["INCOMING_DATA_JSON"] = $address;
			}else{
				$arrGroupUserAcount["INCOMING_DATA_JSON"] = $rowUser["incoming_data"];
			}
			$arrGroupUserAcount["UPDATE_DATE"] = $lib->convertdate($rowUser["update_date"],'d m Y H-i-s',true);
			$arrGroupUserAcount["INPUTGROUP_TYPE"] = $rowUser["inputgroup_type"];
			$arrayGroup[] = $arrGroupUserAcount;
		}
		$arrayResult["MEMBER_EDIT_DATA"] = $arrayGroup;
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
