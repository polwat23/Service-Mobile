<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'webcoop','manageboard')){
		$arrayGroup = array();
		$year_now = date('Y');
		$year_group = [];
		$fetchBoadCoop = $conmysql->prepare("SELECT
													id_board,
													f_name,
													l_name,
													position1,
													position2,
													year,
													img_path,
													img_url,
													type
												FROM
													webcoopboardofdirectors
												WHERE year = :year
												ORDER BY
													 position1	 
											");
		$fetchBoadCoop->execute([
			':year' =>  $dataComing["year"]
		]);
		$arrayType = null;
		while($rowBoadCoop = $fetchBoadCoop->fetch(PDO::FETCH_ASSOC)){
			$arrBoardCoop["ID_BOARD"] = $rowBoadCoop["id_board"];
			$arrBoardCoop["F_NAME"] = $rowBoadCoop["f_name"];
			$arrBoardCoop["L_NAME"] = $rowBoadCoop["l_name"];
			$arrBoardCoop["POSITION1"] = $rowBoadCoop["position1"];
			$arrBoardCoop["POSITION2"] = $rowBoadCoop["position2"];
			$arrBoardCoop["YEAR"] = $rowBoadCoop["year"];
			$arrBoardCoop["IMG_PATH"] = $rowBoadCoop["img_path"];
			$arrBoardCoop["IMG_URL"] = $rowBoadCoop["img_url"];
			$arrBoardCoop["TYPE"] = $rowBoadCoop["type"];
			$arrayGroup[] = $arrBoardCoop;
			$arrayType = $rowBoadCoop["type"];
		}
		
		$fetchYear = $conmysql->prepare("SELECT DISTINCT
												year
											FROM
													webcoopboardofdirectors
											ORDER BY
													year	
											DESC	
											");
		$fetchYear->execute();
		while($rowYear = $fetchYear->fetch(PDO::FETCH_ASSOC)){
			$arrYear=$rowYear["year"];
			$year_group[]=$arrYear;
		}
		
		
		$arrayResult["BOARD_DATA"] = $arrayGroup;
		$arrayResult["YEAR_DATA"] = $year_group;
		$arrayResult["TYPE"] = $arrayType;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>