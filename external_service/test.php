<?php
require_once('../autoloadConnection.php');


$getSumAllAccount = $conoracle->prepare("SELECT SUM(prncbal) as SUM_BALANCE FROM dpdeptmaster WHERE TRIM(member_no) = :member_no ");
$getSumAllAccount->execute([':member_no' => '100001']);
$rowSumbalance = $getSumAllAccount->fetch(PDO::FETCH_ASSOC);
echo json_encode( $rowSumbalance);
?>