<?php
if($lineLib->checkBindAccount($user_id)){
	$data = $lineLib->getMemberNo($user_id);
	$themeColor = $lineLib->getLineConstant('theme_color');
	$member_no = $configAS[$data] ?? $data;
	
	$arrGroupAllLoan = array();
	
		$getWhocollu = $conoracle->prepare("SELECT lnm.principal_balance as PRNBAL,lnm.loancontract_no,NVL(lnm.loanapprove_amt,0) as APPROVE_AMT,lt.LOANTYPE_DESC as TYPE_DESC
											FROM lncontmaster lnm LEFT JOIN LNLOANTYPE lt ON lnm.LOANTYPE_CODE = lt.LOANTYPE_CODE WHERE lnm.member_no = :member_no
											and lnm.contract_status > 0 and lnm.contract_status <> 8
											GROUP BY lnm.loancontract_no,NVL(lnm.loanapprove_amt,0),lt.LOANTYPE_DESC,lnm.principal_balance");
		$getWhocollu->execute([':member_no' => $member_no]);
		while($rowWhocollu = $getWhocollu->fetch(PDO::FETCH_ASSOC)){
			$arrayGroupLoan = array();
			$arrayGroupLoan['APPROVE_AMT'] = number_format($rowWhocollu["APPROVE_AMT"],2);
			$arrayGroupLoan['TYPE_DESC'] = $rowWhocollu["TYPE_DESC"];
			$arrayGroupLoan["CONTRACT_NO"] = $rowWhocollu["LOANCONTRACT_NO"];
			$arrGrpAllLoan = array();
			$getCollDetail = $conoracle->prepare("SELECT DISTINCT lnc.LOANCOLLTYPE_CODE,llc.LOANCOLLTYPE_DESC,lnc.REF_COLLNO,lnc.COLL_PERCENT,
																lnc.DESCRIPTION
																FROM lncontcoll lnc LEFT JOIN lnucfloancolltype llc ON lnc.LOANCOLLTYPE_CODE = llc.LOANCOLLTYPE_CODE
																WHERE lnc.coll_status = '1' and lnc.loancontract_no = :contract_no ORDER BY lnc.LOANCOLLTYPE_CODE ASC");
			$getCollDetail->execute([':contract_no' => $rowWhocollu["LOANCONTRACT_NO"]]);
			while($rowColl = $getCollDetail->fetch(PDO::FETCH_ASSOC)){
				$arrGroupAll = array();
				$arrGroupAllMember = array();
				$arrGroupAll['LOANCOLLTYPE_CODE'] = $rowColl["LOANCOLLTYPE_CODE"];
				$arrGroupAll['COLLTYPE_DESC'] = $rowColl["LOANCOLLTYPE_DESC"];
				if($rowColl["LOANCOLLTYPE_CODE"] == '01'){
					$whocolluMember = $conoracle->prepare("SELECT MUP.PRENAME_DESC,MMB.MEMB_NAME,MMB.MEMB_SURNAME
														FROM MBMEMBMASTER MMB LEFT JOIN MBUCFPRENAME MUP ON MMB.PRENAME_CODE = MUP.PRENAME_CODE
														WHERE MMB.member_no = :member_no");
					$whocolluMember->execute([':member_no' => $rowColl["REF_COLLNO"]]);
					$rowCollMember = $whocolluMember->fetch(PDO::FETCH_ASSOC);
					$arrayAvarTar = $func->getPathpic($rowColl["REF_COLLNO"]);
					$arrGroupAllMember["AVATAR_PATH"] = isset($arrayAvarTar["AVATAR_PATH"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH"] : null;
					$arrGroupAllMember["AVATAR_PATH_WEBP"] = isset($arrayAvarTar["AVATAR_PATH_WEBP"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH_WEBP"] : null;
					$arrGroupAllMember["FULL_NAME"] = $rowCollMember["PRENAME_DESC"].$rowCollMember["MEMB_NAME"].' '.$rowCollMember["MEMB_SURNAME"];
					$arrGroupAllMember["MEMBER_NO"] = $rowColl["REF_COLLNO"];
				}else if($rowColl["LOANCOLLTYPE_CODE"] == '02'){
					$whocolluMember = $conoracle->prepare("SELECT MUP.PRENAME_DESC,MMB.MEMB_NAME,MMB.MEMB_SURNAME
														FROM MBMEMBMASTER MMB LEFT JOIN MBUCFPRENAME MUP ON MMB.PRENAME_CODE = MUP.PRENAME_CODE
														WHERE MMB.member_no = :member_no");
					$whocolluMember->execute([':member_no' => $rowColl["REF_COLLNO"]]);
					$rowCollMember = $whocolluMember->fetch(PDO::FETCH_ASSOC);
					$arrGroupAllMember["SHARE_COLL_AMT"] = number_format($rowWhocollu["PRNBAL"] * $rowColl["COLL_PERCENT"],2);
					$arrGroupAllMember["FULL_NAME"] = $rowCollMember["PRENAME_DESC"].$rowCollMember["MEMB_NAME"].' '.$rowCollMember["MEMB_SURNAME"];
					$arrGroupAllMember["MEMBER_NO"] = $rowColl["REF_COLLNO"];
				}else if($rowColl["LOANCOLLTYPE_CODE"] == '03'){
					$whocolluDept = $conoracle->prepare("SELECT DEPTACCOUNT_NAME FROM dpdeptmaster
														WHERE deptaccount_no = :deptaccount_no");
					$whocolluDept->execute([':deptaccount_no' => $rowColl["REF_COLLNO"]]);
					$rowCollDept = $whocolluDept->fetch(PDO::FETCH_ASSOC);
					$arrGroupAllMember["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($lib->formataccount($rowColl["REF_COLLNO"],$func->getConstant('dep_format')),$func->getConstant('hidden_dep'));
					$arrGroupAllMember["DEPTACCOUNT_NAME"] = $rowCollDept["DEPTACCOUNT_NAME"];
					$arrGroupAllMember["DEPT_AMT"] = number_format($rowWhocollu["PRNBAL"] * $rowColl["COLL_PERCENT"],2);
				}else if($rowColl["LOANCOLLTYPE_CODE"] == '04'){
					$whocolluAsset = $conoracle->prepare("SELECT lcm.COLLMAST_REFNO,lcd.LAND_LANDNO,lcd.POS_TUMBOL,MBD.DISTRICT_DESC,MBP.PROVINCE_DESC,lcm.COLLMAST_NO
																		FROM lncollmaster lcm LEFT JOIN lncolldetail lcd ON lcm.COLLMAST_NO = lcd.COLLMAST_NO 
																		LEFT JOIN MBUCFDISTRICT MBD ON lcd.POS_DISTRICT = MBD.DISTRICT_CODE
																		LEFT JOIN MBUCFPROVINCE MBP ON lcd.POS_PROVINCE = MBP.PROVINCE_CODE
																		WHERE lcm.collmast_no = :collmast_no");
					$whocolluAsset->execute([':collmast_no' => $rowColl["REF_COLLNO"]]);
					$rowCollAsset = $whocolluAsset->fetch(PDO::FETCH_ASSOC);
					$arrGroupAllMember["COLL_DOCNO"] = $rowCollAsset["COLLMAST_NO"];
					if(isset($rowCollAsset["COLLMAST_REFNO"]) && $rowCollAsset["COLLMAST_REFNO"] != ""){
						$address =  isset($rowCollAsset["COLLMAST_REFNO"]) && $rowCollAsset["COLLMAST_REFNO"] != "" ? "โฉนดเลขที่ ".$rowCollAsset["COLLMAST_REFNO"] : "";
						$address .= isset($rowCollAsset["LAND_LANDNO"]) && $rowCollAsset["LAND_LANDNO"] != "" ? " บ้านเลขที่ ".$rowCollAsset["LAND_LANDNO"] : "";
						$address .= isset($rowCollAsset["POS_TUMBOL"]) && $rowCollAsset["POS_TUMBOL"] != "" ? " ต.".$rowCollAsset["POS_TUMBOL"] : "";
						$address .= isset($rowCollAsset["DISTRIC_DESC"]) && $rowCollAsset["DISTRIC_DESC"] != "" ? " อ.".$rowCollAsset["DISTRIC_DESC"] : "";
						$address .= isset($rowCollAsset["PROVINCE_DESC"]) && $rowCollAsset["PROVINCE_DESC"] != "" ? " จ.".$rowCollAsset["PROVINCE_DESC"] : "";
						$arrGroupAllMember["DESCRIPTION"] = $address;
					}else{
						$arrGroupAllMember['DESCRIPTION'] = $rowColl["DESCRIPTION"];
					}
				}else if($rowColl["LOANCOLLTYPE_CODE"] == '05'){
					$arrGroupAllMember['DESCRIPTION'] = $rowColl["DESCRIPTION"];
				}
				if(array_search($rowColl["LOANCOLLTYPE_CODE"],array_column($arrGrpAllLoan,'LOANCOLLTYPE_CODE')) === False){
					$arrGroupAll['ASSET'][] = $arrGroupAllMember;
					$arrGrpAllLoan[] = $arrGroupAll;
				}else{
					($arrGrpAllLoan[array_search($rowColl["LOANCOLLTYPE_CODE"],array_column($arrGrpAllLoan,'LOANCOLLTYPE_CODE'))]["ASSET"])[] = $arrGroupAllMember;
				}
			}
			$arrayGroupLoan["GUARANTEE"] = $arrGrpAllLoan;
			$arrGroupAllLoan[] = $arrayGroupLoan;
		}
	
	$whoColyouData = array();
	
	$whoColyouData["type"] = "flex";
	$whoColyouData["altText"] = "ข้อมูลใครค้ำคุณ";
	$whoColyouData["contents"]["type"] = "bubble";
	$whoColyouData["contents"]["direction"] = "ltr";
	$whoColyouData["contents"]["body"]["type"] = "box";
	$whoColyouData["contents"]["body"]["layout"] = "vertical";
	$whoColyouData["contents"]["body"]["contents"][0]["type"] = "text";
	$whoColyouData["contents"]["body"]["contents"][0]["text"] = "ใครค้ำคุณ";
	$whoColyouData["contents"]["body"]["contents"][0]["weight"] = "bold";
	$whoColyouData["contents"]["body"]["contents"][0]["size"] = "lg";
	$whoColyouData["contents"]["body"]["contents"][0]["color"] = ($themeColor??"#000000");
	$whoColyouData["contents"]["body"]["contents"][0]["align"] = "center";
	$indexLoan = 1;
	if(sizeof($arrGroupAllLoan) > 0){
		foreach($arrGroupAllLoan as $loanData){
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["type"] = "box";
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["layout"] = "vertical";
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][0]["type"] = "separator";
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][0]["margin"] = "lg";
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][1]["type"] = "box";
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][1]["layout"] = "horizontal";
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][1]["margin"] = "md";
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][1]["contents"][0]["type"] = "text";
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][1]["contents"][0]["text"] = "เลขที่สัญญา";
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][1]["contents"][0]["size"] = "xs";
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][1]["contents"][0]["color"] = "#0938A4";
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][1]["contents"][1]["type"] = "text";
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][1]["contents"][1]["text"] = ($loanData["CONTRACT_NO"]??'-');
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][1]["contents"][1]["size"] = "xs";
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][1]["contents"][1]["color"] = "#000000";
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][1]["contents"][1]["align"] = "end";

			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][2]["type"] = "text";
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][2]["text"] = "ประเภทเงินกู้";
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][2]["size"] = "xs";
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][2]["color"] = "#0938A4";
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][3]["type"] = "text";
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][3]["text"] = ($loanData["TYPE_DESC"]??'-');;
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][3]["weight"] = "bold";
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][3]["size"] = "sm";
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][3]["color"] = "#000000";
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][3]["align"] = "end";
			$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][3]["wrap"] = true;
			$indexGuarantee = 4;
			if(sizeof($loanData["GUARANTEE"]) > 0){
				foreach($loanData["GUARANTEE"] as $guarantee){
				
					$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["type"] = "box";
					$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["layout"] = "vertical";
					$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["margin"] = "md";
					$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][0]["type"] = "text";
					$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][0]["text"] = ($guarantee["COLLTYPE_DESC"]??"-");
					$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][0]["weight"] = "bold";
					$indexAsset = 1;
					if(sizeof($guarantee["ASSET"]) > 0){
						foreach($guarantee["ASSET"] as $asset){
							$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["type"] = "box";
							$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["layout"] = "vertical";
							$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["margin"] = "sm";
							$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["paddingStart"] = "20px";
							$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["borderWidth"] = "1px";
							$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["borderColor"] = ($themeColor??"#000000");
							$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["cornerRadius"] = "10px";
							
							if($guarantee["LOANCOLLTYPE_CODE"] == "01"){
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][0]["type"] = "text";
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][0]["text"] = ($asset["FULL_NAME"]??"-");
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][0]["wrap"] = true;
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][1]["type"] = "text";
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][1]["text"] = "เลขสมาชิก : ".($asset["MEMBER_NO"]??"-");
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][1]["color"] = "#0938A4";
							
							}else if($guarantee["LOANCOLLTYPE_CODE"] == "02"){
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][0]["type"] = "text";
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][0]["text"] = ($asset["FULL_NAME"]??"-");
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][0]["wrap"] = true;
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][1]["type"] = "text";
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][1]["text"] = "เลขสมาชิก : ".($asset["MEMBER_NO"]??"-");
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][1]["color"] = "#0938A4";
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][2]["text"] = ($asset["SHARE_COLL_AMT"]??"0.00"). "บาท";
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][2]["color"] = "#35B84B";
							}else if($guarantee["LOANCOLLTYPE_CODE"] == "03"){
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][0]["type"] = "text";
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][0]["text"] = "ชื่อบัญชี : ".($asset["DEPTACCOUNT_NAME"]??"-");
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][0]["wrap"] = true;
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][1]["type"] = "text";
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][1]["text"] = "เลขที่บัญชี : ".($asset["DEPTACCOUNT_NO"]??"-");
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][1]["color"] = "#0938A4";
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][2]["type"] = "text";
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][2]["text"] = "ยอดค้ำประกัน ".($asset["DEPT_AMT"]??"0.00"). " บาท";
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][2]["color"] = "#35B84B";
							}else if($guarantee["LOANCOLLTYPE_CODE"] == "04"){
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][0]["type"] = "text";
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][0]["text"] = ($asset["COLL_DOCNO"]??"-");
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][0]["wrap"] = true;
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][1]["type"] = "text";
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][1]["text"] = ($asset["DESCRIPTION"]??"-");
							}else if($guarantee["LOANCOLLTYPE_CODE"] == "05"){
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][0]["type"] = "text";
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][0]["text"] = ($asset["DESCRIPTION"]??"-");
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][0]["wrap"] = true;
							}else {
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][0]["type"] = "text";
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][0]["text"] = ($asset["DESCRIPTION"]??"-");
								$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][$indexAsset]["contents"][0]["wrap"] = true;
							}
							$indexAsset++;
						}
					}else{
						$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][1]["type"] = "text";
						$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][1]["text"] = "ไม่พบข้อมูล";
						$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][$indexGuarantee]["contents"][1]["margin"] = "md";
					}
					$indexGuarantee++;
				}
			}else{
				$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][4]["type"] = "text";
				$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][4]["text"] = "ไม่พลข้อมูล";
				$whoColyouData["contents"]["body"]["contents"][$indexLoan]["contents"][4]["margin"] = "md";
			}
			$indexLoan++;
		}
	}else{
		$whoColyouData["contents"]["body"]["contents"][1]["type"] = "text";
		$whoColyouData["contents"]["body"]["contents"][1]["text"] = "ไม่พบข้อมูลใครค้ำคุณ";
		$whoColyouData["contents"]["body"]["contents"][1]["margin"] = "20px";
		$whoColyouData["contents"]["body"]["contents"][1]["size"] = "md";
	}
	$arrPostData["messages"][0] = $whoColyouData;
	$arrPostData["replyToken"] = $reply_token;
}else{
	$altText = "ท่านยังไม่ได้ผูกบัญชี";
	$dataMs = $lineLib->notBindAccount();
	$dataPrepare = $lineLib->prepareFlexMessage($altText,$dataMs);
	$arrPostData["messages"] = $dataPrepare;
	$arrPostData["replyToken"] = $reply_token;
}
?>