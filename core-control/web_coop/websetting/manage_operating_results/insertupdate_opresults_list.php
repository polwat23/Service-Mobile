<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','opresult_list','year'],$dataComing)){
	$conmysql->beginTransaction();
	
	$opresult_list = $dataComing["opresult_list"];
	$delete_list = $dataComing["delete_list"];
	if(count($delete_list) > 0){
			$delete_opresultslist = $conmysql->prepare('UPDATE webcoopopresultslist SET is_use = "0"
													WHERE id_opresultslist in ('.implode(',',$dataComing["delete_list"]).')');
			if($delete_opresultslist->execute()){
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถบันทึกข้อมูลได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		
	}
	foreach($opresult_list as $opresult_item){
		if(isset($opresult_item["ID_OPRESULTSLIST"]) && $opresult_item["ID_OPRESULTSLIST"] != null){
			//update opresult list
			$update_opresultslist = $conmysql->prepare("UPDATE webcoopopresultslist SET list_name = :list_name
													WHERE id_opresultslist = :id_opresultslist");
			if($update_opresultslist->execute([
				':list_name' => $opresult_item["LIST_NAME"],
				':id_opresultslist' => $opresult_item["ID_OPRESULTSLIST"],
			])){
				
				//insert update opresult data
				if(isset($opresult_item["ID_OPRESULTS"]) && $opresult_item["ID_OPRESULTS"] != null){
					$update_opresultsdata = $conmysql->prepare("UPDATE webcoopopresultsdata
									SET data_year = :year, data_0 = :data_0, data_1 = :data_1,
									data_2 = :data_2, data_3 = :data_3, data_4 = :data_4, data_5 = :data_5, data_6 = :data_6, data_7 = :data_7,
									data_8 = :data_8, data_9 = :data_9, data_10 = :data_10, data_11 = :data_11, data_12 = :data_12,
									update_by = :update_by
									WHERE id_opresults = :id_opresults");
					if($update_opresultsdata->execute([
						':year' => $dataComing["year"],
						':data_0' => $opresult_item["DATA_0"] ?? null,
						':data_1' => $opresult_item["DATA_1"] ?? null,
						':data_2' => $opresult_item["DATA_2"] ?? null,
						':data_3' => $opresult_item["DATA_3"] ?? null,
						':data_4' => $opresult_item["DATA_4"] ?? null,
						':data_5' => $opresult_item["DATA_5"] ?? null,
						':data_6' => $opresult_item["DATA_6"] ?? null,
						':data_7' => $opresult_item["DATA_7"] ?? null,
						':data_8' => $opresult_item["DATA_8"] ?? null,
						':data_9' => $opresult_item["DATA_9"] ?? null,
						':data_10' => $opresult_item["DATA_10"] ?? null,
						':data_11' => $opresult_item["DATA_11"] ?? null,
						':data_12' => $opresult_item["DATA_12"] ?? null,
						':id_opresults' => $opresult_item["ID_OPRESULTS"],
						':update_by' => $payload["username"]
					])){
						continue;
					}else{
						$conmysql->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อมูล กรุณาติดต่อผู้พัฒนา";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}else{
					$update_opresultsdata = $conmysql->prepare("INSERT INTO webcoopopresultsdata
									(id_opresultslist, data_year, data_0, data_1, data_2, data_3, data_4, data_5, data_6,
									data_7, data_8, data_9, data_10, data_11, data_12,create_by,update_by) 
									VALUES (:id_opresultslist, :year,:data_0,:data_1,:data_2,:data_3,
									:data_4,:data_5,:data_6,:data_7,:data_8,:data_9,:data_10,:data_11,:data_12,:create_by,:update_by)");
					if($update_opresultsdata->execute([
						':id_opresultslist' => $opresult_item["ID_OPRESULTSLIST"],
						':year' => $dataComing["year"],
						':data_0' => $opresult_item["DATA_0"] ?? null,
						':data_1' => $opresult_item["DATA_1"] ?? null,
						':data_2' => $opresult_item["DATA_2"] ?? null,
						':data_3' => $opresult_item["DATA_3"] ?? null,
						':data_4' => $opresult_item["DATA_4"] ?? null,
						':data_5' => $opresult_item["DATA_5"] ?? null,
						':data_6' => $opresult_item["DATA_6"] ?? null,
						':data_7' => $opresult_item["DATA_7"] ?? null,
						':data_8' => $opresult_item["DATA_8"] ?? null,
						':data_9' => $opresult_item["DATA_9"] ?? null,
						':data_10' => $opresult_item["DATA_10"] ?? null,
						':data_11' => $opresult_item["DATA_11"] ?? null,
						':data_12' => $opresult_item["DATA_12"] ?? null,
						':create_by' => $payload["username"],
						':update_by' => $payload["username"]
					])){
						continue;
					}else{
						$conmysql->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อมูล กรุณาติดต่อผู้พัฒนา";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}
				//end update opresult data
				continue;
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มรายการได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			//isert opresult list
			$insert_opresultslist = $conmysql->prepare("INSERT INTO webcoopopresultslist(list_name) 
										VALUES (:list_name)");
			if($insert_opresultslist->execute([
				':list_name' => $opresult_item["LIST_NAME"]
			])){
				$last_id = $conmysql->lastInsertId();
				
				//isert opresult data
				$insert_opresultsdata = $conmysql->prepare("INSERT INTO webcoopopresultsdata
									(id_opresultslist, data_year, data_0, data_1, data_2, data_3, data_4, data_5, data_6,
									data_7, data_8, data_9, data_10, data_11, data_12,create_by,update_by) 
									VALUES (:id_opresultslist, :year,:data_0,:data_1,:data_2,:data_3,
									:data_4,:data_5,:data_6,:data_7,:data_8,:data_9,:data_10,:data_11,:data_12,:create_by,:update_by))");
				if($insert_opresultsdata->execute([
					':id_opresultslist' => $last_id,
					':year' => $dataComing["year"],
					':data_0' => $opresult_item["DATA_0"] ?? null,
					':data_1' => $opresult_item["DATA_1"] ?? null,
					':data_2' => $opresult_item["DATA_2"] ?? null,
					':data_3' => $opresult_item["DATA_3"] ?? null,
					':data_4' => $opresult_item["DATA_4"] ?? null,
					':data_5' => $opresult_item["DATA_5"] ?? null,
					':data_6' => $opresult_item["DATA_6"] ?? null,
					':data_7' => $opresult_item["DATA_7"] ?? null,
					':data_8' => $opresult_item["DATA_8"] ?? null,
					':data_9' => $opresult_item["DATA_9"] ?? null,
					':data_10' => $opresult_item["DATA_10"] ?? null,
					':data_11' => $opresult_item["DATA_11"] ?? null,
					':data_12' => $opresult_item["DATA_12"] ?? null,
					':create_by' => $payload["username"],
					':update_by' => $payload["username"]
				])){
					continue;
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อมูล กรุณาติดต่อผู้พัฒนา";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
				//end insert opresult data
				continue;
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มรายการได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}
	}
	$conmysql->commit();
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);	
	
	
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>