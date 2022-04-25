<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){

	$arrayGroup = array();
	$year_group = [];
	$fecthDept = $conmysql->prepare("
					SELECT DISTINCT
						dept.dept_id,
						dept.dept_name
					FROM
						webcoopboardofdirectors b
					LEFT JOIN webcoopdepartment dept ON
						b.department_id = dept.dept_id
						WHERE b.is_use <> '-9' AND dept.is_use <> '-9' AND b.emp_type = '1'
	");
		$fecthDept->execute([
		':emp_type' =>  $dataComing["emp_type"]??0
	]);
	$deptData = [];
	while($rowDept = $fecthDept->fetch(PDO::FETCH_ASSOC)){
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
												b.department_id,
												branch.branch_name AS branch_name
											FROM
												webcoopboardofdirectors b
											LEFT JOIN webcooposition post ON
												b.position1 = post.id_post
											LEFT JOIN webcooposition post2 ON b.position2 = post2.id_post
											LEFT JOIN webcoopbranch branch ON branch.branch_id =b.branch_id 
											WHERE
												b.emp_type = :emp_type AND b.department_id = :department  AND post.is_use <>'-9'  AND b.is_use <> '-9'
			");
			$fecthOfficer->execute([
				':emp_type' =>  $dataComing["emp_type"]??0,
				':department' =>  $rowDept["dept_id"]
			]);
			$groupOfficer = [];
			while($rowOfficer = $fecthOfficer->fetch(PDO::FETCH_ASSOC)){
					$arrOfficer["ID_BOARD"] = $rowOfficer["id_board"];
					$arrOfficer["FULLNAME"] = $rowOfficer["fullname"];
					$arrOfficer["POSITION1"] = $rowOfficer["position1"];
					$arrOfficer["POSITION_NAME1"] = $rowOfficer["post_name1"];
					$arrOfficer["POSITION_NAME2"] = $rowOfficer["post_name2"];
					$arrOfficer["POSITION2"] = $rowOfficer["position2"];
					$arrOfficer["IMG_URL"] = $rowOfficer["img_url"];
					$arrOfficer["DEPARTMENT"] = $rowOfficer["department_id"];
					$arrOfficer["BRANCH_NAME"] = $rowOfficer["branch_name"];
					$arrOfficer["IMG_PATH"] = $rowOfficer["img_path"]; 
					$arrOfficer["IMG_URL"] = $rowOfficer["img_url"];
					$arrOfficer["YEAR"] = $rowOfficer["year"];
					$arrOfficer["LEVEL"] = $rowOfficer["level"];
					$groupOfficer[] = $arrOfficer;
			}
		$arrDept["dept"] =  $rowDept["dept_name"];
		$arrDept["DEPT_ID"] =  $rowDept["dept_id"];
		$arrDept["officer"] =  $groupOfficer;
		$deptData[]=$arrDept;
	}
	
	
	$fetchDataOfficer = $conmysql->prepare("SELECT type FROM webcoopboardofdirectors WHERE is_use ='1' AND emp_type ='1'");     
	$fetchDataOfficer->execute();
	$arrTypeData = $fetchDataOfficer->fetch(PDO::FETCH_ASSOC);
	$typeData =$arrTypeData["type"];
	
	if($typeData =="0"){
		$fetchBoadCoop = $conmysql->prepare("
										SELECT
											b.id_board,
											b.fullname,
											b.position1,
											post.post_name AS post_name1,
											post.level,
											post.id_post AS id_post,
											b.position2,
											post2.post_name AS post_name2,
											b.year,
											b.img_path,
											b.img_url,
											b.type,
											b.department_id,
											branch.branch_name AS branch_name
										
										FROM
											webcoopboardofdirectors b
										LEFT JOIN webcooposition post ON
											b.position1 = post.id_post
										LEFT JOIN webcooposition post2 ON
											b.position2 = post2.id_post
										LEFT JOIN webcoopbranch branch ON branch.branch_id =b.branch_id 
										WHERE b.is_use = '1'AND b.emp_type ='1' AND post.is_use <>'9' AND (post.level = '1' OR post.level='2')
										");
	}else{
		$fetchBoadCoop = $conmysql->prepare("
										SELECT
											id_board,
											fullname,
											position1,
											position2,
											year,
											img_path,
											img_url,
											type,
											department_id
										FROM
											webcoopboardofdirectors 
										WHERE is_use = '1' AND emp_type ='1'
										");
	}
	$fetchBoadCoop->execute();
	$arrayType = null;
	while($rowBoadCoop = $fetchBoadCoop->fetch(PDO::FETCH_ASSOC)){
		$arrBoardCoop["ID_BOARD"] = $rowBoadCoop["id_board"];
		$arrBoardCoop["FULLNAME"] = $rowBoadCoop["fullname"];
		$arrBoardCoop["POSITION1"] = $rowBoadCoop["position1"];
		$arrBoardCoop["POSITION_NAME1"] = $rowBoadCoop["post_name1"];
		$arrBoardCoop["POSITION2"] = $rowBoadCoop["position2"];
		$arrBoardCoop["POSITION_NAME2"] = $rowBoadCoop["post_name2"];
		$arrBoardCoop["BRANCH_NAME"] = $rowBoadCoop["branch_name"];
		$arrBoardCoop["ID_POST"] = $rowBoadCoop["id_post"];
		$arrBoardCoop["YEAR"] = $rowBoadCoop["year"];
		$arrBoardCoop["LEVEL"] = $rowBoadCoop["level"];
		$arrBoardCoop["IMG_PATH"] = $rowBoadCoop["img_path"];
		$arrBoardCoop["IMG_URL"] = $rowBoadCoop["img_url"];
		$arrBoardCoop["TYPE"] = $rowBoadCoop["type"];
		$arrBoardCoop["DEPARTMENT_ID"] = $rowBoadCoop["department_id"];
		$arrayGroup[] = $arrBoardCoop;
		$arrayType = $rowBoadCoop["type"];
	}
	$arrayResult["TYPE-DATA"] = $arrTypeData;
	$arrayResult["OFFICER_DATA"] = $deptData;
	$arrayResult["BOARD_DATA"] = $arrayGroup;
	$arrayResult["TYPE"] = $typeData;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);

}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>
