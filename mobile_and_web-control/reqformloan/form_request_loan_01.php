<?php
use Dompdf\Dompdf;

$dompdf = new DOMPDF();
function GeneratePDFContract($data,$lib) {
	$minispace = '&nbsp;&nbsp;';
	$space = '&nbsp;&nbsp;&nbsp;&nbsp;';
	$today = date("d/m").'/'.(date("Y")+543);
	$html = '<style>
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
			html {
				margin: 0px;
			}
			body {
				padding: 0px 0px 0px 10px;
				font-size: 14pt;
				margin: 0px;
			}
			.sub-table div {
				padding: 5px;
			}
			.text-input {
				font-weight: bold;
				font-size: 20px;
				border-bottom: 1px dotted black;
			}
			.text-header {
				font-size: 18px;
				border-bottom: 1px dotted black;
			}
			.line-margin {
				margin-top: 2px;
			}
			</style>';
	$html .= '
    <div>
        <img src="../../resource/logo/logo.jpg" style="width:80px;position: absolute;left: 170px;top: 8px;" />
        <div style="display: flex;text-align: center;">
            <div style="text-align:center;width:100%;padding: 30px 0;">
                <div style="font-weight: bold;font-size: 16pt;">
                    คำขอและหนังสือกู้เงินเพื่อเหตุฉุกเฉิน
                </div>
            </div>
        </div>
        <div>
            <div style="width: 100%;">
                <div style="display: inline-block; width: 50%;">
                </div>
                <span style="display: inline-block;">วันที่</span>
                <span
                    style="display: inline-block;width: 90px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;">
                   	'.'&nbsp;'.'
					<span style="position: absolute;">
						'.$today.'
					</span>
                </span>
            </div>
            <div style="padding-bottom: 16px;">
				เรียน<span style="margin-left: 16px">คณะกรรมการดำเนินการสหกรณ์ออมทรัพย์พนักงานสยามคูโบต้า จำกัด</span>
            </div>
            <div class="line-margin" style="padding-left: 64px;">
                <span style="display: inline-block;padding-right: 4px">ข้าพเจ้า ชื่อ</span>
                <span
                    style="display: inline-block;width: 140px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;">
					'.'&nbsp;'.'
					<span style="position: absolute;">
						'.$data["memb_prename"].$data["memb_name"].'
					</span>
                </span>
                <span style="display: inline-block; padding-left: 14px; padding-right: 14px;">นามสกุล</span>
                <span style="display: inline-block;width: 150px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;">
					'.'&nbsp;'.'
					<span style="position: absolute;">
						'.$data["memb_surname"].'
					</span>
                </span>
                </span>
                <span style="display: inline-block; padding-left: 14px; padding-right: 14px;">เลขประจำตัวพนักงาน</span>
                <span
                    style="display: inline-block;width: 90px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;">
                    '.'&nbsp;'.'
					<span style="position: absolute;">
						'.$data["emp_no"].'
					</span>
                </span>
            </div>
            <div class="line-margin">
                <span style="display: inline-block; padding-left: 14px; padding-right: 14px;">เลขทะเบียนสมาชิก</span>
                <span
                    style="display: inline-block;width: 70px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;">
						'.'&nbsp;'.'
						<span style="position: absolute;">
							'.$data["member_no"].'
						</span>
                </span>
                <span style="display: inline-block; padding-left: 20px; padding-right: 20px;">ส่วน</span>
                <span
                    style="display: inline-block;width: 184px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;">
						'.'&nbsp;'.'
						<span style="position: absolute;">
							'.$data["emp_path"].'
						</span>
                </span>
                </span>
                <span style="display: inline-block; padding-left: 20px; padding-right: 20px;">โทรศัพท์มือถือ</span>
                <span
                    style="display: inline-block;width: 164px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;">
						'.'&nbsp;'.'
						<span style="position: absolute;">
							'.$data["tel"].'
						</span>
                </span>
            </div>
            <div class="line-margin" style="font-weight: bold;">
						ขอเสนอคำขอและหนังสือกู้เงินเพื่อเหตุฉุกเฉิน เพื่อคณะกรรมการดำเนินการสหกรณ์ฯ โปรดพิจารณาดังนี้
						จำกัด</span>
            </div>
            <div class="line-margin" style="margin-top: 8px;">
                <span style="display: inline-block; padding-right: 4px;">ข้อ 1. ข้าพเจ้าขอกู้เงินสหกรณ์ฯจำนวน</span>
                <span
                    style="display: inline-block;width: 85px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;">
						'.'&nbsp;'.'
						<span style="position: absolute;">
							'.number_format($data["request_amt"],2).'
						</span>
                </span>
                <span style="display: inline-block;">บาท <span style="font-weight: bold">(ไม่เกิน 1 เท่าของค่าจ้าง และสูงสุดไม่เกิน 30,000 บาท แต่ไม่เกินมูลค่า</span></span>
            </div>
            <div class="line-margin">
                <span style="display: inline-block; padding-right: 4px;padding-left: 32px;"><span
                        style="font-weight: bold">หลักทรัพย์ค้ำประกันที่มี)</span> โดยจะนำไปใช้เพื่อ (ชี้แจงเหตุจำเป็นในการกู้เงิน)</span>
                <span
                    style="display: inline-block;width: 272px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;margin-left: 60px;">
						'.'&nbsp;'.'
						<span style="position: absolute;">
							'.$data["objective"].'
						</span>
                </span>
            </div>
            <div class="line-margin">
                <span style="display: inline-block; padding-right: 4px;">ข้อ 2. ข้าพเจ้าขอผ่อนชำระเงินต้นตามข้อ 1.
						คืนสหกรณ์ฯ เป็นรายงวด ทุกงวดงวดละ</span>
                <span
                    style="display: inline-block;width: 140px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;margin-left: 48px;">
						'.'&nbsp;'.'
						<span style="position: absolute;">
							'.number_format($data["period_payment"],2).'
						</span>
                </span>
                <span style="display: inline-block; padding-right: 4px;padding-left: 8px;">บาท</span>
            </div>
            <div  class="line-margin">
                <span style="display: inline-block; padding-right: 4px;padding-left: 32px;">รวม</span>
                <span
                    style="display: inline-block;width: 60px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;">
						'.'&nbsp;'.'
						<span style="position: absolute;">
							'.$data["period"].'
						</span>
                </span>
                <span style="display: inline-block; padding-right: 4px;padding-left: 8px;">งวด (สูงสุดไม่เกิน 6 งวด) พร้อมดอกเบี้ย ทั้งนี้ตั้งแต่เดือน</span>
                <span
                    style="display: inline-block;width: 60px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;margin-left: 45px;">
						'.'&nbsp;'.'
						<span style="position: absolute;">
							'.$data["pay_month"].'
						</span>
                </span>
                <span style="display: inline-block; padding-right: 4px;">ได้รับเงินหลังวันที่ 10 จะหักเดือนถัดไป</span>
            </div>
            <div class="line-margin">
                <span style="display: inline-block; padding-right: 8px;">ข้อ 3.  ข้าพเจ้ายินยอมเสียดอกเบี้ยในอัตราร้อยละ</span>
                <span
                    style="display: inline-block;width: 90px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;">
						'.'&nbsp;'.'
						<span style="position: absolute;">
							'.$data["int_rate"].'
						</span>
                </span>
                <span style="display: inline-block; padding-right: 4px;">ต่อปี ให้สหกรณ์ฯ เป็นรายงวด
						ทุกงวดตามจำนวน</span>
            </div>
            <div class="line-margin">
                <span style="display: inline-block; padding-right: 4px;padding-left: 32px;">เงินต้นที่เหลือ
						(อัตราดอกเบี้ยจะมีการเปลี่ยนแปลงตามประกาศสหกรณ์ฯ)</span>
            </div>
            <div style="width: 100%; border: 1px solid #000000">
                <!-- Left -->
                <div style="display: inline-block; vertical-align: top; border-right: 1px solid #000000;">
                    <div>
                        <span style="display: inline-block; padding-right: 4px;">ข้อ 4. เมื่อข้าพเจ้าได้รับเงินกู้แล้ว  ข้าพเจ้ายอมรับข้อผูกพันตาม</span>
                    </div>
                    <div class="line-margin">
                        <span style="display: inline-block; padding-right: 4px;padding-left: 32px;">ข้อบังคับของสหกรณ์ฯ
                            ดังนี้</span>
                    </div>
                    <div class="line-margin">
                        <span style="display: inline-block; padding-right: 4px;padding-left: 40px;">4.1 ยินยอมให้บริษัท
                            สยามคูโบต้าคอร์ปอเรชั่น จำกัด</span>
                    </div>
                    <div class="line-margin">
                        <span
                            style="display: inline-block; padding-right: 4px;padding-left: 60px;">หักเงินได้รายเดือนของข้าพเจ้าตามจำนวนงวดชำระหนี้</span>
                    </div>
                    <div class="line-margin">
                        <span style="display: inline-block; padding-right: 4px;padding-left: 60px;">ในข้อ 2.
                            เพื่อส่งต่อสหกรณ์ฯ</span>
                    </div>
                    <div class="line-margin">
                        <span style="display: inline-block; padding-right: 4px;padding-left: 40px;">4.2
                            ยอมให้ถือว่าการขาดจากสมาชิกไม่ว่าในกรณีใด ๆ</span>
                    </div>
                    <div class="line-margin">
                        <span style="display: inline-block; padding-right: 4px;padding-left: 60px;">ตามข้อบังคับข้อ 39
                            ให้เงินกู้ที่ขอกู้ไปจากสหกรณ์ฯ</span>
                    </div>
                    <div class="line-margin">
                        <span
                            style="display: inline-block; padding-right: 4px;padding-left: 60px;">เป็นอันถึงกำหนดส่งคืนโดยสิ้นเชิงพร้อมดอกเบี้ย</span>
                    </div>
                    <div class="line-margin">
                        <span style="display: inline-block; padding-right: 4px;padding-left: 60px;">ในทันที
                            โดยมิให้คำนึงถึงกำหนดเวลาที่ตกลงไว้</span>
                    </div>
                    <div class="line-margin">
                        <span style="display: inline-block; padding-right: 4px;padding-left: 40px;">4.3
                            ถ้าประสงค์จะขอลาออกจากการเป็นสมาชิกสหกรณ์ฯ</span>
                    </div>
                    <div class="line-margin">
                        <span
                            style="display: inline-block; padding-right: 4px;padding-left: 60px;">จะแจ้งเป็นหนังสือให้สหกรณ์ฯ
                            ทราบ และ จัดการ</span>
                    </div>
                    <div class="line-margin">
                        <span
                            style="display: inline-block; padding-right: 4px;padding-left: 60px;">ชำระหนี้ซึ่งมีต่อสหกรณ์ฯ
                            ให้หมดเสียก่อน ถ้าข้าพเจ้า</span>
                    </div>
                    <div class="line-margin">
                        <span
                            style="display: inline-block; padding-right: 4px;padding-left: 60px;">ไม่จัดการชำระหนี้ให้หมดตามที่กล่าวข้างต้น
                            ข้าพเจ้า</span>
                    </div>
                    <div class="line-margin">
                        <span style="display: inline-block; padding-right: 4px;padding-left: 60px;">ยินยอมให้บริษัท
                            สยามคูโบต้าคอร์ปอเรชั่น จำกัด หรือ</span>
                    </div>
                    <div class="line-margin">
                        <span style="display: inline-block; padding-right: 4px;padding-left: 60px;">บริษัทในเครือฯ
                            หักเงินรายได้ที่ข้าพเจ้าพึ่งได้รับเพื่อ</span>
                    </div>
                    <div class="line-margin">
                        <span
                            style="display: inline-block; padding-right: 4px;padding-left: 60px;">ชำระหนี้พร้อมดอกเบี้ยต่อสหกรณ์ฯให้เสร็จสิ้นเสียก่อนได้</span>
                    </div>
                </div>
                <!-- Right -->
                <div
                    style="display: inline-block; vertical-align: top;width: 54%;margin-left: -5px;margin-right: -2px; border-left: 1px solid #000000;">
                    <div style="border-bottom: 1px solid #000000;width: 100%;">
                        <div style="font-weight: bold;padding-left: 2px;">
                            <span style="text-decoration: underline;">ส่วนที่ 1 (สำหรับเจ้าหน้าที่)</span>
                            ข้อมูลประกอบการพิจารณาเงินกู้
                        </div>
                        <div style="margin-top: 6px">
                            <span style="display: inline-block; padding-right: 4px;padding-left: 18px;">อัตราค่าจ้าง
                                เดือนละ</span>
                            <span style="display: inline-block;padding-left: 2px;">
								<span style="position: absolute;padding-left: 5px;">
									'.$data["salary_amount"].'
								</span>..........................................บาท</span>
                        </div>
                        <div class="line-margin">
                            <span style="display: inline-block; padding-right: 4px;padding-left: 18px;">PF.+ หุ้น ณ
                                ปัจจุบัน</span>
                            <span style="display: inline-block; padding-left: 8px;"><span style="position: absolute;padding-left: 5px;">'.$data["share_bf"].'
							</span>..........................................บาท</span>
                        </div>
                        <div class="line-margin">
                            <span
                                style="display: inline-block; padding-right: 4px;padding-left: 18px;">รวมเงินกู้ทุกประเภท
                                ณ ปัจจุบัน.............................บาท</span>
                        </div>
                    </div>
                    <div style="border-bottom: 1px solid #000000;width: 100%;">
                        <div style="font-weight: bold;padding-left: 2px;">
                            <span style="text-decoration: underline;">ส่วนที่ 2 (สำหรับคณะกรรมการ)</span>
                            ความเห็นคณะกรรมการเงินกู้
                        </div>
                        <div style="margin-top: 10px">
                            <span style="display: inline-block;padding-left: 18px;transform: translateY(2px);"><input
                                    type="checkbox" /></span>
                            <span style="display: inline-block; padding-right: 4px;">อนุมัติตามที่ขอ</span>
                            <span style="display: inline-block; transform: translateY(2px);"><input
                                    type="checkbox" /></span>
                            <span
                                style="display: inline-block; padding-right: 8px;">อนุมัติในวงเงิน....................บาท</span>
                            <span style="display: inline-block; transform: translateY(2px);"><input
                                    type="checkbox" /></span>
                            <span style="display: inline-block; padding-right: 4px;">ไม่อนุมัติ</span>
                        </div>
                        <div style="padding-left: 2px;margin-top: 16px;">
                            <span>ลงชื่อ..............................................................................ประธาน/
                                ผู้จัดการ</span>
                        </div>
                    </div>
                    <div style="border-bottom: 1px solid #000000;width: 100%;">
                        <div
                            style="font-weight: bold;padding-left: 2px;color: red;background-color: yellow;margin-right: 8px;margin-top: 1px;padding-bottom: 2px;">
                            <span style="text-decoration: underline;">ส่วนที่ 3 (สำหรับสมาชิก)</span>
                            (กรอกให้ครบทุกช่อง)
                        </div>
                        <div style="padding-left: 2px;margin-top: 6px;">
                            <span style="display: inline-block;">ข้าพเจ้า</span>
                            <span
                                style="display: inline-block;width: 256px;padding-right: 4px; border: 1px dashed blue;">
								'.'&nbsp;'.'
								<span style="position: absolute;">
									'.$data["full_name"].'
								</span>
                            </span>
                            <span style="display: inline-block;">ได้รับเงินกู้ </span>
                        </div>
                        <div class="line-margin" style="padding-left: 2px;">
                            <span style="display: inline-block;">จำนวน</span>
                            <span
                                style="display: inline-block;width: 80px;padding-right: 2px; border: 1px dashed blue;margin-left: 4px;">
									'.'&nbsp;'.'
									<span style="position: absolute;">
										'.number_format($data["request_amt"],2).'
									</span>
                            </span>
                            <span style="display: inline-block;">บาท </span>
                            <span
                                style="display: inline-block;width: 247px;padding-right: 2px; border: 1px dashed blue;position: relative;">
									'.'&nbsp;'.'
									<span style="position: absolute;">
										'.$lib->baht_text($data["request_amt"]).'
									</span>
                                <span
                                    style="font-size: 10pt;position: absolute; bottom: 386px;right: 5px;color: red;line-height: 10pt;">ตัวอักษร</span>
                            </span>
                        </div>
                        <div class="line-margin" style="padding-left: 2px;">
                            <span style="display: inline-block;">ในวันที่</span>
                            <span
                                style="display: inline-block;width: 80px;padding-right: 2px; border: 1px dashed blue;margin-left: 5px;">
									'.'&nbsp;'.'
									<span style="position: absolute;">
										'.$data["receive_date"].'
									</span>
                            </span>
                            <span style="display: inline-block;"> ธนาคาร </span>
                            <span style="display: inline-block;padding-left: 4px;transform: translateY(2px);">'.($data["bank_code"] == "002" ? '<input type="checkbox" checked />' : '<input type="checkbox" />').'</span>
                            <span style="display: inline-block; padding-right: 4px;">กรุงเทพ</span>
                            <span style="display: inline-block;padding-left: 4px;transform: translateY(2px);">'.($data["bank_code"] == "004" ? '<input type="checkbox" checked />' : '<input type="checkbox" />').'</span>
                            <span style="display: inline-block; padding-right: 4px;">กสิกร</span>
                            <span style="display: inline-block;padding-left: 4px;transform: translateY(2px);">'.($data["bank_code"] == "014" ? '<input type="checkbox" checked />' : '<input type="checkbox" />').'</span>
                            <span style="display: inline-block; padding-right: 4px;">ไทยพาณิชย์</span>
                        </div>
                        <div style="padding-left: 2px;">
                            <span style="display: inline-block;padding: 0px 34px;">เลขที่บัญชี</span>
                            <span
                                style="display: inline-block;width: 276px;padding-right: 2px; border: 1px dashed blue;margin-left: 5px;transform: translateY(4);">
									'.'&nbsp;'.'
									<span style="position: absolute;">
										'.$data["deptaccount_no_bank"].'
									</span>
                            </span>
                        </div>
                    </div>
                    <div style="border-bottom: 1px solid #000000;width: 100%;">
                        <div style="font-weight: bold;padding-left: 4px;color: red;margin-right: 8px;font-size: 17pt;padding: 4px 0">
                            <span>ผู้รับเงิน......................................(กรุณาเซ็นชื่อ)</span>
                        </div>
                    </div>
                    <div style="width: 100%;">
                        <div style="padding-left: 2px;margin: 12px 0px;">
                            <span style="display: inline-block;">เจ้าหน้าที่สหกรณ์............................................................................................</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- footer -->
            <div style="width: 100%;padding-top: 8px;">
                <!-- Left -->
                <div style="display: inline-block; vertical-align: top; width: 46%;">
                    <div class="line-margin">
                        <span>ชื่อ............................................................ผู้กู้</span>
                    </div>
                    <div class="line-margin">
                        <span
                            style="padding-left: 12px;">(..........................................................)</span>
                    </div>
                    <div class="line-margin">
                        <span style="padding-left: 12px;">ตำแหน่ง..............................................</span>
                    </div>
                    <div class="line-margin">
                        <span style="padding-left: 12px;font-weight: bold;">ผู้กู้ระดับ ผชส. หรือ บ. 3 ขึ้นไป
                            ไม่ต้องเซ็นชื่อพยาน<span>
                    </div>
                </div>
                <!-- Right -->
                <div
                    style="display: inline-block; vertical-align: top;width: 54%;margin-left: -5px;margin-right: -2px;">
                    <div class="line-margin">
                        <span>ชื่อ............................................................พยาน (ผชส./บ.3 ขึ้นไป
                            )</span>
                    </div>
                    <div class="line-margin">
                        <span
                            style="padding-left: 12px;">(..........................................................)</span>
                    </div>
                    <div class="line-margin">
                        <span style="padding-left: 12px;">ตำแหน่ง..............................................</span>
                    </div>
                </div>
            </div>
            <!-- footer 3 -->
			<div style="padding-right: 10px;position: absolute; bottom: 0;padding-bottom: 2px;width: 100%;">
				<!-- footer 2 -->
				<div style="width: 100%;background-color: yellow;padding-top: 5px;padding-left: 4px;padding-bottom: 4px;">
					<!-- Left -->
					<div style="display: inline-block; vertical-align: top;">
						<div>
							<span style="font-weight: bold;text-decoration: underline;">หมายเหตุ</span>
						</div>
					</div>
					<!-- Right -->
					<div style="display: inline-block; vertical-align: top;">
						<div style="font-weight: bold;color: red;">
							<div style="display: inline-block; vertical-align: top;padding-left: 8px;">
								<div>
									ฝั่งอมตะซิตี้
								</div>
								<div>
									ฝั่งนวนคร
								</div>
							</div>
							<div class="line-margin" style="display: inline-block; vertical-align: top;padding-left: 8px;">
								<div>
									ส่งเอกสารภายในวันอังคาร
								</div>
								<div>
									ส่งเอกสารภายในวันพุธ
								</div>
							</div>
							<div class="line-margin" style="display: inline-block; vertical-align: top;padding-left: 8px;">
								<div>
									ได้รับเงินวันศุกร์
								</div>
								<div>
									ได้รับเงินวันศุกร์
								</div>
							</div>
						</div>
						<div class="line-margin" style="font-weight: bold;">
							(กรุณากรอกส่วนที่ 3 เพื่อสะดวกต่อการโอนเงินเข้าบัญชี (หากไม่ไช่บัญชีเงินเดือน
							กรุณาแนบหน้าสมุดบัญชีมาด้วย)
						</div>
					</div>
				</div>
				<div
					style="border-top: solid 2px #000000;border-left: solid 2px #000000;border-right: solid 2px #000000;padding-left: 8px;">
					หนังสือกู้เลขที่ ฉ.......................................
				</div>
            </div>
        </div>
    </div>
	';
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
	$pathfile_show = '/resource/pdf/request_loan/'.$data["requestdoc_no"].'.pdf';
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