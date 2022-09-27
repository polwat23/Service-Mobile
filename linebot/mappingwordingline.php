<?php
$arrPostData = array();
$arrPostData['replyToken'] = $reply_token;

if($message == "จัดการบัญชี" || $message == "ผูกบัญชี"){
	require_once('./service/bindaccount.php');
}else if($message == "ยกเลิกผูกบัญชี"){
	require_once('./service/unbindaccount.php');
}else if($message == "เมนูทั้งหมด" || $message == "เมนูสหกรณ์" || $message == "เมนู"  ){
	require_once('./service/allmenu.php');
}else if($message == "เงินฝาก" ){
	require_once('./service/deposit/deposit.php');
}else if($message == "เงินกู้"){
	require_once('./service/loan/loan.php');
}else if($message == "ข้อมูลส่วนตัว" || $message == "ข้อมูลของฉัน"){
	require_once('./service/member_info.php');
}else if($message == "หุ้น" || $message == "loan" ){
	require_once('./service/share/share_info.php');
}else if($message == "ค้ำประกัน" || $message == "ภาระค้ำประกัน"){
	require_once('./service/guarantee/guarantee_info.php');
}else if($message == "ข้อมูลใครค้ำคุณ" || $message == "ใครค้ำคุณ" || $message == "ใครค้ำฉัน" ){
	require_once('./service/guarantee/guarantee_whocollu.php');
}else if($message == "ภาระค้ำประกันของฉัน" || $message == "ข้อมูลภาระค้ำประกันของฉัน" || $message == "คุณค้ำใคร" || $message == "ฉันค้ำใคร"){
	require_once('./service/guarantee/guarantee_ucollwho.php');
}else if($message == "เรียกเก็บประจำเดือน" || $message == "เรียกเก็บ"){
	require_once('./service/keeping/keeping.php');

}else if($message == "ปันผล" || $message == "เฉลี่ยคืน"){
	require_once('./service/dividend.php');
}else if($message == "ผู้รับผลประโยชน์"){
	require_once('./service/beneficiary.php');
}else if($message == "--สิทธิ์กู้โดยประมาณ" || $message == "--สิทธิ์กู้"){
	require_once('./service/credit/loan_creadit.php');
}else if($message == "ฌาปนกิจ"){
	require_once('./service/cremation.php');
}else if($message == "ใบเสร็จ"){
	require_once('./service/receipt/receipt_list.php');
}else if($message == "กองทุนสวัสดิการ"){
	require_once('./service/fund/fund_type.php');
}else if($message == "แจ้งปัญหา"){
	require_once('./service/report/report_problem.php');
}else if($message == "กิจกรรมสหกรณ์"){
	require_once('./service/event.php');
}else if($message == "แจ้งเตือน"){
	require_once('./service/notify/line_notify.php');
}else if($message == " เปิดการแจ้งเตือน" || $message == " ปิดการแจ้งเตือน" ){
	require_once('./service/notify/update_notify.php');
}else if($message == "ติดตามใบคำขอกู้" || $message == "ติดตามใบคำขอกู้ออนไลน์" ){
	require_once('./service/trackreqloan.php');
}else if($message == "#ล็อคบัญชี"){
	require_once('./service/lockaccount/lockaccount.php');
}else if($message == "#ยืนยันการล็อคบัญชี"){
	require_once('./service/lockaccount/confirm_lockaccount.php');
}else{
	$pattern = "/[\s:\;\/]/"; 
	$arrMessage = preg_split($pattern, $message,-1,PREG_SPLIT_NO_EMPTY);
	$incomeWord = $arrMessage[0]??null;
	if($lineLib->checkBindAccount($user_id)){
		if($incomeWord == "ดูรายการเคลื่อนไหวเงินฝาก" || $incomeWord == "รายการเคลื่อนไหวเงินฝาก"){
			$deptNo = $arrMessage[1]??null;
			require_once('./service/deposit/deposit_statement.php');
		}else if($incomeWord == "ดูรายการเคลื่อนไหวเงินกู้" || $incomeWord == "รายการเคลื่อนไหวเงินกู้"){
			$loanContract_no = $arrMessage[1]??null;
			require_once('./service/loan/loan_statement.php');
		}else if($incomeWord == "ประเภทเงินฝาก"){
			$depttype = $arrMessage[1]??null;
			require_once('./service/deposit/deposit_type.php'); 
		}else if($incomeWord == "ประเภทเงินกู้"){
			$loan_type = $arrMessage[1]??null;
			require_once('./service/loan/loan_type.php'); 
		}else if($incomeWord == "ใบเสร็จ"){
			$kpslip_no = $arrMessage[1]??null;
			require_once('./service/receipt/receipt_detail.php'); 
		}else if($incomeWord == "ใบเสร็จกองทุนสวัสดิการ" || $incomeWord == "ดูใบเสร็จกองทุนสวัสดิการ"){
			$fund_account = $arrMessage[1]??null;
			require_once('./service/fund/fund_recept.php'); 
		}else if($incomeWord == "แจ้งปัญหา"){
			require_once('./service/report/report_problem.php');
		}else{
			//บันทึกลงข้อความไม่ได้ตอบ
			require_once('./service/notrespondmessage.php');
		}
	}else if($incomeWord == "แจ้งปัญหา"){
		require_once('./service/report/report_problem.php');
	}else{
		require_once('./service/bindaccount_by_otp.php');
	}
}

require_once('./service/incomtext.php');
require_once(__DIR__.'./replyresponse.php');
	
?>