<?php
ob_flush();
echo json_encode($arrayResult);
ob_end_clean();
exit();
?>