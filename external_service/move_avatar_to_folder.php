<?php
require_once(__DIR__.'/../extension/vendor/autoload.php');
require_once(__DIR__.'/../include/lib_util.php');

use Utility\Library;
use WebPConvert\WebPConvert;

$lib = new library();
$webP = new WebPConvert();

$filelist = scandir('../resource/avatar/');

foreach($filelist as $name){
	if (!in_array($name,array(".","..",'.gitignore')) && strpos($name,'.') !== FALSE){
			$fileInfo = explode('.',$name);
			if(!file_exists('../resource/avatar/'.$fileInfo[0])){
				mkdir('../resource/avatar/'.$fileInfo[0], 0777, true);
			}
			$dest = 'D:/Mobile/service-scr/resource/avatar/'.$fileInfo[0].'/'.$name;
			rename('D:/Mobile/service-scr/resource/avatar/'.$name,$dest);
			$webP_destination = 'D:/Mobile/service-scr/resource/avatar/'.$fileInfo[0].'/'.$fileInfo[0].'.webp';
			$webP->convert($dest,$webP_destination,[]);
			echo $name;
		
	}
}


?>
