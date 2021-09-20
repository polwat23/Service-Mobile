<?php
$dataSendLib = $lineLib->sendLineBot($arrPostData);
if($dataSendLib["RESULT"]){
	//รอยิง Log สำเร็จ
}else{
	file_put_contents(__DIR__.'/../log/response.txt', json_encode($dataSendLib) . PHP_EOL, FILE_APPEND);
}
exit();
?>