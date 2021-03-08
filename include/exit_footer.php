<?php
ob_flush();
if($arrayResult["RESULT"] === FALSE){
	$arrayResult["HEADER_CUSTOM"] = $configError["ERROR_HEADER_CUSTOM"][0][$lang_locale];
}
echo json_encode($arrayResult);
ob_end_clean();
exit();
?>