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
	public function getShareInfo($member_no){
		$getCurrShare = $this->conora->prepare("SELECT SHR_QTY_SEN as LAST_STM_NO,SHR_SUM_BTH,SHR_SUM_SHR,LAST_PAGE,LAST_LINE,LAST_LINECARD,LAST_PAGECARD,BOOK_SEQ
												FROM shr_mem WHERE account_id = :member_no");
		$getCurrShare->execute([':member_no' => $member_no]);
		$rowCurrShare = $getCurrShare->fetch(\PDO::FETCH_ASSOC);
		return $rowCurrShare;
	}
	public function buyShare($conoracle,$member_no,$amt_transfer,$config,$operate_date,$log,$lib,$payload,$from_account_no,$ref_no){
		$constShr = $this->getShareInfo($member_no);
		$page_no =  $constShr["LAST_LINE"] <= 24 ? $constShr["LAST_PAGE"] : $constShr["LAST_PAGE"] + 1;
		$line_no = $constShr["LAST_LINE"] < 24 ? $constShr["LAST_LINE"] + 1 : 1;
		$page_card = $constShr["LAST_LINECARD"] <= 24 ? $constShr["LAST_PAGECARD"] : $constShr["LAST_PAGECARD"] + 1;
		$line_card = $constShr["LAST_LINECARD"] < 24 ? $constShr["LAST_LINECARD"] + 1 : 1;
		$getBillID = $conoracle->prepare("SELECT BILL_TYPE,BILL_RUNNING,BILL_YEAR FROM SYS_BILL WHERE BILL_ID = '01'");
		$getBillID->execute();
		$rowBillID = $getBillID->fetch(\PDO::FETCH_ASSOC);
		$billRunning = $rowBillID["BILL_RUNNING"] + 1;
		$billslip_id = $rowBillID["BILL_YEAR"].$rowBillID["BILL_TYPE"].str_pad($billRunning,6,'0',STR_PAD_LEFT);
		$getDataInfo = $conoracle->prepare("SELECT MEM_ID,BR_NO FROM MEM_H_MEMBER WHERE account_id = :member_no");
		$getDataInfo->execute([':member_no' => $member_no]);
		$rowDataInfo = $getDataInfo->fetch(\PDO::FETCH_ASSOC);
		$arrExecuteShStm = [
			':slip_no' => $billslip_id,
			':member_id' => $rowDataInfo["MEM_ID"],
			':br_no' => $rowDataInfo["BR_NO"],
			':month' => date('m'),
			':year' => date('Y') + 543,
			':shr_qty_amt' => $amt_transfer / 10,
			':amt_transfer' => $amt_transfer,
			':lastseq_no' => $constShr["LAST_STM_NO"] + 1,
			':shr_sum_bth' => $constShr["SHR_SUM_BTH"] + $amt_transfer,
			':page_no' => $page_no,
			':line_no' => $line_no,
			':book_seq' => $constShr['BOOK_SEQ'] + 1,
			':line_card' => $line_card,
			':page_card' => $page_card,
			':member_no' => $member_no,
			':ref_no' => $ref_no
		];
		$insertSTMShare = $conoracle->prepare("INSERT INTO shr_t_share(SLIP_NO,MEM_ID,BR_NO,SHR_NO,TYPE_SH_ID,TMP_MONTH,TMP_YEAR,TMP_SHARE_QTY,
												TMP_SHARE_BHT,TMP_SHARE_ST,TMP_DATE_REC,TMP_NAME_REC,SHR_SEN,TMP_DATE_TODAY,TEMP_TODAY,
												SHR_SUM_BTH,PAGE_NO,LINE_NO,FLG_PRN,NOBOOK_SEQ,TRANS_CODE,BR_NO_REC,LINECARD_NO,PAGECARD_NO,
												FLG_PRN_CARD,ACCOUNT_ID,REF_NUM) 
												VALUES (:slip_no,:member_id,:br_no,'01','C',:month,:year,:shr_qty_amt,:amt_transfer,'1',SYSDATE,'APP01',:lastseq_no,
												SYSDATE,TRUNC(SYSDATE),:shr_sum_bth,:page_no,:line_no,'N',:book_seq,'T07',:br_no,:line_card,:page_card,'N',:member_no,:ref_no)");
		if($insertSTMShare->execute($arrExecuteShStm)){
			//เหลือ Update master
			$arrExecuteMaster = [
				':lastseq_no' => $constShr["LAST_STM_NO"] + 1,
				':sum_shr_bth' => $constShr["SHR_SUM_BTH"] + $amt_transfer,
				':sum_shr' => ($constShr["SHR_SUM_BTH"] + $amt_transfer) / 10,
				':page_no' => $page_no,
				':line_no' => $line_no,
				':book_seq' => $constShr['BOOK_SEQ'] + 1,
				':line_card' => $line_card,
				':page_card' => $page_card,
				':member_no' => $member_no
			];
			$updateMaster = $conoracle->prepare("UPDATE shr_mem set rec_date = SYSDATE,rec_usrname = 'APP01',
												shr_qty_sen = :lastseq_no,shr_sum_bth = :sum_shr_bth,shr_sum_shr = :sum_shr,last_page = :page_no,last_line = :line_no,book_seq = :book_seq,
												last_linecard = :line_card,last_pagecard = :page_card where ACCOUNT_ID = :member_no");
			if($updateMaster->execute($arrExecuteMaster)){
				$updateSysBill = $conoracle->prepare("UPDATE sys_bill SET bill_running = :bill_running WHERE bill_id = '01'");
				if($updateSysBill->execute([
					':bill_running' => $billRunning
				])){
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
						':response_message' => 'UPDATE sys_bill ไม่ได้'.$updateSysBill->queryString."\n".json_encode([':bill_running:' => $billRunning])
					];
					$log->writeLog('buyshare',$arrayStruc);
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
					':response_message' => 'UPDATE shr_mem ไม่ได้'.$updateMaster->queryString."\n".json_encode($arrExecuteMaster)
				];
				$log->writeLog('buyshare',$arrayStruc);
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
				':response_message' => 'INSERT shr_t_share ไม่ได้'.$insertSTMShare->queryString."\n".json_encode($arrExecuteShStm)
			];
			$log->writeLog('buyshare',$arrayStruc);
			$arrayResult["RESPONSE_CODE"] = 'WS0065';
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
	}
}
?>
