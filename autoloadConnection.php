<?php
date_default_timezone_set("Asia/Bangkok");

require_once(__DIR__.'/include/connection.php');
require_once(__DIR__.'/include/validate_input.php');
use Connection\connection;

$con = new connection();
$conmysql = $con->connecttomysql();
$checkSystem = $conmysql->prepare("SELECT menu_status FROM gcmenu									
									WHERE menu_parent = '-1'
									and (menu_channel = :channel OR menu_channel = 'both')");
$checkSystem->execute([':channel' => $dataComing["channel"]]);
if($checkSystem->rowCount() > 0){
	$rowSystem = $checkSystem->fetch(PDO::FETCH_ASSOC);
	if($rowSystem["menu_status"] == '1'){
		$conoracle = $con->connecttooracle();
		if(is_array($conoracle)){
			$conoracle["IS_OPEN"] = '1';
		}
	}else{
		$conoracle = $con->connecttooracle();
		$conoracle->IS_OPEN = '0';
		if(!is_array($conoracle)){
			$updateMenu = $conmysql->prepare("UPDATE gcmenu SET menu_status = '1',menu_permission = '0' WHERE menu_parent = '-1' and menu_permission = '3'");
			$updateMenu->execute();
		}
	}
}else{
	$conoracle = $con->connecttooracle();
	$conoracle->IS_OPEN = '0';
	if(!is_array($conoracle)){
		$updateMenu = $conmysql->prepare("UPDATE gcmenu SET menu_status = '1',menu_permission = '0' WHERE menu_parent = '-1' and menu_permission = '3'");
		$updateMenu->execute();
	}
}

	 function convertFileToUtf8($source, $target) {
		$content=file_get_contents($source);
		# detect original encoding
		$original_encoding=mb_detect_encoding($content, "UTF-8, ISO-8859-1, ISO-8859-15", true);
		echo "Content=>".$original_encoding;
		# now convert
		if ($original_encoding!='UTF-8') {
			$content=mb_convert_encoding($content, "UTF-8", $original_encoding);

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
                    if( $bits[0] != '' ) $output[$bits[0]] = trim($bits[1]);//mb_convert_encoding( $bits[1],"UTF8","ASCII");

                }
            }
            return $output;

        } else {
			
            return null;
        }

    
	}
	
	function convertArray(&$rowData,$del=false,$from="tis-620",$to="utf-8"){
		
			$root="c:\\WINDOWS\\TEMP\\";
			$now = DateTime::createFromFormat('U.u', microtime(true));
			$ID=$now->format('Y-m-d-h-i-s-u');
			$iconv_path="\"C:\\Program Files\\gettext-iconv\\bin\\iconv\"";
			$filename=($root.$ID.".txt");
			$filename_=($root.$ID."_.txt");
			$filename_bat=$filename.".bat";
			file_put_contents( $filename, print_r($rowData, true));
			file_put_contents( $filename_bat,($iconv_path." -f ".$from." -t ".$to." ".$filename.">".$filename_.""));
			exec("c:\\WINDOWS\\system32\\cmd.exe /c \"".$filename_bat."\" ");
			$rowData=parseOraDataBufferToArray($filename_);
			if($del){
			 unlink($filename);
			 unlink($filename_);
			 unlink($filename_bat);
			}
			return $rowData;
	}
?>