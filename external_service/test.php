<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

header('Content-Type: application/json;charset=utf-8');

use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();

//$fetchDataSTM = $conoracle->prepare("SELECT * FROM asnreqmaster WHERE member_no = '00002283' and capital_year = '2562'");
$fetchDataSTM = $conoracle->prepare("SELECT *
											FROM slslippayin where payinslip_no = 'SD63006134'");
$fetchDataSTM->execute();
$rowSTM = $fetchDataSTM->fetch(PDO::FETCH_ASSOC);
echo json_encode($rowSTM);
?>