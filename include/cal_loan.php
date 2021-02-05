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
	public function calculateInterest($loancontract_no){
		$constLoanContract = $this->getContstantLoanContract($loancontract_no);
		$constLoan = $this->getLoanConstant();
		$interest = 0;
		if($constLoanContract["CHECK_KEEPING"] == '1'){
			$yearFrom = date('Y',strtotime($constLoanContract["LASTCALINT_DATE"]));
			$yearTo = date('Y');
			$yearDiff = $yearTo - $yearFrom;
			$yearDiffTemp = 0;
			for($i = 0;$i <= $yearDiff;$i++){
				$dayinyear = 0;
				if($constLoan["DAYINYEAR"] > 0){
					$dayinyear = $constLoan["DAYINYEAR"];
				}else{
					$dayinyear = $this->lib->getnumberofYear(date('Y',strtotime('+'.$yearDiffTemp.' year',strtotime($constLoanContract["LASTCALINT_DATE"]))));	
				}
				$dateFrom = new \DateTime(date('d-m-Y',strtotime('+'.$yearDiffTemp.' year',strtotime($constLoanContract["LASTCALINT_DATE"]))));
				if($yearDiffTemp == 0 && $yearDiff > 0){
					$dateTo = new \DateTime(date('d-m-Y',strtotime('+'.$yearDiffTemp.' year',strtotime($constLoanContract["LASTCALINT_DATE"]))));
					$date_duration = $dateTo->diff($dateFrom);
					$dayInterest = $date_duration->days;
					if($dayInterest == 0){
						$dayInterest++;
					}
				}else{
					$dateTo = new \DateTime(date('d-m-Y',strtotime('+'.$yearDiffTemp.' year')));
					$date_duration = $dateTo->diff($dateFrom);
					$dayInterest = $date_duration->days - $yearDiffTemp;
				}
				$yearDiffTemp++;
				
				if($constLoanContract["INTEREST_METHOD"] == '2'){
				}else{
					if($constLoanContract["CONTINT_TYPE"] != '0'){
						if($constLoanContract["CONTINT_TYPE"] == '1'){
							$interest += (($constLoanContract["PRINCIPAL_BALANCE"] * ($constLoanContract["INTEREST_RATE"] / 100)) * $dayInterest) / $dayinyear;
						}else if($constLoanContract["CONTINT_TYPE"] == '2'){
							if($constLoanContract["INTSTEP_TYPE"] == '2'){
								$interest += (($constLoanContract["PRINCIPAL_BALANCE"] * ($constLoanContract["INTEREST_RATE"] / 100)) * $dayInterest) / $dayinyear;
							}else{
								$interest += (($constLoanContract["PRINCIPAL_BALANCE"] * ($constLoanContract["INTEREST_RATE"] / 100)) * $dayInterest) / $dayinyear;
							}
						}
					}
				}
			}
			$interest = $this->lib->roundDecimal($interest,$constLoan["RDINTSATANG_TYPE"]);
		}
		return $interest;
	}
	
	private function getContstantLoanContract($loancontract_no){
		$contLoan = $this->conora->prepare("SELECT LNM.LOANAPPROVE_AMT,LNM.PRINCIPAL_BALANCE,LNM.PERIOD_PAYAMT,LNM.LAST_PERIODPAY,LNM.LOANTYPE_CODE,
											LNM.LASTCALINT_DATE,LNM.LOANPAYMENT_TYPE,LND.INTEREST_RATE,LNT.CONTINT_TYPE,LNT.INTEREST_METHOD,LNT.PAYSPEC_METHOD,LNT.INTSTEP_TYPE,
											(CASE WHEN LNM.LASTPROCESS_DATE <= LNM.LASTCALINT_DATE OR LNM.LASTPROCESS_DATE IS NULL THEN '1' ELSE '0' END) AS CHECK_KEEPING,LNM.LAST_STM_NO
											FROM lncontmaster lnm LEFT JOIN lnloantype lnt ON lnm.LOANTYPE_CODE = lnt.LOANTYPE_CODE 
											LEFT JOIN lncfloanintratedet lnd 
											ON lnt.INTTABRATE_CODE = lnd.LOANINTRATE_CODE
											WHERE lnm.loancontract_no = :contract_no and lnm.contract_status > 0 and lnm.contract_status <> 8 
											and SYSDATE BETWEEN lnd.EFFECTIVE_DATE and lnd.EXPIRE_DATE");
		$contLoan->execute([':contract_no' => $loancontract_no]);
		$constLoanContract = $contLoan->fetch(\PDO::FETCH_ASSOC);
		return $constLoanContract;
	}
	
	private function getLoanConstant(){
		$getLoanConstant = $this->conora->prepare("SELECT RDINTDEC_TYPE,RDINTSATANG_TYPE,DAYINYEAR FROM LNLOANCONSTANT");
		$getLoanConstant->execute();
		$constLoanContractCont = $getLoanConstant->fetch(\PDO::FETCH_ASSOC);
		return $constLoanContractCont;
	}
}
?>