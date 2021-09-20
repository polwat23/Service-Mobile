<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','qrgeneratelist')){
		$arrayGrpAll = array();
		
		$fetchQrgenerateList = $conmysql->prepare("SELECT qrm.qrcodegen_id,qrm.qrgenerate,qrm.member_no,qrm.generate_date,qrm.qrtransfer_amt,qrm.qrtransfer_fee,
										qrm.expire_date,qrm.transfer_status,qrm.update_date,
										qrd.trans_code_qr,qrd.ref_account,qrd.qrtransferdt_amt,qrd.qrtransferdt_fee,
                                        qrc.trans_desc_qr, qrd.trans_status
										FROM gcqrcodegenmaster qrm
										LEFT JOIN gcqrcodegendetail qrd ON qrd.qrgenerate = qrm.qrgenerate
										LEFT JOIN gcconttypetransqrcode qrc ON qrd.trans_code_qr = qrc.trans_code_qr
									");
			
		$fetchQrgenerateList->execute();
		while($rowQr = $fetchQrgenerateList->fetch(PDO::FETCH_ASSOC)){
			$arrayQr = array();
			$arrayQr["QRCODEGEN_ID"] = $rowQr["qrcodegen_id"];
			$arrayQr["QRGENERATE"] = $rowQr["qrgenerate"];
			$arrayQr["MEMBER_NO"] = $rowQr["member_no"];
			$arrayQr["GENERATE_DATE"] = $lib->convertdate($rowQr["generate_date"],'d m Y',true);
			$arrayQr["QRTRANSFER_AMT"] = number_format($rowQr["qrtransfer_amt"],2);
			$arrayQr["QRTRANSFER_FEE"] = number_format($rowQr["qrtransfer_fee"],2);
			$arrayQr["EXPIRE_DATE"] = $lib->convertdate($rowQr["expire_date"],'d m Y',true);
			$arrayQr["TRANSFER_STATUS"] = $rowQr["transfer_status"];
			$arrayQr["UPDATE_DATE"] = $lib->convertdate($rowQr["update_date"],'d m Y',true);
			$arrayQr["TRANS_CODE_QR"] = $rowQr["trans_code_qr"];
			$arrayQr["REF_ACCOUNT"] = $rowQr["ref_account"];
			$arrayQr["QRTRANSFERDT_AMT"] = number_format($rowQr["qrtransferdt_amt"],2);
			$arrayQr["QRTRANSFERDT_FEE"] = number_format($rowQr["qrtransferdt_fee"],2);
			$arrayQr["TRANS_DESC_QR"] = $rowQr["trans_desc_qr"];
			$arrayQr["TRANS_STATUS"] = $rowQr["trans_status"];
			
			$arrayGrpAll[] = $arrayQr;
		}
		
		$arrayResult['QrGenerateList'] = $arrayGrpAll;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
}
?>