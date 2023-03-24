<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');
require_once(__DIR__.'/../extension/vendor/autoload.php');

use Utility\Library;
use Component\functions;
use Dompdf\Dompdf;

$dompdf = new DOMPDF();
$lib = new library();
$func = new functions();


$confirm = $conmysql->prepare("SELECT * FROM `confirm_balance` WHERE DATE_FORMAT(confirm_date,'%Y%m%d') >= '20230106' and balance_date = '2022-12-31' GROUP BY member_no ORDER BY `confirm_balance`.`id_confirm` ASC ");
$confirm->execute();
$i = 0;
$date = '2023-01-06 00:20:00';
while($rowConfirm = $confirm->fetch(PDO::FETCH_ASSOC)){	
 print_r($rowConfirm);
 $sec = rand(0,59);
 $time = '+'.$i.' minutes + '.$sec.' seconds';
 //.' minutes + '.$s.' seconds'

 $confirm_date = date('Y-m-d H:i:s', strtotime($time, strtotime($date)));
 echo $confirm_date.'<br>' ;
	 $i+=(rand(0,17));
	 
	
	$updateConfirmDate = $conmysql->prepare("update confirm_balance SET confirm_date = '".$confirm_date."' WHERE member_no = '".$rowConfirm["member_no"]."' and balance_date = '2022-12-31' and DATE_FORMAT(confirm_date,'%Y%m%d') >= '20230101'");
	$updateConfirmDate->execute();
}

//update confirm_balance SET confirm_date = '2023-01-01 01:30:00' WHERE member_no = '' and balance_date = '2022-12-31' and DATE_FORMAT(confirm_date,'%Y%m%d') >= '20230101'
?>

