<?php

namespace ControlLog;

use Connection\connection;
use Component\functions;

class insertLog {
	private $con;
	private $func;
		function __construct() {
			$this->func = new functions();
			$connection = new connection();
			$this->con = $connection->connecttooracle();
		}
		
		public function writeLog($type_log,$log_struc,$is_catch=false) {
			if($type_log == 'use_application'){
				$this->logUseApplication($log_struc);
			}else if($type_log == 'bindaccount'){
				$this->logBindAccount($log_struc,$is_catch);
			}else if($type_log == 'unbindaccount'){
				$this->logUnBindAccount($log_struc,$is_catch);
			}else if($type_log == 'deposittrans'){
				$this->logDepositTransfer($log_struc);
			}else if($type_log == 'withdrawtrans'){
				$this->logWithdrawTransfer($log_struc);
			}else if($type_log == 'transferinside'){
				$this->logTransferInsideCoop($log_struc);
			}else if($type_log == 'manageuser'){
				$this->logManageUserAccount($log_struc);
			}else if($type_log == 'editadmincontrol'){
				$this->logEditAdminControl($log_struc);
			}else if($type_log == 'lockaccount'){
				$this->logLockAccount($log_struc);
			}else if($type_log == 'errorusage'){
				$this->logErrorUsage($log_struc);
			}else if($type_log == 'buyshare'){
				$this->logBuyShare($log_struc);
			}else if($type_log == 'repayloan'){
				$this->logRepayLoan($log_struc);
			}else if($type_log == 'editsms'){
				$this->logEditSMS($log_struc);
			}else if($type_log == 'editinfo'){
				$this->logEditInfo($log_struc);
			}else if($type_log == 'changepass'){
				$this->logChangePassword($log_struc);
			}else if($type_log == 'manageapplication'){
				$this->logChangeAssist($log_struc);
			}
			
		}
		private function logChangePassword($log_struc){
			$id_logchange = $this->func->getMaxTable('id_logchange' , 'logchangepassword');
			$insertLog = $this->con->prepare("INSERT INTO logchangepassword(id_logchange,member_no,id_userlogin,app_version,method,email,is_send_mail,send_error) 
												VALUES(".$id_logchange.",:member_no,:id_userlogin,:app_version,:method,:email,:status_send,:send_error)");
			$insertLog->execute($log_struc);
		}
		private function logUseApplication($log_struc){
			$id_loguseapp = $this->func->getMaxTable('id_loguseapp' , 'loguseapplication');
			$insertLog = $this->con->prepare("INSERT INTO loguseapplication(id_loguseapp,member_no,id_userlogin,access_date,ip_address) 
												VALUES(".$id_loguseapp.",:member_no,:id_userlogin, SYSDATE ,:ip_address)");
			$insertLog->execute($log_struc);
		}
		
		private function logBindAccount($log_struc,$is_catch){
			$id_logbindaccount = $this->func->getMaxTable('id_logbindaccount' , 'logbindaccount');
			if($log_struc[":bind_status"] == '-9'){
				if($log_struc[":query_flag"] == '-9'){
					$insertLog = $this->con->prepare("INSERT INTO logbindaccount(id_logbindaccount,member_no,id_userlogin,bind_status,
														response_code,response_message,coop_account_no,data_bind_error,query_error,query_flag) 
														VALUES(".$id_logbindaccount.",:member_no,:id_userlogin,:bind_status,:response_code,:response_message,:coop_account_no
														,:data_bind_error,:query_error,:query_flag)");
				}else{
					if($is_catch){
						$insertLog = $this->con->prepare("INSERT INTO logbindaccount(id_logbindaccount,member_no,id_userlogin,bind_status
															,response_code,response_message,query_flag) 
															VALUES(".$id_logbindaccount.",:member_no,:id_userlogin,:bind_status,:response_code,:response_message,:query_flag)");
					}else{
						$insertLog = $this->con->prepare("INSERT INTO logbindaccount(id_logbindaccount,member_no,id_userlogin,bind_status
															,response_code,response_message,coop_account_no,query_flag) 
															VALUES(".$id_logbindaccount.",:member_no,:id_userlogin,:bind_status,:response_code,:response_message,:coop_account_no,:query_flag)");
					}
				}
			}else{
				$insertLog = $this->con->prepare("INSERT INTO logbindaccount(id_logbindaccount,member_no,id_userlogin,bind_status,coop_account_no) 
													VALUES(".$id_logbindaccount.",:member_no,:id_userlogin,:bind_status,:coop_account_no)");
			}
			$insertLog->execute($log_struc);
		}
		
		private function logUnBindAccount($log_struc,$is_catch){
			$id_logunbindaccount = $this->func->getMaxTable('id_logunbindaccount' , 'logunbindaccount');
			if($log_struc[":unbind_status"] == '-9'){
				if($log_struc[":query_flag"] == '-9'){
					$insertLog = $this->con->prepare("INSERT INTO logunbindaccount(id_logunbindaccount,member_no,id_userlogin,unbind_status,
														response_code,response_message,id_bindaccount,data_unbind_error,query_error,query_flag) 
														VALUES(".$id_logunbindaccount.",:member_no,:id_userlogin,:unbind_status,:response_code,:response_message,:id_bindaccount
														,:data_bind_error,:query_error,'-9')");
				}else{
					if($is_catch){
						$insertLog = $this->con->prepare("INSERT INTO logunbindaccount(id_logunbindaccount,member_no,id_userlogin,unbind_status
															,response_code,response_message,query_flag) 
															VALUES(".$id_logunbindaccount.",:member_no,:id_userlogin,:unbind_status,:response_code,:response_message,:query_flag)");
					}else{
						$insertLog = $this->con->prepare("INSERT INTO logunbindaccount(id_logunbindaccount,member_no,id_userlogin,unbind_status
															,response_code,response_message,id_bindaccount,query_flag) 
															VALUES(".$id_logunbindaccount.",:member_no,:id_userlogin,:unbind_status,:response_code,:response_message,:id_bindaccount,:query_flag)");
					}
				}
			}else{
				$insertLog = $this->con->prepare("INSERT INTO logunbindaccount(id_logunbindaccount,member_no,id_userlogin,unbind_status,id_bindaccount) 
													VALUES(".$id_logunbindaccount.",:member_no,:id_userlogin,:unbind_status,:id_bindaccount)");
			}
			$insertLog->execute($log_struc);
		}
		private function logDepositTransfer($log_struc){
			$id_deptransbankerr = $this->func->getMaxTable('id_deptransbankerr' , 'logdepttransbankerror');
			$insertLog = $this->con->prepare("INSERT INTO logdepttransbankerror(id_deptransbankerr,member_no,id_userlogin,transaction_date,sigma_key,amt_transfer
												,response_code,response_message,is_adj) 
												VALUES(".$id_deptransbankerr.",:member_no,:id_userlogin,TO_DATE(:operate_date,'yyyy-mm-dd hh24:mi:ss'),:sigma_key,:amt_transfer,:response_code,:response_message,:is_adj)");
			$insertLog->execute($log_struc);
		}
		private function logWithdrawTransfer($log_struc){
			$id_withdrawtransbankerr = $this->func->getMaxTable('id_withdrawtransbankerr' , 'logwithdrawtransbankerror');
			if(isset($log_struc[":fee_amt"])){
				$insertLog = $this->con->prepare("INSERT INTO logwithdrawtransbankerror(id_withdrawtransbankerr,member_no,id_userlogin,transaction_date,amt_transfer,penalty_amt,fee_amt,deptaccount_no
												,response_code,response_message) 
												VALUES(".$id_withdrawtransbankerr.",:member_no,:id_userlogin,TO_DATE(:operate_date,'yyyy-mm-dd hh24:mi:ss'),:amt_transfer,:penalty_amt,:fee_amt,:deptaccount_no,:response_code,:response_message)");
			}else{
				$insertLog = $this->con->prepare("INSERT INTO logwithdrawtransbankerror(id_withdrawtransbankerr,member_no,id_userlogin,transaction_date,amt_transfer,deptaccount_no
												,response_code,response_message) 
												VALUES(".$id_withdrawtransbankerr.",:member_no,:id_userlogin,TO_DATE(:operate_date,'yyyy-mm-dd hh24:mi:ss'),:amt_transfer,:deptaccount_no,:response_code,:response_message)");
			}
			$insertLog->execute($log_struc);
		}
		private function logTransferInsideCoop($log_struc){
			if(isset($log_struc[":penalty_amt"])){
				$id_transferinsidecoop = $this->func->getMaxTable('id_transferinsidecoop' , 'logtransferinsidecoop');
				$insertLog = $this->con->prepare("INSERT INTO logtransferinsidecoop(id_transferinsidecoop,member_no,id_userlogin,transaction_date,deptaccount_no,amt_transfer,penalty_amt,type_request,transfer_flag
													,destination,response_code,response_message) 
													VALUES(".$id_transferinsidecoop.",:member_no,:id_userlogin,TO_DATE(:operate_date,'yyyy-mm-dd hh24:mi:ss'),:deptaccount_no,:amt_transfer,:penalty_amt,:type_request,:transfer_flag,
													:destination,:response_code,:response_message)");
			}else{
				$id_transferinsidecoop = $this->func->getMaxTable('id_transferinsidecoop' , 'logtransferinsidecoop');
				$insertLog = $this->con->prepare("INSERT INTO logtransferinsidecoop(id_transferinsidecoop,member_no,id_userlogin,transaction_date,deptaccount_no,amt_transfer,type_request,transfer_flag
													,destination,response_code,response_message) 
													VALUES(".$id_transferinsidecoop.", :member_no,:id_userlogin,TO_DATE(:operate_date,'yyyy-mm-dd hh24:mi:ss'),:deptaccount_no,:amt_transfer,:type_request,:transfer_flag,
													:destination,:response_code,:response_message)");

			}
			$insertLog->execute($log_struc);
		}
		private function logManageUserAccount($log_struc){		
			$id_logeditmobileadmin = $this->func->getMaxTable('id_logeditmobileadmin' , 'logeditmobileadmin');
			$insertLog = $this->con->prepare("INSERT INTO logeditmobileadmin(id_logeditmobileadmin,menu_name,username,use_list,details) 
												VALUES(".$id_logeditmobileadmin.",:menu_name,:username,:use_list,:details)");
			$insertLog->execute($log_struc);
		}
		private function logEditAdminControl($log_struc){
			$id_logeditadmincontrol = $this->func->getMaxTable('id_logeditadmincontrol' , 'logeditadmincontrol');
			$insertLog = $this->con->prepare("INSERT INTO logeditadmincontrol(id_logeditadmincontrol,menu_name,username,use_list,details) 
												VALUES(".$id_logeditadmincontrol.",:menu_name,:username,:use_list,:details)");
			$insertLog->execute($log_struc);
		}
		private function logLockAccount($log_struc){
			$id_lockacc = $this->func->getMaxTable('id_lockacc' , 'loglockaccount');
			$insertLog = $this->con->prepare("INSERT INTO loglockaccount(id_lockacc,member_no,device_name,unique_id) 
												VALUES(".$id_logeditadmincontrol.",:member_no,:device_name,:unique_id)");
			$insertLog->execute($log_struc);
		}
		private function logErrorUsage($log_struc){
			$id_errorusage = $this->func->getMaxTable('id_errorusage' , 'logerrorusageapplication');
			$insertLog = $this->con->prepare("INSERT INTO logerrorusageapplication(id_errorusage,error_menu,error_code,error_desc,error_device) 
												VALUES(".$id_errorusage.",:error_menu,:error_code,:error_desc,:error_device)");
			$insertLog->execute($log_struc);
		}
		private function logBuyShare($log_struc){
			$id_buyshare = $this->func->getMaxTable('id_buyshare' , 'logbuyshare');
			$insertLog = $this->con->prepare("INSERT INTO logbuyshare(id_buyshare,member_no,id_userlogin,transaction_date,deptaccount_no,amt_transfer,status_flag
											,destination,response_code,response_message) 
											VALUES(".$id_buyshare.",:member_no,:id_userlogin,TO_DATE(:operate_date,'yyyy-mm-dd hh24:mi:ss'),:deptaccount_no,:amt_transfer,:status_flag,
											:destination,:response_code,:response_message)");
			$insertLog->execute($log_struc);
		}
		private function logRepayLoan($log_struc){			
			$id_logrepaylon = $this->func->getMaxTable('id_repayloan' , 'logrepayloan');
			$insertLog = $this->con->prepare("INSERT INTO logrepayloan(id_repayloan,member_no,id_userlogin,transaction_date,deptaccount_no,amt_transfer,status_flag
											,destination,response_code,response_message) 
											VALUES(".$id_logrepaylon.",:member_no,:id_userlogin,TO_DATE(:operate_date,'yyyy-mm-dd hh24:mi:ss'),:deptaccount_no,:amt_transfer,:status_flag,
											:destination, :response_code, :response_message)");
			$insertLog->execute($log_struc);
		}
		private function logEditSMS($log_struc){
			//$id_logrepaylon = $this->func->getMaxTable('id_repayloan' , 'logrepayloan');
			$insertLog = $this->con->prepare("INSERT INTO logeditsms(menu_name,username,use_list,details) 
												VALUES(:menu_name,:username,:use_list,:details)");
			$insertLog->execute($log_struc);
		}
		private function logEditInfo($log_struc){
			$insertLog = $this->con->prepare("INSERT INTO logchangeinfo(member_no,old_data,new_data,data_type,id_userlogin) 
												VALUES(:member_no,:old_data,:new_data,:data_type,:id_userlogin)");
			$insertLog->execute($log_struc);
		}
}
?>