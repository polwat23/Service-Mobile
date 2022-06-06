<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;

$lib = new library();
$i = 0 ;
$fetchCoop = $conoracle->prepare("SELECT  mb.member_no,
							mb.memb_name,
							mb.addr_email
							FROM mbmembmaster mb 
							LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
							WHERE mb.member_status = 1  and mb.member_no in('300710','100650','100060','101174','100572','100128','100014'
							,'100623','100033','100287','100356','100498','100294','100025','100254','100119','101075','100195','100358')");
$fetchCoop->execute();
while($rowCoop = $fetchCoop->fetch(PDO::FETCH_ASSOC)){
	$password = $lib->randomText('number',6);
	$insertUserCoop = $conmysql->prepare ("INSERT INTO gcmemberaccount(member_no, ref_memno, acc_name, email , account_status , temppass, password_excell)  
							VALUES (:member_no ,:ref_memno ,:acc_name , :email, '-9' , :temppass, :password_excell)");
	if($insertUserCoop->execute([
		':member_no' => $rowCoop["MEMBER_NO"],
		':ref_memno' => $rowCoop["MEMBER_NO"],
		':acc_name' => $rowCoop["MEMB_NAME"],
		':email'=> $rowCoop["ADDR_EMAIL"],
		':temppass' => password_hash($password,PASSWORD_DEFAULT),
		':password_excell'=> $password
		
	])){
		echo $rowCoop["MEMBER_NO"];
	}else{
		echo "error";

	}
	
	
}
?>