<?php

require(__DIR__.'/../../include/connection.php');
use Connection\connection;

$con = new connection();
$conmysql = $con->connecttomysql();
$line_token = $_GET["id"]??null;
$checkUser = $conmysql->prepare("SELECT *FROM gcmemberaccount WHERE line_token = :line_token");
$checkUser->execute([
	':line_token'=> $line_token
]);
$rowUser = $checkUser->fetch(PDO::FETCH_ASSOC);
$user = 0;

if($rowUser){
	$user = 1;
}

header("Cache-Control: no-cache");
header("Cache-Control: no-store");
header("Cache-Control: max-age=0");

$json = file_get_contents(__DIR__.'/../../config/config_linebot.json');
$config = json_decode($json,true);

										
//require(__DIR__.'./autoloadConnection.php');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>นโยบายความเป็นส่วนตัว   <?=$config["LINEBOT_NAME"]?> </title>
<link rel="shortcut icon" href="https://cdn.thaicoop.co/coop/<?=$config["COOP_KEY"]?>.png" type="image/x-icon" />
<link rel="icon" href="https://cdn.thaicoop.co/coop/<?=$config["COOP_KEY"]?>.png" type="image/x-icon" />
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" />
<link href="https://fonts.googleapis.com/css?family=Sriracha" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="css/util.css?cc=<?=time()?>">
<link rel="stylesheet" type="text/css" href="css/main.css?cc=<?=time()?>">
<link rel="stylesheet" type="text/css" href="css/toast.css?cc=<?=time()?>">
<link rel="stylesheet" type="text/css" href="css/loading.css?cc=<?=time()?>">
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.33.1/sweetalert2.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv="expires" content="0">
<meta http-equiv="cache-control" content="no-cache">


<style>
.section {
  max-height: 250px;
  padding: 1rem;
  overflow-y: auto;
  direction: ltr;
  scrollbar-color: #d4aa70 #e4e4e4;
  scrollbar-width: thin;

  h2 {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
  }

  p + p {
    margin-top: 1rem;
  }
}

.section::-webkit-scrollbar {
  width: 20px;
}

.section::-webkit-scrollbar-track {
  background-color: #e4e4e4;
  border-radius: 100px;
}

