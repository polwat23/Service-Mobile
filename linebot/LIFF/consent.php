<?php
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
<title>นโยบายความเป็นส่วนตัว   <?=$config["LINEBOT_NAME"]?></title>
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

</head>

<body class="body">
	<div id="loader">
	  <div id="shadow"></div>
	  <div id="box"></div> 
	</div>
	<div id="warning"> 
	    กรุณาใช้งานผ่านแอปพลิเคชัน Line Version มือถือเท่านั้น
	</div>
<div class="limiter" id="limiter"> 
	<div class="container">
	<div class="box-consent">
		<div class="row" >
			<div class="center" style="color:#0d6efd; text-align:center; font-weight:bold; margin-top:20px;">
				นโยบายความเป็นส่วนตัว
			</div>
		
			<div class="content-text">
				ข้าพเจ้าสมาชิกสหกรณ์ฯ (เจ้าของข้อมูล)ยินยอมให้สหกรณ์ เก็บ รวบรวม ใช้เปิดเผยข้อมูลของข้าพเจ้า ดังต่อไปนี้
			</div>
			<div class="apcept-content">
				  <label class="form-control accept-text content-text">
						 1. เลขทะเบียนสมาชิก ชื่อ นามสกุล วัน เดือน ปีเกิด อายุ สถานภาพสมรส อายุการเป็นสมาชิก สังกัดหน่วยงาน
				   </label>
				   <label class="form-control accept-text content-text">
						 2. หมายเลขประจำตัวประชาชน หมายเลขประจำตัวพนักงาน หมายเลขหนังสือเดินทาง หมายเลขใบขับขี่ภาพถ่าย
				   </label> 
				   <label class="form-control accept-text content-text">
						 3. ที่อยู่ตามทะเบียนบ้าน และ/หรือที่อยู่ปัจจุบัน หมายเลขโทรศัพท์ E-Mail 
				   </label>
				   <label class="form-control accept-text content-text">
						4. คำขอสมัครสมาชิก หนังสือเปิดบัญชีสหกรณ์ฯ หนังสือยินยอมให้บริษัทหักเงินส่งให้สหกรณ์ฯ หนังสือแต่งตั้งผู้รับผลประโยชน์ หนังสือคำร้องต่าง ๆ เป็นต้น
				   </label>
				   <label class="form-control accept-text content-text">
						5. การถือหุ้น สถานะทางการเงิน การกู้ยืมเงินจากสหกรณ์ฯ การจ่ายชำระเงินกู้ของสมาชิกเงินได้รายเดือน รายละเอียดบัญชี เงินฝากสหกรณ์/ธนาคาร สวัสดิการที่สมาชิกพึงได้รับจากสหกรณ์ฯ
				   </label>  
				   <label class="form-control accept-text content-text">
						6. ผลการตรวจสอบของสหกรณ์ฯ รายละเอียดเกี่ยวกับสมาชิกในเรื่องเครดิต ความน่าเชื่อถือ หรือประวัติทางการเงิน
				   </label>
				    <label class="form-control accept-text content-text">
						7. ข้อมูลสมาชิกเกี่ยวกับการเข้าใช้เว็ปแอพพลิเคชั่น เว็บไซต์ หรือเทคโนโลยีอื่น ๆ ของสหกรณ์ฯ
				   </label>
				   
				   <label class="accept-text content-text" style="text-align:justify; margin-top:20px;" >
					ข้อมูลอื่น ๆ ที่สมาชิกได้ให้ไว้กับสหกรณ์ฯ และข้าพเจ้ายินยอมให้สหกรณ์ฯ เปิดเผยข้อมูลส่วนบุคคล หากมีเหตุอันชอบด้วยกฎหมายให้ต้องเปิดเผยแก่ บุคคลภายนอก ภายใต้พระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562
                                                 ( กฏหมายคุ้มครองข้อมูลส่วนบุคคล ) และกฎหมายที่เกี่ยวข้อง ให้แก่บุคคลภายนอก ดังต่อไปนี้
				   </label>
				    <label class="form-control accept-text content-text">
						1. หน่วยงานราชการ หรือบุคคล หรือนิติบุคคลที่มีอำนาจหน้าที่ตามกฎหมายในการกำกับและดูแลสหกรณ์ฯเช่น กรมส่งเสริม สหกรณ์ กรมตรวจบัญชีสหกรณ์ ผู้สอบบัญชีสหกรณ์ ผู้ตรวจสอบกิจการ
