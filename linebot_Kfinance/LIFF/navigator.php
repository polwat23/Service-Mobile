<?php
if(isset($_GET["liff_state"]) && $_GET["liff_state"] != ""){
	$pagenameArr = $_GET["liff_state"] ?? null;
	$pagenameArr = explode('=',$pagenameArr);
	if($pagenameArr[1] == 'register'){
		require_once('./register.php');
	}
}else{
	if($_GET['page'] == 'register'){
		require_once('./register.php');
	}
}
?>