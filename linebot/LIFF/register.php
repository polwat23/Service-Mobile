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
$userStatus = 0;

if($rowUser){
	$userStatus = 1;
}

header("Cache-Control: no-cache");
header("Cache-Control: no-store");
header("Cache-Control: max-age=0");
$json = file_get_contents(__DIR__.'/../../config/config_linebot.json');
$config = json_decode($json,true);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>สมัครใช้บริการ <?=$config["LINEBOT_NAME"]?></title>
<link rel="shortcut icon" href="https://cdn.thaicoop.co/coop/<?=$config["COOP_KEY"]?>.png" type="image/x-icon" />
<link rel="icon" href="https://cdn.thaicoop.co/coop/<?=$config["COOP_KEY"]?>.png" type="image/x-icon" />
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" />
<link href="https://fonts.googleapis.com/css?family=Sriracha" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
<link rel="stylesheet" type="text/css" href="css/util.css?cc=<?=time()?>">
<link rel="stylesheet" type="text/css" href="css/main.css?cc=<?=time()?>">
<link rel="stylesheet" type="text/css" href="css/toast.css?cc=<?=time()?>">
<link rel="stylesheet" type="text/css" href="css/loading.css?cc=<?=time()?>">
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.33.1/sweetalert2.min.css">
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv="expires" content="0">
<meta http-equiv="cache-control" content="no-cache">

</head>

<body class="body">
	<div id="toast"><div id="desc"></div></div>
	<div id="loader">
	  <div id="shadow"></div>
	  <div id="box"></div>
	</div>
	<div id="warning">
	กรุณาใช้งานผ่านแอปพลิเคชัน Line Version มือถือเท่านั้น 
	</div>
	<?php if($userStatus == 0){ ?>
	<div class="row">
		<div class="limiter" id="limiter">
			<div class="container-login100" >
				<div class="wrap-login100 p-b-30">
					<div class="login100-form validate-form">
						<div class="login100-form-avatar">
							<img src="https://cdn.thaicoop.co/coop/<?=$config["COOP_KEY"]?>.jpg" id="avatar" alt="logo">
						</div>
						<span class="login100-form-title p-t-20">
							สมัครใช้บริการ <?=$config["LINEBOT_NAME"]?>
						</span>
						<span class="login100-form-title p-t-20 p-b-45" >
							สวัสดี <span id="nameline"> </span>  
						</span>
						<div class="container-login100-form-btn p-t-10" id="showwhenload" style="display:none;">
						<a href="#" class="login100-form-btn" id="deeplink">
							ผูกบัญชี 
						</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php }
	else{ ?>
			<div class="row">
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
			</div>
	<?php } ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.33.1/sweetalert2.min.js"></script>
<script src="https://static.line-scdn.net/liff/edge/2.1/sdk.js"></script>
<script>
$(document).ready(function(){
	var url = new URL(location.href);
	$('#loader').css('display','block')
	liff.init({
		liffId: "<?=$config["LIFF_ID"]?>"
	}).then(() => {
		if(url.searchParams.get("register") == 'success'){
			liff.closeWindow();
		}else{
			liff.getProfile()
			.then(profile => {
				let dataProfile = {
					"type" : "linebotregister",
					"line_id" : profile.userId
				}
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
/*window.addEventListener("keydown", function (event) {
	if(event.keyCode == 13){
		$('#submit').click();
	}
})
	$('#submit').click(function(){
		var member_no = $('#member_no').val();
		var idcard = $('#idcard').val();
		var lineid = $('#lineid').val();
		var namecoop = $('#namecoop').val();
		if(member_no == "" || idcard == ""){
			$('#desc').html("กรุณากรอกข้อมูลให้ครบถ้วน")
			launch_toast()
		}else if(lineid == "" || namecoop == ""){
			$('#desc').html("กรุณาเข้า Link นี้จาก Line")
			launch_toast()
		}else if(member_no != "" && idcard != "" && lineid != "" && namecoop != ""){
			$("#loader").addClass("show_load");
			$("#limiter").addClass("loading");
			$.ajax({
				url: 'https://mobilecore.gensoft.co.th/'+namecoop+'/Connect/register.php',
				headers: {
					'Authorization':'Basic aXNvY2FyZS5zeXN0ZW06aXNvY2FyZUAxODg4',
					'NONENCRYPTION':'gensoft@dev501888',
					'Content-Type':'application/json'
				},
				method: 'POST',
				dataType: 'json',
				data: JSON.stringify({
					member_no: member_no,
					idcard: idcard,
					lineid: lineid
				}),
				success: function(data){
					$("#loader").removeClass("show_load");
					$("#limiter").removeClass("loading");
					  if(!data.RESULT){
							$('#desc').html(data.RESPONSE)
							launch_toast()
					  }else {
						Swal.fire({
							title: 'สมัครใช้บริการเรียบร้อย',
							text: 'เมื่อคุณกดตกลงหน้าเว็บจะปิดเองอัตโนมัติ',
							type: 'success',
							onClose: () => {
								liff.closeWindow();
							}
						})
					  }
				}
			 });
		}
	})
function launch_toast() {
    var x = document.getElementById("toast")
    x.className = "show";
    setTimeout(function(){ x.className = x.className.replace("show", ""); }, 5000);
}*/
</script>
</body>

</html>