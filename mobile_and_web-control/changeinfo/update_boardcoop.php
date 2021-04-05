<?php
require_once('../autoload.php');

if ($lib->checkCompleteArgument(['menu_component', 'president', 'board', 'business', 'manager', 'officer_count', 'member_count'], $dataComing)) {
	if ($func->check_permission($payload["user_type"], $dataComing["menu_component"], 'SettingMemberInfo')) {
		$member_no = $configAS[$payload["ref_memno"]] ?? $payload["ref_memno"];
		$conmysql->beginTransaction();

		//president
		if (isset($dataComing["president"]["ID"]) && $dataComing["president"]["ID"] != "") {
			$insertPresData = $conmysql->prepare("UPDATE gcmanagement
											SET md_name=:md_name
											WHERE id = :id");
			if ($insertPresData->execute([
				':md_name' => $dataComing["president"]["VALUE"],
				':id' => $dataComing["president"]["ID"]
			])) {
				$arrayResult["RESULT_EDIT"] = TRUE;
			} else {
				$arrayResult["RESULT_EDIT"] = FALSE;
			}
		} else {
			$insertPresData = $conmysql->prepare("INSERT INTO gcmanagement(member_no, md_name, md_type, md_count, board_status) 
											VALUES (:member_no,:md_name,'0',null,'1')");
			if ($insertPresData->execute([
				':member_no' => $member_no,
				':md_name' => $dataComing["president"]["VALUE"]
			])) {
				$arrayResult["RESULT_EDIT"] = TRUE;
			} else {
				$arrayResult["RESULT_EDIT"] = FALSE;
			}
		}

		//board
		foreach ($dataComing["board"] as $board) {
			if (isset($board["ID"]) && $board["ID"] != "") {
				//delete board
				if (isset($board["DELETED"]) && $board["DELETED"] == "1") {
					$insertPresData = $conmysql->prepare("UPDATE gcmanagement
												SET board_status = '0'
												WHERE id = :id");
					if ($insertPresData->execute([
						':id' => $board["ID"]
					])) {
						$arrayResult["RESULT_EDIT"] = TRUE;
					} else {
						$arrayResult["RESULT_EDIT"] = FALSE;
					}
				} else {
					$insertPresData = $conmysql->prepare("UPDATE gcmanagement
												SET md_name=:md_name
												WHERE id = :id");
					if ($insertPresData->execute([
						':md_name' => $board["VALUE"],
						':id' => $board["ID"]
					])) {
						$arrayResult["RESULT_EDIT"] = TRUE;
					} else {
						$arrayResult["RESULT_EDIT"] = FALSE;
					}
				}
			} else {
				$insertPresData = $conmysql->prepare("INSERT INTO gcmanagement(member_no, md_name, md_type, md_count, board_status) 
												VALUES (:member_no,:md_name,'2',null,'1')");
				if ($insertPresData->execute([
					':member_no' => $member_no,
					':md_name' => $board["VALUE"]
				])) {
					$arrayResult["RESULT_EDIT"] = TRUE;
				} else {
					$arrayResult["RESULT_EDIT"] = FALSE;
				}
			}
		}

		//business
		foreach ($dataComing["business"] as $business) {
			if (isset($business["ID"]) && $business["ID"] != "") {
				//delete business
				if (isset($business["DELETED"]) && $business["DELETED"] == "1") {
					$insertPresData = $conmysql->prepare("UPDATE gcmanagement
												SET board_status = '0'
												WHERE id = :id");
					if ($insertPresData->execute([
						':id' => $business["ID"]
					])) {
						$arrayResult["RESULT_EDIT"] = TRUE;
					} else {
						$arrayResult["RESULT_EDIT"] = FALSE;
					}
				} else {
					$insertPresData = $conmysql->prepare("UPDATE gcmanagement
												SET md_name=:md_name
												WHERE id = :id");
					if ($insertPresData->execute([
						':md_name' => $business["VALUE"],
						':id' => $business["ID"]
					])) {
						$arrayResult["RESULT_EDIT"] = TRUE;
					} else {
						$arrayResult["RESULT_EDIT"] = FALSE;
					}
				}
			} else {
				$insertPresData = $conmysql->prepare("INSERT INTO gcmanagement(member_no, md_name, md_type, md_count, board_status) 
												VALUES (:member_no,:md_name,'3',null,'1')");
				if ($insertPresData->execute([
					':member_no' => $member_no,
					':md_name' => $business["VALUE"]
				])) {
					$arrayResult["RESULT_EDIT"] = TRUE;
				} else {
					$arrayResult["RESULT_EDIT"] = FALSE;
				}
			}
		}
		
		//manager
		if (isset($dataComing["manager"]["ID"]) && $dataComing["manager"]["ID"] != "") {
			$insertPresData = $conmysql->prepare("UPDATE gcmanagement
											SET md_name=:md_name
											WHERE id = :id");
			if ($insertPresData->execute([
				':md_name' => $dataComing["manager"]["VALUE"],
				':id' => $dataComing["manager"]["ID"]
			])) {
				$arrayResult["RESULT_EDIT"] = TRUE;
			} else {
				$arrayResult["RESULT_EDIT"] = FALSE;
			}
		} else {
			$insertPresData = $conmysql->prepare("INSERT INTO gcmanagement(member_no, md_name, md_type, md_count, board_status) 
											VALUES (:member_no,:md_name,'1',null,'1')");
			if ($insertPresData->execute([
				':member_no' => $member_no,
				':md_name' => $dataComing["manager"]["VALUE"]
			])) {
				$arrayResult["RESULT_EDIT"] = TRUE;
			} else {
				$arrayResult["RESULT_EDIT"] = FALSE;
			}
		}

		//officer_count
		if (isset($dataComing["officer_count"]["ID"]) && $dataComing["officer_count"]["ID"] != "") {
			$insertPresData = $conmysql->prepare("UPDATE gcmanagement
											SET md_count=:md_count
											WHERE id = :id");
			if ($insertPresData->execute([
				':md_count' => $dataComing["officer_count"]["VALUE"],
				':id' => $dataComing["officer_count"]["ID"]
			])) {
				$arrayResult["RESULT_EDIT"] = TRUE;
			} else {
				$arrayResult["RESULT_EDIT"] = FALSE;
			}
		} else {
			$insertPresData = $conmysql->prepare("INSERT INTO gcmanagement(member_no, md_name, md_type, md_count, board_status) 
											VALUES (:member_no,null,'5',:md_count,'1')");
			if ($insertPresData->execute([
				':member_no' => $member_no,
				':md_count' => $dataComing["officer_count"]["VALUE"]
			])) {
				$arrayResult["RESULT_EDIT"] = TRUE;
			} else {
				$arrayResult["RESULT_EDIT"] = FALSE;
			}
		}

		//member_count
		if (isset($dataComing["member_count"]["ID"]) && $dataComing["member_count"]["ID"] != "") {
			$insertPresData = $conmysql->prepare("UPDATE gcmanagement
											SET md_count=:md_count
											WHERE id = :id");
			if ($insertPresData->execute([
				':md_count' => $dataComing["member_count"]["VALUE"],
				':id' => $dataComing["member_count"]["ID"]
			])) {
				$arrayResult["RESULT_EDIT"] = TRUE;
			} else {
				$arrayResult["RESULT_EDIT"] = FALSE;
			}
		} else {
			$insertPresData = $conmysql->prepare("INSERT INTO gcmanagement(member_no, md_name, md_type, md_count, board_status) 
											VALUES (:member_no,null,'4',:md_count,'1')");
			if ($insertPresData->execute([
				':member_no' => $member_no,
				':md_count' => $dataComing["member_count"]["VALUE"]
			])) {
				$arrayResult["RESULT_EDIT"] = TRUE;
			} else {
				$arrayResult["RESULT_EDIT"] = FALSE;
			}
		}

		if (isset($arrayResult["RESULT_EDIT"]) && !$arrayResult["RESULT_EDIT"]) {
			$conmysql->rollback();
			$arrayResult['RESPONSE_CODE'] = "WS1039";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		} else {
			$conmysql->commit();
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}
	} else {
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
	}
} else {
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ " . "\n" . json_encode($dataComing),
		":error_device" => $dataComing["channel"] . ' - ' . $dataComing["unique_id"] . ' on V.' . $dataComing["app_version"]
	];
	$log->writeLog('errorusage', $logStruc);
	$message_error = "ไฟล์ " . $filename . " ส่ง Argument มาไม่ครบมาแค่ " . "\n" . json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
}
