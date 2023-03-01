<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'MemberInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? TRIM($payload["member_no"]);
		$memberInfoMobile = $conmysql->prepare("SELECT phone_number,email,path_avatar,member_no FROM gcmemberaccount WHERE member_no = :member_no");
		$memberInfoMobile->execute([':member_no' => $payload["member_no"]]);
		if($memberInfoMobile->rowCount() > 0){
			
			$rowInfoMobile = $memberInfoMobile->fetch(PDO::FETCH_ASSOC);
			$arrayResult["PHONE"] = $lib->formatphone($rowInfoMobile["phone_number"]);
			$arrayResult["EMAIL"] = $rowInfoMobile["email"];
			if(isset($rowInfoMobile["path_avatar"])){
				if ($forceNewSecurity == true) {
					$arrayResult['AVATAR_PATH'] = $config["URL_SERVICE"]."/resource/get_resource?id=".hash("sha256", $rowInfoMobile["path_avatar"]);
					$arrayResult["AVATAR_PATH_TOKEN"] = $lib->generate_token_access_resource($rowInfoMobile["path_avatar"], $jwt_token, $config["SECRET_KEY_JWT"]);
					
					$explodePathAvatar = explode('.',$rowInfoMobile["path_avatar"]);
					$arrayResult["AVATAR_PATH_WEBP"] = $config["URL_SERVICE"]."/resource/get_resource?id=".hash("sha256", $explodePathAvatar[0].'.webp');
					$arrayResult["AVATAR_PATH_WEBP_TOKEN"] = $lib->generate_token_access_resource($explodePathAvatar[0].'.webp', $jwt_token, $config["SECRET_KEY_JWT"]);
				} else {
					$arrayResult["AVATAR_PATH"] = $config["URL_SERVICE"].$rowInfoMobile["path_avatar"];
					$explodePathAvatar = explode('.',$rowInfoMobile["path_avatar"]);
					$arrayResult["AVATAR_PATH_WEBP"] = $config["URL_SERVICE"].$explodePathAvatar[0].'.webp';
				}
			}else{
				$arrayResult["AVATAR_PATH"] = null;
				$arrayResult["AVATAR_PATH_WEBP"] = null;
			}
			/*
			$gclogintoken = $conoracle->prepare("delete from gclogintoken where member_no=:member_no ");
			$gclogintoken->execute([':member_no' => $member_no]);
			$gclogintoken = $conoracle->prepare("insert into gclogintoken(member_no,refresh_token,unique_id,update_date)values(:member_no ,:refresh_token,:unique_id,sysdate");
			$gclogintoken->execute([
				':member_no' => $member_no,
				':refresh_token' => $dataComing["refresh_token"],
				':unique_id' => $dataComing["unique_id"]
				]);
			$gclogintoken = $conoracle->prepare("commit");	
			$gclogintoken->execute();	
		    file_put_contents( "c:\\WINDOWS\\TEMP\\in.log", print_r($dataComing, true));
		    */
		
			$memberInfo = $conoracle->prepare("SELECT mp.prename_short,
													mb.memb_name as memb_name,
													mb.memb_surname,
													mb.birth_date,mb.card_person,
													mb.member_date,mb.position_desc,mt.membtype_desc,
													mb.MEMB_ADDR as ADDR_NO,
													mb.ADDR_GROUP as ADDR_MOO,
													mb.SOI as ADDR_SOI,
													mb.MOOBAN as ADDR_VILLAGE,
													mb.ROAD as ADDR_ROAD,
													MB.TAMBOL AS TAMBOL_DESC,
													MBD.DISTRICT_DESC AS DISTRICT_DESC,
													MB.PROVINCE_CODE AS PROVINCE_CODE,
													MBP.PROVINCE_DESC AS PROVINCE_DESC,
													MB.POSTCODE AS ADDR_POSTCODE,
													mb.MEMBGROUP_CODE
													FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													LEFT JOIN MBUCFMEMBTYPE mt ON mb.MEMBTYPE_CODE = mt.MEMBTYPE_CODE
													LEFT JOIN MBUCFDISTRICT MBD ON mb.DISTRICT_CODE = MBD.DISTRICT_CODE
													and mb.PROVINCE_CODE = MBD.PROVINCE_CODE
													LEFT JOIN MBUCFPROVINCE MBP ON mb.PROVINCE_CODE = MBP.PROVINCE_CODE
													WHERE TRIM(mb.member_no) = :member_no");
			$memberInfo->execute([':member_no' => $member_no]);
			$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
			
			/*
			$root="c:\\WINDOWS\\TEMP\\";
			$now = DateTime::createFromFormat('U.u', microtime(true));
			$ID=$now->format('Y-m-d-h-i-s-u');
			$iconv_path="\"C:\\Program Files\\gettext-iconv\\bin\\iconv\"";
			$filename=($root.$ID.".txt");
			$filename_=($root.$ID."_.txt");
			$filename_bat=$filename.".bat";
			file_put_contents( $filename, print_r($rowMember, true));
			file_put_contents( $filename_bat,($iconv_path." -f tis-620 -t utf-8 ".$filename.">".$filename_.""));
			exec("c:\\WINDOWS\\system32\\cmd.exe /c \"".$filename_bat."\" ");
			$rowMember=parseOraDataBufferToArray($filename_);//autoloadConnection.php
			//unlink($filename);
			//unlink($filename_);
			//unlink($filename_bat);
			*/
			
			convertArray($rowMember,true);
			
			$sqlGetMembGrp = $conoracle->prepare("SELECT (B.MEMBGROUP_DESC || ' / ' || A.MEMBGROUP_DESC ) AS MEMBGROUP_CODE_STR 
												FROM MBUCFMEMBGROUP A LEFT JOIN MBUCFMEMBGROUP B ON A.MEMBGROUP_CONTROL = B.MEMBGROUP_CODE 
												WHERE A.MEMBGROUP_CODE = :MEMBGRP");
			$sqlGetMembGrp->execute([':MEMBGRP' => $rowMember["MEMBGROUP_CODE"]]);
			$rowMembGrp = $sqlGetMembGrp->fetch(PDO::FETCH_ASSOC);
			$getRecvAcc = $conoracle->prepare("SELECT
													cmt.MONEYTYPE_DESC as DIVPAYTYPE_DESC,
													cmt.MONEYTYPE_CODE as DIVPAYTYPE_CODE,
													mx.DIVIDEND_ACCID as BANK_ACCID,
													cb.BANK_DESC,
													cmbb.BRANCH_NAME
												FROM 
													MBMEMBMASTER MB LEFT JOIN mbmembexpense mx on mb.member_no = mx.member_no
													LEFT JOIN cmucfbank cb ON mx.DIVIDEND_BANK = cb.bank_code LEFT JOIN cmucfbankbranch cmbb ON 
													mx.DIVIDEND_BRANCH = cmbb.branch_id and
													mx.DIVIDEND_BANK = cmbb.bank_code
													LEFT JOIN cmucfmoneytype cmt ON mx.DIVIDEND_CODE = cmt.MONEYTYPE_CODE
												WHERE TRIM(MB.MEMBER_NO) = :member_no");
			$getRecvAcc->execute([':member_no' => $member_no]);
			$rowRecvAcc = $getRecvAcc->fetch(PDO::FETCH_ASSOC);
			
			convertArray($rowRecvAcc,true);
			
			$address = (isset($rowMember["ADDR_NO"]) ? $rowMember["ADDR_NO"] : null);
			if(isset($rowMember["PROVINCE_CODE"]) && $rowMember["PROVINCE_CODE"] == '10'){
				$address .= (isset($rowMember["ADDR_MOO"]) ? ' ม.'.$rowMember["ADDR_MOO"] : null);
				$address .= (isset($rowMember["ADDR_SOI"]) ? ' ซอย'.$rowMember["ADDR_SOI"] : null);
				$address .= (isset($rowMember["ADDR_VILLAGE"]) ? ' หมู่บ้าน'.$rowMember["ADDR_VILLAGE"] : null);
				$address .= (isset($rowMember["ADDR_ROAD"]) ? ' ถนน'.$rowMember["ADDR_ROAD"] : null);
				$address .= (isset($rowMember["TAMBOL_DESC"]) ? ' แขวง'.$rowMember["TAMBOL_DESC"] : null);
				$address .= (isset($rowMember["DISTRICT_DESC"]) ? ' เขต'.$rowMember["DISTRICT_DESC"] : null);
				$address .= (isset($rowMember["PROVINCE_DESC"]) ? ' '.$rowMember["PROVINCE_DESC"] : null);
				$address .= (isset($rowMember["ADDR_POSTCODE"]) ? ' '.$rowMember["ADDR_POSTCODE"] : null);
			}else{
				$address .= (isset($rowMember["ADDR_MOO"]) ? ' ม.'.$rowMember["ADDR_MOO"] : null);
				$address .= (isset($rowMember["ADDR_SOI"]) ? ' ซอย'.$rowMember["ADDR_SOI"] : null);
				$address .= (isset($rowMember["ADDR_VILLAGE"]) ? ' หมู่บ้าน'.$rowMember["ADDR_VILLAGE"] : null);
				$address .= (isset($rowMember["ADDR_ROAD"]) ? ' ถนน'.$rowMember["ADDR_ROAD"] : null);
				$address .= (isset($rowMember["TAMBOL_DESC"]) ? ' ต.'.$rowMember["TAMBOL_DESC"] : null);
				$address .= (isset($rowMember["DISTRICT_DESC"]) ? ' อ.'.$rowMember["DISTRICT_DESC"] : null);
				$address .= (isset($rowMember["PROVINCE_DESC"]) ? ' จ.'.$rowMember["PROVINCE_DESC"] : null);
				$address .= (isset($rowMember["ADDR_POSTCODE"]) ? ' '.$rowMember["ADDR_POSTCODE"] : null);
			}
			$getAddress = $conoracle->prepare("SELECT MBA.MEMB_ADDRC AS ADDR_NO,
											MBA.TAMBOLC AS TAMBOL_DESC,
											MBD.DISTRICT_DESC,
											MBP.PROVINCE_DESC,
											MBA.POSTCODEC AS ADDR_POSTCODE,
											MBA.SOIC as ADDR_SOI,
											MBA.ADDR_GROUPC as ADDR_MOO,
											MBA.MOOBANC as ADDR_VILLAGE,
											MBA.ROADC as ADDR_ROAD,
											MBA.PROVINCE_CODEC as PROVINCE_CODE
											FROM MBADDRESS MBA LEFT JOIN MBUCFDISTRICT MBD ON MBA.DISTICT_CODEC = MBD.DISTRICT_CODE
											and MBA.PROVINCE_CODEC = MBD.PROVINCE_CODE
											LEFT JOIN MBUCFPROVINCE MBP ON MBA.PROVINCE_CODEC = MBP.PROVINCE_CODE
											WHERE TRIM(MBA.MEMBER_NO) = :member_no");
			$getAddress->execute([':member_no' => $member_no]);
			$rowAddress = $getAddress->fetch(PDO::FETCH_ASSOC);
			
			convertArray($rowAddress,true);
			
			$addressCurr = (isset($rowAddress["ADDR_NO"]) ? $rowAddress["ADDR_NO"] : null);
			if(isset($rowAddress["PROVINCE_CODE"]) && $rowAddress["PROVINCE_CODE"] == '10'){
				$addressCurr .= (isset($rowAddress["ADDR_MOO"]) ? ' ม.'.$rowAddress["ADDR_MOO"] : null);
				$addressCurr .= (isset($rowAddress["ADDR_SOI"]) ? ' ซอย'.$rowAddress["ADDR_SOI"] : null);
				$addressCurr .= (isset($rowAddress["ADDR_VILLAGE"]) ? ' หมู่บ้าน'.$rowAddress["ADDR_VILLAGE"] : null);
				$addressCurr .= (isset($rowAddress["ADDR_ROAD"]) ? ' ถนน'.$rowAddress["ADDR_ROAD"] : null);
				$addressCurr .= (isset($rowAddress["TAMBOL_DESC"]) ? ' แขวง'.$rowAddress["TAMBOL_DESC"] : null);
				$addressCurr .= (isset($rowAddress["DISTRICT_DESC"]) ? ' เขต'.$rowAddress["DISTRICT_DESC"] : null);
				$addressCurr .= (isset($rowAddress["PROVINCE_DESC"]) ? ' '.$rowAddress["PROVINCE_DESC"] : null);
				$addressCurr .= (isset($rowAddress["ADDR_POSTCODE"]) ? ' '.$rowAddress["ADDR_POSTCODE"] : null);
			}else{
				$addressCurr .= (isset($rowAddress["ADDR_MOO"]) ? ' ม.'.$rowAddress["ADDR_MOO"] : null);
				$addressCurr .= (isset($rowAddress["ADDR_SOI"]) ? ' ซอย'.$rowAddress["ADDR_SOI"] : null);
				$addressCurr .= (isset($rowAddress["ADDR_VILLAGE"]) ? ' หมู่บ้าน'.$rowAddress["ADDR_VILLAGE"] : null);
				$addressCurr .= (isset($rowAddress["ADDR_ROAD"]) ? ' ถนน'.$rowAddress["ADDR_ROAD"] : null);
				$addressCurr .= (isset($rowAddress["TAMBOL_DESC"]) ? ' ต.'.$rowAddress["TAMBOL_DESC"] : null);
				$addressCurr .= (isset($rowAddress["DISTRICT_DESC"]) ? ' อ.'.$rowAddress["DISTRICT_DESC"] : null);
				$addressCurr .= (isset($rowAddress["PROVINCE_DESC"]) ? ' จ.'.$rowAddress["PROVINCE_DESC"] : null);
				$addressCurr .= (isset($rowAddress["ADDR_POSTCODE"]) ? ' '.$rowAddress["ADDR_POSTCODE"] : null);
			}
			$arrayResult["PRENAME"] = $rowMember["PRENAME_SHORT"];
			$arrayResult["NAME"] = $rowMember["MEMB_NAME"];
			//$arrayResult["NAME"]  = mb_detect_encoding($arrayResult["NAME"]);
			//$arrayResult["NAME"] = mb_convert_encoding($arrayResult["NAME"], 'ISO-8859-1','utf-8'); 
			$arrayResult["SURNAME"] = $rowMember["MEMB_SURNAME"];
			$arrayResult["BIRTH_DATE"] = $lib->convertdate($rowMember["BIRTH_DATE"],"D m Y");
			$arrayResult["BIRTH_DATE_COUNT"] =  $lib->count_duration($rowMember["BIRTH_DATE"],"ym");
			$arrayResult["CARD_PERSON"] = $lib->formatcitizen($rowMember["CARD_PERSON"]);
			$arrayResult["MEMBER_DATE"] = $lib->convertdate($rowMember["MEMBER_DATE"],"D m Y");
			$arrayResult["MEMBER_DATE_COUNT"] = $lib->count_duration($rowMember["MEMBER_DATE"],"ym");
			$arrayResult["POSITION_DESC"] = $rowMember["POSITION_DESC"];
			$arrayResult["MEMBER_TYPE"] = $rowMember["MEMBTYPE_DESC"];
			$arrayResult["MEMBERGROUP_DESC"] = $rowMembGrp["MEMBGROUP_CODE_STR"];
			$arrayResult["FULL_ADDRESS_CURR"] = $addressCurr;
			$arrayResult["MEMBER_NO"] = $member_no;
			$arrayResult["RECEIVE_DIV"] = $rowRecvAcc["DIVPAYTYPE_CODE"] == 'TRN' ? 'บัญชีสหกรณ์ : '.$lib->formataccount($rowRecvAcc["BANK_ACCID"],$func->getConstant('dep_format'))
			: $rowRecvAcc["DIVPAYTYPE_DESC"].' '.$rowRecvAcc["BANK_DESC"].' '.$rowRecvAcc["BANK_ACCID"];
			$arrayResult["RESULT"] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0003";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
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