<?php
require_once(__DIR__.'/../include/connection.php');

use Connection\connection;

$con = new connection();

$conoracle = $con->connecttooracle();

$getBeneficiary = $conoracle->prepare("SELECT fm.base64_img,fmt.mimetypes,fm.data_type,to_char(fm.update_date,'YYYYMMDDHH24MI') as UPDATE_DATE 
												FROM fomimagemaster fm LEFT JOIN fomucfmimetype fmt ON fm.data_type = fmt.typefile
												where fm.system_code = 'mbshr' and fm.column_name = 'member_no' 
												and fm.column_data = :member_no and fm.img_type_code = '003' and rownum <= 1 ORDER BY fm.seq_no DESC");
		$getBeneficiary->execute([':member_no' => '00009445']);
		$rowBenefit = $getBeneficiary->fetch(PDO::FETCH_ASSOC);
	// header("Content-type: image/jpeg");
	$img = stream_get_contents($rowBenefit['BASE64_IMG']);
    echo '<img src="data:image/jpeg;base64,$img" />';
?>