<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');
require_once(__DIR__.'/../extension/vendor/autoload.php');


use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();

if ( $xlsx = SimpleXLSX::parse('div.xlsx') ) {
	foreach($xlsx->rows(0) as $row => $rowData){
		if($row != 0){
			if(isset($rowData[0]) && $rowData[0] != ""){
				$insert = $conmysql->prepare("INSERT INTO yrimport(member_no, year, div_amt, avg_amt, execution, revenue, loan_kp, loan_waitpay, 
											share_waitpay, loan_coop, insure_loan, forward_cremation, grj_deposit, ssk_waitpay, sks_waitpay, shareandloan_waitpay, cre_forward_ssak, 
											cremation_ssak, cre_forward_fscct, cre_forward_s_ch_a_n, cre_forward_ss_st, balance, receive_desc, receive_acc, remark1, remark2) 
											VALUES (:member_no,'2564',:div_amt,:avg_amt,:execution,:revenue,:loan_kp,:loan_waitpay,:share_waitpay,:loan_coop,
											:insure_loan,:forward_cremation,:grj_deposit,:ssk_waitpay,:sks_waitpay,:shareandloan_waitpay,:cre_forward_ssak,:cremation_ssak,
											:cre_forward_fscct,:cre_forward_s_ch_a_n,:cre_forward_ss_st,:balance,:receive_desc,:receive_acc,:remark1,:remark2)");
				if($insert->execute([
					':member_no' => $lib->mb_str_pad($rowData[0]),
					':div_amt' => $rowData[4],
					':avg_amt' => $rowData[5],
					':execution' => $rowData[6],
					':revenue' => $rowData[7],
					':loan_kp' => $rowData[8],
					':loan_waitpay' => $rowData[9],
					':share_waitpay' => $rowData[10],
					':loan_coop' => $rowData[11],
					':insure_loan' => $rowData[12],
					':forward_cremation' => $rowData[13],
					':grj_deposit' => $rowData[14],
					':ssk_waitpay' => $rowData[15],
					':sks_waitpay' => $rowData[16],
					':shareandloan_waitpay' => $rowData[17],
					':cre_forward_ssak' => $rowData[18],
					':cremation_ssak' => $rowData[19],
					':cre_forward_fscct' => $rowData[20],
					':cre_forward_s_ch_a_n' => $rowData[21],
					':cre_forward_ss_st' => isset($rowData[22]) && $rowData[22] != "" ? $rowData[22] : 0,
					':balance' => $rowData[23],
					':receive_desc' => $rowData[24],
					':receive_acc' => $rowData[25],
					':remark1' => $rowData[26],
					':remark2' => $rowData[27]
				])){
					echo 'success / ';
					//echo json_encode($rowData,JSON_UNESCAPED_UNICODE );
				}else{
					echo 'fail'.$rowData[0].' /'.json_encode($insert->errorInfo());
				}
			}
		}
	}
} else {
	echo SimpleXLSX::parseError();
}
?>