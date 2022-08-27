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
		$getCurrShare = $this->conora->prepare("SELECT SHARESTK_AMT,(SHARESTK_AMT * 50) as SHARE_AMT,LAST_PERIOD,LAST_STM_NO
												FROM SHSHAREMASTER WHERE member_no = :member_no");
		$getCurrShare->execute([':member_no' => $member_no]);
		$rowCurrShare = $getCurrShare->fetch(\PDO::FETCH_ASSOC);
		return $rowCurrShare;
	}
	public function buyShare($conoracle,$member_no,$amt_transfer,$penalty_amt,$config,$shslip_docno,$operate_date,
	$tofrom_accid,$slipwtd=null,$log,$lib,$payload,$from_account_no,$shslip_no,$ref_no,$is_paymonth=false){
		//$rowContShare = $this->getConstShare();
		$dataShare = $this->getShareInfo($member_no);
		$sharereq_value = $dataShare["SHARE_AMT"] + $amt_transfer;
		$getMemberInfo = $conoracle->prepare("SELECT MEMBGROUP_CODE FROM mbmembmaster WHERE member_no = :member_no");
		$getMemberInfo->execute([':member_no' => $member_no]);
		$rowMember = $getMemberInfo->fetch(\PDO::FETCH_ASSOC);
		/*if($sharereq_value > $rowContShare["MAXSHARE_HOLD"]){
			$arrayResult['RESPONSE_CODE'] = "WS0075";
			$arrayResult['TYPE_ERR'] = "MAXSHARE_HOLD";
			$arrayResult['SHARE_ERR'] = "0001";
			$arrayResult['AMOUNT_ERR'] = $rowContShare["MAXSHARE_HOLD"];
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}*/
		if($is_paymonth){
			$arrExecuteShStm = [
				':coop_id' => $config["COOP_ID"],
				':member_no' => $member_no,
				':last_seq' => $dataShare["LAST_STM_NO"] + 1,
				':ref_docno' => $shslip_docno,
				':ref_slipno' => $shslip_no,
				':itemtype' => 'SPM',
				':period' => $dataShare["LAST_PERIOD"] + 1,
				':share_amt' => $amt_transfer / 50,
				':sharebal' => $dataShare["SHARESTK_AMT"] + ($amt_transfer / 50),
				':moneytype_code' => 'TRN'
			];
		}else{
			$arrExecuteShStm = [
				':coop_id' => $config["COOP_ID"],
				':member_no' => $member_no,
				':last_seq' => $dataShare["LAST_STM_NO"] + 1,
				':ref_docno' => $shslip_docno,
				':ref_slipno' => $shslip_no,
				':itemtype' => 'SPX',
				':period' => $dataShare["LAST_PERIOD"] + 1,
				':share_amt' => $amt_transfer / 50,
				':sharebal' => $dataShare["SHARESTK_AMT"] + ($amt_transfer / 50),
				':moneytype_code' => 'TRN'
			];
		}
		$insertSTMShare = $conoracle->prepare("INSERT INTO shsharestatement(COOP_ID,MEMBER_NO,SHARETYPE_CODE,SEQ_NO,SLIP_DATE,OPERATE_DATE,SHARE_DATE,ACCOUNT_DATE,
												REF_DOCNO,REF_SLIPNO,SHRITEMTYPE_CODE,PERIOD,SHARE_AMOUNT,SHARESTK_AMT,MONEYTYPE_CODE,ENTRY_ID,ENTRY_DATE,SYNC_NOTIFY_FLAG)
												VALUES(:coop_id,:member_no,'01',:last_seq,TRUNC(SYSDATE),TRUNC(SYSDATE),TRUNC(SYSDATE),TRUNC(SYSDATE),:ref_docno,:ref_slipno,
												:itemtype,:period,:share_amt,:sharebal,:moneytype_code,'MOBILE',SYSDATE,'1')");
		if($insertSTMShare->execute($arrExecuteShStm)){
			$arrExecuteMaster = [
				':sharebal' => $dataShare["SHARESTK_AMT"] + ($amt_transfer / 50),
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
