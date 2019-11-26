<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['user_type','id_token','member_no','id_userlogin'],$payload) 
&& $lib->checkCompleteArgument(['menu_component','ref_no','allow_create_menu','name_fav'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'FavoriteAccount')){
		$conmysql->beginTransaction();
		$fav_refno = substr($dataComing["ref_no"],0,3).(date("Y") + 543).substr($payload["member_no"],4).date("i").date("s").$lib->randomText("all",2)."FAV";
		$insertFavAccount = $conmysql->prepare("INSERT INTO gcfavoritelist(fav_refno,ref_no,name_fav,member_no)
												VALUES(:fav_refno,:ref_no,:name_fav,:member_no)");
		if($insertFavAccount->execute([
			':fav_refno' => $fav_refno,
			':ref_no' => $dataComing["ref_no"],
			':name_fav' => $dataComing["name_fav"],
			':member_no' => $payload["member_no"],
		])){
			if($dataComing["allow_create_menu"]){
				if($lib->checkCompleteArgument(['id_palette'],$dataComing)){
					$insertToFavMenu = $conmysql->prepare("INSERT INTO gcfavoritemenu(id_userlogin,id_palette,fav_refno)
															VALUES(:id_userlogin,:id_palette,:fav_refno)");
					if($insertToFavMenu->execute([
						':id_userlogin' => $payload["id_userlogin"],
						':id_palette' => $dataComing["id_palette"],
						':fav_refno' => $fav_refno
					])){
						$conmysql->commit();
						if(isset($new_token)){
							$arrayResult['NEW_TOKEN'] = $new_token;
						}
						$arrayResult['RESULT'] = TRUE;
						echo json_encode($arrayResult);
					}else{
						$conmysql->rollback();
						$arrayResult['RESPONSE_CODE'] = "WS1022";
						$arrayResult['RESPONSE_MESSAGE'] = "Cannot insert to favorite menu";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS4004";
					$arrayResult['RESPONSE_MESSAGE'] = "Not complete argument";
					$arrayResult['RESULT'] = FALSE;
					http_response_code(400);
					echo json_encode($arrayResult);
					exit();
				}
			}else{
				$conmysql->commit();
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}
		}else{
			$conmysql->rollback();
			$arrayResult['RESPONSE_CODE'] = "WS1021";
			$arrayResult['RESPONSE_MESSAGE'] = "Cannot insert to favorite list";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>