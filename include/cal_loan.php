<?php

namespace CalculateLoan;

use Connection\connection;
use Utility\Library;


class CalculateLoan {
	private $con;
	private $conms;
	private $lib;
	
	function __construct() {
		$connection = new connection();
		$this->lib = new library();
		$this->con = $connection->connecttomysql();
		$this->conms = $connection->connecttosqlserver();
	}
	public function calculateIntAPI($loancontract_no,$amount=null){
		$dataCont = $this->getContstantLoanContract($loancontract_no);
		$json = file_get_contents(__DIR__.'/../config/config_constructor.json');
		$json_data = json_decode($json,true);
		$url = $json_data["URL_CONSTANT"].'getconstantfunc/'.$json_data["COOP_KEY_PROD"].'/calculateintperiod';
		$header = ["requestId: ".$this->lib->randomText(10)];
		$dataInt = $this->dataChangeRateInt($dataCont["INT_CONTINTTABCODE"],$this->lib->convertdate($dataCont["LASTCALINT_DATE"],'y n d',false,true));
		$intRate = $this->getRateInt($dataCont["INT_CONTINTTABCODE"],date('Y-m-d'));
		$dataReq = array();
		$dataReq["condition"] = [$dataCont["LOANTYPE_CODE"]];
		$dataReq["data"] = [
			"amount" => (float)($amount ?? $dataCont["PRINCIPAL_BALANCE"]),
			"loanBalance" => (float)$dataCont["PRINCIPAL_BALANCE"],
			"keepingAmount" => (float)$dataCont["SPACE_KEEPING"],
			"prinKeepingAmount" => (float)$dataCont["RKEEP_PRINCIPAL"],
			"calintFrom" => date('Y-m-d',strtotime($dataCont["LASTCALINT_DATE"])),
			"calintTo" => date('Y-m-d'),
			"intArrear" => (float)$dataCont["INTEREST_ARREAR_SRC"],
			"intRate" => (float)$intRate["INTEREST_RATE"],
			"changeRateInt" => $dataInt["is_change"],
			"changeRateInfo" => $dataInt,
			"intReturn" => (float)$dataCont["INTEREST_RETURN"]
			
		];
		$interestResult = $this->lib->posting_data($url,$dataReq,$header);
		$arrResponse = json_decode($interestResult);
		if($arrResponse->RESULT){
			return [
				"INT_PAYMENT" => $arrResponse->INT_PAYMENT,
				"INT_PERIOD" => $arrResponse->INT_PERIOD,
				"INT_ARREAR" => $arrResponse->INT_ARREAR,
				"INT_RETURN" => $arrResponse->INT_RETURN
			];
		}else{
			return [
				"INT_PAYMENT" => 0,
				"INT_PERIOD" => 0,
				"INT_ARREAR" => 0,
				"INT_RETURN" => 0
			];
		}
	}

