<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id','rootmenu'],$dataComing)){
	$arrayGroup = array();
	$getGroupSubMenu = $conoracle->prepare("SELECT asg.GROUP_DESC AS MENU_NAME,asg.PAGE_NAME,asg.GROUP_CODE
										FROM amsecwinsgroup asg
										WHERE asg.application = 'user' and asg.PAGE_NAME <> '0' and asg.ROOT_MENU = :rootmenu ORDER BY asg.GROUP_ORDER ASC");
	$getGroupSubMenu->execute([
		':rootmenu' => $dataComing["rootmenu"]
	]);
	while($rowGrpMenu = $getGroupSubMenu->fetch(PDO::FETCH_ASSOC)){
		$arrGroupRootMenu = array();
		$arrGroupRootMenu["ROOT_MENU_NAME"] = $rowGrpMenu["MENU_NAME"];
		$arrGroupRootMenu["ROOT_PATH"] = $rowGrpMenu["PAGE_NAME"];
		$getSubMenu = $conoracle->prepare("SELECT ams.WIN_DESCRIPTION as MENU_NAME,ams.WIN_OBJECT as PAGE_NAME
										FROM amsecwins ams LEFT JOIN  amsecpermiss amp ON ams.application = amp.application AND ams.window_id = amp.window_id
										WHERE ams.application = 'user' and amp.check_flag = '1' AND user_name =: user_name and ams.GROUP_CODE = :grp_code ORDER BY ams.WIN_ORDER ASC");
		$getSubMenu->execute([
			':grp_code' => $rowGrpMenu["GROUP_CODE"],
			':user_name' => $payload["username"]
		]);
		while($rowSubMenu = $getSubMenu->fetch(PDO::FETCH_ASSOC)){
			$arrayGroupSubMenu = array();
			$arrayGroupSubMenu["SUB_MENU_NAME"] = $rowSubMenu["MENU_NAME"];
			$arrayGroupSubMenu["SUB_PAGE_NAME"] = '/'.$dataComing["rootmenu"].'/'.$rowGrpMenu["PAGE_NAME"].'/'.$rowSubMenu["PAGE_NAME"];
			($arrGroupRootMenu["SUB_MENU"])[] = $arrayGroupSubMenu;
		}
		if(isset($arrGroupRootMenu["SUB_MENU"])){
			$arrayGroup[] = $arrGroupRootMenu;
		}
	}
	$arrayResult["SUB_MENU"] = $arrayGroup;
	$arrayResult["RESULT"] = TRUE;
	require_once('../../include/exit_footer.php');

}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>