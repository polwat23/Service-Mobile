<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();

$getSchduler = $conmysql->prepare("SELECT id_menuschedule,schedule_type,start_menu_status,start_menu_channel,schedule_end,time_end,end_menu_status,end_menu_channel,id_menu,is_action
														FROM gcmenuschedule WHERE is_use = '1' and NOW() >= schedule_start");
$getSchduler->execute();
while($rowSch = $getSchduler->fetch(PDO::FETCH_ASSOC)){
	if(isset($rowSch["schedule_end"]) && date('YmdHi',strtotime($rowSch["schedule_end"])) <= date('YmdHi')){
		$actionMenu = $conmysql->prepare("UPDATE gcmenu SET menu_status = :menu_status,menu_channel = :menu_channel WHERE id_menu  = :id_menu");
		$actionMenu->execute([
			':menu_status' => $rowSch["end_menu_status"],
			':menu_channel' => $rowSch["end_menu_channel"],
			':id_menu' => $rowSch["id_menu"]
		]);
		$updateSchedule = $conmysql->prepare("UPDATE gcmenuschedule SET is_use = '0' WHERE id_menuschedule = :id_menuschedule");
		$updateSchedule->execute([':id_menuschedule' => $rowSch["id_menuschedule"]]);
	}else{
		if($rowSch["schedule_type"] == 'onetime'){
			if($rowSch["is_action"] == '0'){
				$actionMenu = $conmysql->prepare("UPDATE gcmenu SET menu_status = :menu_status,menu_channel = :menu_channel WHERE id_menu  = :id_menu");
				$actionMenu->execute([
					':menu_status' => $rowSch["start_menu_status"],
					':menu_channel' => $rowSch["start_menu_channel"],
					':id_menu' => $rowSch["id_menu"]
				]);
				$updateSchedule = $conmysql->prepare("UPDATE gcmenuschedule SET is_action = '1' WHERE id_menuschedule = :id_menuschedule");
				$updateSchedule->execute([':id_menuschedule' => $rowSch["id_menuschedule"]]);
			}
		}else{
			if(date('Hi',strtotime($rowSch["time_end"])) <= date('Hi') && $rowSch["is_action"] == '1'){
				$actionMenu = $conmysql->prepare("UPDATE gcmenu SET menu_status = :menu_status,menu_channel = :menu_channel WHERE id_menu  = :id_menu");
				$actionMenu->execute([
					':menu_status' => $rowSch["end_menu_status"],
					':menu_channel' => $rowSch["end_menu_channel"],
					':id_menu' => $rowSch["id_menu"]
				]);
				$updateSchedule = $conmysql->prepare("UPDATE gcmenuschedule SET is_action = '0' WHERE id_menuschedule = :id_menuschedule");
				$updateSchedule->execute([':id_menuschedule' => $rowSch["id_menuschedule"]]);
			}else{
				if($rowSch["is_action"] == '0'){
					$actionMenu = $conmysql->prepare("UPDATE gcmenu SET menu_status = :menu_status,menu_channel = :menu_channel WHERE id_menu  = :id_menu");
					$actionMenu->execute([
						':menu_status' => $rowSch["start_menu_status"],
						':menu_channel' => $rowSch["start_menu_channel"],
						':id_menu' => $rowSch["id_menu"]
					]);
					$updateSchedule = $conmysql->prepare("UPDATE gcmenuschedule SET is_action = '1' WHERE id_menuschedule = :id_menuschedule");
					$updateSchedule->execute([':id_menuschedule' => $rowSch["id_menuschedule"]]);
				}
			}
		}
	}
}
?>