	public function calculateInterestArr($loancontract_no,$amt_transfer=0){
		$constLoanContract = $this->getContstantLoanContract($loancontract_no);
		$constLoan = $this->getLoanConstant();
		$betweenKeeping = FALSE;
		$interest = 0;
		if($constLoanContract["CHECK_KEEPING"] == '1'){
			$calInt = TRUE;
		}else{
			if($constLoanContract["SPACE_KEEPING"] == 0){
				$betweenKeeping = TRUE;
				$calInt = TRUE;
			}else{
				if($constLoanContract["PXAFTERMTHKEEP_TYPE"] == '1'){
					$calInt = TRUE;
				}else{
					$betweenKeeping = TRUE;
					$calInt = TRUE;
				}
			}
		}
		if($calInt){
			if($betweenKeeping){
				$yearFrom = date('Y');
				$changerateint = $this->checkChangeRateInt($constLoanContract["LOANTYPE_CODE"],date('Y m d'));
				$yearTo = date('Y',strtotime($constLoanContract["LASTPROCESS_DATE"]));
				$roundLoop = 0;
				$yearDiff = $yearTo - $yearFrom;
				if($yearDiff > 0){
					$roundLoop += 1;
				}
				if($changerateint){
					$roundLoop += 1;
				}
				$yearDiffTemp = 0;
				for($i = 0;$i <= $roundLoop;$i++){
					if($constLoanContract["INT_CONTINTTYPE"] == '2'){
						if($changerateint){
							if($i == 0){
								$intrateData = $this->getRateInt($constLoanContract["INT_CONTINTTABCODE"],date('Y-m-d'));
							}else{
								$intrateData = $this->getRateInt($constLoanContract["INT_CONTINTTABCODE"],$this->lib->convertdate($constLoanContract["LASTPROCESS_DATE"],'y-n-d'));
							}
						}else{
							$intrateData = $this->getRateInt($constLoanContract["INT_CONTINTTABCODE"],date('Y-m-d'));
						}
						$intrate = $intrateData["INTEREST_RATE"];
					}else if($constLoanContract["INT_CONTINTTYPE"] == '1'){
						$intrate = $constLoanContract["INT_CONTINTRATE"];
					}else if($constLoanContract["INT_CONTINTTYPE"] == '0'){
						return 0;
					}
					$dayinyear = 0;
					if($constLoan["DAYINYEAR"] > 0){
						$dayinyear = $constLoan["DAYINYEAR"];
					}else{
						if($changerateint){
							if($i == 0){
								$dayinyear = $this->lib->getnumberofYear(date('Y',strtotime('+0 year',strtotime(date('Y-m-d')))));
							}else if($i == 1){
								if($yearDiff > 0){
									$dayinyear = $this->lib->getnumberofYear(date('Y',strtotime('+0 year',strtotime(date('Y-m-d')))));
								}else{
									$dayinyear = $this->lib->getnumberofYear(date('Y'));
								}
							}else{
								$dayinyear = $this->lib->getnumberofYear(date('Y',strtotime('+1 year',strtotime(date('Y-m-d')))));
							}
						}else{
							$dayinyear = $this->lib->getnumberofYear(date('Y',strtotime('+'.$yearDiffTemp.' year',strtotime(date('Y-m-d')))));
						}
					}
					if($changerateint){
						if($i == 0){
							$dateFrom = new \DateTime(date('d-m-Y',strtotime('+0 year',strtotime(date('Y-m-d')))));
							$dateTo = new \DateTime(date('d-m-Y',strtotime('+1 days',strtotime($intrateData["EXPIRE_DATE"]))));
							$date_duration = $dateTo->diff($dateFrom);
							$dayInterest = $date_duration->days;
						}else if($i == 1){
							if($yearDiff > 0){
								$dateFrom = new \DateTime($intrateData["EFFECTIVE_DATE"]);
								$dateTo = new \DateTime('31-12-'.date('Y',strtotime($constLoanContract["LASTPROCESS_DATE"])));
								$date_duration = $dateTo->diff($dateFrom);
								$dayInterest = $date_duration->days;
							}else{
								$dateFrom = new \DateTime($intrateData["EFFECTIVE_DATE"]);
								$dateTo = new \DateTime(date('d-m-Y',strtotime($constLoanContract["LASTPROCESS_DATE"])));
								$date_duration = $dateTo->diff($dateFrom);
								$dayInterest = $date_duration->days;
							}
						}else{
							$dateFrom = new \DateTime('01-01-'.date('Y'));
							$dateTo = new \DateTime(date('d-m-Y',strtotime($constLoanContract["LASTPROCESS_DATE"])));
							$date_duration = $dateTo->diff($dateFrom);
							$dayInterest = $date_duration->days;
						}
					}else{
						if($yearDiffTemp == 0 && $yearDiff > 0){
							$dateFrom = new \DateTime(date('d-m-Y',strtotime('+'.$yearDiffTemp.' year',strtotime(date('Y-m-d')))));
							$dateTo = new \DateTime('31-12-'.date('Y',strtotime($constLoanContract["LASTPROCESS_DATE"])));
							$date_duration = $dateTo->diff($dateFrom);
							$dayInterest = $date_duration->days;
						}else{
							if($yearDiffTemp > 0){
								$dateFrom = new \DateTime('01-01-'.date('Y'));
							}else{
								$dateFrom = new \DateTime(date('d-m-Y',strtotime('+0 year',strtotime(date('Y-m-d')))));
							}
							$dateTo = new \DateTime(date('d-m-Y',strtotime($constLoanContract["LASTPROCESS_DATE"])));
							$date_duration = $dateTo->diff($dateFrom);
							$dayInterest = $date_duration->days;
						}
					}
					if(!$changerateint){
						$yearDiffTemp++;
					}
					$prn_bal = $amt_transfer;
					$interest += (($prn_bal * ($intrate / 100)) * $dayInterest) / $dayinyear;
				}
			}else{
				$yearFrom = date('Y',strtotime($constLoanContract["LASTCALINT_DATE"]));
				$changerateint = $this->checkChangeRateInt($constLoanContract["LOANTYPE_CODE"],$this->lib->convertdate($constLoanContract["LASTCALINT_DATE"],'y n d',false,true));
				$yearTo = date('Y');
				$roundLoop = 0;
				$yearDiff = $yearTo - $yearFrom;
				if($yearDiff > 0){
					$roundLoop += 1;
				}
				if($changerateint){
					$roundLoop += 1;
				}
				$yearDiffTemp = 0;
				for($i = 0;$i <= $roundLoop;$i++){
					if($constLoanContract["INT_CONTINTTYPE"] == '2'){
						if($changerateint){
							if($i == 0){
								$intrateData = $this->getRateInt($constLoanContract["INT_CONTINTTABCODE"],$this->lib->convertdate($constLoanContract["LASTCALINT_DATE"],'y-n-d'));
							}else{
								$intrateData = $this->getRateInt($constLoanContract["INT_CONTINTTABCODE"],date('Y-m-d'));
							}
						}else{
							$intrateData = $this->getRateInt($constLoanContract["INT_CONTINTTABCODE"],$this->lib->convertdate($constLoanContract["LASTCALINT_DATE"],'y-n-d'));
						}
						$intrate = $intrateData["INTEREST_RATE"];
					}else if($constLoanContract["INT_CONTINTTYPE"] == '1'){
						$intrate = $constLoanContract["INT_CONTINTRATE"];
					}else if($constLoanContract["INT_CONTINTTYPE"] == '0'){
						return 0;
					}
					$dayinyear = 0;
					if($constLoan["DAYINYEAR"] > 0){
						$dayinyear = $constLoan["DAYINYEAR"];
					}else{
						if($changerateint){
							if($i == 0){
								$dayinyear = $this->lib->getnumberofYear(date('Y',strtotime('+0 year',strtotime($constLoanContract["LASTCALINT_DATE"]))));
							}else if($i == 1){
								if($yearDiff > 0){
									$dayinyear = $this->lib->getnumberofYear(date('Y',strtotime('+0 year',strtotime($constLoanContract["LASTCALINT_DATE"]))));
								}else{
									$dayinyear = $this->lib->getnumberofYear(date('Y'));
								}
							}else{
								$dayinyear = $this->lib->getnumberofYear(date('Y',strtotime('+1 year',strtotime($constLoanContract["LASTCALINT_DATE"]))));
							}
						}else{
							$dayinyear = $this->lib->getnumberofYear(date('Y',strtotime('+'.$yearDiffTemp.' year',strtotime($constLoanContract["LASTCALINT_DATE"]))));
						}
					}
					if($changerateint){
						if($i == 0){
							$dateFrom = new \DateTime(date('d-m-Y',strtotime('+0 year',strtotime($constLoanContract["LASTCALINT_DATE"]))));
							$dateTo = new \DateTime(date('d-m-Y',strtotime('+1 days',strtotime($intrateData["EXPIRE_DATE"]))));
							$date_duration = $dateTo->diff($dateFrom);
							$dayInterest = $date_duration->days;
						}else if($i == 1){
							if($yearDiff > 0){
								$dateFrom = new \DateTime($intrateData["EFFECTIVE_DATE"]);
								$dateTo = new \DateTime('31-12-'.date('Y',strtotime($constLoanContract["LASTCALINT_DATE"])));
								$date_duration = $dateTo->diff($dateFrom);
								$dayInterest = $date_duration->days;
							}else{
								$dateFrom = new \DateTime($intrateData["EFFECTIVE_DATE"]);
								$dateTo = new \DateTime(date('d-m-Y'));
								$date_duration = $dateTo->diff($dateFrom);
								$dayInterest = $date_duration->days;
							}
						}else{
							$dateFrom = new \DateTime('01-01-'.date('Y'));
							$dateTo = new \DateTime(date('d-m-Y'));
							$date_duration = $dateTo->diff($dateFrom);
							$dayInterest = $date_duration->days;
						}
					}else{
						if($yearDiffTemp == 0 && $yearDiff > 0){
							$dateFrom = new \DateTime(date('d-m-Y',strtotime('+'.$yearDiffTemp.' year',strtotime($constLoanContract["LASTCALINT_DATE"]))));
							$dateTo = new \DateTime('31-12-'.date('Y',strtotime($constLoanContract["LASTCALINT_DATE"])));
							$date_duration = $dateTo->diff($dateFrom);
							$dayInterest = $date_duration->days;
						}else{
							if($yearDiffTemp > 0){
								$dateFrom = new \DateTime('01-01-'.date('Y'));
							}else{
								$dateFrom = new \DateTime(date('d-m-Y',strtotime('+0 year',strtotime($constLoanContract["LASTCALINT_DATE"]))));
							}
							$dateTo = new \DateTime(date('d-m-Y'));
							$date_duration = $dateTo->diff($dateFrom);
							$dayInterest = $date_duration->days;
						}
					}
					if(!$changerateint){
						$yearDiffTemp++;
					}
					$prn_bal = $constLoanContract["PRINCIPAL_BALANCE"];
					$interest += (($prn_bal * ($intrate / 100)) * $dayInterest) / $dayinyear;
				}
			}
			$interest = $this->lib->roundDecimal($interest,$constLoan["RDINTSATANG_TYPE"]) + $constLoanContract["INTEREST_ARREAR"];
		}
		return $interest;
	}
	private function getRateInt($inttabcode,$date){
		$contLoan = $this->conms->prepare("SELECT INTEREST_RATE,CONVERT(VARCHAR(10),EXPIRE_DATE,20) as EXPIRE_DATE
											,CONVERT(VARCHAR(10),EFFECTIVE_DATE,20) as EFFECTIVE_DATE
											FROM lncfloanintratedet
											WHERE LOANINTRATE_CODE = :inttabcode
											and '".$date."' BETWEEN CONVERT(VARCHAR(10),EFFECTIVE_DATE,20) and CONVERT(VARCHAR(10),EXPIRE_DATE,20)");
		$contLoan->execute([
			':inttabcode' => $inttabcode
		]);
		$constLoanRate = $contLoan->fetch(\PDO::FETCH_ASSOC);
		return $constLoanRate;
	}
	private function dataChangeRateInt($inttabcode,$date){
		$changeRateData = array();
		$contLoan = $this->conms->prepare("SELECT CONVERT(VARCHAR(8),EFFECTIVE_DATE,112) as EFFECTIVE_DATE,INTEREST_RATE
											FROM lncfloanintratedet
											WHERE LOANINTRATE_CODE = :inttabcode");
		$contLoan->execute([
			':inttabcode' => $inttabcode
		]);
		while($constLoanRate = $contLoan->fetch(\PDO::FETCH_ASSOC)){
			if($constLoanRate["EFFECTIVE_DATE"] > $date){
				if($constLoanRate["EFFECTIVE_DATE"] < date('Ymd')){
					if($constLoanRate["EFFECTIVE_DATE"] == (date('Y') + 1).'0101'){
						$changeRateData["is_change"] = FALSE;
					}else{
						$getDataNowInt = $this->conms->prepare("SELECT CONVERT(VARCHAR(10),EFFECTIVE_DATE,20) as EFFECTIVE_DATE,INTEREST_RATE
											FROM lncfloanintratedet
											WHERE LOANINTRATE_CODE = :inttabcode and CONVERT(VARCHAR(8),GETDATE(),112) BETWEEN CONVERT(VARCHAR(8),EFFECTIVE_DATE,112) and CONVERT(VARCHAR(8),EXPIRE_DATE,112)");
						$getDataNowInt->execute([':inttabcode' => $inttabcode]);
						$rowInt = $getDataNowInt->fetch(\PDO::FETCH_ASSOC);
						$getDataOldInt = $this->conms->prepare("SELECT CONVERT(VARCHAR(10),EXPIRE_DATE,20) as EXPIRE_DATE,INTEREST_RATE
											FROM lncfloanintratedet
											WHERE LOANINTRATE_CODE = :inttabcode and 
											CONVERT(VARCHAR(8),".$date.",112) BETWEEN CONVERT(VARCHAR(8),EFFECTIVE_DATE,112) and CONVERT(VARCHAR(8),EXPIRE_DATE,112)");
						$getDataOldInt->execute([':inttabcode' => $inttabcode]);
						$rowOldInt = $getDataOldInt->fetch(\PDO::FETCH_ASSOC);
						$changeRateData["exprieDate"] = $rowOldInt["EXPIRE_DATE"];
						$changeRateData["effectiveDate"] = $rowInt["EFFECTIVE_DATE"];
						$changeRateData["bfIntRate"] = (float)$rowOldInt["INTEREST_RATE"];
						$changeRateData["newIntRate"] = (float)$rowInt["INTEREST_RATE"];
						$changeRateData["is_change"] = TRUE;
					}
				}else{
					$changeRateData["is_change"] = FALSE;
				}
			}else{
				$changeRateData["is_change"] = FALSE;
			}
		}
		return $changeRateData;
	}


	public function calculateIntAccum($member_no){
		$getAccYear = $this->conms->prepare("SELECT ACCOUNT_YEAR FROM CMACCOUNTYEAR WHERE CONVERT(VARCHAR(10),GETDATE(),20) 
											BETWEEN CONVERT(VARCHAR(10),ACCSTART_DATE,20) AND CONVERT(VARCHAR(10),ACCEND_DATE,20)");
		$getAccYear->execute();
		$rowAccYear = $getAccYear->fetch(\PDO::FETCH_ASSOC);
		$getIntAccum = $this->conms->prepare("SELECT ISNULL(SUM(LNS.INTEREST_PAYMENT),0) AS INT_ACCUM FROM LNCONTMASTER LNM 
												LEFT JOIN LNCONTSTATEMENT LNS ON LNM.LOANCONTRACT_NO = LNS.LOANCONTRACT_NO,CMACCOUNTYEAR CMY
												WHERE LNM.MEMBER_NO = :member_no AND CMY.ACCOUNT_YEAR = :account_year AND CONVERT(VARCHAR(10),ENTRY_DATE,20) >= CONVERT(VARCHAR(10),ACCEND_DATE,20) 
												AND CONVERT(VARCHAR(10),ENTRY_DATE,20) <= CONVERT(VARCHAR(10),ACCEND_DATE,20)");
		$getIntAccum->execute([
			':member_no' => $member_no,
			':account_year' => $rowAccYear["ACCOUNT_YEAR"]  || ''
		]);
		$rowIntAccum = $getIntAccum->fetch(\PDO::FETCH_ASSOC);
		return $rowIntAccum["INT_ACCUM"];
	}
	public function calculateIntReturn($loancontract_no,$amt_transfer,$interest=0){
		$constLoanContract = $this->getContstantLoanContract($loancontract_no);
		$constLoan = $this->getLoanConstant();
		$roundLoop = 0;
		$yearFrom = date('Y',strtotime($constLoanContract["LASTPROCESS_DATE"]));
		$changerateint = $this->checkChangeRateInt($constLoanContract["LOANTYPE_CODE"],$this->lib->convertdate($constLoanContract["LASTPROCESS_DATE"],'y n d',false,true));
		$yearTo = date('Y');
		$yearDiff = $yearFrom - $yearTo;
		if($yearDiff > 0){
			$roundLoop += 1;
		}
		if($changerateint){
			$roundLoop += 1;
		}
		$yearDiffTemp = 0;
		for($i = 0;$i <= $roundLoop;$i++){
			if($constLoanContract["INT_CONTINTTYPE"] == '2'){
				if($changerateint){
					if($i == 0){
						$intrateData = $this->getRateInt($constLoanContract["INT_CONTINTTABCODE"],$this->lib->convertdate($constLoanContract["LASTPROCESS_DATE"],'y-n-d'));
					}else{
						$intrateData = $this->getRateInt($constLoanContract["INT_CONTINTTABCODE"],date('Y-m-d'));
					}
				}else{
					$intrateData = $this->getRateInt($constLoanContract["INT_CONTINTTABCODE"],$this->lib->convertdate($constLoanContract["LASTPROCESS_DATE"],'y-n-d'));
				}
				$intrate = $intrateData["INTEREST_RATE"];
			}else if($constLoanContract["INT_CONTINTTYPE"] == '1'){
				$intrate = $constLoanContract["INT_CONTINTRATE"];
			}else if($constLoanContract["INT_CONTINTTYPE"] == '0'){
				return 0;
			}
			$dayinyear = 0;
			if($constLoan["DAYINYEAR"] > 0){
				$dayinyear = $constLoan["DAYINYEAR"];
			}else{
				if($changerateint){
					if($i == 0){
						$dayinyear = $this->lib->getnumberofYear(date('Y',strtotime('+0 year',strtotime($constLoanContract["LASTPROCESS_DATE"]))));
					}else if($i == 1){
						if($yearDiff > 0){
							$dayinyear = $this->lib->getnumberofYear(date('Y',strtotime('+0 year',strtotime($constLoanContract["LASTPROCESS_DATE"]))));
						}else{
							$dayinyear = $this->lib->getnumberofYear(date('Y'));
						}
					}else{
						$dayinyear = $this->lib->getnumberofYear(date('Y',strtotime('+1 year',strtotime($constLoanContract["LASTPROCESS_DATE"]))));
					}
				}else{
					$dayinyear = $this->lib->getnumberofYear(date('Y',strtotime('+'.$yearDiffTemp.' year',strtotime($constLoanContract["LASTPROCESS_DATE"]))));
				}
			}
			if($changerateint){
				if($i == 0){
					$dateFrom = new \DateTime(date('d-m-Y',strtotime('+0 year',strtotime($constLoanContract["LASTPROCESS_DATE"]))));
					$dateTo = new \DateTime(date('d-m-Y',strtotime('+1 days',strtotime($intrateData["EXPIRE_DATE"]))));
					$date_duration = $dateTo->diff($dateFrom);
					$dayInterest = $date_duration->days;
				}else if($i == 1){
					if($yearDiff > 0){
						$dateFrom = new \DateTime($intrateData["EFFECTIVE_DATE"]);
						$dateTo = new \DateTime('31-12-'.date('Y',strtotime($constLoanContract["LASTPROCESS_DATE"])));
						$date_duration = $dateTo->diff($dateFrom);
						$dayInterest = $date_duration->days;
					}else{
						$dateFrom = new \DateTime($intrateData["EFFECTIVE_DATE"]);
						$dateTo = new \DateTime(date('d-m-Y'));
						$date_duration = $dateTo->diff($dateFrom);
						$dayInterest = $date_duration->days;
					}
				}else{
					$dateFrom = new \DateTime('01-01-'.date('Y'));
					$dateTo = new \DateTime(date('d-m-Y'));
					$date_duration = $dateTo->diff($dateFrom);
					$dayInterest = $date_duration->days;
				}
			}else{
				if($yearDiffTemp == 0 && $yearDiff > 0){
					$dateFrom = new \DateTime(date('d-m-Y',strtotime('+'.$yearDiffTemp.' year',strtotime($constLoanContract["LASTPROCESS_DATE"]))));
					$dateTo = new \DateTime('31-12-'.date('Y',strtotime($constLoanContract["LASTPROCESS_DATE"])));
					$date_duration = $dateTo->diff($dateFrom);
					$dayInterest = $date_duration->days;
				}else{
					if($yearDiffTemp > 0){
						$dateFrom = new \DateTime('01-01-'.date('Y'));
					}else{
						$dateFrom = new \DateTime(date('d-m-Y',strtotime('+0 year',strtotime($constLoanContract["LASTPROCESS_DATE"]))));
					}
					$dateTo = new \DateTime(date('d-m-Y'));
					$date_duration = $dateTo->diff($dateFrom);
					$dayInterest = $date_duration->days;
				}
			}
			if(!$changerateint){
				$yearDiffTemp++;
			}
			$prn_bal = $amt_transfer;
			if($constLoanContract["INTEREST_METHOD"] != '2'){
				$int_return += (($prn_bal * ($intrate / 100)) * $dayInterest) / $dayinyear;
			}
		}
		if($constLoanContract["PXAFTERMTHKEEP_TYPE"] != '1'){
			$int_return = $int_return + $interest;
		}
		$int_return = $this->lib->roundDecimal($int_return,$constLoan["RDINTSATANG_TYPE"],'1');
		return $int_return;
	}

	private function checkChangeRateInt($inttabcode,$date){
		$change_rate = FALSE;
		$contLoan = $this->conms->prepare("SELECT CONVERT(VARCHAR(8),EFFECTIVE_DATE,112) as EFFECTIVE_DATE
											FROM lncfloanintratedet
											WHERE LOANINTRATE_CODE = :inttabcode");
		$contLoan->execute([
			':inttabcode' => $inttabcode
		]);
		while($constLoanRate = $contLoan->fetch(\PDO::FETCH_ASSOC)){
			if($constLoanRate["EFFECTIVE_DATE"] > $date){
				if($constLoanRate["EFFECTIVE_DATE"] < date('Ymd')){
					if($constLoanRate["EFFECTIVE_DATE"] == (date('Y') + 1).'0101'){
						$change_rate = FALSE;
					}else{
						$change_rate = TRUE;
					}
				}else{
					$change_rate = FALSE;
				}
			}else{
				$change_rate = FALSE;
			}
		}
		return $change_rate;
	}
	public function getContstantLoanContract($loancontract_no){
		$contLoan = $this->conms->prepare("SELECT LNM.LOANAPPROVE_AMT,LNM.PRINCIPAL_BALANCE,LNM.PERIOD_PAYMENT,LNM.PERIOD_PAYAMT,LNM.LAST_PERIODPAY,LNM.WITHDRAWABLE_AMT,
											LNM.LOANTYPE_CODE,(LNM.INTEREST_ARREAR - (LNM.RKEEP_INTEREST - LNM.NKEEP_INTEREST)) as INTEREST_ARREAR,LNM.INTEREST_ARREAR as INTEREST_ARREAR_SRC
											,LNT.PXAFTERMTHKEEP_TYPE,LNM.RKEEP_PRINCIPAL,LNM.RKEEP_INTEREST,
											LNM.LASTCALINT_DATE,LNM.LOANPAYMENT_TYPE,LNT.CONTINT_TYPE,LNT.PAYSPEC_METHOD,LNT.INTSTEP_TYPE,LNM.LASTPROCESS_DATE,
											(LNM.NKEEP_PRINCIPAL + LNM.NKEEP_INTEREST) as SPACE_KEEPING,LNM.INTEREST_RETURN,LNM.NKEEP_PRINCIPAL,LNM.NKEEP_INTEREST,
											(CASE WHEN LNM.LASTPROCESS_DATE < LNM.LASTCALINT_DATE OR LNM.LASTPROCESS_DATE IS NULL THEN '1' ELSE '0' END) AS CHECK_KEEPING,LNM.LAST_STM_NO,
											LNM.INT_CONTINTTYPE,LNM.INT_CONTINTRATE,LNM.INT_CONTINTTABCODE
											FROM lncontmaster lnm LEFT JOIN lnloantype lnt ON lnm.LOANTYPE_CODE = lnt.LOANTYPE_CODE
											WHERE lnm.loancontract_no = :contract_no and lnm.contract_status > 0 and lnm.contract_status <> 8");
		$contLoan->execute([':contract_no' => $loancontract_no]);
		$constLoanContract = $contLoan->fetch(\PDO::FETCH_ASSOC);
		$constLoanContract["INTEREST_RATE"] = $this->getRateIntTable($constLoanContract["INT_CONTINTTABCODE"]);
		return $constLoanContract;
	}
	private function getRateIntTable($inttabcode){
		$conRate = $this->conms->prepare("SELECT INTEREST_RATE FROM lncfloanintratedet WHERE LOANINTRATE_CODE = :inttabcode
											and GETDATE() BETWEEN EFFECTIVE_DATE and EXPIRE_DATE");
		$conRate->execute([':inttabcode' => $inttabcode]);
		$rowRate = $conRate->fetch(\PDO::FETCH_ASSOC);
		return $rowRate["INTEREST_RATE"];
	}
	private function getLoanConstant(){
		$getLoanConstant = $this->conms->prepare("SELECT RDINTDEC_TYPE,RDINTSATANG_TYPE,DAYINYEAR FROM LNLOANCONSTANT");
		$getLoanConstant->execute();
		$constLoanContractCont = $getLoanConstant->fetch(\PDO::FETCH_ASSOC);
		return $constLoanContractCont;
	}
	public function repayLoan($conmssql,$contract_no,$amt_transfer,$penalty_amt,$config,$slipdocno,$operate_date,
	$tofrom_accid,$slipwtd,$log,$lib,$payload,$from_account_no,$lnslip_no,$member_no,$ref_no,$app_version){
		$dataCont = $this->getContstantLoanContract($contract_no);
		$int_return = $dataCont["INTEREST_RETURN"];
		if($amt_transfer > $dataCont["INTEREST_ARREAR"]){
			$intarrear = $dataCont["INTEREST_ARREAR"];
		}else{
			$intarrear = $amt_transfer;
		}
		$int_returnFull = 0;
		$interest = $this->calculateIntAPI($contract_no,$amt_transfer);
		$interestFull = $interest["INT_PAYMENT"];
		if($interest["INT_PERIOD"] > 0){
			$interestPeriod = $interest["INT_PERIOD"];
		}else{
			$interestPeriod = $interest["INT_PAYMENT"];
		}
		if($interestPeriod < 0){
			$interestPeriod = 0;
		}
		$int_returnSrc = $interest["INT_RETURN"] ?? 0;
		$prinPay = 0;
		if($interest["INT_PAYMENT"] > 0){
			if($amt_transfer < $interest["INT_PAYMENT"]){
				$interest["INT_PAYMENT"] = $amt_transfer;
			}else{
				$prinPay = $amt_transfer - $interest["INT_PAYMENT"];
			}
			if($prinPay < 0){
				$prinPay = 0;
			}
		}else{
			$prinPay = $amt_transfer;
		}

		$lastperiod = $dataCont["LAST_PERIODPAY"];
		$interest_accum = $this->calculateIntAccum($member_no);
		$updateInterestAccum = $conmssql->prepare("UPDATE mbmembmaster SET ACCUM_INTEREST = :int_accum WHERE member_no = :member_no");
		if($updateInterestAccum->execute([
			':int_accum' => $interest_accum + $interest,
			':member_no' => $member_no
		])){
			$intArr = $interestFull - $amt_transfer - $int_returnFull;
			if($intArr < 0){
				$intArr = 0;
				if($dataCont["INTEREST_ARREAR_SRC"] - $dataCont["INTEREST_ARREAR"] > 0){
					$intArr = $dataCont["INTEREST_ARREAR_SRC"] - $dataCont["INTEREST_ARREAR"];
				}
			}
			
			if($interestPeriod > 0){
				$executeLnSTM = [
					$config["COOP_ID"],$contract_no,$dataCont["LAST_STM_NO"] + 1,'LPX',$slipdocno,
					$lastperiod,$prinPay,$interest,$dataCont["PRINCIPAL_BALANCE"] - $prinPay,
					$dataCont["PRINCIPAL_BALANCE"],date('Y-m-d H:i:s',strtotime($dataCont["LASTCALINT_DATE"])),
					$dataCont["INTEREST_ARREAR_SRC"],$interestPeriod,$intArr,$int_returnSrc,'TRN',$config["COOP_ID"],
					$lnslip_no,$dataCont["INTEREST_RETURN"]
				];
						
				$insertSTMLoan = $conmssql->prepare("INSERT INTO lncontstatement(COOP_ID,LOANCONTRACT_NO,SEQ_NO,LOANITEMTYPE_CODE,SLIP_DATE,
														OPERATE_DATE,ACCOUNT_DATE,REF_DOCNO,PERIOD,PRINCIPAL_PAYMENT,INTEREST_PAYMENT,PRINCIPAL_BALANCE,
														PRNCALINT_AMT,CALINT_FROM,CALINT_TO,BFINTARREAR_AMT,INTEREST_PERIOD,INTEREST_ARREAR,
														INTEREST_RETURN,MONEYTYPE_CODE,ITEM_STATUS,ENTRY_ID,ENTRY_DATE,ENTRY_BYCOOPID,REF_SLIPNO,
														BFINTRETURN_AMT,INTACCUM_DATE,SYNC_NOTIFY_FLAG)
														VALUES(?,?,?,?,CONVERT(VARCHAR(10),GETDATE(),20),CONVERT(VARCHAR(10),GETDATE(),20),
														CONVERT(VARCHAR(10),GETDATE(),20),?,?,?,?,?,?,
														CONVERT(VARCHAR(10),?,20),
														CONVERT(VARCHAR(10),GETDATE(),20),?,?,?,
														?,?,1,'MOBILE',GETDATE(),?,?,?,CONVERT(VARCHAR(10),GETDATE(),20),'1')");
			}else{
				$executeLnSTM = [
					$config["COOP_ID"],$contract_no,$dataCont["LAST_STM_NO"] + 1,'LPX',$slipdocno,
					$lastperiod,$prinPay,$interest,$dataCont["PRINCIPAL_BALANCE"] - $prinPay,
					$dataCont["PRINCIPAL_BALANCE"],date('Y-m-d H:i:s',strtotime($dataCont["LASTCALINT_DATE"])),date('Y-m-d H:i:s',strtotime($dataCont["LASTCALINT_DATE"])),
					$dataCont["INTEREST_ARREAR_SRC"],$interestPeriod,$intArr,$int_returnSrc,'TRN',$config["COOP_ID"],
					$lnslip_no,$dataCont["INTEREST_RETURN"]
				];
				$insertSTMLoan = $conmssql->prepare("INSERT INTO lncontstatement(COOP_ID,LOANCONTRACT_NO,SEQ_NO,LOANITEMTYPE_CODE,SLIP_DATE,
														OPERATE_DATE,ACCOUNT_DATE,REF_DOCNO,PERIOD,PRINCIPAL_PAYMENT,INTEREST_PAYMENT,PRINCIPAL_BALANCE,
														PRNCALINT_AMT,CALINT_FROM,CALINT_TO,BFINTARREAR_AMT,INTEREST_PERIOD,INTEREST_ARREAR,
														INTEREST_RETURN,MONEYTYPE_CODE,ITEM_STATUS,ENTRY_ID,ENTRY_DATE,ENTRY_BYCOOPID,REF_SLIPNO,
														BFINTRETURN_AMT,INTACCUM_DATE,SYNC_NOTIFY_FLAG)
														VALUES(?,?,?,?,CONVERT(VARCHAR(10),GETDATE(),20),CONVERT(VARCHAR(10),GETDATE(),20),
														CONVERT(VARCHAR(10),GETDATE(),20),?,?,?,?,?,?,
														CONVERT(VARCHAR(10),?,20),CONVERT(VARCHAR(10),?,20)
														,?,?,?,
														?,?,1,'MOBILE',GETDATE(),?,?,?,CONVERT(VARCHAR(10),GETDATE(),20),'1')");
			}
			
			if($insertSTMLoan->execute($executeLnSTM)){
				if($interestPeriod > 0){
					if($dataCont["RKEEP_PRINCIPAL"] == 0 && $dataCont["PRINCIPAL_BALANCE"] - $prinPay == 0){
						if($dataCont["LOANTYPE_CODE"] == '13'){
							$executeLnMaster = [
								$dataCont["PRINCIPAL_BALANCE"] - $prinPay,$lastperiod,$intArr,$interest_accum + $interest,
								$int_returnSrc,$prinPay,$interest,$dataCont["LAST_STM_NO"] + 1,$prinPay,$contract_no
							];
							$updateLnContmaster = $conmssql->prepare("UPDATE lncontmaster SET 
																		PRINCIPAL_BALANCE = ?,LAST_PERIODPAY = ?,
																		LASTPAYMENT_DATE = CONVERT(VARCHAR(10),GETDATE(),20),LASTCALINT_DATE = CONVERT(VARCHAR(10),GETDATE(),20),
																		INTEREST_ARREAR = ?,INTEREST_ACCUM = ?,
																		INTEREST_RETURN = ?,PRNPAYMENT_AMT = PRNPAYMENT_AMT + ?,
																		INTPAYMENT_AMT = INTPAYMENT_AMT + ?,LAST_STM_NO = ?,WITHDRAWABLE_AMT = ?
																		WHERE loancontract_no = ?");

						}else{
							$executeLnMaster = [
								$dataCont["PRINCIPAL_BALANCE"] - $prinPay,$lastperiod,$intArr,$interest_accum + $interest,
								$int_returnSrc,$prinPay,$interest,$dataCont["LAST_STM_NO"] + 1,$contract_no
							];
							$updateLnContmaster = $conmssql->prepare("UPDATE lncontmaster SET 
																		PRINCIPAL_BALANCE = ?,LAST_PERIODPAY = ?,
																		LASTPAYMENT_DATE = CONVERT(VARCHAR(10),GETDATE(),20),LASTCALINT_DATE = CONVERT(VARCHAR(10),GETDATE(),20),
																		INTEREST_ARREAR = ?,INTEREST_ACCUM = ?,
																		INTEREST_RETURN = ?,PRNPAYMENT_AMT = PRNPAYMENT_AMT + ?,
																		INTPAYMENT_AMT = INTPAYMENT_AMT + ?,LAST_STM_NO = ?,
																		CONTRACT_STATUS = '0'
																		WHERE loancontract_no = ?");
						}
					}else{
						
						$executeLnMaster = [
							$dataCont["PRINCIPAL_BALANCE"] - $prinPay,$lastperiod,$intArr,$interest_accum + $interest,
							$int_returnSrc,$prinPay,$interest,$dataCont["LAST_STM_NO"] + 1,$contract_no
						];
						$updateLnContmaster = $conmssql->prepare("UPDATE lncontmaster SET 
																	PRINCIPAL_BALANCE = ?,LAST_PERIODPAY = ?,
																	LASTPAYMENT_DATE = CONVERT(VARCHAR(10),GETDATE(),20),LASTCALINT_DATE = CONVERT(VARCHAR(10),GETDATE(),20),
																	INTEREST_ARREAR = ?,INTEREST_ACCUM = ?,
																	INTEREST_RETURN = ?,PRNPAYMENT_AMT = PRNPAYMENT_AMT + ?,
																	INTPAYMENT_AMT = INTPAYMENT_AMT + ?,LAST_STM_NO = ?
																	WHERE loancontract_no = ?");
					}
				}else{
					if($dataCont["RKEEP_PRINCIPAL"] == 0 && $dataCont["PRINCIPAL_BALANCE"] - $prinPay == 0){
						if($dataCont["LOANTYPE_CODE"] == '13'){
							$executeLnMaster = [
								$dataCont["PRINCIPAL_BALANCE"] - $prinPay,$lastperiod,$intArr,$interest_accum + $interest,
								$int_returnSrc,$prinPay,$interest,$dataCont["LAST_STM_NO"] + 1,$prinPay,$contract_no
							];
							$updateLnContmaster = $conmssql->prepare("UPDATE lncontmaster SET 
																		PRINCIPAL_BALANCE = ?,LAST_PERIODPAY = ?,
																		LASTPAYMENT_DATE = CONVERT(VARCHAR(10),GETDATE(),20),
																		INTEREST_ARREAR = ?,INTEREST_ACCUM = ?,
																		INTEREST_RETURN = ?,PRNPAYMENT_AMT = PRNPAYMENT_AMT + ?,
																		INTPAYMENT_AMT = INTPAYMENT_AMT + ?,LAST_STM_NO = ?,WITHDRAWABLE_AMT = ?
																		WHERE loancontract_no = ?");

						}else{
							$executeLnMaster = [
								$dataCont["PRINCIPAL_BALANCE"] - $prinPay,$lastperiod,$intArr,$interest_accum + $interest,
								$int_returnSrc,$prinPay,$interest,$dataCont["LAST_STM_NO"] + 1,$contract_no
							];
							$updateLnContmaster = $conmssql->prepare("UPDATE lncontmaster SET 
																		PRINCIPAL_BALANCE = ?,LAST_PERIODPAY = ?,
																		LASTPAYMENT_DATE = CONVERT(VARCHAR(10),GETDATE(),20),
																		INTEREST_ARREAR = ?,INTEREST_ACCUM = ?,
																		INTEREST_RETURN = ?,PRNPAYMENT_AMT = PRNPAYMENT_AMT + ?,
																		INTPAYMENT_AMT = INTPAYMENT_AMT + ?,LAST_STM_NO = ?,
																		CONTRACT_STATUS = '0'
																		WHERE loancontract_no = ?");
						}
					}else{
						$executeLnMaster = [
							$dataCont["PRINCIPAL_BALANCE"] - $prinPay,$lastperiod,$intArr,$interest_accum + $interest,
							$int_returnSrc,$prinPay,$interest,$dataCont["LAST_STM_NO"] + 1,$contract_no
						];
						$updateLnContmaster = $conmssql->prepare("UPDATE lncontmaster SET 
																	PRINCIPAL_BALANCE = ?,LAST_PERIODPAY = ?,
																	LASTPAYMENT_DATE = CONVERT(VARCHAR(10),GETDATE(),20),
																	INTEREST_ARREAR = ?,INTEREST_ACCUM = ?,
																	INTEREST_RETURN = ?,PRNPAYMENT_AMT = PRNPAYMENT_AMT + ?,
																	INTPAYMENT_AMT = INTPAYMENT_AMT + ?,LAST_STM_NO = ?
																	WHERE loancontract_no = ?");
					}
				}
				if($updateLnContmaster->execute($executeLnMaster)){
					if($interestPeriod > 0){
						$insertTransLog = $this->con->prepare("INSERT INTO gcrepayloan(ref_no,from_account,loancontract_no,source_type,amount,penalty_amt,principal
																,interest,interest_return,interest_arrear,bfinterest_return,bfinterest_arrear,member_no,id_userlogin,
																app_version,is_offset,bfkeeping,calint_to)
																VALUES(:ref_no,:from_account,:loancontract_no,'1',:amount,:penalty_amt,:principal,:interest,
																:interest_return,:interest_arrear,:bfinterest_return,:bfinterest_arrear,:member_no,:id_userlogin,
																:app_version,:is_offset,:bfkeeping,NOW())");
						$insertTransLog->execute([
							':ref_no' => $ref_no,
							':from_account' => $from_account_no,
							':loancontract_no' => $contract_no,
							':amount' => $amt_transfer,
							':penalty_amt' => $penalty_amt,
							':principal' => $prinPay,
							':interest' => $interest,
							':interest_return' => $int_returnSrc,
							':interest_arrear' => $intArr,
							':bfinterest_return' => $dataCont["INTEREST_RETURN"],
							':bfinterest_arrear' => $dataCont["INTEREST_ARREAR"],
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':app_version' => $app_version,
							':is_offset' => ($dataCont["RKEEP_PRINCIPAL"] == 0 && $dataCont["PRINCIPAL_BALANCE"] - $prinPay == 0) ? '2' : '1',
							':bfkeeping' => $dataCont["RKEEP_PRINCIPAL"]
						]);
					}else{
						$insertTransLog = $this->con->prepare("INSERT INTO gcrepayloan(ref_no,from_account,loancontract_no,source_type,amount,penalty_amt,principal
																,interest,interest_return,interest_arrear,bfinterest_return,bfinterest_arrear,member_no,id_userlogin,
																app_version,is_offset,bfkeeping,calint_to)
																VALUES(:ref_no,:from_account,:loancontract_no,'1',:amount,:penalty_amt,:principal,:interest,
																:interest_return,:interest_arrear,:bfinterest_return,:bfinterest_arrear,:member_no,:id_userlogin,
																:app_version,:is_offset,:bfkeeping,:calint_from)");
						$insertTransLog->execute([
							':ref_no' => $ref_no,
							':from_account' => $from_account_no,
							':loancontract_no' => $contract_no,
							':amount' => $amt_transfer,
							':penalty_amt' => $penalty_amt,
							':principal' => $prinPay,
							':interest' => $interest,
							':interest_return' => $int_returnSrc,
							':interest_arrear' => $intArr,
							':bfinterest_return' => $dataCont["INTEREST_RETURN"],
							':bfinterest_arrear' => $dataCont["INTEREST_ARREAR"],
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':app_version' => $app_version,
							':is_offset' => ($dataCont["RKEEP_PRINCIPAL"] == 0 && $dataCont["PRINCIPAL_BALANCE"] - $prinPay == 0) ? '2' : '1',
							':bfkeeping' => $dataCont["RKEEP_PRINCIPAL"],
							':calint_from' => date('Y-m-d H:i:s',strtotime($dataCont["LASTCALINT_DATE"]))
						]);
					}
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
						':destination' => $contract_no,
						':response_code' => "WS0066",
						':response_message' => 'UPDATE lncontmaster ไม่ได้'.$updateLnContmaster->queryString."\n".json_encode($executeLnMaster)
					];
					$log->writeLog('repayloan',$arrayStruc);
					$arrayResult["RESPONSE_CODE"] = 'WS0066';
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
					':destination' => $contract_no,
					':response_code' => "WS0066",
					':response_message' => 'INSERT lncontstatement ไม่ได้'.$insertSTMLoan->queryString."\n".json_encode($executeLnSTM)
				];
				$log->writeLog('repayloan',$arrayStruc);
				$arrayResult["RESPONSE_CODE"] = 'WS0066';
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
				':destination' => $contract_no,
				':response_code' => "WS0066",
				':response_message' => 'UPDATE mbmembmaster ไม่ได้'.$updateInterestAccum->queryString."\n".json_encode([
					':int_accum' => $interest_accum + $interest,
					':member_no' => $member_no
				])
			];
			$log->writeLog('repayloan',$arrayStruc);
			$arrayResult["RESPONSE_CODE"] = 'WS0066';
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}

	}
	public function paySlip($conmssql,$amt_transfer,$config,$slipdoc_no,$operate_date,
	$tofrom_accid,$slipwtd=null,$log,$lib,$payload,$from_account_no,$payinslip_no,$member_no,$ref_no,$itemtypeWTD,$conmysql,$penalty_amt=0){
		$interest_accum = $this->calculateIntAccum($member_no);
		$getShareinfo = $conmssql->prepare("SELECT SHARESTK_AMT FROM SHSHAREMASTER WHERE member_no = :member_no");
		$getShareinfo->execute([':member_no' => $member_no]);
		$rowShare = $getShareinfo->fetch(\PDO::FETCH_ASSOC);
		$getMemberInfo = $conmssql->prepare("SELECT MEMBGROUP_CODE FROM mbmembmaster WHERE member_no = :member_no");
		$getMemberInfo->execute([':member_no' => $member_no]);
		$rowMember = $getMemberInfo->fetch(\PDO::FETCH_ASSOC);
		$arrExecuteSlSlip = [
			$config["COOP_ID"],
			$payinslip_no,
			$config["COOP_ID"],
			$rowMember["MEMBGROUP_CODE"],
			$member_no,
			$slipdoc_no,'PX',
			$operate_date,
			$operate_date,
			$rowShare["SHARESTK_AMT"] * 10,
			$rowShare["SHARESTK_AMT"] * 10,
			$interest_accum,
			$slipwtd ?? null,
			$amt_transfer,
			$config["COOP_ID"]
		];
		$insertPayinSlip = $conmssql->prepare("INSERT INTO slslippayin(COOP_ID,PAYINSLIP_NO,MEMCOOP_ID,MEMBGROUP_CODE,MEMBER_NO,DOCUMENT_NO,SLIPTYPE_CODE,
												SLIP_DATE,OPERATE_DATE,SHARESTKBF_VALUE,SHARESTK_VALUE,INTACCUM_AMT,REF_SYSTEM,REF_SLIPNO,SLIP_AMT,SLIP_STATUS,ENTRY_ID,ENTRY_DATE,ENTRY_BYCOOPID)
												VALUES(?,?,?,?,?,?,?,CONVERT(VARCHAR(10),?,20),CONVERT(VARCHAR(10),?,20),
												?,?,?,'DEP',?,?,1,
												'MOBILE',GETDATE(),?)");
		if($insertPayinSlip->execute($arrExecuteSlSlip)){
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
				':destination' => $payinslip_no,
				':response_code' => "WS0066",
				':response_message' => 'Insert slslippayin ไม่ได้'.$insertPayinSlip->queryString."\n".json_encode($arrExecuteSlSlip)
			];
			$log->writeLog('repayloan',$arrayStruc);
			$arrayResult["RESPONSE_CODE"] = 'WS0066';
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
	}
	public function paySlipDet($conmssql,$amt_transfer,$config,$operate_date,
	$log,$payload,$from_account_no,$payinslip_no,$slipitemtype,$shrloantype_code,$itemtyp_desc,$slipseq_no,$stmitemtype=null,$share_value=0){
		$executeSlDet = [
			$config["COOP_ID"],$payinslip_no,$slipitemtype,$slipseq_no,
			$shrloantype_code,$config["COOP_ID"],$itemtyp_desc,
			$amt_transfer,$share_value + $amt_transfer,$stmitemtype ?? null,
			$dataShare["LAST_PERIOD"],$share_value
		];
		$insertSLSlipDet = $conmssql->prepare("INSERT INTO slslippayindet(COOP_ID,PAYINSLIP_NO,SLIPITEMTYPE_CODE,SEQ_NO,OPERATE_FLAG,
												SHRLONTYPE_CODE,CONCOOP_ID,SLIPITEM_DESC,PERIOD,ITEM_PAYAMT,ITEM_BALANCE,
												INTEREST_PERIOD,INTEREST_RETURN,STM_ITEMTYPE,
												BFPERIOD,BFSHRCONT_BALAMT)
												VALUES(?,?,?,?,1,?,?,?,
												0,?,?,0,0,?,?,?)");
		if($insertSLSlipDet->execute($executeSlDet)){
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
				':response_message' => 'INSERT slslippayindet ไม่ได้'.$insertSLSlipDet->queryString."\n".json_encode($executeSlDet)
			];
			$log->writeLog('repayloan',$arrayStruc);
			$arrayResult["RESPONSE_CODE"] = 'WS0065';
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
	}
	public function paySlipLonDet($conmssql,$dataCont,$amt_transfer,$config,$operate_date,
	$log,$payload,$from_account_no,$payinslip_no,$slipitemtype,$shrloantype_code,$contract_no,$prinPay=0,$interest=0
	,$intarrear=0,$int_returnSrc=0,$interestPeriod=0,$slipseq_no=1){
		$lastperiod = $dataCont["LAST_PERIODPAY"] + 1;
		if($interestPeriod > 0){
			$executeSlDet = [
				$config["COOP_ID"],$payinslip_no,$slipitemtype,$slipseq_no,$shrloantype_code,
				$contract_no ?? null,'ชำระพิเศษ',$lastperiod,$prinPay,$interest,$amt_transfer,
				$dataCont["PRINCIPAL_BALANCE"] - $prinPay,$dataCont["PRINCIPAL_BALANCE"],
				date('Y-m-d H:i:s',strtotime($dataCont["LASTCALINT_DATE"])),$interestPeriod,
				$int_returnSrc,'LPX',$dataCont["LAST_PERIODPAY"],$dataCont["INTEREST_ARREAR_SRC"],
				date('Y-m-d H:i:s',strtotime($dataCont["LASTCALINT_DATE"])),
				date('Y-m-d H:i:s',strtotime($dataCont["LASTPROCESS_DATE"])),
				$dataCont["PERIOD_PAYMENT"],$dataCont["PRINCIPAL_BALANCE"],
				$dataCont["RKEEP_PRINCIPAL"],
				$dataCont["RKEEP_INTEREST"],$dataCont["NKEEP_INTEREST"]
			];
			$insertSLSlipDet = $conmssql->prepare("INSERT INTO slslippayindet(COOP_ID,PAYINSLIP_NO,SLIPITEMTYPE_CODE,SEQ_NO,OPERATE_FLAG,
													SHRLONTYPE_CODE,LOANCONTRACT_NO,SLIPITEM_DESC,PERIOD,PRINCIPAL_PAYAMT,INTEREST_PAYAMT,
													INTARREAR_PAYAMT,ITEM_PAYAMT,ITEM_BALANCE,PRNCALINT_AMT,CALINT_FROM,CALINT_TO,INTEREST_PERIOD,INTEREST_RETURN,STM_ITEMTYPE,
													BFPERIOD,BFINTARR_AMT,BFLASTCALINT_DATE,BFLASTPROC_DATE,BFPERIOD_PAYMENT,BFSHRCONT_BALAMT,
													RKEEP_PRINCIPAL,RKEEP_INTEREST,NKEEP_INTEREST,BFINTRETURN_FLAG)
													VALUES(?,?,?,?,1,?,?,?,?,?,?,0,?,?,?,
													CONVERT(VARCHAR(10),?,20),CONVERT(VARCHAR(10),GETDATE(),20),?,?,
													?,?,?,CONVERT(VARCHAR(10),?,20),
													CONVERT(VARCHAR(10),?,20),
													?,?,?,?,?,0)");
		}else{
			$executeSlDet = [
				$config["COOP_ID"],$payinslip_no,$slipitemtype,$slipseq_no,$shrloantype_code,
				$contract_no ?? null,'ชำระพิเศษ',$lastperiod,$prinPay,$interest,$amt_transfer,
				$dataCont["PRINCIPAL_BALANCE"] - $prinPay,$dataCont["PRINCIPAL_BALANCE"],
				date('Y-m-d H:i:s',strtotime($dataCont["LASTCALINT_DATE"])),date('Y-m-d H:i:s',strtotime($dataCont["LASTCALINT_DATE"])),
				$interestPeriod,$int_returnSrc,'LPX',$dataCont["LAST_PERIODPAY"],$dataCont["INTEREST_ARREAR_SRC"],
				date('Y-m-d H:i:s',strtotime($dataCont["LASTCALINT_DATE"])),
				date('Y-m-d H:i:s',strtotime($dataCont["LASTPROCESS_DATE"])),
				$dataCont["PERIOD_PAYMENT"],$dataCont["PRINCIPAL_BALANCE"],
				$dataCont["RKEEP_PRINCIPAL"],
				$dataCont["RKEEP_INTEREST"], $dataCont["NKEEP_INTEREST"]
			];
			$insertSLSlipDet = $conmssql->prepare("INSERT INTO slslippayindet(COOP_ID,PAYINSLIP_NO,SLIPITEMTYPE_CODE,SEQ_NO,OPERATE_FLAG,
													SHRLONTYPE_CODE,LOANCONTRACT_NO,SLIPITEM_DESC,PERIOD,PRINCIPAL_PAYAMT,INTEREST_PAYAMT,
													INTARREAR_PAYAMT,ITEM_PAYAMT,ITEM_BALANCE,PRNCALINT_AMT,CALINT_FROM,CALINT_TO,INTEREST_PERIOD,INTEREST_RETURN,STM_ITEMTYPE,
													BFPERIOD,BFINTARR_AMT,BFLASTCALINT_DATE,BFLASTPROC_DATE,BFPERIOD_PAYMENT,BFSHRCONT_BALAMT,
													RKEEP_PRINCIPAL,RKEEP_INTEREST,NKEEP_INTEREST,BFINTRETURN_FLAG)
													VALUES(?,?,?,?,1,?,?,?,?,?,?,0,?,?,?,
													CONVERT(VARCHAR(10),?,20),CONVERT(VARCHAR(10),?,20)
													,?,?,?,?,?,CONVERT(VARCHAR(10),?,20),
													CONVERT(VARCHAR(10),?,20),
													?,?,?,?,?,0)");

		}
		if($insertSLSlipDet->execute($executeSlDet)){
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
				':destination' => $payinslip_no,
				':response_code' => "WS0066",
				':response_message' => 'INSERT slslippayindet ไม่ได้'.$insertSLSlipDet->queryString."\n".json_encode($executeSlDet)
			];
			$log->writeLog('repayloan',$arrayStruc);
			$arrayResult["RESPONSE_CODE"] = 'WS0066';
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
	}
	public function paySlipLonOut($conmssql,$config,$payoutslip_no,$member_no,$sliptype_code,$document_no,$operate_date,$loantype_code,$loancontract_no,$amt_transfer,$payload,$deptaccount_no
	,$moneytype_code,$bank_code,$vcc_id,$log){
		$dataCont = $this->getContstantLoanContract($loancontract_no);
		$arrExecuteSlOutSlip = [
			$config["COOP_ID"],$payoutslip_no,$config["COOP_ID"],$member_no,
			$sliptype_code,$document_no ?? null,$operate_date,$operate_date,
			$loantype_code,$loancontract_no,$amt_transfer,$amt_transfer,
			$dataCont["LOANAPPROVE_AMT"],$dataCont["WITHDRAWABLE_AMT"],$dataCont["LASTCALINT_DATE"],$moneytype_code,$bank_code ?? null,$deptaccount_no,$vcc_id,$config["COOP_ID"]
		];
		$insertSLSlipPayout = $conmssql->prepare("INSERT INTO slslippayout(COOP_ID,PAYOUTSLIP_NO,MEMCOOP_ID,MEMBER_NO,SLIPTYPE_CODE,DOCUMENT_NO,SLIP_DATE,OPERATE_DATE,SHRLONTYPE_CODE,
													LOANCONTRACT_NO,PAYOUT_AMT,PAYOUTNET_AMT,BFLOANAPPROVE_AMT,BFWITHDRAW_AMT,CALINT_FROM,CALINT_TO,MONEYTYPE_CODE,EXPENSE_BANK,EXPENSE_BRANCH,EXPENSE_ACCID,
													TOFROM_ACCID,SLIP_STATUS,ENTRY_ID,ENTRY_DATE,ENTRY_BYCOOPID)
													VALUES(?,?,?,?,?,?,CONVERT(VARCHAR(10),?,20),CONVERT(VARCHAR(10),?,20),
													?,?,?,?,?,?,CONVERT(VARCHAR(10),?,20),CONVERT(VARCHAR(10),GETDATE(),20),?,?,'ฮฮฮ',?,?,'1','MOBILE',GETDATE(),?)");
		if($insertSLSlipPayout->execute($arrExecuteSlOutSlip)){
			$arrayResult['RESULT'] = TRUE;
			return $arrayResult;
		}else{
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':deptaccount_no' => $deptaccount_no,
				':loancontract_no' => $loancontract_no,
				':request_amt' => $amt_transfer,
				':status_flag' => '0',
				':response_code' => "WS1040",
				':response_message' => 'Insert slslippayout ไม่ได้'.$insertSLSlipPayout->queryString."\n".json_encode($arrExecuteSlOutSlip)
			];
			$log->writeLog('receiveloan',$arrayStruc);
			$arrayResult["RESPONSE_CODE"] = 'WS1040';
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
	}
	public function paySlipLonOutExpense($conmssql,$config,$payoutslip_no,$bank_account_no,$amt_transfer,$vccid,$payload,$operate_date,$loancontract_no,$log){
		$arrExecuteSlOutExpenseSlip = [
			$config["COOP_ID"],$payoutslip_no,$bank_account_no,$amt_transfer,$vccid
		];
		$insertSLSlipPayOutExpense = $conmssql->prepare("INSERT INTO slslippayoutexpense(COOP_ID,PAYOUTSLIP_NO,SEQ_NO,MONEYTYPE_CODE,EXPENSE_BANK,EXPENSE_BRANCH,EXPENSE_ACCID,EXPENSE_AMT,
														BANKFEE_AMT,TOFROM_ACCID)
														VALUES(?,?,'1','CBT','006','ฮฮฮ',?,?,?)");
		if($insertSLSlipPayOutExpense->execute($arrExecuteSlOutExpenseSlip)){
			$arrayResult['RESULT'] = TRUE;
			return $arrayResult;
		}else{
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':deptaccount_no' => $bank_account_no,
				':loancontract_no' => $loancontract_no,
				':request_amt' => $amt_transfer,
				':status_flag' => '0',
				':response_code' => "WS1040",
				':response_message' => 'Insert slslippayoutexpense ไม่ได้'.$insertSLSlipPayOutExpense->queryString."\n".json_encode($arrExecuteSlOutExpenseSlip)
			];
			$log->writeLog('receiveloan',$arrayStruc);
			$arrayResult["RESPONSE_CODE"] = 'WS1040';
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
	}
	public function receiveLoanOD($conmssql,$config,$contract_no,$dataCont,$slipdocno,$amt_transfer,$lnslip_no,$ref_no,$destination,$fee_amt,$payload,$app_version,$operate_date,$log){
		$interest = $this->calculateInterestArr($contract_no,$amt_transfer);
		$interestFull = $interest;
		$prinPay = 0;
		$interestPeriod = $interest;
		if($interestPeriod < 0){
			$interestPeriod = 0;
		}
		$prinPay = $amt_transfer;
		$int_returnSrc = 0;
		$intArr = $dataCont["INTEREST_ARREAR"] + $interest;
		$lastperiod = $dataCont["LAST_PERIODPAY"] + 1;
		
		if($interestPeriod > 0){
			$executeLnSTM = [
				$config["COOP_ID"],$contract_no,$dataCont["LAST_STM_NO"] + 1,
				'LRC',$slipdocno,$lastperiod,$prinPay,0,$dataCont["PRINCIPAL_BALANCE"] + $prinPay,
				$dataCont["PRINCIPAL_BALANCE"],date('Y-m-d H:i:s',strtotime($dataCont["LASTCALINT_DATE"])),
				$dataCont["INTEREST_ARREAR"],$interestPeriod,$intArr,$int_returnSrc,'TRN',$config["COOP_ID"],
				$lnslip_no,$dataCont["INTEREST_RETURN"]
			];
			$insertSTMLoan = $conmssql->prepare("INSERT INTO lncontstatement(COOP_ID,LOANCONTRACT_NO,SEQ_NO,LOANITEMTYPE_CODE,SLIP_DATE,
													OPERATE_DATE,ACCOUNT_DATE,REF_DOCNO,PERIOD,PRINCIPAL_PAYMENT,INTEREST_PAYMENT,PRINCIPAL_BALANCE,
													PRNCALINT_AMT,CALINT_FROM,CALINT_TO,BFINTARREAR_AMT,INTEREST_PERIOD,INTEREST_ARREAR,
													INTEREST_RETURN,MONEYTYPE_CODE,ITEM_STATUS,ENTRY_ID,ENTRY_DATE,ENTRY_BYCOOPID,REF_SLIPNO,
													BFINTRETURN_AMT,INTACCUM_DATE,SYNC_NOTIFY_FLAG)
													VALUES(?,?,?,?,CONVERT(VARCHAR(10),GETDATE(),20),CONVERT(VARCHAR(10),GETDATE(),20),
													CONVERT(VARCHAR(10),GETDATE(),20),?,?,?,?,?,?,CONVERT(VARCHAR(10),?,20),
													CONVERT(VARCHAR(10),GETDATE(),20),?,?,?,
													?,?,1,'MOBILE',CONVERT(VARCHAR(10),GETDATE(),20),?,?,?,CONVERT(VARCHAR(10),GETDATE(),20),'1')");
		}else{
			$executeLnSTM = [
				$config["COOP_ID"],$contract_no,$dataCont["LAST_STM_NO"] + 1,
				'LRC',$slipdocno,$lastperiod,$prinPay,0,$dataCont["PRINCIPAL_BALANCE"] + $prinPay,
				$dataCont["PRINCIPAL_BALANCE"],date('Y-m-d H:i:s',strtotime($dataCont["LASTCALINT_DATE"])),
				date('Y-m-d H:i:s',strtotime($dataCont["LASTCALINT_DATE"])),
				$dataCont["INTEREST_ARREAR"],$interestPeriod,$intArr,$int_returnSrc,'TRN',$config["COOP_ID"],
				$lnslip_no,$dataCont["INTEREST_RETURN"]
			];
			$insertSTMLoan = $conmssql->prepare("INSERT INTO lncontstatement(COOP_ID,LOANCONTRACT_NO,SEQ_NO,LOANITEMTYPE_CODE,SLIP_DATE,
													OPERATE_DATE,ACCOUNT_DATE,REF_DOCNO,PERIOD,PRINCIPAL_PAYMENT,INTEREST_PAYMENT,PRINCIPAL_BALANCE,
													PRNCALINT_AMT,CALINT_FROM,CALINT_TO,BFINTARREAR_AMT,INTEREST_PERIOD,INTEREST_ARREAR,
													INTEREST_RETURN,MONEYTYPE_CODE,ITEM_STATUS,ENTRY_ID,ENTRY_DATE,ENTRY_BYCOOPID,REF_SLIPNO,
													BFINTRETURN_AMT,INTACCUM_DATE,SYNC_NOTIFY_FLAG)
													VALUES(?,?,?,?,CONVERT(VARCHAR(10),GETDATE(),20),CONVERT(VARCHAR(10),GETDATE(),20),
													CONVERT(VARCHAR(10),GETDATE(),20),?,?,?,?,?,?,CONVERT(VARCHAR(10),?,20),
													CONVERT(VARCHAR(10),?,20),?,?,?,
													?,?,1,'MOBILE',CONVERT(VARCHAR(10),GETDATE(),20),?,?,?,CONVERT(VARCHAR(10),GETDATE(),20),'1')");
		}
		if($insertSTMLoan->execute($executeLnSTM)){
			$executeLnMaster = [
				$dataCont["WITHDRAWABLE_AMT"] - $prinPay,$dataCont["PRINCIPAL_BALANCE"] + $prinPay,$lastperiod,
				$intArr,$dataCont["LAST_STM_NO"] + 1,$contract_no
			];
			if($interestPeriod > 0){
				$updateLnContmaster = $conmssql->prepare("UPDATE lncontmaster SET WITHDRAWABLE_AMT = ?,
															PRINCIPAL_BALANCE = ?,LAST_PERIODPAY = ?,
															LASTPAYMENT_DATE = CONVERT(VARCHAR(10),GETDATE(),20),LASTCALINT_DATE = CONVERT(VARCHAR(10),GETDATE(),20),
															INTEREST_ARREAR = ?,LAST_STM_NO = ?
															WHERE loancontract_no = ?");
			}else{
				$updateLnContmaster = $conmssql->prepare("UPDATE lncontmaster SET WITHDRAWABLE_AMT = ?,
															PRINCIPAL_BALANCE = ?,LAST_PERIODPAY = ?,
															LASTPAYMENT_DATE = CONVERT(VARCHAR(10),GETDATE(),20),
															INTEREST_ARREAR = ?,LAST_STM_NO = ?
															WHERE loancontract_no = ?");
			}
			if($updateLnContmaster->execute($executeLnMaster)){
				if($interestPeriod > 0){
					$insertTransLog = $this->con->prepare("INSERT INTO gcrepayloan(ref_no,from_account,loancontract_no,source_type,amount,fee_amt,penalty_amt,principal
															,interest,interest_return,interest_arrear,bfinterest_return,bfinterest_arrear,member_no,id_userlogin,
															app_version,is_offset,bfkeeping,calint_to)
															VALUES(:ref_no,:from_account,:loancontract_no,'1',:amount,:fee_amt,:penalty_amt,:principal,:interest,
															:interest_return,:interest_arrear,:bfinterest_return,:bfinterest_arrear,:member_no,:id_userlogin,
															:app_version,:is_offset,:bfkeeping,NOW())");
					$insertTransLog->execute([
						':ref_no' => $ref_no,
						':from_account' => $destination,
						':loancontract_no' => $contract_no,
						':amount' => $amt_transfer,
						':fee_amt' => $fee_amt,
						':penalty_amt' => 0,
						':principal' => $prinPay,
						':interest' => 0,
						':interest_return' => $int_returnSrc,
						':interest_arrear' => $intArr,
						':bfinterest_return' => $dataCont["INTEREST_RETURN"],
						':bfinterest_arrear' => $dataCont["INTEREST_ARREAR"],
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':app_version' => $app_version,
						':is_offset' => ($dataCont["RKEEP_PRINCIPAL"] == 0 && $dataCont["PRINCIPAL_BALANCE"] - $prinPay == 0) ? '2' : '1',
						':bfkeeping' => $dataCont["RKEEP_PRINCIPAL"]
					]);
				}else{
					$insertTransLog = $this->con->prepare("INSERT INTO gcrepayloan(ref_no,from_account,loancontract_no,source_type,amount,fee_amt,penalty_amt,principal
															,interest,interest_return,interest_arrear,bfinterest_return,bfinterest_arrear,member_no,id_userlogin,
															app_version,is_offset,bfkeeping,calint_to)
															VALUES(:ref_no,:from_account,:loancontract_no,'1',:amount,:fee_amt,:penalty_amt,:principal,:interest,
															:interest_return,:interest_arrear,:bfinterest_return,:bfinterest_arrear,:member_no,:id_userlogin,
															:app_version,:is_offset,:bfkeeping,:calint_from)");
					$insertTransLog->execute([
						':ref_no' => $ref_no,
						':from_account' => $destination,
						':loancontract_no' => $contract_no,
						':amount' => $amt_transfer,
						':fee_amt' => $fee_amt,
						':penalty_amt' => $penalty_amt,
						':principal' => $prinPay,
						':interest' => 0,
						':interest_return' => $int_returnSrc,
						':interest_arrear' => $intArr,
						':bfinterest_return' => $dataCont["INTEREST_RETURN"],
						':bfinterest_arrear' => $dataCont["INTEREST_ARREAR"],
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':app_version' => $app_version,
						':is_offset' => ($dataCont["RKEEP_PRINCIPAL"] == 0 && $dataCont["PRINCIPAL_BALANCE"] - $prinPay == 0) ? '2' : '1',
						':bfkeeping' => $dataCont["RKEEP_PRINCIPAL"],
						':calint_from' => date('Y-m-d H:i:s',strtotime($dataCont["LASTCALINT_DATE"]))
					]);
				}
				$arrayResult['RESULT'] = TRUE;
				return $arrayResult;
			}else{
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':deptaccount_no' => $destination,
					':loancontract_no' => $contract_no,
					':request_amt' => $amt_transfer,
					':status_flag' => '0',
					':response_code' => "WS1040",
					':response_message' => 'UPDATE lncontmaster ไม่ได้'.$updateLnContmaster->queryString."\n".json_encode($executeLnMaster)
				];
				$log->writeLog('receiveloan',$arrayStruc);
				$arrayResult["RESPONSE_CODE"] = 'WS1040';
				$arrayResult['RESULT'] = FALSE;
				return $arrayResult;
			}
		}
	}
}
?>