.section::-webkit-scrollbar-thumb {
  border-radius: 100px;
  background-image: linear-gradient(180deg, #d0368a 0%, #708ad4 99%);
  box-shadow: inset 2px 2px 5px 0 rgba(#fff, 0.5);
}

</style>
</head>

<body class="body">
	<div id="loader">
	  <div id="shadow"></div>
	  <div id="box"></div> 
	</div>
	<div id="warning"> 
	    กรุณาใช้งานผ่านแอปพลิเคชัน Line Version มือถือเท่านั้น
	</div>
<!-- <div class="limiter" id="limiter">  -->
<div>
	
	<?php if($rowUser){  ?>
		<div class="row" >
			<div style="display:flex; justify-content: center; align-items: center; height: 100vh;">
				<div class="center">
					<div style="font-size:20px;">ท่านได้ผูกบัญชีอยู่แล้ว</div>
					<div style="margin-top:30px; margin-bottom:30px;"><img src="https://cdn.thaicoop.co/icon/link.png" style="width:180px;"></div>
					<div><input type="text" size="8" id="counter" class="center" />  <!-- text box แสดงการนับถอยหลัง   --></div>
					<div style="margin-top:30px;"><a href ='line://ti/p/<?=$config["LINE_ID"]?>'><button type="button" class="btn ">กลับไปยังห้องแชท</button></a></div>
					<script>
					 var seconds=10;// กำหนดค่าเริ่มต้น 10 วินาที
					 document.getElementById("counter").value='10';//แสดงค่าเริ่มต้นใน 10 วินาที ใน text box

					function display(){ //function ใช้ในการ นับถอยหลัง
						seconds-=1;//ลบเวลาทีละหนึ่งวินาทีทุกครั้งที่ function ทำงาน
					 if(seconds==-1){ 
						 $("#counter").hide();
						 return window.location = 'line://ti/p/<?=$config["LINE_ID"]?>';
					  } //เมื่อหมดเวลาแล้วจะหยุดการทำงานของ function display
						document.getElementById("counter").value=seconds; //แสดงเวลาที่เหลือ
						setTimeout("display()",1000);// สั่งให้ function display() ทำงาน หลังเวลาผ่านไป 1000 milliseconds ( 1000  milliseconds = 1 วินาที )
					}
						display(); //เปิดหน้าเว็บให้ทำงาน function  display()	
					</script>
					
				</div>
			</div>
	<?php }
	else{ ?>
<div class="container">
	<div class="box-consent">
		<div class="row" >
			<iframe src="https://policy.thaicoop.co/privacy.html?coop=ryt" style="height:70vh; width:100%;" title="consent"></iframe>
		
			<div style="margin-top:10px;">
				<div class = "accept-all">
					<input class="form-check-input mt-0" type="checkbox" value="" id = "agall">
					<label for = "agall" class="accept-all-text"> ข้าพเจ้าความยินยอม(เจ้าของข้อมูล) </label>
				</div>
			</div>
			<div class="spac right" style="margin-top:10px;">
				<button type="button" class="btn btn-lg" id="btn-next" disabled  style="">ถัดไป</button>
			</div>
		</div>
	<?php }?>
	</div>
</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.33.1/sweetalert2.min.js"></script>
<script src="https://static.line-scdn.net/liff/edge/2.1/sdk.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-pprn3073KE6tl6bjs2QrFaJGz5/SUsLqktiwsUTF55Jfv3qYSDhgCecCxMW52nD2" crossorigin="anonymous"></script>
<script>
$(document).ready(function(){
	var url = new URL(location.href);
	if($("#ag1").prop("checked")===true && $("#ag2").prop("checked")===true && $("#ag3").prop("checked")===true){
			$("#btn-next").prop('disabled',false);
			$("#agall").prop('checked',true);
	}else{
		$("#btn-next").prop('disabled',true);
		$("#agall").prop('checked',false);
	}
	$("#btn-next").prop('disabled',true);
	$("#ag1").change(function(){
		if($("#ag1").prop("checked")===true && $("#ag2").prop("checked")===true && $("#ag3").prop("checked")===true){
			$("#btn-next").prop('disabled',false);
			$("#agall").prop('checked',true);
		}else{
			$("#btn-next").prop('disabled',true);
			$("#agall").prop('checked',false);
		}
	});
	$("#ag2").change(function(){
		if($("#ag1").prop("checked")===true && $("#ag2").prop("checked")===true && $("#ag3").prop("checked")===true){
			$("#btn-next").prop('disabled',false);
			$("#agall").prop('checked',true);
		}else{
			$("#btn-next").prop('disabled',true);
			$("#agall").prop('checked',false);
		}
	});
	$("#ag3").change(function(){
		if($("#ag1").prop("checked")===true && $("#ag2").prop("checked")===true && $("#ag3").prop("checked")===true){
			$("#btn-next").prop('disabled',false);
			$("#agall").prop('checked',true);
		}else{
			$("#btn-next").prop('disabled',true);
			$("#agall").prop('checked',false);
		}
	});
	$("#btn-next").click(function(event){
		event.preventDefault();
		var id = $('#user_id').val()
		var url =  "https://liff.line.me/<?=$config["LIFF_ID"]?>?=regiter=regiter"
		
	    window.location.href = url;
	});

	
	$("#agall").change(function(){
		$("#ag1").prop('checked', $(this).prop("checked"));
		$("#ag2").prop('checked', $(this).prop("checked"));
		$("#ag3").prop('checked', $(this).prop("checked"));
		$("#btn-next").prop('disabled', !$(this).prop("checked"));
	});
	$('#loader').css('display','block')
	liff.init({
		liffId: "<?=$config["LIFF_ID"]?>"
	}).then(() => {
		if(url.searchParams.get("register") == 'success'){
			liff.closeWindow();
		}else{
			liff.getProfile()
			.then(profile => {
				
				console.log("profile",profile);
				let dataProfile = {
					"type" : "linebotregister",
					"line_id" : profile.userId
				}
				$('#user_id').val(profile.userId);
				
				$('#nameline').text(profile.displayName)
				$('#avatar').attr('src',profile.pictureUrl)
				$('#deeplink').attr('href',"<?=$config["LINK_BIND"]?>" + encodeURI(JSON.stringify(dataProfile)) ) ,
				$('#showwhenload').css('display','block')
				$('#limiter').css('display','block')
				$('#loader').css('display','none')
			})
			.catch((err) => {
			  $('#warning').css('display','block')
			});
		}
	}).catch((err) => {
		console.log('errorInit',err)
	});
});


</script>
</body>

</html>