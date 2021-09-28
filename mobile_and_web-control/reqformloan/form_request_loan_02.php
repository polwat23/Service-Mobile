<?php
use Dompdf\Dompdf;

$dompdf = new DOMPDF();
function GeneratePDFContract($data,$lib) {
	$minispace = '&nbsp;&nbsp;';
	$space = '&nbsp;&nbsp;&nbsp;&nbsp;';
		
	//demo data
	//$header["สามัญประเภท"] = 'ฉุกเฉิน';
	//$header["หนังสือกู้ที่"] = '1212454';
	$header["เขียนที่"] = 'Surin Saving (Mobile Application)';
	$header["วันที่เขียน"] = date('d');
	$header["เดือนที่เขียน"] = (explode(' ',$lib->convertdate(date("Y-m-d"),"d M Y")))[1];
	$header["ปีที่เขียน"] = (date('Y') + 543);
	$header["ชื่อผู้ขอกู้"] = $data["full_name"];
	$header["วันเกิดชื่อผู้ขอกู้"] = (explode(' ',$data["birth_date"]))[0];
	$header["เดือนเกิดชื่อผู้ขอกู้"] = (explode(' ',$data["birth_date"]))[1];
	$header["ปีเกิดชื่อผู้ขอกู้"] = (explode(' ',$data["birth_date"]))[2];
	$header["อายุปี"] = (explode(' ',$lib->count_duration($data["birth_date_raw"],"ym")))[0];
	$header["อายุเดือน"] = (explode(' ',$lib->count_duration($data["birth_date_raw"],"ym")))[2];
	$header["สมาชิกทะเบียน"] = $data["member_no"];
	$header["ตำแหน่ง"] = $data["position"];
	$header["สังกัดโรงเรียน"] = $data["pos_group"];
	
	$header["group_tambol_desc"] = $data["group_tambol_desc"];
	$header["group_district_desc"] = $data["group_district_desc"];
	$header["group_province_desc"] = $data["group_province_desc"];
	$header["group_phone"] = $data["group_phone"];
	
	$header["mate_addr_no"] = $data["mateaddr_no"];
	$header["mate_addr_road"] = $data["mateaddr_road"];
	$header["mate_addr_moo"] = $data["mateaddr_moo"];
	$header["mate_tambol"] = $data["matetambol_desc"];
	$header["mate_district"] = $data["matedistrict_desc"];
	$header["mate_province"] = $data["mateprovince_desc"];
	$header["mate_phone"] = $data["mateaddr_phone"];
	$header["mate_card_id"] = $data["mate_cardperson"];
	
	$header["ตำบล"] = $data["tambol_desc"];
	$header["อำเภอ"] = $data["district_desc"];
	$header["จังหวัด"] = $data["province_desc"];
	$header["เงินเดือน"] = $data["salary_amount"];
	$header["โทร"] = $data["mem_telmobile"];
	$header["เลขที่"] = $data["addr_no"];
	$header["ถนน"] = $data["addr_road"];
	$header["หมู่ที่"] = $data["addr_moo"];
	$header["เลขประจำตัว"] = $data["card_person"];
	$header["คู่สมรส"] = $data["mate_name"];
	$header["อาชีพ"] = '';
	$header["รายได้ต่อเดือน"] = '';

	$dataRepor["จำนวนขอกู้ตัวเลข"] = number_format($data["request_amt"],2);
	$dataRepor["เหตุผล"] = $data["objective"];
	$dataRepor["จำนวนขอกู้ตัวอักษร"] = $lib->baht_text($data["request_amt"]);
	$dataRepor["หุ้น"] = $data["sharestk_amt"];
	$dataRepor["หุ้นต่อบาท"] = $data["share_bf"];
	$dataRepor["ส่งหุ้นต่อเดือน"] = $data["periodshare_amt"];
	
	$dataRepor["extra_contract_no"] = '';
	$dataRepor["extra_contract_start"] = '';
	$dataRepor["extra_prnc"] = '';
	$dataRepor["emer_contract_no"] = '';
	$dataRepor["emer_contract_start"] = '';
	$dataRepor["emer_prnc"] = '';

	$dataRepor["หนังสือกู้สามัญเงินกู้ที่"] = $data["emer_startcont_date"];
	
	$dataRepor["guarantee_to"] = '';
	$dataRepor["guarantee_member_no"] = '';
	$dataRepor["หนี้สินสถาบันการเงิน"] = '';

	$dataRepor["period_payment"] = number_format($data["period_payment"],2);
	$dataRepor["period"] = $data["period"];

	$html = '
	<style>
	@font-face {
		  font-family: TH Niramit AS;
		  src: url(../../resource/fonts/TH Niramit AS.ttf);
	}
	@font-face {
			font-family: TH Niramit AS;
			src: url(../../resource/fonts/TH Niramit AS Bold.ttf);
			font-weight: bold;
	}
	* {
		  font-family: TH Niramit AS;
	}
	body {
	  font-size:14pt;
	  line-height: 18px;

	}
	 .text-center {
            text-align: center
        }

        .text-right {
            text-align: right
        }

        .nowrap {
            white-space: nowrap;
        }

        .wrapper-page {
            page-break-after: always;
        }

        .wrapper-page:last-child {
            page-break-after: avoid;
        }

        .relative {
            position: relative;
        }

        .text-input {
            position: absolute;
            left: 5px;
        }

        table {
            border-collapse: collapse;
        }

        input[type="checkbox" i] {
            border-radius: 0px;
        }

        .container {
            display: block;
            position: relative;
            padding-left: 2px;
            cursor: pointer;
            font-size: 13px;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        .container input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }

        .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            height: 13px;
            width: 13px;
            border: solid 1px;
            box-shadow: 2px 2px black;
        }

        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }

        .container input:checked ~ .checkmark:after {
            display: block;
        }

        .container .checkmark:after {
            left: 4px;
            top: 0px;
            width: 5px;
            height: 10px;
            border: solid black;
            border-width: 0 2px 2px 0;
            -webkit-transform: rotate(45deg);
            -ms-transform: rotate(45deg);
            transform: rotate(45deg);
        }

        .checkmark-radio {
            position: absolute;
            top: 0;
            left: 0;
            height: 13px;
            width: 13px;
            border: solid 1px;
            box-shadow: 1px 1px black;
            border-radius: 50%;
        }

        .checkmark-radio:after {
        content: "";
        position: absolute;
        display: none;
        }

        .container input:checked ~ .checkmark-radio:after {
        display: block;
        }

        .container .checkmark-radio:after {
        top: 9px;
        left: 9px;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: white;
        }
	</style>
	';

	$html .= '
        <!-- page 1 -->
        <div class="relative">
            <!-- ส่วนหัว -->
            <div style=" position:absolute; left:0px; top:50px;  ">
                <span style="font-weight:bold;">เงินกู้สามัญประเภท</span><span
                    class="relative">....................................<span
                        class="text-input">'.$data["loantype_desc"].'</span></span>
            </div>
            <div style="text-align:center">
                <img src="../../resource/logo/logo.jpg" style="width:100px" />
            </div>
            <div style="border:0.5px solid #000000; position:absolute; right:0px; top:10px; padding: 5px 10px;width: 200px;">
                <div class="nowrap">
                    หนังสือกู้ที่ <span class="relative">.................................</span>
                </div>
                <div>
                    วันที่........./...................../............
                </div>
                <div class="text-center">
                    หนังสือค้ำประกัน
                </div>
            </div>

            <div style="text-align:center;  font-size:26px; font-weight:bold;line-height: 26px;margin-top: 8px; ">
                คำขอกู้สามัญ
            </div>
            <div
                style="border:0.5px solid #000000; position:absolute; left:0px; top:110px; padding:3spx 5px; line-height:16px; font-size:13px; width: 230px;">
                <div>
                    <b style="text-decoration: underline;">คำเตือน</b> <span
                        style="padding-left:5px; ">ผู้ขอกู้ต้องกรอกข้อความตามรายการที่กำหนดไว้</span>
                </div>
                <div>
                    ในแบบคำขอกู้นี้ ด้วยลายมือของตนเองโดยถูกต้องและ
                </div>
                <div>
                    ครบถ้วน มิฉะนั้นสหกรณ์ไม่รับพิจารณา
                </div>
            </div>
            <div style="text-align:right;">
                เขียนที่<span class="relative">....................................................<span
                        class="text-input" style="margin-left: 490px;">'.$header["เขียนที่"].'</span></span>
            </div>
            <div style="text-align:right;">
                วันที่<span class="relative">..........<span class="text-input" style="margin-left: 430px;">'.$header["วันที่เขียน"].'</span></span>เดือน<span
                    class="relative">...................................<span
                        class="text-input" style="margin-left: 430px;">'.$header["เดือนที่เขียน"].'</span></span>พ.ศ<span class="relative">...............<span
                        class="text-input" style="margin-left: 430px;">'.$header["ปีที่เขียน"].'</span></span>
            </div>
            <div style="font-weight:bold;font-size: 22px;padding-top: 4px;">
                เรียน คณะกรรมการดําเนินการ สหกรณ์ออมทรัพย์ครูสุรินทร์ จํากัด
            </div>
            <!-- ข้อมูลสมาชิก -->
            <div>
                <div class="nowrap" style="padding-left:45px">
                    ข้าพเจ้าชื่อ<span
                        class="relative">...............................................................<span
                            class="text-input">'.$header["ชื่อผู้ขอกู้"].'</span></span>เกิดวันที่<span class="relative">........<span
                            class="text-input">'.$header["วันเกิดชื่อผู้ขอกู้"].'</span></span>เดือน<span
                        class="relative">.......................<span
                            class="text-input">'.$header["เดือนเกิดชื่อผู้ขอกู้"].'</span></span>พ.ศ<span class="relative">..............<span
                            class="text-input">'.$header["ปีเกิดชื่อผู้ขอกู้"].'</span></span>อายุ<span class="relative">.......<span
                            class="text-input">'.$header["อายุปี"].'</span></span>ปี<span class="relative">......<span
                            class="text-input">'.$header["อายุเดือน"].'</span></span>เดือน
                </div>
                <div class="nowrap">
                    สมาชิกเลขทะเบียนที่<span class="relative">........................<span
                        class="text-input">'.$header["สมาชิกทะเบียน"].'</span></span>ตำแหน่ง<span class="relative">...........................................<span
                        class="text-input">'.$header["ตำแหน่ง"].'</span></span>สังกัด/โรงเรียน<span class="relative">...........................................................<span
                        class="text-input">'.$header["สังกัดโรงเรียน"].'</span></span>
                </div>
                <div class="nowrap">
                    ตำบล<span class="relative">...............................<span
                        class="text-input">'.$header["group_tambol_desc"].'</span></span>อำเภอ<span class="relative">...........................<span
                        class="text-input">'.$header["group_district_desc"].'</span></span>จังหวัด<span class="relative">...........................<span
                        class="text-input">'.$header["group_province_desc"].'</span></span>ได้รับเงินเดือน<span class="relative">....................<span
                        class="text-input">'.$header["เงินเดือน"].'</span></span>บาท โทร<span class="relative">.......................<span
                        class="text-input">'.$header["group_phone"].'</span></span></div>
                <div class="nowrap">
                    อาศัยอยู่บ้านเลขที่<span class="relative">.......<span
                        class="text-input">'.$header["เลขที่"].'</span></span>ถนน<span class="relative">........................<span
                        class="text-input">'.$header["ถนน"].'</span></span>หมู่ที่<span class="relative">.....<span
                        class="text-input">'.$header["หมู่ที่"].'</span></span>ตำบล<span class="relative">..........................<span
                        class="text-input">'.$header["ตำบล"].'</span></span>อำเภอ<span class="relative">........................<span
                        class="text-input">'.$header["อำเภอ"].'</span></span>จังหวัด<span class="relative">...................................<span
                        class="text-input">'.$header["จังหวัด"].'</span></span>
                </div>
                <div style="margin-top:3px;white-space: nowrap;">
                    <div style="display: inline-block;vertical-align: middle;">
                        บัตรประจำตัวประชาชนเลขที่
                    </div>
                    <div style="display: inline-block;height: 18px;vertical-align: top;">
                        <div style="display:inline-block;height: 18px;vertical-align: top;">
                            <div style="display:inline-block;border:0.5px solid; width:18px; height:18px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["เลขประจำตัว"],0,1).'</div></div><!--
                            --><div style="display:inline-block;border:0.5px solid; width:18px; height:18px;border-left: 0px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["เลขประจำตัว"],1,1).'</div></div><!--
                            --><div style="display:inline-block;border:0.5px solid; width:18px; height:18px;border-left: 0px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["เลขประจำตัว"],2,1).'</div></div><!--
                            --><div style="display:inline-block;border:0.5px solid; width:18px; height:18px;border-left: 0px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["เลขประจำตัว"],3,1).'</div></div><!--
                            --><div style="display:inline-block;border:0.5px solid; width:18px; height:18px;border-left: 0px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["เลขประจำตัว"],4,1).'</div></div><!--
                            --><div style="display:inline-block;border:0.5px solid; width:18px; height:18px;border-left: 0px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["เลขประจำตัว"],5,1).'</div></div><!--
                            --><div style="display:inline-block;border:0.5px solid; width:18px; height:18px;border-left: 0px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["เลขประจำตัว"],6,1).'</div></div><!--
                            --><div style="display:inline-block;border:0.5px solid; width:18px; height:18px;border-left: 0px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["เลขประจำตัว"],7,1).'</div></div><!--
                            --><div style="display:inline-block;border:0.5px solid; width:18px; height:18px;border-left: 0px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["เลขประจำตัว"],8,1).'</div></div><!--
                            --><div style="display:inline-block;border:0.5px solid; width:18px; height:18px;border-left: 0px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["เลขประจำตัว"],9,1).'</div></div><!--
                            --><div style="display:inline-block;border:0.5px solid; width:18px; height:18px;border-left: 0px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["เลขประจำตัว"],10,1).'</div></div><!--
                            --><div style="display:inline-block;border:0.5px solid; width:18px; height:18px;border-left: 0px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["เลขประจำตัว"],11,1).'</div></div><!--
                            --><div style="display:inline-block;border:0.5px solid; width:18px; height:18px;border-left: 0px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["เลขประจำตัว"],12,1).'</div></div>
                        </div>
                    </div>
                    <div style="display: inline-block; padding-left: 20px;vertical-align: middle;">โทร<span class="relative">.................................................<span
                            class="text-input">'.$header["โทร"].'</span></span>
                    </div>
                </div>
                <div class="nowrap" style="margin-top:3px;">
                    คู่สมรสชื่อ<span class="relative">..............................................................<span
                        class="text-input">'.$header["คู่สมรส"].'</span></span>อาชีพ<span class="relative">........................................<span
                        class="text-input">'.$header["อาชีพ"].'</span></span>รายได้ต่อเดือน/ต่อปี<span class="relative">.............................<span
                        class="text-input">'.$header["รายได้ต่อเดือน"].'</span></span>บาท
                </div>
                <div class="nowrap">
                    อยู่บ้านเลขที่<span class="relative">.......<span
                        class="text-input">'.$header["mate_addr_no"].'</span></span>ถนน<span class="relative">.......................<span
                        class="text-input">'.$header["mate_addr_road"].'</span></span>หมู่ที่<span class="relative">..........<span
                        class="text-input">'.$header["mate_addr_moo"].'</span></span>ตำบล<span class="relative">..........................<span
                        class="text-input">'.$header["mate_tambol"].'</span></span>อำเภอ<span class="relative">............................<span
                        class="text-input">'.$header["mate_district"].'</span></span>จังหวัด<span class="relative">....................................<span
                        class="text-input">'.$header["mate_province"].'</span></span>
                </div>
                <div class="nowrap">
                    <div style="display: inline-block;vertical-align: middle;">
                        โทร<span class="relative">............................................<span
                            class="text-input">'.$header["mate_phone"].'</span></span> บัตรประจำตัวประชาชนเลขที่
                    </div>
                    <div style="display: inline-block;height: 18px;vertical-align: top;">
                        <div style="display:inline-block;height: 18px;vertical-align: top;">
                            <div style="display:inline-block;border:0.5px solid; width:18px; height:18px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["mate_card_id"],0,1).'</div></div><!--
                            --><div style="display:inline-block;border:0.5px solid; width:18px; height:18px;border-left: 0px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["mate_card_id"],1,1).'</div></div><!--
                            --><div style="display:inline-block;border:0.5px solid; width:18px; height:18px;border-left: 0px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["mate_card_id"],2,1).'</div></div><!--
                            --><div style="display:inline-block;border:0.5px solid; width:18px; height:18px;border-left: 0px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["mate_card_id"],3,1).'</div></div><!--
                            --><div style="display:inline-block;border:0.5px solid; width:18px; height:18px;border-left: 0px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["mate_card_id"],4,1).'</div></div><!--
                            --><div style="display:inline-block;border:0.5px solid; width:18px; height:18px;border-left: 0px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["mate_card_id"],5,1).'</div></div><!--
                            --><div style="display:inline-block;border:0.5px solid; width:18px; height:18px;border-left: 0px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["mate_card_id"],6,1).'</div></div><!--
                            --><div style="display:inline-block;border:0.5px solid; width:18px; height:18px;border-left: 0px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["mate_card_id"],7,1).'</div></div><!--
                            --><div style="display:inline-block;border:0.5px solid; width:18px; height:18px;border-left: 0px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["mate_card_id"],8,1).'</div></div><!--
                            --><div style="display:inline-block;border:0.5px solid; width:18px; height:18px;border-left: 0px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["mate_card_id"],9,1).'</div></div><!--
                            --><div style="display:inline-block;border:0.5px solid; width:18px; height:18px;border-left: 0px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["mate_card_id"],10,1).'</div></div><!--
                            --><div style="display:inline-block;border:0.5px solid; width:18px; height:18px;border-left: 0px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["mate_card_id"],11,1).'</div></div><!--
                            --><div style="display:inline-block;border:0.5px solid; width:18px; height:18px;border-left: 0px;text-align: center;vertical-align: top;"><div style="width:18px; height:18px">'.substr($header["mate_card_id"],12,1).'</div></div>
                        </div>
                    </div>
                </div>
                <div class="nowrap" style="padding-left:45px; margin-top:5px; font-weight:bold;">ข้อ 1. ขอกู้เงินสามัญจากสหกรณ์จำนวน<span class="relative">.......................<span
                    class="text-input">'.$dataRepor["จำนวนขอกู้ตัวเลข"].'</span></span>บาท (<span class="relative">....................................................................<span
                    class="text-input">'.$dataRepor["จำนวนขอกู้ตัวอักษร"].'</span></span>)</div>
                <div class="nowrap" style="font-weight:bold;">โดยจะนำไปใช้เพื่อการดังต่อไปนี้ (ชี้แจงความมุ่งหมายและเหตุผลแห่งการกู้โดยละเอียด)<span class="relative">...........................................<span
                    class="text-input">'.$dataRepor["เหตุผล"].'</span></span></div>
                <div class="nowrap" style="padding-left:45px;">ข้อ 2. ในเวลานี้ข้าพเจ้ามีหุ้นอยู่ในสหกรณ์รวม<span class="relative">........................<span
                    class="text-input">'.$dataRepor["หุ้น"].'</span></span>หุ้น เป็นเงิน<span class="relative">...............................................................<span
                    class="text-input">'.$dataRepor["หุ้นต่อบาท"].'</span></span>บาท</div>
                <div class="nowrap">และข้าพเจ้าส่งเงินค่าหุ้นในอัตราเดือนละ<span class="relative">................................................<span
                    class="text-input">'.$dataRepor["ส่งหุ้นต่อเดือน"].'</span></span>บาท</div>
                <div class="nowrap" style="padding-left:45px;">ข้อ 3. ข้าพเจ้ามีหนี้สินอยู่ต่อสหกรณ์ในฐานะผู้กู้ดังต่อไปนี้ :- </div>';
				
				$i = 0;
				if(sizeof($data["common_contract"]) > 0){
					foreach ($data["common_contract"] as $contract) {
						$html .= $i == 0 ? '<div class="nowrap" style="padding-left:63px;">(1) หนังสือกู้สามัญ/พิเศษที่<span class="relative">.................................<span
							class="text-input">'.$contract["LOANCONTRACT_NO"].'</span></span>วันที่<span class="relative">..................................<span
							class="text-input">'.$contract["STARTCONT_DATE"].'</span></span>ต้นเงินคงเหลือ<span class="relative">.......................................<span
							class="text-input">'.$contract["PRINCIPAL_BALANCE"].'</span></span>
						</div>' : '<div class="nowrap" style="padding-left:63px;"><span style="visibility: hidden;">(1)</span> หนังสือกู้สามัญ/พิเศษที่<span class="relative">.................................<span
							class="text-input">'.$contract["LOANCONTRACT_NO"].'</span></span>วันที่<span class="relative">..................................<span
							class="text-input">'.$contract["STARTCONT_DATE"].'</span></span>ต้นเงินคงเหลือ<span class="relative">.......................................<span
							class="text-input">'.$contract["PRINCIPAL_BALANCE"].'</span></span>
						</div>';
						$i++;
					}
				}else{
					$html .= '<div class="nowrap" style="padding-left:63px;">(1) หนังสือกู้สามัญ/พิเศษที่<span class="relative">.................................<span
							class="text-input"></span></span>วันที่<span class="relative">..................................<span
							class="text-input"></span></span>ต้นเงินคงเหลือ<span class="relative">.......................................<span
							class="text-input"></span></span>
						</div>';
				}
				
				$i = 0;
				if(sizeof($data["emer_contract"]) > 0){
					foreach ($data["emer_contract"] as $contract) {
						$html .= $i == 0 ? '<div class="nowrap" style="padding-left:63px;"><span>(2)</span> หนังสือเงินกู้ฉุกเฉินที่<span class="relative">....................................<span
							class="text-input">'.$contract["LOANCONTRACT_NO"].'</span></span>วันที่<span class="relative">..................................<span
							class="text-input">'.$contract["STARTCONT_DATE"].'</span></span>ต้นเงินคงเหลือ<span class="relative">......................................<span
							class="text-input">'.$contract["PRINCIPAL_BALANCE"].'</span></span>
						</div>' : '<div class="nowrap" style="padding-left:63px;"><span style="visibility: hidden;">(2)</span> หนังสือเงินกู้ฉุกเฉินที่<span class="relative">....................................<span
							class="text-input">'.$contract["LOANCONTRACT_NO"].'</span></span>วันที่<span class="relative">..................................<span
							class="text-input">'.$contract["STARTCONT_DATE"].'</span></span>ต้นเงินคงเหลือ<span class="relative">......................................<span
							class="text-input">'.$contract["PRINCIPAL_BALANCE"].'</span></span>
						</div>';
						$i++;
					}
				}else{
					$html .= '<div class="nowrap" style="padding-left:63px;">(2) หนังสือเงินกู้ฉุกเฉินที่<span class="relative">....................................<span
								class="text-input"></span></span>วันที่<span class="relative">..................................<span
								class="text-input"></span></span>ต้นเงินคงเหลือ<span class="relative">......................................<span
								class="text-input"></span></span>
							</div>';
				}
				
				$html .= '<div class="nowrap" style="padding-left:63px;">
                    <div style="display: inline-block;vertical-align: top;">
                        (3) ข้าพเจ้าค้ำประกันเงินกู้สามัญให้ 
                    </div>
                    <div class="nowrap" style="display: inline-block;margin-left:10px;vertical-align: top;">';
					
						$i = 1;
						foreach ($data["guarantee"] as $guarantee) {
							$html .= '<div>
                            '.$i.'<span class="relative">.........................................................<span
                                class="text-input">'.$guarantee["LOANCONTRACT_NO"].'</span></span>เลขทะเบียน<span class="relative">.......................................<span
                                    class="text-input">'.$guarantee["MEMBER_NO"].'</span></span>
							</div>';
							$i++;
						}
                $html .= '</div>
                </div>
                <div class="nowrap" style="padding-left:45px;">ข้อ 4. ข้าพเจ้ามีภาระหนี้สินสถาบันการเงินอื่นที่ต้องส่งชำระเป็นรายเดือน รวมเป็นเงิน<span class="relative">.............................................<span
                    class="text-input">'.$dataRepor["หนี้สินสถาบันการเงิน"].'</span></span>บาท</div>
                <div>(ลงรายละเอียดประกอบด้านหลัง)</div>
                <div class="nowrap" style="padding-left:45px;">ข้อ 5. นอกจากค่าหุ้นที่ข้าพเจ้ามีอยู่ในสหกรณ์ ข้าพเจ้าขอเสนอหลักประกันดังต่อไปนี้ คือ (ผู้ค้ำเขียน)</div>
                <div class="nowrap" style="padding-left:81px">(1.) ชื่อ....................................................................................................เลขทะเบียนที่........................................</div>
                <div class="nowrap">ตำแหน่ง..................................................สังกัดหรือโรงเรียน........................................................ตำบล.....................................</div>
                <div class="nowrap">อำเภอ..........................................จังหวัดสุรินทร์ ลายมือชื่อ....................................................เงินเดือน...............................บาท</div>
          
                <div class="nowrap" style="padding-left:81px">(2.) ชื่อ....................................................................................................เลขทะเบียนที่........................................</div>
                <div class="nowrap">ตำแหน่ง..................................................สังกัดหรือโรงเรียน........................................................ตำบล.....................................</div>
                <div class="nowrap">อำเภอ..........................................จังหวัดสุรินทร์ ลายมือชื่อ....................................................เงินเดือน...............................บาท</div>
          
                <div class="nowrap" style="padding-left:81px">(3.) ชื่อ....................................................................................................เลขทะเบียนที่........................................</div>
                <div class="nowrap">ตำแหน่ง..................................................สังกัดหรือโรงเรียน........................................................ตำบล.....................................</div>
                <div class="nowrap">อำเภอ..........................................จังหวัดสุรินทร์ ลายมือชื่อ....................................................เงินเดือน...............................บาท</div>          
                <div class="nowrap" style="padding-left:45px;">ข้อ 6. ถ้าข้าพเจ้าได้รับเงินกู้ ข้าพเจ้าขอส่งต้นเงินกู้เป็นงวดรายเดือนเท่านั้น งวดล่ะ<span class="relative">......................................<span
                                    class="text-input">'.$dataRepor["period_payment"].'</span></span>บาท</div>
                <div class="nowrap" style="letter-spacing:-0.05px;">(เว้นแต่งวดสุดท้าย) พร้อมดอกเบี้ยในอัตราร้อยละสิบห้าต่อปี และหรือตามที่คณะกรรมการกำหนดไว้ในประกาศสหกรณ์ </div>
                <div class="nowrap">เป็นจำนวนงวด<div class="relative" style="display: inline-block;vertical-align: top;">.......................<div 
									class="text-input" style="top: 0;">'.$dataRepor["period"].'</div></div>งวด ตั้งแต่เดือนที่สหกรณ์จ่ายเงินกู้ให้ </div>
        
                <div class="nowrap" style="padding-left:45px;">ข้อ 7. ในการรับเงินกู้ ข้าพเจ้าจะทำหนังสือสำหรับเงินกู้สามัญ ให้ไวต่อสหกรณ์ตามที่สหกรณ์กำหนด</div>
                <div class="nowrap" style="padding-left:45px; letter-spacing:0.07px;">ข้อ 8. การใด ๆ ที่ข้าพเจ้าได้ทำให้สหกรณ์ได้รับความเสียหาย ข้าพเจ้ายินดีชดใช้ความเสียหายให้สหกรณ์ทั้งสิ้น</div>
                <div class="nowrap" style="padding-left:45px; letter-spacing:-0.25px;">ข้อ 9. (เฉพาะในกรณีผู้ขอกู้มีคู่สมรส) ในการกู้เงินตามคำขอนี้ ข้าพเจ้าได้รัยบอนุญาตของคู่สมรส ซึ่งพร้อมที่จะทำคำ</div>
                <div class="nowrap" >อนุญาตให้ไว้เป็นหลักฐานในท้ายหนังสือกู้ด้วย</div>
                </div>
                <div>
                    <div style="text-align: right;">
                        <div>...........................................................ผู้ขอกู้</div>
                        <div>
                            <div style="display: inline-block;margin-left: 483px;">(</div>
							<div style="display: inline-block;width: 190px;text-align: center;">'.$header["ชื่อผู้ขอกู้"].'</div>
                            <div style="display: inline-block;">)</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- page 2 -->
            <div class="relative" style="page-break-before: always;">
                <div style="font-weight:bold; font-size:22px;">บันทึกการพิจารณาให้ความเห็นของผู้บังคับบัญชา</div>
                <div class="text-right" style="margin-top:16px;">วันที่........................................................................................</div>
                <div class="nowrap" style="padding-left:45px;">ข้าพเจ้าได้พิจารณาตามความรู้สึกเห็นและตามที่ได้สอบแล้ว (เห็นสมควรที่สหกรณ์จะให้เงินกู้สมาชิกนี้ได้หรือไม่)....................</div>
                <div class="nowrap">..................................................................................................................................................................................................</div>
                <div class="text-right" style="margin-top:5px;font-weight: bold;">ลงมือชื่อ...............................................................ตำแหน่ง...............................................................</div>
                <div style="margin-left:155px; margin-top:5px;">(...............................................................)</div>
              
                <div class="text-center" style="font-weight:bold; font-size:22px; margin-top:40px;">หนังสือมอบอำนาจ</div>
                <div class="nowrap" style="margin-top:20px; padding-left:45px;">ข้าพเจ้า....................................................................................................สมาชิกเลขทะเบียน........................................</div>
                <div class="nowrap">ขอมอบอำนาจให้..............................................................................เป็นผู้รับเงินกู้สามัญจำนวน.........................................บาท</div>
                <div style="margin-left:120px; margin-top:5px;font-weight: bold;">.......................................................................ผู้มอบอำนาจ</div>
                <div style="margin-left:120px; margin-top:5px;font-weight: bold;">(.....................................................................)</div>
                <div style="margin-left:120px; margin-top:5px;font-weight: bold;">.......................................................................ผู้รับมอบอำนาจ</div>
                <div style="margin-left:120px; margin-top:5px;font-weight: bold;">.......................................................................พยาน</div>
                <div style="margin-left:120px; margin-top:5px;font-weight: bold;">(.....................................................................)</div>
              
                <div style="margin-top:40px;">
                  <table style="width:100%;">
                      <thead>
                          <tr>
                              <th style="width:50%; height:25px; border-top:solid 1px; border-bottom:solid 1px; border-right:solid 1px;" class="text-center">สำหรับสมาชิก</th>
                              <th style="width:50%; height:25px; border-top:solid 1px; border-bottom:solid 1px;" class="text-center">สำหรับเจ้าหน้าที่</th>
                          </tr>
                      </thead>
                      <tbody>
                          <tr>
                              <td style="vertical-align: top; border-right: solid 1px;">
                                    <div class="nowrap">ข้อ 4 การส่งชำระหนี้รายเดือนต่อสถาบันการเงินอื่น มีดังนี้</div>
                                    <div>
                                        <div>
                                            <div class="nowrap"><div class="container" style="margin-top:4px;" >
                                                <input type="checkbox">
                                                <span class="checkmark"></span>
                                              </div>
                                              <div style="margin-left: 20px;">
                                                ธนาคารออมสิน สาขา........................................................
                                              </div>
                                            </div>
                                            <div style="margin-left: 20px;">
                                                <div class="nowrap">
                                                    <div>
                                                        <div class="container" style="margin-top:4px;margin-left: 13px;" >
                                                        <input type="checkbox">
                                                        <span class="checkmark"></span>
                                                    </div>
                                                    <div>
                                                       <span style="margin-right: 25px;">-</span> หนี้ ชพค...............................................................บาท
                                                    </div>
                                                </div>
                                                <div class="nowrap">
                                                    <div>
                                                        <div class="container" style="margin-top:4px;margin-left: 13px;" >
                                                        <input type="checkbox">
                                                        <span class="checkmark"></span>
                                                    </div>
                                                    <div>
                                                       <span style="margin-right: 25px;">-</span> หนี้ ชพส...............................................................บาท
                                                    </div>
                                                </div>
                                                <div class="nowrap">
                                                    <div>
                                                        <div class="container" style="margin-top:4px;margin-left: 13px;" >
                                                        <input type="checkbox">
                                                        <span class="checkmark"></span>
                                                    </div>
                                                    <div>
                                                       <span style="margin-right: 25px;">-</span> หนี้ สวัสดิการ.......................................................บาท
                                                    </div>
                                                </div>
                                                <div class="nowrap">
                                                    <div>
                                                        <div class="container" style="margin-top:4px;margin-left: 13px;" >
                                                        <input type="checkbox">
                                                        <span class="checkmark"></span>
                                                    </div>
                                                    <div>
                                                       <span style="margin-right: 25px;">-</span> หนี้ วิทยาฐานะ.....................................................บาท
                                                    </div>
                                                </div>
                                                <div class="nowrap">
                                                    <div>
                                                        <div class="container" style="margin-top:4px;margin-left: 13px;" >
                                                        <input type="checkbox">
                                                        <span class="checkmark"></span>
                                                    </div>
                                                    <div>
                                                       <span style="margin-right: 25px;">-</span> หนี้ พัฒนาชีวิต.....................................................บาท
                                                    </div>
                                                </div>
                                                <div class="nowrap">
                                                    <div>
                                                        <div class="container" style="margin-top:4px;margin-left: 13px;" >
                                                        <input type="checkbox">
                                                        <span class="checkmark"></span>
                                                    </div>
                                                    <div>
                                                       <span style="margin-right: 25px;">-</span> หนี้ ประกันชีวิต....................................................บาท
                                                    </div>
                                                </div>
                                                <div class="nowrap">
                                                    <div>
                                                        <div class="container" style="margin-top:4px;margin-left: 13px;" >
                                                        <input type="checkbox">
                                                        <span class="checkmark"></span>
                                                    </div>
                                                    <div>
                                                       <span style="margin-right: 25px;">-</span> หนี้ เงินฝากรายเดือน...........................................บาท
                                                    </div>
                                                </div>
                                                <div class="nowrap">
                                                    <div>
                                                        <div class="container" style="margin-top:4px;margin-left: 13px;" >
                                                        <input type="checkbox">
                                                        <span class="checkmark"></span>
                                                    </div>
                                                    <div>
                                                       <span style="margin-right: 25px;">-</span> อื่น.......................................................................บาท
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="nowrap"><div class="container" style="margin-top:4px;" >
                                                <input type="checkbox">
                                                <span class="checkmark"></span>
                                              </div>
                                              <div style="margin-left: 20px;">
                                                ธนาคารอื่น ๆ สาขา............................................................
                                              </div>
                                            </div>
                                        </div>
                                        </div>
                                    </div>
                              </td>
                              <td>
                                <div style="padding-left:20px;">สหกรณ์ฯ ได้ทำการตรวจสอบเอกสารหลักฐานประกอบอการ</div>
                                <div style="margin-left:5px;margin-top: 4px;">ขอรับเงินกู้แล้วปรากฎว่าต้องดำเนินการแก้ไขเพิ่มเติมดังนี้</div>
                                <div style="margin-left: 20px;">
                                    <div>
                                        <div class="nowrap">
                                            <div class="container" style="margin-top:4px;" >
                                                <input type="checkbox">
                                                <span class="checkmark"></span>
                                            </div>
                                            <span style="margin-left: 20px;display: inline-block;vertical-align: top;">
                                                สำเนาบัตร
                                            </span>
                                            <div style="display: inline-block;vertical-align: top;margin-left: 20px;" class="nowrap">
                                                    <div class="container" >
                                                    <input type="checkbox">
                                                    <span class="checkmark-radio"></span>
                                                </div>
                                                <span style="margin-left: 20px;display: inline-block;vertical-align: top;">
                                                    ผู้กู้
                                                </span>
                                            </div>
                                            <div style="display: inline-block;vertical-align: top;margin-left: 20px;" class="nowrap">
                                                    <div class="container" >
                                                    <input type="checkbox">
                                                    <span class="checkmark-radio"></span>
                                                </div>
                                                <span style="margin-left: 20px;display: inline-block;vertical-align: top;">
                                                    ผู้ค้ำ
                                                </span>
                                            </div>
                                            <div style="display: inline-block;vertical-align: top;margin-left: 20px;" class="nowrap">
                                                        <div class="container" >
                                                        <input type="checkbox">
                                                        <span class="checkmark-radio"></span>
                                                    </div>
                                                    <span style="margin-left: 20px;display: inline-block;vertical-align: top;">
                                                    คู่สมรส
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="nowrap">
                                                <div class="container" style="margin-top:7px;" >
                                                <input type="checkbox">
                                                <span class="checkmark"></span>
                                            </div>
                                            <span style="margin-left: 20px;">
                                                สลิปเงินเดือน ประจำเดือน.............................................
                                            </span>
                                        </div> 
                                        <div class="nowrap">
                                                <div class="container" style="margin-top:7px;" >
                                                <input type="checkbox">
                                                <span class="checkmark"></span>
                                            </div>
                                            <span style="margin-left: 20px;">
                                             สำเนาทะเบียนบ้าน........................................................
                                            </span>
                                        </div> 
                                        <div class="nowrap">
                                                <div class="container" style="margin-top:7px;" >
                                                <input type="checkbox">
                                                <span class="checkmark"></span>
                                            </div>
                                            <span style="margin-left: 20px;">
                                            ลายมือชื่อ......................................................................
                                            </span>
                                      </div> 
                                      <div class="nowrap">
                                              <div class="container" style="margin-top:7px;" >
                                              <input type="checkbox">
                                              <span class="checkmark"></span>
                                          </div>
                                          <span style="margin-left: 20px;">
                                            สำเนาบัตรนักศึกษา
                                        </span>
                                      </div> 
                                      <div class="nowrap">
                                              <div class="container" style="margin-top:7px;" >
                                              <input type="checkbox">
                                              <span class="checkmark"></span>
                                          </div>
                                          <span style="margin-left: 20px;">
                                        ใบเสร็จรับเงิน
                                        </span>
                                        </div> 
                                        <div class="nowrap">
                                                <div class="container" style="margin-top:7px;" >
                                                <input type="checkbox">
                                                <span class="checkmark"></span>
                                            </div>
                                            <span style="margin-left: 20px;">
                                        สำเนา กพ.7
                                        </span>
                                        </div> 
                                        <div class="nowrap">
                                                <div class="container" style="margin-top:7px;" >
                                                <input type="checkbox">
                                                <span class="checkmark"></span>
                                            </div>
                                            <span style="margin-left: 20px;">
                                        บัญชีรายชื่อ...................................................................
                                        </span>
                                        </div> 
                                        <div class="nowrap">
                                                <div class="container" style="margin-top:7px;" >
                                                <input type="checkbox">
                                                <span class="checkmark"></span>
                                            </div>
                                            <span style="margin-left: 20px;">
                                        อื่น ๆ ............................................................................
                                        </span>
                                        </div> 
                                    </div>
                                </div>
                                <div class="nowrap" style="margin-left: 5px;">
                                    <div>
                                        <div class="container" style="margin-top:7px;" >
                                        <input type="checkbox">
                                        <span class="checkmark"></span>
                                    </div>
                                    <span style="margin-left: 20px;">
                                  ไม่อนุญาตเนื่องจาก ..........................................................
                                  </span>
                                </div>
                              </td>
                          </tr>
                          <tr>
                             <td style="padding-top: 36px;vertical-align: top; border-right: solid 1px;">
                                <div style="text-align: center;">(ลงชื่อ).............................................................</div>
                                <div style="text-align: center;margin-left: 20px;">(............................................................)</div>
                              </td>
                              <td style="padding-top: 36px;">
                                <div style="text-align: center;">(ลงชื่อ).............................................................</div>
                                <div style="text-align: center;margin-left: 20px;">(............................................................)</div>
                              </td>
                          </tr>
                      </tbody>
                   </table>  
                </div>
            </div>';

	$dompdf = new Dompdf([
		'fontDir' => realpath('../../resource/fonts'),
		'chroot' => realpath('/'),
		'isRemoteEnabled' => true
	]);

	$dompdf->set_paper('A4');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__.'/../../resource/pdf/request_loan';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$data["requestdoc_no"].'.pdf';
	$pathfile_show = '/resource/pdf/request_loan/'.$data["requestdoc_no"].'.pdf?v='.time();
	$arrayPDF = array();
	$output = $dompdf->output();
	if(file_put_contents($pathfile, $output)){
		$arrayPDF["RESULT"] = TRUE;
	}else{
		$arrayPDF["RESULT"] = FALSE;
	}
	$arrayPDF["PATH"] = $pathfile_show;
	return $arrayPDF;
}
?>