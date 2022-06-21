<?php
	//configLine
	file_put_contents(__DIR__.'/response.txt', json_encode($jsonLine,JSON_UNESCAPED_UNICODE ) . PHP_EOL, FILE_APPEND);
?>