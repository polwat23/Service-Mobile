<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	
$arrayGroup = array();
	$year_now = date('Y');
	$year_group = [];
	
	
	$fecthDept = $conmysql->prepare("
										SELECT DISTINCT
											department

										FROM
											webcoopboardofdirectors
										WHERE
											emp_type = '1' AND YEAR = '2020' AND position1 != 1
	");
	
	
		$fecthDept->execute();
	$deptData = [];
	while($rowDept = $fecthDept->fetch(PDO::FETCH_ASSOC)){
	
	$fecthOfficer = $conmysql->prepare("
										  SELECT 
											id_board,
											f_name,
											l_name,
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
											emp_type = :emp_type AND YEAR = :year  and  position1 != '1' and position1 != '2'
		");
		$fecthOfficer->execute([
			':year' =>  $dataComing["year"],
			':emp_type' =>  $dataComing["emp_type"]??1,
			':department' =>  $rowDept["department"]
		]);
		$groupOfficer = [];
		while($rowOfficer = $fecthOfficer->fetch(PDO::FETCH_ASSOC)){
				$arrOfficer["F_NAME"] = $rowOfficer["f_name"];
				$arrOfficer["L_NAME"] = $rowOfficer["l_name"];
				$arrOfficer["POSITION1"] = $rowOfficer["position1"];
				$arrOfficer["POSITION2"] = $rowOfficer["position2"];
				$arrOfficer["IMG_URL"] = $rowOfficer["img_url"];
				$arrOfficer["DEPARTMENT"] = $rowOfficer["department"];
				$groupOfficer[] = $arrOfficer;
		}
	
	
	
	
	$arrDept["dept"] =  $rowDept["department"];
	$arrDept["officer"] =  $groupOfficer;
	$deptData[]=$arrDept;
	
	}
	
	$fetchBoadCoop = $conmysql->prepare("SELECT
												id_board,
												fullname,
												f_name,
												l_name,
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
		$arrBoardCoop["F_NAME"] = $rowBoadCoop["f_name"];
		$arrBoardCoop["L_NAME"] = $rowBoadCoop["l_name"];
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
	
	$fetchYear = $conmysql->prepare("SELECT DISTINCT
											year
										FROM
												webcoopboardofdirectors
												WHERE emp_type = :emp_type
										ORDER BY
												year	
										DESC	
										");
	$fetchYear->execute([
	':emp_type' =>  $dataComing["emp_type"]??1
	]);
	while($rowYear = $fetchYear->fetch(PDO::FETCH_ASSOC)){
		$arrYear=$rowYear["year"];
		$year_group[]=$arrYear;
	}
	
	
	$arrayResult["BOARD_DATA"] = $arrayGroup;
	$arrayResult["OFFICER_DATA"] = $deptData;
	$arrayResult["YEAR_DATA"] = $year_group;
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