<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','name_fav'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'FavoriteAccount')){
		$fav_refno = substr($dataComing["ref_no"],0,3).(date("Y") + 543).substr($payload["member_no"],4).date("i").date("s").$lib->randomText("all",2)."FAV";
		if(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != ''){
			$fetchTransaction = $conmysql->prepare("SELECT destination,transfer_mode FROM gctransaction WHERE ref_no = :ref_no");
			$fetchTransaction->execute([':ref_no' => $dataComing["ref_no"]]);
			$rowTransaction = $fetchTransaction->fetch();
			if($rowTransaction["transfer_mode"] == '1' || $rowTransaction["transfer_mode"] == '9'){
				$trn_mode = 'TRN';
			}else{
				$trn_mode = 'LPM';
			}
			$insertFavAccount = $conmysql->prepare("INSERT INTO gcfavoritelist(fav_refno,name_fav,flag_trans,destination,member_no)
													VALUES(:fav_refno,:name_fav,:flag_trans,:destination,:member_no)");
			if($insertFavAccount->execute([
				':fav_refno' => $fav_refno,
				':name_fav' => $dataComing["name_fav"],
				':flag_trans' => $trn_mode,
				':destination' => $rowTransaction["destination"],
				':member_no' => $payload["member_no"]
			])){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrExecute = [
					':fav_refno' => $fav_refno,
					':name_fav' => $dataComing["name_fav"],
					':flag_trans' => $trn_mode,
					':destination' => $rowTransaction["destination"],
					':member_no' => $payload["member_no"]
				];
				$arrError = array();
				$arrError["EXECUTE"] = $arrExecute;
				$arrError["QUERY"] = $insertFavAccount;
				$arrError["ERROR_CODE"] = 'WS1029';
				$lib->addLogtoTxt($arrError,'fav_error');
				$arrayResult['RESPONSE_CODE'] = "WS1029";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			if($lib->checkCompleteArgument(['destination','flag_trans'],$dataComing)){
				$insertFavAccount = $conmysql->prepare("INSERT INTO gcfavoritelist(fav_refno,name_fav,flag_trans,destination,member_no)
												VALUES(:fav_refno,:name_fav,:flag_trans,:destination,:member_no)");
				if($insertFavAccount->execute([
					':fav_refno' => $fav_refno,
					':name_fav' => $dataComing["name_fav"],
					':flag_trans' => $dataComing["flag_trans"],
					':destination' => $dataComing["destination"],
					':member_no' => $payload["member_no"]
				])){
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
				}else{
					$arrExecute = [
						':fav_refno' => $fav_refno,
						':name_fav' => $dataComing["name_fav"],
						':flag_trans' => $dataComing["flag_trans"],
						':destination' => $dataComing["destination"],
						':member_no' => $payload["member_no"]
					];
					$arrError = array();
					$arrError["EXECUTE"] = $arrExecute;
					$arrError["QUERY"] = $insertFavAccount;
					$arrError["ERROR_CODE"] = 'WS1030';
					$lib->addLogtoTxt($arrError,'fav_error');
					$arrayResult['RESPONSE_CODE'] = "WS1030";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS4004";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				http_response_code(400);
				echo json_encode($arrayResult);
				exit();
			}
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>