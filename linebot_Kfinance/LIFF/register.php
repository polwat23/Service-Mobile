<?php
header("Cache-Control: no-cache");
header("Cache-Control: no-store");
header("Cache-Control: max-age=0");
$json = file_get_contents(__DIR__.'/../../config/config_linebotKfinance.json');
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
<link rel="stylesheet" type="text/css" href="css/util.css?cc=<?=time()?>">
<link rel="stylesheet" type="text/css" href="css/main.css?cc=<?=time()?>">
<link rel="stylesheet" type="text/css" href="css/toast.css?cc=<?=time()?>">
<link rel="stylesheet" type="text/css" href="css/loading.css?cc=<?=time()?>">
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.33.1/sweetalert2.min.css">
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv="expires" content="0">
<meta http-equiv="cache-control" content="no-cache">

</head>

<body>
	<div id="toast"><div id="desc"></div></div>
	<div id="loader">
	  <div id="shadow"></div>
	  <div id="box"></div>
	</div>
	<div id="warning">
	กรุณาใช้งานผ่านแอปพลิเคชัน Line Version มือถือเท่านั้น
	</div>
	<div class="limiter" id="limiter">
		<div class="container-login100" >
			<div class="wrap-login100 p-b-30">
				<div class="login100-form validate-form">
					<div class="login100-form-avatar">
						<img src="https://cdn.thaicoop.co/coop/<?=$config["COOP_KEY"]?>.png" id="avatar" alt="logo">
					</div>
					<span class="login100-form-title p-t-20">
						สมัครใช้บริการ <?=$config["LINEBOT_NAME"]?>
					</span>
					<span class="login100-form-title p-t-20 p-b-45" >
						สวัสดี <span id="nameline"></span>
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