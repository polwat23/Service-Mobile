<?php
require_once(__DIR__.'/../extension/vendor/autoload.php');

use Endroid\QrCode\QrCode;

$url = 'https://mobilecore.gensoft.co.th/FSCT-TEST';

if(isset($_GET["durt_regno"])){
	$stringQRGenerate = 'https://10.100.110.174/FSCT/GCOOP/Saving/Applications/sup/ws_sup_durtmaster_ctrl/ws_sup_durtmaster.aspx?setApp=sup&setGroup=A&setWinId=SUP-A00070&durt_regno='.$_GET["durt_regno"];
	$qrCode = new QrCode($stringQRGenerate);
	header('Content-Type: '.$qrCode->getContentType());
	$qrCode->writeString();
	$qrCode->writeFile(__DIR__.'/../resource/qrcode/'.$_GET["durt_regno"].'.png');
	$fullPath = $url.'/resource/qrcode/'.$_GET["durt_regno"].'.png';
}else if($_GET["invt_id"]){
	$stringQRGenerate = 'https://10.100.110.174/FSCT/GCOOP/Saving/Applications/durinvt/ws_invt_invtmaster_ctrl/ws_invt_invtmaster.aspx?setApp=dur&setGroup=A&setWinId=DU-E000010&invt_id='.$_GET["invt_id"];
	$qrCode = new QrCode($stringQRGenerate);
	header('Content-Type: '.$qrCode->getContentType());
	$qrCode->writeString();
	$qrCode->writeFile(__DIR__.'/../resource/qrcode/'.$_GET["invt_id"].'.png');
	$fullPath = $url.'/resource/qrcode/'.$_GET["invt_id"].'.png';
}
header('Content-Type: application/json;charset=utf-8');
echo $fullPath;
?>