<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','name_fav','show_menu','destination','flag_trans'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'FavoriteAccount')){
		$fav_refno = substr(time(),0,3).(date("Y") + 543).substr($payload["member_no"],4).date("i").date("s").$lib->randomText("all",2)."FAV";
		$insertFavAccount = $conmysql->prepare("INSERT INTO gcfavoritelist(fav_refno,name_fav,flag_trans,destination,member_no,show_menu)
											VALUES(:fav_refno,:name_fav,:flag_trans,:destination,:member_no,:show_menu)");
		if($insertFavAccount->execute([
			':fav_refno' => $fav_refno,
			':name_fav' => $dataComing["name_fav"],
			':flag_trans' => $dataComing["flag_trans"],
			':destination' => $dataComing["destination"],
			':member_no' => $payload["member_no"],
			':show_menu' => $dataComing["show_menu"]
		])){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1029",
				":error_desc" => "สร้างรายการโปรดไม่ได้ไม่สามารถ Insert gcfavoritelist ได้"."\n"."Query => ".$insertFavAccount->queryString."\n".json_encode([
					':fav_refno' => $fav_refno,
					':name_fav' => $dataComing["name_fav"],
					':flag_trans' => $dataComing["flag_trans"],
					':destination' => $dataComing["destination"],
					':member_no' => $payload["member_no"],
					':show_menu' => $dataComing["show_menu"]
				]),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "สร้างรายการโปรดไม่ได้ไม่สามารถ Insert gcfavoritelist ได้"."\n"."Query => ".$insertFavAccount->queryString."\n".json_encode([
				':fav_refno' => $fav_refno,
				':name_fav' => $dataComing["name_fav"],
				':flag_trans' => $dataComing["flag_trans"],
				':destination' => $dataComing["destination"],
				':member_no' => $payload["member_no"],
				':show_menu' => $dataComing["show_menu"]
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS1029";
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