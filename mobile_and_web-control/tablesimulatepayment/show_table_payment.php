<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="description" content="Free Web tutorials">
<meta name="keywords" content="HTML,CSS,XML,JavaScript">
<meta name="author" content="John Doe">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css?family=Prompt:400&display=swap" rel="stylesheet" />
<title>ตารางประมาณการชำระ</title>
<style>
* {
	font-family: 'Prompt', sans-serif;
}
body {
	background-color: #0095DA;
	margin: 2px;
}
table {
	border-spacing: unset;
	width: 100%;
}
table thead {
	background-color: #FFFFFF;
	box-shadow: 0 4px 15px 0px #00000021;
	border-radius: 5px;
}
table thead th {
	padding: 15px 20px;
	text-align: center;
}

.body-card-table tr {
	background-color: white;
	text-align: center;
}
.body-card-table td {
	padding: 15px 20px;
}
</style>
</head>

<body>
<table>
<thead>
	<tr>
		<th colspan="1"  style="font-weight: bold;border-right: solid 1px grey;background-color: #2fa1dc;color: white;">งวด</th>
		<?php
		if($Formula == "2"){
		?>
			<th colspan="1"  style="font-weight: bold;background-color: #2fa1dc;color: white;">ชำระต่องวด (ส่วนลด)</th>
		<?php
		}else{
		?>
			<th colspan="1"  style="font-weight: bold;background-color: #2fa1dc;color: white;">ชำระต่องวด</th>
		<?php
		}
		?>
		<?php
		if($Formula == "2"){
		?>
			<th colspan="1"  style="font-weight: bold;background-color: #2fa1dc;color: white;">ชำระต่องวด (ปกติ)</th>
		<?php
		}
		?>
	</tr>
</thead>
<tbody class="body-card-table">
	<?php 
	for ($i = 0; $i < (count($L_Unit)); $i++)
	{
	?>
		<tr>
			<td colspan="1" style="font-weight: bold;border-right: solid 1px grey;background-color: #2fa1dc11;"><?php echo ($L_Unit[$i]) ?></td>
			<td colspan="1" style="font-weight: bold;background-color: #2fa1dc11;"><?php echo number_format($L_PerPayL[$i], 2);  ?></td>
			<?php
			if($Formula == "2"){
			?>
			<td colspan="1" style="font-weight: bold;background-color: #2fa1dc11;"><?php echo number_format($L_PerPayLNormal[$i], 2);  ?></td>
			<?php
			}
			?>
		</tr>
	<?php } ?>
	<tr style="background-color: #606060;">
		<td colspan="7" style="font-weight: bold;">
		
		</td>
	</tr>
</tbody>
<tbody class="body-card-table">

</tbody>
</table>
<div style="margin-bottom: 10px;" >
</div>
</body>

</html>