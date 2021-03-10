<?php

namespace CalculateLoan;

use Connection\connection;
use Utility\Library;


class CalculateLoan {
	private $con;
	private $conora;
	private $lib;
	
	function __construct() {
		$connection = new connection();
		$this->lib = new library();
		$this->con = $connection->connecttomysql();
		$this->conora = $connection->connecttooracle();
	}
	public function calculateInterest($loancontract_no,$amt_transfer=0){
		$constLoanContract = $this->getContstantLoanContract($loancontract_no);
		$constLoan = $this->getLoanConstant();
		$interest = 0;
		if($constLoanContract["CHECK_KEEPING"] == '1'){
			$calInt = TRUE;
		}else{
			if($constLoanContract["SPACE_KEEPING"] == 0){
				$calInt = TRUE;
			}else{
				if($constLoanContract["PXAFTERMTHKEEP_TYPE"] == '1'){
					$calInt = FALSE;
					$interest = $constLoanContract["INTEREST_ARREAR"];
				}else{
					$calInt = TRUE;
				}
			}
		}
		if($calInt){
			$yearFrom = date('Y',strtotime($constLoanContract["LASTCALINT_DATE"]));
			$changerateint = $this->checkChangeRateInt($constLoanContract["LOANTYPE_CODE"],$this->lib->convertdate($constLoanContract["LASTCALINT_DATE"],'ynd'));
			$yearTo = date('Y');
			$yearDiff = $yearTo - $yearFrom;
			if($changerateint){
				$yearDiff = 1;
			}
			$yearDiffTemp = 0;
			for($i = 0;$i <= $yearDiff;$i++){
				if($constLoanContract["INT_CONTINTTYPE"] == '2'){
					$intrate = $this->getRateInt($constLoanContract["INT_CONTINTTABCODE"],$this->lib->convertdate($constLoanContract["LASTCALINT_DATE"],'y-n-d'));
				}else if($constLoanContract["INT_CONTINTTYPE"] == '1'){
					$intrate = $constLoanContract["INT_CONTINTRATE"];
				}else if($constLoanContract["INT_CONTINTTYPE"] == '0'){
					return 0;
				}
				$dayinyear = 0;
				if($constLoan["DAYINYEAR"] > 0){
					$dayinyear = $constLoan["DAYINYEAR"];
				}else{
					$dayinyear = $this->lib->getnumberofYear(date('Y',strtotime('+'.$yearDiffTemp.' year',strtotime($constLoanContract["LASTCALINT_DATE"]))));	
				}
				$dateFrom = new \DateTime(date('d-m-Y',strtotime('+'.$yearDiffTemp.' year',strtotime($constLoanContract["LASTCALINT_DATE"]))));
				if($yearDiffTemp == 0 && $yearDiff > 0){
					$dateTo = new \DateTime('31-12-'.date('Y',strtotime($constLoanContract["LASTCALINT_DATE"])));
					$date_duration = $dateTo->diff($dateFrom);
					$dayInterest = $date_duration->days;
					if($dayInterest == 0){
						$dayInterest++;
					}
				}else{
					if($yearDiffTemp > 0){
						$dateFrom = new \DateTime('31-12-'.date('Y',strtotime($constLoanContract["LASTCALINT_DATE"])));
					}
					$dateTo = new \DateTime(date('d-m-Y'));
					$date_duration = $dateTo->diff($dateFrom);
					$dayInterest = $date_duration->days;
				}
				$yearDiffTemp++;
				if($constLoanContract["PAYSPEC_METHOD"] == '1'){
					$prn_bal = $constLoanContract["PRINCIPAL_BALANCE"];
				}else{
					$prn_bal = $amt_transfer;
				}
				if($constLoanContract["INTEREST_METHOD"] != '2'){
					$interest += (($prn_bal * ($intrate / 100)) * $dayInterest) / $dayinyear;
				}
			}
			$interest = $this->lib->roundDecimal($interest,$constLoan["RDINTSATANG_TYPE"]) + $constLoanContract["INTEREST_ARREAR"];
		}
		return $interest;
	}
	public function calculateIntAccum($member_no){
		$getAccYear = $this->conora->prepare("SELECT ACCOUNT_YEAR FROM CMACCOUNTYEAR WHERE TRUNC(SYSDATE) BETWEEN TRUNC(ACCSTART_DATE) AND TRUNC(ACCEND_DATE)");
		$getAccYear->execute();
		$rowAccYear = $getAccYear->fetch(\PDO::FETCH_ASSOC);
		$getIntAccum = $this->conora->prepare("SELECT NVL(SUM(LNS.INTEREST_PAYMENT),0) AS INT_ACCUM FROM LNCONTMASTER LNM 
												LEFT JOIN LNCONTSTATEMENT LNS ON LNM.LOANCONTRACT_NO = LNS.LOANCONTRACT_NO,CMACCOUNTYEAR CMY
												WHERE LNM.MEMBER_NO = :member_no AND CMY.ACCOUNT_YEAR = :account_year AND TRUNC(ENTRY_DATE) >= TRUNC(ACCSTART_DATE) 
												AND TRUNC(ENTRY_DATE) <= TRUNC(ACCEND_DATE)");
		$getIntAccum->execute([
			':member_no' => $member_no,
			':account_year' => $rowAccYear["ACCOUNT_YEAR"]
		]);
		$rowIntAccum = $getIntAccum->fetch(\PDO::FETCH_ASSOC);
		return $rowIntAccum["INT_ACCUM"];
	}
	public function calculateIntReturn($loancontract_no,$amt_transfer,$interest=0){
		$constLoanContract = $this->getContstantLoanContract($loancontract_no);
		$constLoan = $this->getLoanConstant();
		$yearFrom = date('Y',strtotime($constLoanContract["LASTPROCESS_DATE"]));
		$changerateint = $this->checkChangeRateInt($constLoanContract["LOANTYPE_CODE"],$this->lib->convertdate($constLoanContract["LASTPROCESS_DATE"],'ynd'));
		$yearTo = date('Y');
		$yearDiff = $yearFrom - $yearTo;
		if($changerateint){
			$yearDiff = 1;
		}
		$yearDiffTemp = 0;
		for($i = 0;$i <= $yearDiff;$i++){
			if($constLoanContract["INT_CONTINTTYPE"] == '2'){
				$intrate = $this->getRateInt($constLoanContract["INT_CONTINTTABCODE"],$this->lib->convertdate($constLoanContract["LASTPROCESS_DATE"],'y-n-d'));
			}else if($constLoanContract["INT_CONTINTTYPE"] == '1'){
				$intrate = $constLoanContract["INT_CONTINTRATE"];
			}else if($constLoanContract["INT_CONTINTTYPE"] == '0'){
				return 0;
			}
			$dayinyear = 0;
			if($constLoan["DAYINYEAR"] > 0){
				$dayinyear = $constLoan["DAYINYEAR"];
			}else{
				$dayinyear = $this->lib->getnumberofYear(date('Y',strtotime('+'.$yearDiffTemp.' year',strtotime($constLoanContract["LASTPROCESS_DATE"]))));	
			}
			$dateFrom = new \DateTime(date('d-m-Y',strtotime('+'.$yearDiffTemp.' year',strtotime($constLoanContract["LASTPROCESS_DATE"]))));
			if($yearDiffTemp == 0 && $yearDiff > 0){
				$dateTo = new \DateTime('31-12-'.date('Y'));
				$date_duration = $dateFrom->diff($dateTo);
				$dayInterest = $date_duration->days;
				if($dayInterest == 0){
					$dayInterest++;
				}
			}else{
				if($yearDiffTemp > 0){
					$dateFrom = new \DateTime('31-12-'.date('Y'));
				}
				$dateTo = new \DateTime(date('d-m-Y'));
				$date_duration = $dateFrom->diff($dateTo);
				$dayInterest = $date_duration->days;
			}
			$yearDiffTemp++;
			$prn_bal = $amt_transfer;
			if($constLoanContract["INTEREST_METHOD"] != '2'){
				$int_return += (($prn_bal * ($intrate / 100)) * $dayInterest) / $dayinyear;
			}
		}
		if($constLoanContract["PXAFTERMTHKEEP_TYPE"] != '1'){
			$int_return = $int_return + $interest;
		}
		$int_return = $this->lib->roundDecimal($int_return,$constLoan["RDINTSATANG_TYPE"],'1') + $constLoanContract["INTEREST_RETURN"];
		return $int_return;
	}
	private function getRateInt($inttabcode,$date){
		$contLoan = $this->conora->prepare("SELECT INTEREST_RATE
											FROM lncfloanintratedet
											WHERE LOANINTRATE_CODE = :inttabcode
											and '".$date."' BETWEEN TO_CHAR(EFFECTIVE_DATE,'YYYY-MM-DD') and TO_CHAR(EXPIRE_DATE,'YYYY-MM-DD')");
		$contLoan->execute([
			':inttabcode' => $inttabcode
		]);
		$constLoanRate = $contLoan->fetch(\PDO::FETCH_ASSOC);
		return $constLoanRate["INTEREST_RATE"];
	}
	private function checkChangeRateInt($inttabcode,$date){
		$change_rate = FALSE;
		$contLoan = $this->conora->prepare("SELECT TO_CHAR(EFFECTIVE_DATE,'YYYYMMDD') as EFFECTIVE_DATE
											FROM lncfloanintratedet
											WHERE LOANINTRATE_CODE = :inttabcode");
		$contLoan->execute([
			':inttabcode' => $inttabcode
		]);
		while($constLoanRate = $contLoan->fetch(\PDO::FETCH_ASSOC)){
			if($constLoanRate["EFFECTIVE_DATE"] > $date){
				return TRUE;
			}else{
				$change_rate = FALSE;
			}
		}
		return $change_rate;
	}
	public function getContstantLoanContract($loancontract_no){
		$contLoan = $this->conora->prepare("SELECT LNM.LOANAPPROVE_AMT,LNM.PRINCIPAL_BALANCE,LNM.PERIOD_PAYMENT,LNM.PERIOD_PAYAMT,LNM.LAST_PERIODPAY,
											LNM.LOANTYPE_CODE,LNM.INTEREST_ARREAR,LNT.PXAFTERMTHKEEP_TYPE,LNM.RKEEP_PRINCIPAL,LNM.RKEEP_INTEREST,
											LNM.LASTCALINT_DATE,LNM.LOANPAYMENT_TYPE,LNT.CONTINT_TYPE,LNT.INTEREST_METHOD,LNT.PAYSPEC_METHOD,LNT.INTSTEP_TYPE,LNM.LASTPROCESS_DATE,
											(LNM.NKEEP_PRINCIPAL + LNM.NKEEP_INTEREST) as SPACE_KEEPING,LNM.INTEREST_RETURN,LNM.NKEEP_PRINCIPAL,LNM.NKEEP_INTEREST,
											(CASE WHEN LNM.LASTPROCESS_DATE <= LNM.LASTCALINT_DATE OR LNM.LASTPROCESS_DATE IS NULL THEN '1' ELSE '0' END) AS CHECK_KEEPING,LNM.LAST_STM_NO,
											LNM.INT_CONTINTTYPE,LNM.INT_CONTINTRATE,LNM.INT_CONTINTTABCODE
											FROM lncontmaster lnm LEFT JOIN lnloantype lnt ON lnm.LOANTYPE_CODE = lnt.LOANTYPE_CODE
											WHERE lnm.loancontract_no = :contract_no and lnm.contract_status > 0 and lnm.contract_status <> 8");
		$contLoan->execute([':contract_no' => $loancontract_no]);
		$constLoanContract = $contLoan->fetch(\PDO::FETCH_ASSOC);
		$constLoanContract["INTEREST_RATE"] = $this->getRateIntTable($constLoanContract["INT_CONTINTTABCODE"]);
		return $constLoanContract;
	}
	private function getRateIntTable($inttabcode){
		$conRate = $this->conora->prepare("SELECT INTEREST_RATE FROM lncfloanintratedet WHERE LOANINTRATE_CODE = :inttabcode
											and SYSDATE BETWEEN EFFECTIVE_DATE and EXPIRE_DATE");
		$conRate->execute([':inttabcode' => $inttabcode]);
		$rowRate = $conRate->fetch(\PDO::FETCH_ASSOC);
		return $rowRate["INTEREST_RATE"];
	}
	private function getLoanConstant(){
		$getLoanConstant = $this->conora->prepare("SELECT RDINTDEC_TYPE,RDINTSATANG_TYPE,DAYINYEAR FROM LNLOANCONSTANT");
		$getLoanConstant->execute();
		$constLoanContractCont = $getLoanConstant->fetch(\PDO::FETCH_ASSOC);
		return $constLoanContractCont;
	}
}
?>