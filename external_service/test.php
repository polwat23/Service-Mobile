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
$fetchDataSTM = $conoracle->prepare("SELECT 
																		CASE kut.keepitemtype_code 
																		WHEN 'LON' THEN NVL(lt.LOANTYPE_DESC,kut.keepitemtype_desc) 
																		WHEN 'DEP' THEN NVL(dp.DEPTTYPE_DESC,kut.keepitemtype_desc) 
																		ELSE kut.keepitemtype_desc
																		END as TYPE_DESC,
																		kut.keepitemtype_grp as TYPE_GROUP,
																		'1' as MONEY_RETURN_STATUS,
																		kpd.ADJUST_ITEMAMT,
																		kpd.ADJUST_PRNAMT,
																		kpd.ADJUST_INTAMT,
																		case kut.keepitemtype_grp 
																			WHEN 'DEP' THEN kpd.description
																			WHEN 'LON' THEN kpd.loancontract_no
																		ELSE kpd.description END as PAY_ACCOUNT,
																		kpd.period,
																		NVL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
																		NVL(kpd.ITEM_BALANCE,0) AS ITEM_BALANCE,
																		NVL(kpd.principal_payment,0) AS PRN_BALANCE,
																		NVL(kpd.interest_payment,0) AS INT_BALANCE
																		FROM kpmastreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
																		kpd.keepitemtype_code = kut.keepitemtype_code
																		LEFT JOIN lnloantype lt ON kpd.shrlontype_code = lt.loantype_code
																		LEFT JOIN dpdepttype dp ON kpd.shrlontype_code = dp.depttype_code
																		WHERE kpd.member_no = '00863173' and TRIM(kpd.recv_period) = '256309'
																		and kpd.seq_no = '1'
																		ORDER BY kut.SORT_IN_RECEIVE ASC");
$fetchDataSTM->execute();
$rowSTM = $fetchDataSTM->fetch(PDO::FETCH_ASSOC);
echo json_encode($rowSTM);
?>