<?php
$json = file_get_contents('../../../json/config.json');
$json_data = json_decode($json,true);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>สมัครใช้บริการ <?=$json_data["LINEBOT_NAME"]?></title>
<link rel="shortcut icon" href="https://cdn.icoopsiam.com/<?=$json_data["COOP_ID"]?>/icons/logo.png" type="image/x-icon" />
<link rel="icon" href="https://cdn.icoopsiam.com/<?=$json_data["COOP_ID"]?>/icons/logo.png" type="image/x-icon" />
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" />
<link href="https://fonts.googleapis.com/css?family=Sriracha" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="css/util.css">
<link rel="stylesheet" type="text/css" href="css/main.css">
<link rel="stylesheet" type="text/css" href="css/toast.css">
<link rel="stylesheet" type="text/css" href="css/loading.css">
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.33.1/sweetalert2.min.css">

</head>

<body>
	<div id="toast"><div id="desc"></div></div>
	<div id="loader">
	  <div id="shadow"></div>
	  <div id="box"></div>
	</div>
	<div class="limiter" id="limiter">
		<div class="container-login100" >
			<div class="wrap-login100 p-b-30">
				<div class="login100-form validate-form">
					<div class="login100-form-avatar">
						<img src="https://cdn.icoopsiam.com/<?=$json_data["COOP_ID"]?>/icons/logo.png" id="avatar" alt="logo">
					</div>
					<span class="login100-form-title p-t-20">
						สมัครใช้บริการ <?=$json_data["LINEBOT_NAME"]?>
					</span>
					<span class="login100-form-title p-t-20 p-b-45" >
						สวัสดี <span id="nameline"></span>
					</span>

					<div class="wrap-input100 validate-input m-b-10">
						<input class="input100" type="text" name="member_no" maxlength="8" id="member_no" placeholder="เลขสมาชิก" autocomplete="off">
						<span class="focus-input100"></span>
						<span class="symbol-input100">
							<i class="fa fa-user"></i>
						</span>
					</div>

					<div class="wrap-input100 validate-input m-b-10">
						<input class="input100" type="text" name="idcard" maxlength="13" id="idcard" placeholder="เลขบัตรประชาชน" autocomplete="off">
						<span class="focus-input100"></span>
						<span class="symbol-input100">
							<i class="fa fa-lock"></i>
						</span>
					</div>
					<input type="hidden" id="lineid" name="lineid">
					<input type="hidden" value="<?=$_GET["namecoop"]?>" id="namecoop" name="namecoop">
					<div class="container-login100-form-btn p-t-10">
						<button class="login100-form-btn" id="submit">
							สมัครใช้บริการ
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.33.1/sweetalert2.min.js"></script>
<script src="https://d.line-scdn.net/liff/1.0/sdk.js"></script>
<script>
$(document).ready(function(){
	liff.init(
	  data => {
		liff.getProfile()
		.then(profile => {
		  const avatar = profile.pictureUrl
		  const name = profile.displayName
		  const lineid = profile.userId
		  $('#nameline').html(name)
		  $('#avatar').attr('src',avatar)
		  $('#lineid').val(lineid)
		})
		.catch((err) => {
		  console.log('error', err);
		});
	  },
	  err => {
		console.log(err)
	  }
);
});
window.addEventListener("keydown", function (event) {
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
}
</script>
</body>

</html>