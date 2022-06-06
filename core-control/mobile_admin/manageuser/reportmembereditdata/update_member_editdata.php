<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','editMember'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','reportmembereditdata')){
		$conoracle->beginTransaction();
		$conmysql->beginTransaction();	
		$year = date(Y) +543;
		$arrayGroup = $dataComing["editMember"];
		

		foreach ($arrayGroup as $value) {
			$member_no = $value["MEMBER_NO"];
			$id_editdata = $value["ID_EDITDATA"];
			
			$inputgroup_type =  $value["INPUTGROUP_TYPE"];
			if($inputgroup_type == "editBoard"){
				$member_count =  $value["INCOMING_DATA_JSON"]["MEMBER_COUNT"];
				$officer_count =  $value["INCOMING_DATA_JSON"]["OFFICER_COUNT"];
				$board = $value["INCOMING_DATA_JSON"]["BOARD"];
				$business =  $value["INCOMING_DATA_JSON"]["BUSINESS"];
				$manager =  $value["INCOMING_DATA_JSON"]["MANAGER"];
				$president =  $value["INCOMING_DATA_JSON"]["PRESIDENT"];
				$branch_id = "001001";
				$seq_no = 1;
						
				$deleteHistory = $conoracle->prepare("DELETE FROM mbmembdetyearboard WHERE member_no = :member_no and biz_year = :year");
			
				if($deleteHistory->execute([
					':member_no' => $member_no,
					':year' => $year
				])){
					
				}else{
					exit();
				}
				
				$UpdateMember = $conoracle->prepare("UPDATE mbmembdetyearbiz SET membership_amt = :member_count WHERE member_no = :member_no and biz_year = :year");
				if($UpdateMember->execute([
					':member_no' => $member_no,
					':year' => $year,
					':member_count' => $member_count
				])){
				
				}else{
					exit();
				}
			
				if(isset($president)){
					$md_name	= $president["MD_NAME"];
					$addr_no	= $president["ADDR_NO"];
					$addr_moo	= $president["ADDR_MOO"];
					$president_email	= $president["BOARD_EMAIL"];
					$manager_tel	= $president["BOARD_TEL"];
					$district_code	= $president["DISTRICT_CODE"];
					$province_code	= $president["PROVINCE_CODE"];
					$tambol_code	= $president["TAMBOL_CODE"];
					$person_id	= $president["PERSON_ID"];
					$addr_road = $president["ADDR_ROAD"];
					$addr_soi = $president["ADDR_SOI"];
					
					
					
					$inset_president = $conoracle->prepare("INSERT INTO mbmembdetyearboard(branch_id,member_no,seq_no,biz_year,board_name ,bdrank_code ,add_no ,addr_moo,addr_soi,addr_road,addr_district,addr_tambol,addr_province,board_tel,board_email,person_id) 
														VALUES(:branch_id,:member_no,:seq_no,:year,:board_name, :bdrank_code ,:addr_no ,:addr_moo,:addr_soi ,:addr_road, :district_code ,:tambol_code ,:province_code ,:board_tel ,:board_email ,:person_id )");
					if($inset_president->execute([
							':branch_id' => $branch_id,
							':addr_moo' => $addr_moo,
							':addr_no' => $addr_no,
							':addr_road' => $addr_road,
							':addr_soi' => $addr_soi,
							':board_email' => $president_email,
							':board_tel' => $manager_tel,
							':tambol_code' => $tambol_code,
							':district_code' => $district_code,
							':province_code' => $province_code,
							':board_name' => $md_name,
							':bdrank_code' => '01',
							':person_id' => $person_id,
							':member_no' => $member_no,
							':year' => $year,
							':seq_no' => $seq_no							
					])){
							
					$seq_no++;		
					}else{
						$conoracle->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถบันทึกได้ กรุณาติดต่อผู้พัฒนา";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
					}
				}
					
				if(isset($manager)){
					$md_name	= $manager["MD_NAME"];
					$addr_no	= $manager["ADDR_NO"];
					$addr_moo	= $manager["ADDR_MOO"];
					$manager_email	= $manager["BOARD_EMAIL"];
					$manager_tel	= $manager["BOARD_TEL"];
					$district_code	= $manager["DISTRICT_CODE"];
					$province_code	= $manager["PROVINCE_CODE"];
					$tambol_code	= $manager["TAMBOL_CODE"];
					$person_id	= $manager["PERSON_ID"];
					$addr_road = $manager["ADDR_ROAD"];
					$addr_soi = $manager["ADDR_SOI"];
					
					$inset_manager = $conoracle->prepare("INSERT INTO mbmembdetyearboard(branch_id,member_no,seq_no,biz_year,board_name ,bdrank_code ,add_no ,addr_moo,addr_soi,addr_road,addr_district,addr_tambol,addr_province,board_tel,board_email,person_id) 
														VALUES(:branch_id,:member_no,:seq_no,:year,:board_name, :bdrank_code ,:addr_no ,:addr_moo,:addr_soi ,:addr_road, :district_code ,:tambol_code ,:province_code ,:board_tel ,:board_email ,:person_id )");
					if($inset_manager->execute([
							':branch_id' => $branch_id,
							':addr_moo' => $addr_moo,
							':addr_no' => $addr_no,
							':addr_road' => $addr_road,
							':addr_soi' => $addr_soi,
							':board_email' => $manager_email,
							':board_tel' => $manager_tel,
							':tambol_code' => $tambol_code,
							':district_code' => $district_code,
							':province_code' => $province_code,
							':board_name' => $md_name,
							':bdrank_code' => '09',
							':person_id' => $person_id,
							':member_no' => $member_no,
							':year' => $year,
							':seq_no' => $seq_no							
					])){
							
					$seq_no++;		
					}else{
						$conoracle->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถบันทึกได้ กรุณาติดต่อผู้พัฒนา";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
					}
				}

				foreach ($board as $value_board) {   // insert คณะ กรรมการ
					$addr_moo = $value_board["ADDR_MOO"];
					$addr_no = $value_board["ADDR_NO"];
					$addr_road = $value_board["ADDR_ROAD"];
					$addr_soi = $value_board["ADDR_SOI"];
					$board_email = $value_board["BOARD_EMAIL"];
					$board_tel = $value_board["BOARD_TEL"];
					$tambol_code = $value_board["TAMBOL_CODE"];
					$district_code = $value_board["DISTRICT_CODE"];
					$province_code = $value_board["PROVINCE_CODE"];
					$md_name = $value_board["MD_NAME"];
					$md_type = $value_board["MD_TYPE"];
					$person_id = $value_board["PERSON_ID"];
					
					$update_board = $conoracle->prepare("INSERT INTO mbmembdetyearboard(branch_id,member_no,seq_no,biz_year,board_name ,bdrank_code ,add_no ,addr_moo,addr_soi,addr_road,addr_district,addr_tambol,addr_province,board_tel,board_email,person_id) 
														VALUES(:branch_id,:member_no,:seq_no,:year,:board_name, :bdrank_code ,:addr_no ,:addr_moo,:addr_soi ,:addr_road, :district_code ,:tambol_code ,:province_code ,:board_tel ,:board_email ,:person_id )");
					if($update_board->execute([
							':branch_id' => $branch_id,
							':addr_moo' => $addr_moo,
							':addr_no' => $addr_no,
							':addr_road' => $addr_road,
							':addr_soi' => $addr_soi,
							':board_email' => $board_email,
							':board_tel' => $board_tel,
							':tambol_code' => $tambol_code,
							':district_code' => $district_code,
							':province_code' => $province_code,
							':board_name' => $md_name,
							':bdrank_code' => '08',
							':person_id' => $person_id,
							':member_no' => $member_no,
							':year' => $year,
							':seq_no' => $seq_no							
						])){
							
					$seq_no++;		
					}else{
						$conoracle->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถบันทึกได้ กรุณาติดต่อผู้พัฒนา";
						$arrayResult['DATA'] = [
								':addr_moo' => $addr_moo,
								':addr_no' => $addr_no,
								':addr_road' => $addr_road,
								':addr_soi' => $addr_soi,
								':board_email' => $board_email,
								':board_tel' => $board_tel,
								':tambol_code' => $tambol_code,
								':district_code' => $district_code,
								':province_code' => $province_code,
								':board_name' => $md_name,
								':bdrank_code' => $md_type,
								':person_id' => $person_id,
								':member_no' => $member_no,
								':year' => $year,
								':seq_no' => $seq_no							
							];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
					}
				}
				foreach ($business as $value_business) { //ผู้ตรวจสอบกิจการ
					$addr_moo = $value_business["ADDR_MOO"];
					$addr_no = $value_business["ADDR_NO"];
					$addr_road = $value_business["ADDR_ROAD"];
					$addr_soi = $value_business["ADDR_SOI"];
					$board_email = $value_business["BOARD_EMAIL"];
					$board_tel = $value_business["BOARD_TEL"];
					$tambol_code = $value_business["TAMBOL_CODE"];
					$district_code = $value_business["DISTRICT_CODE"];
					$province_code = $value_business["PROVINCE_CODE"];
					$md_name = $value_business["MD_NAME"];
					$md_type = $value_business["MD_TYPE"];
					$person_id = $value_business["PERSON_ID"];

					
					$update_board = $conoracle->prepare("INSERT INTO mbmembdetyearboard(branch_id,member_no,seq_no,biz_year,board_name ,bdrank_code ,add_no ,addr_moo,addr_soi,addr_road,addr_district,addr_tambol,addr_province,board_tel,board_email,person_id) 
														VALUES(:branch_id,:member_no,:seq_no,:year,:board_name, :bdrank_code ,:addr_no ,:addr_moo,:addr_soi ,:addr_road, :district_code ,:tambol_code ,:province_code ,:board_tel ,:board_email ,:person_id )");
					if($update_board->execute([
							':branch_id' => $branch_id,
							':addr_moo' => $addr_moo,
							':addr_no' => $addr_no,
							':addr_road' => $addr_road,
							':addr_soi' => $addr_soi,
							':board_email' => $board_email,
							':board_tel' => $board_tel,
							':tambol_code' => $tambol_code,
							':district_code' => $district_code,
							':province_code' => $province_code,
							':board_name' => $md_name,
							':bdrank_code' => '12',
							':person_id' => $person_id,
							':member_no' => $member_no,
							':year' => $year,
							':seq_no' => $seq_no							
						])){
					$seq_no++;
					}else{
						$conoracle->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถบันทึกได้ กรุณาติดต่อผู้พัฒนา";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
					}
				}	
				$update_edit = $conmysql->prepare("UPDATE gcmanagement 
																SET is_updateoncore = '1'
																WHERE  id_editdata = :id_editdata AND member_no = :member_no");
				if($update_edit->execute([
						':member_no' => $member_no,
						':id_editdata' => $id_editdata
					])){
						
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE'] = "ไม่สามารถบันทึกได้ กรุณาติดต่อผู้พัฒนา";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
				}
			}else{
				$addr_no = $value["INCOMING_DATA_JSON"]["addr_no"];
				$addr_moo = $value["INCOMING_DATA_JSON"]["addr_moo"];
				$addr_village = $value["INCOMING_DATA_JSON"]["addr_village"];
				$addr_soi = $value["INCOMING_DATA_JSON"]["addr_soi"];
				$addr_road = $value["INCOMING_DATA_JSON"]["addr_road"];
				$tambol_code = $value["INCOMING_DATA_JSON"]["tambol_code"];
				$district_code = $value["INCOMING_DATA_JSON"]["district_code"];
				$province_code = $value["INCOMING_DATA_JSON"]["province_code"];
				$addr_postcode = $value["INCOMING_DATA_JSON"]["addr_postcode"];
				$coopregis_no = $value["INCOMING_DATA_JSON"]["coopregis_no"];
				$coopregis_date = $value["INCOMING_DATA_JSON"]["coopregis_date"];
				$accyearclose_date = $value["INCOMING_DATA_JSON"]["accyearclose_date"];		
				$memb_regno = $value["INCOMING_DATA_JSON"]["memb_regno"];
				$email = $value["INCOMING_DATA_JSON"]["email"];
				$tax_id = $value["INCOMING_DATA_JSON"]["tax_id"];
				$tel = $value["INCOMING_DATA_JSON"]["tel"];
				$addr_fax = $value["INCOMING_DATA_JSON"]["addr_fax"];
				$website = $value["INCOMING_DATA_JSON"]["website"];
				
				
				$update_coop = $conoracle->prepare("UPDATE mbmembmaster SET addr_no =:addr_no ,addr_moo =:addr_moo ,addr_village =:addr_village ,addr_soi =:addr_soi , addr_road =:addr_road ,
								tambol_code =:tambol_code,district_code =:district_code ,province_code =:province_code, addr_postcode=:addr_postcode ,coopregis_no =:coopregis_no,
								coopregis_date =  TRUNC(TO_DATE(:coopregis_date,'yyyy-mm-dd hh24:mi:ss')),accyearclose_date = TRUNC(TO_DATE(:accyearclose_date ,'yyyy-mm-dd hh24:mi:ss')) , 
								memb_regno =:memb_regno ,addr_email =:email ,tax_id =:tax_id ,addr_phone =:tel ,addr_fax =:addr_fax
								WHERE   member_no = :member_no");
				if($update_coop->execute([
						':addr_no' => $addr_no,
						':addr_moo' => $addr_moo,
						':addr_village' => $addr_village,
						':addr_soi' => $addr_soi,
						':addr_road' => $addr_road,
						':tambol_code' => $tambol_code,
						':district_code' => $district_code,
						':province_code' => $province_code,
						':addr_postcode' => $addr_postcode,
						':coopregis_no' => $coopregis_no,
						':coopregis_date' => $coopregis_date,
						':accyearclose_date' => $accyearclose_date,
						':memb_regno' => $memb_regno,
						':email' => $email,
						':tax_id' => $tax_id,
						':tel' => $tel,
						':addr_fax' => $addr_fax,
						':member_no' => $member_no
					])){	
				}else{
					$conoracle->rollback();
					$arrayResult['RESPONSE'] = "ไม่สามารถบันทึกได้ กรุณาติดต่อผู้พัฒนา";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
				}
				
				$update_edit = $conmysql->prepare("UPDATE gcmembereditdata 
																SET is_updateoncore = '1'
																WHERE  id_editdata = :id_editdata AND member_no = :member_no");
				if($update_edit->execute([
						':member_no' => $member_no,
						':id_editdata' => $id_editdata
					])){
						
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE'] = "ไม่สามารถบันทึกได้ กรุณาติดต่อผู้พัฒนา";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
				}
			}			
		}
		
		$conoracle->commit();
		$conmysql->commit();
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