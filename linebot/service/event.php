<?php
$limit = $lineLib->getLineConstant('limit_event');
$arrayGroupNews = array();
$themeColor = $lineLib->getLineConstant('theme_color');
$fetchEvent = $conmysql->prepare("SELECT id_task,task_topic,task_detail,start_date,end_date,
										date_format(event_start_time,'%H:%i') as event_start_time,
										date_format(event_end_time,'%H:%i') as event_end_time,
										is_settime,create_date,update_date,is_notify,is_notify_before,create_by,event_html
										FROM gctaskevent
										WHERE (start_date >= CURDATE() or end_date >= CURDATE()) AND is_use = '1'
										ORDER BY start_date LIMIT ".$limit);
$fetchEvent->execute();
while($rowEvent = $fetchEvent->fetch(PDO::FETCH_ASSOC)){
	$start_date = $lib->convertdate($rowEvent["start_date"],'D m ');
	$end_date = $lib->convertdate($rowEvent["end_date"],'D m');
	$arrayEvent = array();
	$arrayEvent["ID_TASK"] = $lib->text_limit($rowEvent["id_task"]);
	$arrayEvent["TASK_TOPIC"] = $lib->text_limit($rowEvent["task_topic"]);
	$arrayEvent["TASK_DETAIL"] = $lib->text_limit($rowEvent["task_detail"],100);
	$arrayEvent["START_DATE"] = $lib->convertdate($rowEvent["start_date"],'D m Y');
	$arrayEvent["START_DATE_RAW"] = $lib->convertdate($rowEvent["start_date"],'D-n-y');
	$arrayEvent["END_DATE"] = $lib->convertdate($rowEvent["end_date"],'D m Y');
	$arrayEvent["END_DATE_RAW"] = $lib->convertdate($rowEvent["end_date"],'D-n-y');
	$arrayEvent["START_TIME"] = $rowEvent["event_start_time"];
	$arrayEvent["END_TIME"] = $rowEvent["event_end_time"];
	$arrayEvent["IS_SETTIME"] = $rowEvent["is_settime"];
	$arrayEvent["CREATE_DATE"] = $lib->convertdate($rowEvent["create_date"],'D m Y',true);
	$arrayEvent["UPDATE_DATE"] = $lib->convertdate($rowEvent["update_date"],'D m Y',true);
	$arrayEvent["IS_NOTIFY"] = $rowEvent["is_notify"];
	$arrayEvent["IS_NOTIFY_BEFORE"] = $rowEvent["is_notify_before"];
	$arrayEvent["CREATE_BY"] = $rowEvent["create_by"];
	$arrayEvent["EVENT_HTML"] = $rowEvent["event_html"];
	$arrayEvent["START"] = $rowEvent["start_date"];
	$arrayEvent["TIME_LINE"] = $start_date == $end_date?$start_date:($start_date.'-'.$end_date);
	$arrayGroupNews[] = $arrayEvent;
}	
$date_now = date("d-m-Y");
if(sizeof($arrayGroupNews) >0 ){	
	$eventData = array();
	$eventData["type"] = "flex";
	$eventData["altText"] = "กิจกรรมสหกรณ์".$date_now;
	$eventData["contents"]["type"] = "bubble";
	$eventData["contents"]["size"] = "mega";
	$eventData["contents"]["header"]["type"] = "box";
	$eventData["contents"]["header"]["layout"] = "vertical";
	$eventData["contents"]["header"]["contents"][0]["type"] = "box";
	$eventData["contents"]["header"]["contents"][0]["layout"] = "vertical";
	$eventData["contents"]["header"]["contents"][0]["contents"][0]["type"] = "text";
	$eventData["contents"]["header"]["contents"][0]["contents"][0]["text"] = "Event";
	$eventData["contents"]["header"]["contents"][0]["contents"][0]["color"] = "#ffffff66";
	$eventData["contents"]["header"]["contents"][0]["contents"][0]["size"] = "sm";
	$eventData["contents"]["header"]["contents"][0]["contents"][1]["type"] = "text";
	$eventData["contents"]["header"]["contents"][0]["contents"][1]["text"] = "กิจกรมสหกรณ์";
	$eventData["contents"]["header"]["contents"][0]["contents"][1]["color"] = "#ffffff";
	$eventData["contents"]["header"]["contents"][0]["contents"][1]["size"] = "xl";
	$eventData["contents"]["header"]["contents"][0]["contents"][1]["flex"] = 4;
	$eventData["contents"]["header"]["contents"][0]["contents"][1]["weight"] = "bold";
	$eventData["contents"]["header"]["backgroundColor"] = $themeColor;
	$eventData["contents"]["header"]["spacing"] = "md";
	$eventData["contents"]["body"]["type"] = "box";
	$eventData["contents"]["body"]["layout"] = "vertical";
	$eventData["contents"]["body"]["contents"][0]["type"] = "text";
	$eventData["contents"]["body"]["contents"][0]["text"] = 'ทั้งหมด'.sizeof($arrayGroupNews).' กิจกรรม';
	$eventData["contents"]["body"]["contents"][0]["color"] = "#b7b7b7";
	$eventData["contents"]["body"]["contents"][0]["size"] = "xxs";
	$indexEvent = 1;
	foreach($arrayGroupNews as $rowEvent){
		$eventData["contents"]["body"]["contents"][$indexEvent]["type"] = "box";
		$eventData["contents"]["body"]["contents"][$indexEvent]["layout"] = "vertical";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["type"] = "box";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["layout"] = "horizontal";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][0]["type"] = "box";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][0]["layout"] = "horizontal";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][0]["contents"][0]["type"] = "text";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][0]["contents"][0]["text"] = ($rowEvent["TIME_LINE"]??'-');
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][0]["contents"][0]["color"] = ($date_now==$rowEvent["START_DATE_RAW"])?'#FF0000':'#6486E3';;
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][0]["contents"][0]["gravity"] = "center";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][0]["contents"][0]["size"] = "xxs";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][0]["contents"][0]["position"] = "relative";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][0]["contents"][0]["wrap"] = true;
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][0]["flex"] = 2;
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][1]["type"] = "box";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][1]["layout"] = "vertical";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][1]["contents"][0]["type"] = "filler";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][1]["contents"][1]["type"] = "box";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][1]["contents"][1]["layout"] = "vertical";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][1]["contents"][1]["cornerRadius"] = "30px";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][1]["contents"][1]["width"] = "12px";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][1]["contents"][1]["height"] = "12px";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][1]["contents"][1]["borderWidth"] = "2px";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][1]["contents"][1]["borderColor"] = ($date_now==$rowEvent["START_DATE_RAW"])?'#FF0000':'#6486E3';
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][1]["contents"][1]["backgroundColor"] = ($date_now==$rowEvent["START_DATE_RAW"])?'#F8877F':'#6486E3';
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][1]["contents"][1]["contents"][0]["type"] = "filler";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][1]["contents"][2]["type"] = "filler";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][1]["flex"] = 0;
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][2]["type"] = "text";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][2]["text"] = ($rowEvent["TASK_TOPIC"]??'-');
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][2]["color"] = ($date_now==$rowEvent["START_DATE_RAW"])?'#FF0000':'#6486E3';
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][2]["gravity"] = "center";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][2]["flex"] = 4;
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][2]["size"] = "xxs";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["contents"][2]["weight"] = "bold";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][0]["spacing"] = "lg";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["type"] = "box";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["layout"] = "horizontal";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][0]["type"] = "box";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][0]["layout"] = "baseline";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][0]["contents"][0]["type"] = "filler";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][1]["type"] = "box";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][1]["layout"] = "vertical";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][1]["contents"][0]["type"] = "box";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][1]["contents"][0]["layout"] = "horizontal";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][1]["contents"][0]["flex"] = 1;
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][1]["contents"][0]["contents"][0]["type"] = "filler";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][1]["contents"][0]["contents"][0]["type"] = "box";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][1]["contents"][0]["contents"][0]["layout"] = "vertical";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][1]["contents"][0]["contents"][0]["width"] = "2px";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][1]["contents"][0]["contents"][0]["backgroundColor"] = ($date_now==$rowEvent["START_DATE_RAW"])?'#FF0000':'#6486E3';
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][1]["contents"][0]["contents"][0]["offsetStart"] = "5px";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][1]["contents"][0]["contents"][0]["contents"][0]["type"] = "filler";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][1]["width"] = "12px";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][2]["type"] = "box";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][2]["layout"] = "vertical";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][2]["flex"] = 2;
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][2]["contents"][0]["type"] = "text";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][2]["contents"][0]["text"] = 'เริ่ม '.($rowEvent["START_DATE"]??'-').($rowEvent["IS_SETTIME"]=='1'?(' เวลา '.$rowEvent["START_TIME"]):' ');
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][2]["contents"][0]["gravity"] = "center";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][2]["contents"][0]["size"] = "xxs";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][2]["contents"][0]["color"] = "#8c8c8c";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][2]["contents"][1]["type"] = "text";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][2]["contents"][1]["text"] = ' สิ้นสุด '.($rowEvent["END_DATE"]??'-').($rowEvent["IS_SETTIME"]=='1'?(' เวลา '.$rowEvent["END_TIME"]):' ');
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][2]["contents"][1]["gravity"] = "center";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][2]["contents"][1]["size"] = "xxs";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["contents"][2]["contents"][1]["color"] = "#8c8c8c";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["spacing"] = "lg";
		$eventData["contents"]["body"]["contents"][$indexEvent]["contents"][1]["height"] = "40px";
		$indexEvent++;
	}
	$arrPostData["messages"][0] = $eventData;
	$arrPostData["replyToken"] = $reply_token; 
}else{
	$messageResponse = "ไม่พบกิจกรรมของสหกรณ์";
	$dataPrepare = $lineLib->prepareMessageText($messageResponse);
	$arrPostData["messages"] = $dataPrepare;
	$arrPostData["replyToken"] = $reply_token;
}
?>