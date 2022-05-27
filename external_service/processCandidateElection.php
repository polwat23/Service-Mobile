<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');
require_once(__DIR__.'/../extension/vendor/autoload.php');

use Utility\Library;
use Component\functions;
use PHPMailer\PHPMailer\{PHPMailer,Exception};
use Dompdf\Dompdf;

$lib = new library();
$func = new functions();
$mailFunction = new PHPMailer(false);

$arrGrpCan = array();
$getGroup = $conmysql->prepare("SELECT group_code,group_desc FROM gcgroupcandidate WHERE year_election = '2564' and group_number <> '999'");
$getGroup->execute();
while($rowGrp = $getGroup->fetch(PDO::FETCH_ASSOC)){
	$arrGrp = array();
	$arrGrp["GROUP_DESC"] = $rowGrp["group_desc"];
	$getPerson = $conmysql->prepare("SELECT gcc.id_cdperson,gcc.candidate_name,gcc.number_candidate
																	FROM gccandidate gcc 
																	WHERE gcc.year_election = '2564' and gcc.group_code = :group_code");
	$getPerson->execute([':group_code' => $rowGrp["group_code"]]);
	while($rowPerson = $getPerson->fetch(PDO::FETCH_ASSOC)){
		$arrScore = array();
		if($rowPerson["number_candidate"] != '999'){
			$getScore = $conmysql->prepare("SELECT COUNT(member_no) as SCORE_CANDIDATE FROM gcelection WHERE id_cdperson = :id_cdperson");
			$getScore->execute([':id_cdperson' => $rowPerson["id_cdperson"]]);
			$rowScore = $getScore->fetch(PDO::FETCH_ASSOC);
			$arrScore["CANDIDATE_NAME"] = $rowPerson["candidate_name"];
			$arrScore["CANDIDATE_NUMBER"] = $rowPerson["number_candidate"];
			$arrScore["SCORE"] = $rowScore["SCORE_CANDIDATE"];
		}
		$arrGrp["PERSON"][] = $arrScore;
	}
	$arrGrpCan[] = $arrGrp;
}
$getScoreNot = $conmysql->prepare("SELECT COUNT(member_no) as SCORE_CANDIDATE FROM gcelection WHERE id_cdperson IS NULL");
$getScoreNot->execute();
$rowScoreNot = $getScoreNot->fetch(PDO::FETCH_ASSOC);
$header["score_notchoose"] = $rowScoreNot["SCORE_CANDIDATE"];
$header["elec_year"] = '2565';

$textRan = $lib->randomText('all',10);
$passwordFirst = substr($textRan,0,5);
$passwordSecon = substr($textRan,5);
$password = '1234';//$passwordFirst.$passwordSecon;
$arrayPDF = GenerateReport($arrGrpCan,$header,$password,$lib);
$arrayAttach = array();
if($arrayPDF["RESULT"]){
	$arrayAttach[] = $arrayPDF["PATH"];
}
/*
$arrayDataTemplate = array();
$arrayDataTemplate["TEXT"] = "ชุดแรก ของชื่อไฟล์ : ".$arrayPDF["FILE_NAME"];
$arrayDataTemplate["PASSWORD"] = $passwordFirst;
$template = $func->getTemplateSystem('PasswordElectionFile');
$arrResponse = $lib->mergeTemplate($template["SUBJECT"],$template["BODY"],$arrayDataTemplate);
$arrMailStatus = $lib->sendMail('wanna.sri@mahidol.ac.th',$arrResponse["SUBJECT"],$arrResponse["BODY"],$mailFunction,[]);
$mailFunction = new PHPMailer(false);
$arrayDataTemplate2 = array();
$arrayDataTemplate2["TEXT"] = "ชุดสอง ของชื่อไฟล์ : ".$arrayPDF["FILE_NAME"];
$arrayDataTemplate2["PASSWORD"] = $passwordSecon;
$arrResponse2 = $lib->mergeTemplate($template["SUBJECT"],$template["BODY"],$arrayDataTemplate2);
$arrMailStatus2 = $lib->sendMail('yoot_ne@yahoo.com',$arrResponse2["SUBJECT"],$arrResponse2["BODY"],$mailFunction,[]);
$mailFunction = new PHPMailer(false);
$arrResponse3["SUBJECT"] = "เอกสารสรุปคะแนนการสรรหาออนไลน์";
$arrResponse3["BODY"] = "เอกสารสรุปคะแนนการสรรหาออนไลน์อยู่ในไฟล์แนบ";
$arrMailStatus23 = $lib->sendMail('it.support@musaving.com',$arrResponse3["SUBJECT"],$arrResponse3["BODY"],$mailFunction,$arrayAttach);*/


function GenerateReport($dataReport,$header,$password,$lib){
	
	$html = '
		<style>
		@font-face {
		  font-family: TH Niramit AS;
		  src: url(../resource/fonts/TH Niramit AS.ttf);
		}
		@font-face {
		  font-family: TH Niramit AS;
		  src: url(../resource/fonts/TH Niramit AS Bold.ttf);
		  font-weight: bold;
		}
		* {
		  font-family: TH Niramit AS;
		}
		body {
		  font-size:14pt;
		  line-height: 25px;
		}
		.text-center{
		  text-align:center
		}
		.text-right{
		  text-align:right
		}
		.nowrap{
		  white-space: nowrap;
		}
		.wrapper-page {
		  page-break-after: always;
		}
		.wrapper-page:last-child {
		  page-break-after: avoid;
		}
		table{
		  border-collapse: collapse;
		  line-height: 20px
		}
		th,td{

		}
		th{
		  text-align:center;
		  background-color:#ddd9c4;
		  padding-top:10px;
		  padding-bottom:10px;
		  font-size:18pt;
		  border-top:4px double;
		  border-left:1px solid;
		  border-right:1px solid;
		  border-bottom:3px solid;
		  line-height:30px;
		}
		td{
		  padding-left:3px;
		  font-size:18pt;
		  line-height:25px;
		  color:blue;
		}
		.border{
		  border:1px solid #000000;
		}
		.border-left{
		  border-left:1px solid #000000;
		}
		.border-right{
		  border-right:1px solid #000000;
		}
		.border-top{
		  border-top:1px solid #000000;
		}
		.border-bottom{
		  border-bottom:1px solid #000000;
		}
		</style>
		';
		//ระยะขอบ
		$html .= '<div style=" margin: 0px;">';

		$html .='
		  <div>
			<div style="display:flex; height:80px;">
			  <div style="text-align:left;"><img src="../resource/logo/logo.jpg" style="margin: 5px 0 0 5px" alt="" width="70" height="70" /></div>
			  <div class="text-center"style="font-weight:bold; font-size:18pt;">
				 <div>สหกรณ์ออมทรัพย์มหาวิทยาลัยมหิดล จำกัด</div>
				 <div>สรุปผลการลงคะแนนสรรหากรรมการแทนกรรมการที่หมดวาระ ประจำปี 2565</div>
				 <div>ระบบสรรหาออนไลน์ (E-Vote)</div>
			  </div>
			</div><
		  </div>
		  <div style="margin-top:20px;">
			  <table style="width:100%">
				 <tr>
				  <th style="width:70px;">หมายเลข<br>ผู้สมัคร</th>
				  <th>ชื่อ - สกุล</th>
				  <th style="width:100px;">คะแนน</th>
				 </tr>';
		 foreach($dataReport as $groupData){
			$html .='
				<tr>
					<td colspan="2" class="border-left border-right" style="color:red; padding:5px"><u>'.($groupData["GROUP_DESC"]??null).'</u></td>
					<td class="border-right "></td>
				</tr>
			';
			$i = 0;
			foreach($groupData["PERSON"] as $data){

			if($i == 0){
			  $html.=' 
			  <tr>
				<td class="text-center border-left border-bottom border-right">'.($data["CANDIDATE_NUMBER"]??null).'</td>
				<td class="border-left border-bottom border-right">'.($data["CANDIDATE_NAME"]??null).'</td>
				<td class="text-center border-left border-bottom border-right" style="padding-right:5px;">'.($data["SCORE"]??null).'</td>
			  </tr>';
			}else{
			  $html.=' 
			  <tr>
				<td class="text-center border">'.($data["CANDIDATE_NUMBER"]??null).'</td>
				<td class="border">'.($data["CANDIDATE_NAME"]??null).'</td>
				<td class="text-center border" style="padding-right:5px;">'.($data["SCORE"]??null).'</td>
			  </tr>';
			}
			

			}
			$i++;
		 }
		$html.='
				  <tr>
					  <td style="border-top:3px solid #000000; padding-top:30px;"></td>
					  <td style="border-top:3px solid #000000; padding-top:30px;">ไม่ประสงค์เลือกผู้ใด</td>
					  <td style="border-top:3px solid #000000; padding-top:30px;" class="text-center">'.($header["score_notchoose"]??0).'</td>
				  </tr>
			  </table>
		  </div>
		';
		//ระยะขอบ
		$html .= '</div>';
	
	$dompdf = new Dompdf([
		'fontDir' => realpath('../resource/fonts'),
		'chroot' => realpath('/'),
		'isRemoteEnabled' => true
	]);
	$dompdf->set_paper('A4', 'landscape');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__;
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$name = $header["elec_year"].date('YmdHi');
	$pathfile = $pathfile.'/'.$name.'.pdf';
	$arrayPDF = array();
	$pathOutput = __DIR__."/".$name.".pdf";
	$dompdf->getCanvas()->get_cpdf()->setEncryption($password);
	$output = $dompdf->output();
	if(file_put_contents($pathfile, $output)){
		$arrayPDF["RESULT"] = TRUE;
	}else{
		$arrayPDF["RESULT"] = FALSE;
	}
	$arrayPDF["FILE_NAME"] = $name;
	$arrayPDF["PATH"] = $pathOutput;
	return $arrayPDF; 
}
?>