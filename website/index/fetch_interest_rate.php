<?php
require_once('../autoload.php');

		$arrayGroup = array();
		$arrayGroupFile = array();
		
			
		$fetchInterestType = $conmysql->prepare("SELECT interestrate_id, interest_name, active_date FROM webcoopinterestrate WHERE is_use = '1'");
		$fetchInterestType->execute();
		$arrayGroupFile=[];		
		while($rowInterestType = $fetchInterestType->fetch(PDO::FETCH_ASSOC)){
				$arrIntType = array();
				$arrIntType["INTEREST_NAME"] = $rowInterestType["interest_name"];
				$arrIntType["interestrate_id"] = $rowInterestType["interestrate_id"];
				$arrIntType["ACTIVE_DATE"] = $lib->convertdate($rowInterestType["active_date"],'d m Y',false); 
				$arrIntType["INTEREST_RATE_ARR"] = array();
				
				$fetchInterestRate = $conmysql->prepare("SELECT interestratelist_name, interest_rate, remark FROM webcoopinterestratelist 
														WHERE is_use = '1' AND interestrate_id = :interestrate_id");
				$fetchInterestRate->execute([
					':interestrate_id' => $rowInterestType["interestrate_id"]
				]);
				
				while($rowInterestRate = $fetchInterestRate->fetch(PDO::FETCH_ASSOC)){
					$arrIntRate = array();
					$arrIntRate["INTERESTRATELIST_NAME"] = $rowInterestRate["interestratelist_name"];
					$arrIntRate["INTEREST_RATE"] = $rowInterestRate["interest_rate"];
					$arrIntRate["REMARK"] = $rowInterestRate["remark"];
					
					$arrIntType["INTEREST_RATE_ARR"][] = $arrIntRate;
				}
				
				$arrayGroupFile[] = $arrIntType;
		}
		$arrayResult["INTEREST_RATE"] = $arrayGroupFile;
		
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);

?>