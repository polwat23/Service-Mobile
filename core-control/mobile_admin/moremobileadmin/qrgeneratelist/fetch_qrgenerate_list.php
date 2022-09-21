<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','qrgeneratelist')){
		$arrayGrpAll = array();
		$arrayMemberGrp = array();
		$arrayQueryMembNo = array();
		$arrayCount = 0;
		$arrayGrpQr = array();
		
		$fetchQrgenerateList = $conmysql->prepare("SELECT qrm.qrcodegen_id,qrm.qrgenerate,qrm.member_no,qrm.generate_date,qrm.qrtransfer_amt,qrm.qrtransfer_fee,
										qrm.expire_date,qrm.transfer_status,qrm.update_date,
										qrd.trans_code_qr,qrd.ref_account,qrd.qrtransferdt_amt,qrd.qrtransferdt_fee,
                                        qrc.trans_desc_qr
										FROM gcqrcodegenmaster qrm
										LEFT JOIN gcqrcodegendetail qrd ON qrd.qrgenerate = qrm.qrgenerate
										LEFT JOIN gcconttypetransqrcode qrc ON qrd.trans_code_qr = qrc.trans_code_qr order by  update_date desc");
			 
		$fetchQrgenerateList->execute();
		while($rowQr = $fetchQrgenerateList->fetch(PDO::FETCH_ASSOC)){
			$arrayQr = array();
			$arrayQr["QRCODEGEN_ID"] = $rowQr["qrcodegen_id"];
			$arrayQr["QRGENERATE"] = $rowQr["qrgenerate"];
			$arrayQr["MEMBER_NO"] = $rowQr["member_no"];
			if(isset($arrayMemberGrp[$rowQr["member_no"]])){
				
			}else{
				if(isset($rowQr["member_no"]) && $rowQr["member_no"] != ""){
					$arrayMemberGrp[$rowQr["member_no"]]["MEMBER_NO"] = $rowQr["member_no"];
					$arrayQueryMembNo[] = $rowQr["member_no"];
					$arrayCount++;
				}
			}
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
			
			if($arrayCount == 1000){
				$memberInfo = $conoracle->prepare("SELECT mp.prename_short,mb.memb_name,mb.memb_surname,mb.member_no
													FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													WHERE mb.member_no IN('".implode("','",$arrayQueryMembNo)."')");
				$memberInfo->execute();
				while($rowMemberInfo = $memberInfo->fetch(PDO::FETCH_ASSOC)){
					$arrayMemberGrp[$rowMemberInfo["MEMBER_NO"]]["FULLNAME"] = $rowMemberInfo["PRENAME_SHORT"].$rowMemberInfo["MEMB_NAME"]." ".$rowMemberInfo["MEMB_SURNAME"];
				}
				$arrayQueryMembNo = array();
				$arrayCount = 0;
			}
			$arrayGrpAll[] = $arrayQr;
		}
		
		if($arrayCount > 0){
			$memberInfo = $conoracle->prepare("SELECT mp.prename_short,mb.memb_name,mb.memb_surname,mb.member_no
											FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
											WHERE mb.member_no IN('".implode("','",$arrayQueryMembNo)."')");
			$memberInfo->execute();
			while($rowMemberInfo = $memberInfo->fetch(PDO::FETCH_ASSOC)){
				$arrayMemberGrp[$rowMemberInfo["MEMBER_NO"]]["FULLNAME"] = $rowMemberInfo["PRENAME_SHORT"].$rowMemberInfo["MEMB_NAME"]." ".$rowMemberInfo["MEMB_SURNAME"];
			}
		}
		
		foreach ($arrayGrpAll as $value) {
			$arrayQr = $value;
			$arrayQr["FULLNAME"] = $arrayMemberGrp[$value["MEMBER_NO"]]["FULLNAME"];
			$arrayGrpQr[] = $arrayQr;
		}
		
		$arrayResult['QrGenerateList'] = $arrayGrpQr;
		$arrayResult['i'] = $memberInfo;
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