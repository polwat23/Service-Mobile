<?php

		try{
			
		$json = file_get_contents(__DIR__.'/config/config_connection.json');
		$json_data = json_decode($json,true);
		
		//putenv("NLS_LANG=AMERICAN_AMERICA.WE8MSWIN1252");
		
			$dbuser = $json_data["DBORACLE_USERNAME"];
			$dbpass = $json_data["DBORACLE_PASSWORD"];
			
			//$conn = oci_connect($dbuser, $dbpass, $json_data["DBORACLE_HOST"].":".$json_data["DBORACLE_PORT"]."/".$json_data["DBORACLE_SERVICE"],'WE8MSWIN1252');
			$conn = oci_connect($dbuser, $dbpass, $json_data["DBORACLE_HOST"].":".$json_data["DBORACLE_PORT"]."/".$json_data["DBORACLE_SERVICE"]);
					
			if (!$conn) {
				$e = oci_error();
				trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
			}
			
			//$stid = oci_parse($conn, "ALTER SESSION SET NLS_LANG='AMERICAN_AMERICA.WE8MSWIN1252'");
			//oci_execute($stid);

			$stid = oci_parse($conn, 
				"SELECT mp.prename_short, 
													mb.memb_name,
													mb.memb_surname,
													mb.birth_date,mb.card_person,
													mb.member_date,mb.position_desc,mt.membtype_desc,
													mb.MEMB_ADDR as ADDR_NO,
													mb.ADDR_GROUP as ADDR_MOO,
													mb.SOI as ADDR_SOI,
													mb.MOOBAN as ADDR_VILLAGE,
													mb.ROAD as ADDR_ROAD,
													MB.TAMBOL AS TAMBOL_DESC,
													MBD.DISTRICT_DESC AS DISTRICT_DESC,
													MB.PROVINCE_CODE AS PROVINCE_CODE,
													MBP.PROVINCE_DESC AS PROVINCE_DESC,
													MB.POSTCODE AS ADDR_POSTCODE,
													mb.MEMBGROUP_CODE
													FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													LEFT JOIN MBUCFMEMBTYPE mt ON mb.MEMBTYPE_CODE = mt.MEMBTYPE_CODE
													LEFT JOIN MBUCFDISTRICT MBD ON mb.DISTRICT_CODE = MBD.DISTRICT_CODE
													and mb.PROVINCE_CODE = MBD.PROVINCE_CODE
													LEFT JOIN MBUCFPROVINCE MBP ON mb.PROVINCE_CODE = MBP.PROVINCE_CODE
													WHERE TRIM(mb.member_no) = '002827'");
			oci_execute($stid);

			while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
			//json_decode($json,true);
				//foreach ($row as $item) {
					//echo "    <td>" . ($item) . "</td>\n";
					$data_json=json_encode($row);

				//}
				//header('Content-Type: text/pdf');
				file_put_contents( (__DIR__."/data_1.txt"), print_r($row, true));
			}
			$member_no ="002827";
			$dbname = "(DESCRIPTION =
						(ADDRESS_LIST =
						  (ADDRESS = (PROTOCOL = TCP)(HOST = ".$json_data["DBORACLE_HOST"].")(PORT = ".$json_data["DBORACLE_PORT"]."))
						)
						(CONNECT_DATA =
						  (".$json_data["DBORACLE_TYPESERVICE"]." = ".$json_data["DBORACLE_SERVICE"].")
						)
					  )";
			//$this->conoracle = new \PDO("oci:dbname=".$dbname."", $dbuser, $dbpass);
			$conoracle = new \PDO("oci:dbname=".$dbname.";", $dbuser, $dbpass );
			$memberInfo = $conoracle->prepare("SELECT mp.prename_short,
													mb.memb_name as memb_name,
													mb.memb_surname,
													mb.birth_date,mb.card_person,
													mb.member_date,mb.position_desc,mt.membtype_desc,
													mb.MEMB_ADDR as ADDR_NO,
													mb.ADDR_GROUP as ADDR_MOO,
													mb.SOI as ADDR_SOI,
													mb.MOOBAN as ADDR_VILLAGE,
													mb.ROAD as ADDR_ROAD,
													MB.TAMBOL AS TAMBOL_DESC,
													MBD.DISTRICT_DESC AS DISTRICT_DESC,
													MB.PROVINCE_CODE AS PROVINCE_CODE,
													MBP.PROVINCE_DESC AS PROVINCE_DESC,
													MB.POSTCODE AS ADDR_POSTCODE,
													mb.MEMBGROUP_CODE
													FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													LEFT JOIN MBUCFMEMBTYPE mt ON mb.MEMBTYPE_CODE = mt.MEMBTYPE_CODE
													LEFT JOIN MBUCFDISTRICT MBD ON mb.DISTRICT_CODE = MBD.DISTRICT_CODE
													and mb.PROVINCE_CODE = MBD.PROVINCE_CODE
													LEFT JOIN MBUCFPROVINCE MBP ON mb.PROVINCE_CODE = MBP.PROVINCE_CODE
													WHERE TRIM(mb.member_no) = :member_no");
			$memberInfo->execute([':member_no' => $member_no]);
			$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
			
			$filename=(__DIR__."/data_$member_no.txt");
			
		   // file_put_contents( $filename, (print_r($rowMember, true)));
				
			$rowMember=parseOraDataBufferToArray($filename);	
			
			echo mb_detect_encoding($rowMember["MEMB_SURNAME"]);
			//echo json_encode($rowMember);
			
		}catch(\Throwable $e){
			$arrayError = array();
			$arrayError["ERROR"] = $e->getMessage();
			$arrayError["RESULT"] = FALSE;
			$arrayError["MESSAGE"] = "Can't connect To Oracle";
			print_r($arrayError);
			//http_response_code(200);
			exit();
		}
		
	function convertTextToUtf8($content) {
		# detect original encoding
		$original_encoding=mb_detect_encoding($content, "UTF-8, ISO-8859-1, ISO-8859-15", true);
		echo "Content=>".$original_encoding;
		# now convert
		if ($original_encoding!='UTF-8') {
			$content=mb_convert_encoding($content, "UTF-8", $original_encoding);

		}
		//$bom=chr(239) . chr(187) . chr(191); # use BOM to be on safe side
		//file_put_contents($target, $bom.$content);
		return $content;
	}	
	
	function convertFileToUtf8($source, $target) {
		$content=file_get_contents($source);
		# detect original encoding
		$original_encoding=mb_detect_encoding($content, "UTF-8, ISO-8859-1, ISO-8859-15","TIS-620", true);
		echo "Content=>".$original_encoding;
		# now convert
		if ($original_encoding!='UTF-8') {
			//$content=mb_convert_encoding($content, "UTF-8", $original_encoding);
			$content=utf8_encode($content);

		}
		//$bom=chr(239) . chr(187) . chr(191); # use BOM to be on safe side
		//file_put_contents($target, $bom.$content);
		file_put_contents($target, $content);
	}	
		
	function parseOraDataBufferToArray($filename) {
		//convertFileToUtf8($filename, $filename);
		$str=file_get_contents($filename);

        $keys = array();
        $values = array();
        $output = array();

        if( substr($str, 0, 5) == 'Array' ) {

            $array_contents = substr($str, 7, -2);
            $array_contents = str_replace(array('[', ']', '=>'), array('#!#', '#?#', ''), $array_contents);
            $array_fields = explode("#!#", $array_contents);
            for($i = 0; $i < count($array_fields); $i++ ) {

                if( $i != 0 ) {

                    $bits = explode('#?#', $array_fields[$i]);
                    if( $bits[0] != '' ) $output[$bits[0]] = $bits[1];//mb_convert_encoding( $bits[1],"UTF8","ASCII");

                }
            }
            return $output;

        } else {
			
            return null;
        }

    
	}
?>