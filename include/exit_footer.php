<?php
if(empty($headers["transaction_scheduler"])){
	ob_flush();
	echo json_encode($arrayResult);
	ob_end_clean();
}else{
	echo json_encode($arrayResult);
}
exit();
?>