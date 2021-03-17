<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	
$arrayGroup = array();
	$year_now = date('Y');
	$year_group = [];
	$Layer1 = [];
	$Layer2 = [];
	
	
	$fecthDept = $conmysql->prepare("
										SELECT DISTINCT
											department

										FROM
											webcoopboardofdirectors
										WHERE
											emp_type = :emp_type AND YEAR = :year AND position1 != 1 AND DEPARTMENT IS NOT NULL
	");
	
	
		$fecthDept->execute([
		':year' =>  $dataComing["year"],
		':emp_type' =>  $dataComing["emp_type"]??1
	]);
	$deptData = [];
	while($rowDept = $fecthDept->fetch(PDO::FETCH_ASSOC)){
	
	$fecthOfficer = $conmysql->prepare("
										  SELECT 
											id_board,
											fullname,
											position1,
											position2,
											year,
											img_path,
											img_url,
											type,
											department
										FROM
											webcoopboardofdirectors
										WHERE
											emp_type = :emp_type AND YEAR = :year  and department = :department and position1 != '1' and position1 != '2'
										ORDER BY POSITION1 ASC
		");
		$fecthOfficer->execute([
			':year' =>  $dataComing["year"],
			':emp_type' =>  $dataComing["emp_type"]??1,
			':department' =>  $rowDept["department"]
		]);
		$groupOfficer = [];
		while($rowOfficer = $fecthOfficer->fetch(PDO::FETCH_ASSOC)){
				$arrOfficer["ID_BOARD"] = $rowOfficer["id_board"];
				$arrOfficer["FULLNAME"] = $rowOfficer["fullname"];
				$arrOfficer["POSITION1"] = $rowOfficer["position1"];
				$arrOfficer["POSITION2"] = $rowOfficer["position2"];
				$arrOfficer["DEPARTMENT"] = $rowOfficer["department"];
				$arrOfficer["IMG_PATH"] = $rowOfficer["img_path"]; 
				$arrOfficer["IMG_URL"] = $rowOfficer["img_url"];
				$arrOfficer["YEAR"] = $rowOfficer["year"];
				$groupOfficer[] = $arrOfficer;
		}
	
	
	
	
	$arrDept["dept"] =  $rowDept["department"];
	$arrDept["officer"] =  $groupOfficer;
	$deptData[]=$arrDept;
	
	}
	
	$fetchBoadCoop = $conmysql->prepare("SELECT
												id_board,
												fullname,
												position1,
												position2,
												year,
												img_path,
												img_url,
												type,
												department
											FROM
												webcoopboardofdirectors
											WHERE year = :year AND emp_type =:emp_type
											ORDER BY
												 position1	 
										");
	$fetchBoadCoop->execute([
		':year' =>  $dataComing["year"],
		':emp_type' =>  $dataComing["emp_type"]??1
	]);
	$arrayType = null;
	while($rowBoadCoop = $fetchBoadCoop->fetch(PDO::FETCH_ASSOC)){
		$arrBoardCoop["ID_BOARD"] = $rowBoadCoop["id_board"];
		$arrBoardCoop["FULLNAME"] = $rowBoadCoop["fullname"];
		$arrBoardCoop["POSITION1"] = $rowBoadCoop["position1"];
		$arrBoardCoop["POSITION2"] = $rowBoadCoop["position2"];
		$arrBoardCoop["YEAR"] = $rowBoadCoop["year"];
		$arrBoardCoop["IMG_PATH"] = $rowBoadCoop["img_path"];
		$arrBoardCoop["IMG_URL"] = $rowBoadCoop["img_url"];
		$arrBoardCoop["TYPE"] = $rowBoadCoop["type"];
		$arrBoardCoop["DEPARTMENT"] = $rowBoadCoop["department"];
		$arrayGroup[] = $arrBoardCoop;
		$arrayType = $rowBoadCoop["type"];
	}
	$fetchLayer1 = $conmysql->prepare("SELECT
												id_board,
												fullname,
												position1,
												position2,
												year,
												img_path,
												img_url,
												type,
												department
											FROM
												webcoopboardofdirectors
											WHERE year = :year AND emp_type =:emp_type AND POSITION1 ='1'
											ORDER BY
												 position1	 
										");
	$fetchLayer1->execute([
		':year' =>  $dataComing["year"],
		':emp_type' =>  $dataComing["emp_type"]??1
	]);

	while($rowBoadCoop = $fetchLayer1->fetch(PDO::FETCH_ASSOC)){
		$arrBoardCoop["ID_BOARD"] = $rowBoadCoop["id_board"];
		$arrBoardCoop["FULLNAME"] = $rowBoadCoop["fullname"];
		$arrBoardCoop["POSITION1"] = $rowBoadCoop["position1"];
		$arrBoardCoop["POSITION2"] = $rowBoadCoop["position2"];
		$arrBoardCoop["YEAR"] = $rowBoadCoop["year"];
		$arrBoardCoop["IMG_PATH"] = $rowBoadCoop["img_path"];
		$arrBoardCoop["IMG_URL"] = $rowBoadCoop["img_url"];
		$arrBoardCoop["TYPE"] = $rowBoadCoop["type"];
		$arrBoardCoop["DEPARTMENT"] = $rowBoadCoop["department"];
		$Layer1[] = $arrBoardCoop;
		
	}
	
	$fetchLayer2 = $conmysql->prepare("SELECT
												id_board,
												fullname,
												position1,
												position2,
												year,
												img_path,
												img_url,
												type,
												department
											FROM
												webcoopboardofdirectors
											WHERE year = :year AND emp_type =:emp_type AND POSITION1 ='2'
											ORDER BY
												 position1	 
										");
	$fetchLayer2->execute([
		':year' =>  $dataComing["year"],
		':emp_type' =>  $dataComing["emp_type"]??1
	]);
	
	while($rowBoadCoop = $fetchLayer2->fetch(PDO::FETCH_ASSOC)){
		$arrBoardCoop["ID_BOARD"] = $rowBoadCoop["id_board"];
		$arrBoardCoop["FULLNAME"] = $rowBoadCoop["fullname"];
		$arrBoardCoop["POSITION1"] = $rowBoadCoop["position1"];
		$arrBoardCoop["POSITION2"] = $rowBoadCoop["position2"];
		$arrBoardCoop["YEAR"] = $rowBoadCoop["year"];
		$arrBoardCoop["IMG_PATH"] = $rowBoadCoop["img_path"];
		$arrBoardCoop["IMG_URL"] = $rowBoadCoop["img_url"];
		$arrBoardCoop["TYPE"] = $rowBoadCoop["type"];
		$arrBoardCoop["DEPARTMENT"] = $rowBoadCoop["department"];
		$Layer2[] = $arrBoardCoop;
		
	}
	

	
	
	$arrayResult["BOARD_DATA"] = $arrayGroup;
	$arrayResult["OFFICER_DATA"] = $deptData;
	$arrayResult["LAYER1"] = $Layer1;
	$arrayResult["LAYER2"] = $Layer2;
	$arrayResult["TYPE"] = $arrayType;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);

}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>