สำนักงานป้องกันและปราบปรามการฟอกเงิน และ/หรือหน่วยงาน ของรัฐที่เกี่ยวข้อง
				   </label>
				   <label class="form-control accept-text content-text">
						2. ชุมนุมสหกรณ์ออมทรัพย์ที่สหกรณ์ฯเป็นสมาชิก
				   </label> 
				   <label class="form-control accept-text content-text">
						3. หน่วยงานต้นสังกัดของสมาชิกรวมถึงหน่วยงานที่ทำหน้าที่ในการหักเงินได้ทุกประเภทของสมาชิกเพื่อชำระค่าหุ้น หนี้ หรือ รายการอื่น ๆ นำส่งสหกรณ์ฯ
				   </label>
				   <label class="form-control accept-text content-text">
						4. ธนาคารพาณิชย์ที่สหกรณ์ฯ ทำธุรกรรมหรือคู่ค้า ในการให้บริการด้านต่าง ๆ แก่สมาชิก
				   </label>
				   <label class="form-control accept-text content-text">
						5. บริษัท ข้อมูลเครดิตแห่งชาติ จำกัด (เครดิตบูโร) 
				   </label> 
				   <label class="form-control accept-text content-text">
						6. สำนักงานที่ดินที่เกี่ยวข้องกับหลักประกันเงินกู้ของสมาชิก
				   </label>
				   <label class="form-control accept-text content-text">
						7. สำนักงานกฎหมายที่ได้รับมอบอำนาจจากสหกรณ์ฯเพื่อทำหน้าที่ในการฟ้องร้องดำเนินคดี
				   </label>
				   <label class="form-control accept-text content-text">
						8. หน่วนงานอื่นๆ ที่สมาชิกร้องขอให้สหกรณ์ฯ ออกหนังสือรับรองเพื่อใช้ในการทำธุรกรรม
				   </label>
				   
				 
				   
				   
				<!--
				<div style="margin-top:30px">
					<div class="input-group mb-3">
					  <div class="input-group-text">
						<input class="form-check-input mt-0" type="checkbox" value="" id = "ag1">
					  </div>
					  <label class="form-control accept-text" for = "ag1" >
						 1. เลขทะเบียนสมาชิก ชื่อ นามสกุล วัน เดือน ปีเกิด อายุ สถานภาพสมรส อายุการเป็นสมาชิก สังกัดหน่วยงา
					  </label>
					</div>
				</div>
				<!--
				<div style="margin-top:10px">
					<div class="input-group mb-3">
					  <div class="input-group-text">
						<input class="form-check-input mt-0" type="checkbox" value="" id = "ag2">
					  </div>
					  <label class="form-control accept-text" for = "ag2" >
						  ข้าพเจ้า ยินยอมให้สหกรณ์เก็บข้อมูล ติดต่อ และข้อมูลที่อยู่อาศัยปัจจุบัน ของข้าพเจ้า
					  </label>
					</div>
				</div>
				<div style="margin-top:10px">
					<div class="input-group mb-3">
					  <div class="input-group-text">
						<input class="form-check-input mt-0" type="checkbox" value="" id = "ag3">
					  </div>
					  <label class="form-control accept-text" for = "ag3" >
						ข้าพเจ้า ข้าพเจ้ายินยอมให้สหกรณ์เก็บข้อมูล บัญชีเงินฝาก เงินกู้ และหุ้น  และเปิดเผยข้อมกับเจ้าหน้าของสหกรณ์
					  </label>
					</div>
				</div> -->
				
			</div>
			<div style="margin-top:10px;">
				<div class = "accept-all">
					<input class="form-check-input mt-0" type="checkbox" value="" id = "agall">
					<label for = "agall" class="accept-all-text"> ข้าพเจ้าความยินยอม(เจ้าของข้อมูล)</label>
				</div>
			</div>
			<div class="spac right" style="margin-top:10px;">
				<button type="button" class="btn btn-lg" id="btn-next" disabled  style="">ถัดไป</button>
			</div>
			
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
	$("#btn-next").click(function(){
		console.log("clicked")
		if($("#btn-next").prop('disabled') === false){
			window.location.href = "https://liff.line.me/<?=$config["LIFF_ID"]?>"
		}
	})
	
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