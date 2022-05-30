<?php

namespace CalculateShare;

use Connection\connection;
use Utility\Library;


class CalculateShare {
	private $con;
	private $conora;
	private $lib;
	
	function __construct() {
		$connection = new connection();
		$this->lib = new library();
		$this->con = $connection->connecttomysql();
		$this->conora = $connection->connecttooracle();
	}
	private function getConstShare(){
		$getConstantShare = $this->conora->prepare("SELECT MAXSHARE_HOLD FROM SHSHARETYPE WHERE SHARETYPE_CODE = '01'");
		$getConstantShare->execute();
		$rowContShare = $getConstantShare->fetch(\PDO::FETCH_ASSOC);
		return $rowContShare;
	}
	public function getShareInfo($member_no){
		$getCurrShare = $this->conora->prepare("SELECT SHR_QTY_SEN as LAST_STM_NO,SHR_SUM_BTH,SHR_SUM_SHR
												FROM shr_mem WHERE account_id = :member_no");
		$getCurrShare->execute([':member_no' => $member_no]);
		$rowCurrShare = $getCurrShare->fetch(\PDO::FETCH_ASSOC);
		return $rowCurrShare;
	}
	public function buyShare($conoracle,$member_no,$amt_transfer,$penalty_amt,$config,$shslip_docno,$operate_date,
	$tofrom_accid,$slipwtd=null,$log,$lib,$payload,$from_account_no,$shslip_no,$ref_no,$is_paymonth=false){
		$getBillID = $conoracle->prepare("SELECT BILL_TYPE,BILL_RUNNING,BILL_YEAR FROM SYS_BILL WHERE BILL_ID = '01'");
		$getBillID->execute();
		$rowBillID = $getBillID->fetch(\PDO::FETCH_ASSOC);
		$billslip_id = $rowBillID["BILL_YEAR"].$rowBillID["BILL_TYPE"].($rowBillID["BILL_RUNNING"] + 1);
		$getDataInfo = $conoracle->prepare("SELECT MEM_ID,BR_NO FROM MEM_H_MEMBER WHERE account_id = :member_no");
		$getDataInfo->execute([':member_no' => $member_no]);
		$rowDataInfo = $getDataInfo->fetch(\PDO::FETCH_ASSOC);
		$arrExecuteShStm = [
			':slip_no' => $billslip_id,
			':member_id' => $rowDataInfo["MEM_ID"],
			':br_no' => $rowDataInfo["BR_NO"],
			':month' => date('m'),
			':year' => date('Y') + 543,
			':ref_slipno' => $shslip_no,
			':itemtype' => 'SPM',
			':period' => $dataShare["LAST_PERIOD"],
			':share_amt' => $amt_transfer / 10,
			':sharebal' => $dataShare["SHARESTK_AMT"] + ($amt_transfer / 10),
			':moneytype_code' => 'TRN'
		];
		$insertSTMShare = $conoracle->prepare("INSERT INTO shr_t_share(SLIP_NO,MEM_ID,BR_NO,SHR_NO,TYPE_SH_ID,TMP_MONTH,TMP_YEAR,TMP_SHARE_QTY,
												TMP_SHARE_BHT,TMP_SHARE_ST,TMP_DATE_REC,TMP_NAME_REC,SHR_SEN,TMP_DATE_TODAY,TEMP_TODAY,
												SHR_SUM_BTH,PAGE_NO,LINE_NO,FLG_PRN,NOBOOK_SEQ,TRANS_CODE,BR_NO_REC,LINECARD_NO,PAGECARD_NO,
												FLG_PRN_CARD,ACCOUNT_ID) 
												VALUES (:slip_no,:member_id,:br_no,'01','C',:month,:year,:shr_amt,:amt_transfer,'1',SYSDATE,'APP01',82,
												SYSDATE,TRUNC(SYSDATE),9000,4,12,'N',84,'S04','121',4,2,'N','1210123585')");
		if($insertSTMShare->execute($arrExecuteShStm)){
			$arrExecuteMaster = [
				':sharebal' => $dataShare["SHARESTK_AMT"] + ($amt_transfer / 10),
				':last_period' => $dataShare["LAST_PERIOD"] + 1,
				':last_stm' => $dataShare["LAST_STM_NO"] + 1,
				':member_no' => $member_no
			];
			$updateMaster = $conoracle->prepare("UPDATE shsharemaster SET SHARESTK_AMT = :sharebal,LAST_PERIOD = :last_period,LAST_STM_NO = :last_stm,
												LASTKEEPING_DATE = TRUNC(SYSDATE) WHERE member_no = :member_no");
			if($updateMaster->execute($arrExecuteMaster)){
				$arrayResult['RESULT'] = TRUE;
				return $arrayResult;
			}else{
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => $operate_date,
					':deptaccount_no' => $from_account_no,
					':amt_transfer' => $amt_transfer,
					':status_flag' => '0',
					':destination' => $member_no,
					':response_code' => "WS0065",
					':response_message' => 'UPDATE shsharemaster ไม่ได้'.$updateMaster->queryString."\n".json_encode($arrExecuteMaster)
				];
				$log->writeLog('buyshare',$arrayStruc);
				file_put_contents('test.txt',json_encode($arrayStruc));
				$arrayResult["RESPONSE_CODE"] = 'WS0065';
				$arrayResult['RESULT'] = FALSE;
				return $arrayResult;
			}
		}else{
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':operate_date' => $operate_date,
				':deptaccount_no' => $from_account_no,
				':amt_transfer' => $amt_transfer,
				':status_flag' => '0',
				':destination' => $member_no,
				':response_code' => "WS0065",
				':response_message' => 'INSERT shsharestatement ไม่ได้'.$insertSTMShare->queryString."\n".json_encode($arrExecuteShStm)
			];
			$log->writeLog('buyshare',$arrayStruc);
			file_put_contents('test.txt',json_encode($arrayStruc));
			$arrayResult["RESPONSE_CODE"] = 'WS0065';
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
	}
}
?>
