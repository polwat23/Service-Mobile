<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){

	$arrayGroup = array();
	$year_group = [];
	$groupOfficer = array();
	
	$fecthOfficer = $conmysql->prepare("
										SELECT
											b.id_board,
											b.fullname,
											b.position1,
											post.post_name AS post_name1,
											post.level AS level,
											post.id_post AS id_post,
											b.position2,
											post2.post_name AS post_name2,
											b.year,
											b.img_path,
											b.img_url,
											b.type,
											b.department_id
										FROM
											webcoopboardofdirectors b
										LEFT JOIN webcooposition post ON
											b.position1 = post.id_post
										LEFT JOIN webcooposition post2 ON b.position2 = post2.id_post
										WHERE
											b.emp_type = :emp_type AND b.YEAR = :year   AND b.is_use <> '-9'
		");
	$fecthOfficer->execute([
		':year' =>  $dataComing["year"],
		':emp_type' =>  0
	]);
	
	while($rowOfficer = $fecthOfficer->fetch(PDO::FETCH_ASSOC)){
			$arrOfficer["ID_BOARD"] = $rowOfficer["id_board"];
			$arrOfficer["FULLNAME"] = $rowOfficer["fullname"];
			$arrOfficer["POSITION1"] = $rowOfficer["position1"];
			$arrOfficer["POSITION_NAME1"] = $rowOfficer["post_name1"];
			$arrOfficer["POSITION_NAME2"] = $rowOfficer["post_name2"];
			$arrOfficer["POSITION2"] = $rowOfficer["position2"];
			$arrOfficer["IMG_URL"] = $rowOfficer["img_url"];
			$arrOfficer["DEPARTMENT"] = $rowOfficer["department_id"];
			$arrOfficer["IMG_PATH"] = $rowOfficer["img_path"]; 
			$arrOfficer["IMG_URL"] = $rowOfficer["img_url"];
			$arrOfficer["YEAR"] = $rowOfficer["year"];
			$arrOfficer["LEVEL"] = $rowOfficer["level"];
			$groupOfficer[] = $arrOfficer;
	}
	$fetchBoadCoop = $conmysql->prepare("SELECT
												id_board,
												fullname,
												position1,
												position2,
												year,
												img_path,
												img_url,
												type
											FROM
												webcoopboardofdirectors
											WHERE year = :year AND is_use <> '-9'  AND emp_type = '0'  
											ORDER BY
												 position1	 
										");
	$fetchBoadCoop->execute([
		':year' =>  $dataComing["year"]
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
		$arrayGroup[] = $arrBoardCoop;
		$arrayType = $rowBoadCoop["type"];
	}
	$fetchYear = $conmysql->prepare("SELECT DISTINCT
											year
										FROM
												webcoopboardofdirectors
										WHERE emp_type = '0'
										ORDER BY
												year	
										DESC	
										");
	$fetchYear->execute();
	while($rowYear = $fetchYear->fetch(PDO::FETCH_ASSOC)){
		$arrYear=$rowYear["year"];
		$year_group[]=$arrYear;
	}
	$arrayResult["OFFICER_DATA"] = $groupOfficer;
	$arrayResult["BOARD_DATA"] = $arrayGroup;
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
