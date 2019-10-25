<?php
require_once('autoload.php');

$arrayGroup = array();
$fetchMenu = $conmysql->prepare("SELECT * FROM coremenu WHERE coremenu_status = '1' and coremenu_parent = '0'");
$fetchMenu->execute();
while($rowMenu = $fetchMenu->fetch()){
	$arrMenu = array();
	$arrMenu["MENU_NAME"] = $rowMenu["coremenu_name"];
	$arrMenu["MENU_ICONPATH"] = $rowMenu["coremenu_iconpath"];
	$arrayGroup[] = $arrMenu;
}
$arrayResult["MENU"] = $arrayGroup;
$arrayResult["RESULT"] = TRUE;
echo json_encode($arrayResult);
?>