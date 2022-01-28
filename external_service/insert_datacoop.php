<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;

$lib = new library();

$fetchCoop = $conoracle->prepare("SELECT  mb.member_no,
							mp.prename_desc,
							mp.prename_edesc,
							mb.memb_name,
							mb.memb_ename,
							mp.suffname_desc,
							mb.sector_id,
							'1' as service_status,
							'1' as appl_status,
							'dev@mode'  as USERNAME ,
							'' as REMARK
							FROM mbmembmaster mb 
							LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
							WHERE mb.member_status = 1 and mb.member_no <> '300710' " );
$fetchCoop->execute();
while($rowCoop = $fetchCoop->fetch(PDO::FETCH_ASSOC)){
	$insertMemberCoop = $conmysql->prepare("INSERT INTO gcmembonlineregis(member_no, prename_desc, memb_name, prename_edesc, memb_ename, approve_id, remark ,sector_id , suffname_desc) 
							VALUES (:member_no ,:prename_desc ,:memb_name ,:prename_edesc , :memb_ename, :approve_id, :remark ,:sector_id , :suffname_desc)");
	if($insertMemberCoop->execute([
		':member_no' => $rowCoop["MEMBER_NO"],
		':prename_desc' => $rowCoop["PRENAME_DESC"],
		':memb_name' => $rowCoop["MEMB_NAME"],
		':prename_edesc'=> $rowCoop["PRENAME_EDESC"],
		':memb_ename'=> $rowCoop["MEMB_ENAME"],
		':approve_id'=> $rowCoop["USERNAME"],
		':remark'=> $rowCoop["REMARK"],
		':sector_id'=> $rowCoop["SECTOR_ID"],
		':suffname_desc'=> $rowCoop["SUFFNAME_DESC"]
	])){
		echo $rowCoop["MEMBER_NO"];
	}else{
		echo "error";

	}
}
?>