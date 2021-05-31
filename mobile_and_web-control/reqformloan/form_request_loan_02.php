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
					font-size: 13pt;
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
				.padding-text {
					padding-bottom:0px;
				}
			</style>';
	$html .= '
	 <div>
        <img src="../../resource/logo/logo.jpg" style="width:80px;position: absolute;left: 250px;" />
        <div style="display: flex;text-align: center;">
            <div style="text-align:center;width:100%;">
                <div style="font-weight: bold;font-size: 14pt;">
                    คำขอกู้เงินสามัญ
                </div>
            </div>
        </div>
        <div>
            <div style="width: 100%;padding-top: 24px;">
                <div style="display: inline-block; width: 50%;">
                </div>
                <span style="display: inline-block;">วันที่</span>
                <span
                    style="display: inline-block;margin-left: 16px;width: 100px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;">
					'.'&nbsp;'.'
					<span style="position: absolute;width: 130px;">
						'.$today.'
					</span>
                </span>
            </div>
            <div class="padding-text" style="font-weight: bold;">
					เรียน<span style="margin-left: 16px">คณะกรรมการดำเนินการสหกรณ์ออมทรัพย์พนักงานสยามคูโบต้า จำกัด</span>
            </div>
            <div  class="line-margin" style="padding-left: 64px; margin-top: 4px;">
                <span class="padding-text" style="display: inline-block;padding-right: 4px">ข้าพเจ้า ชื่อ</span>
                <span class="padding-text"style="display: inline-block;width: 130px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;">
						'.'&nbsp;'.'
						<span style="position: absolute;width: 130px;">
							'.$data["memb_prename"].$data["memb_name"].'
						</span>
                </span>
                <span class="padding-text" style="display: inline-block; padding-left: 20px; padding-right: 20px;">นามสกุล</span>
                <span class="padding-text" style="display: inline-block;width: 150px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;">
						'.'&nbsp;'.'
						<span style="position: absolute;width: 130px;">
							'.$data["memb_surname"].'
						</span>
                </span>
                <span class="padding-text" style="display: inline-block; padding-left: 4px; padding-right: 4px;">เลขทะเบียนสมาชิก</span>
                <span class="padding-text" style="display: inline-block;width: 65px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;">
					'.'&nbsp;'.'
					<span style="position: absolute;width: 130px;">
						'.$data["member_no"].'
					</span>
                </span>
            </div>
            <div  class="line-margin">
                <span class="padding-text" style="display: inline-block; padding-right: 20px;">เลขประจำตัวพนักงาน</span>
                <span class="padding-text" style="display: inline-block;width: 66px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;">
					'.'&nbsp;'.'
					<span style="position: absolute;width: 130px;">
						'.$data["emp_no"].'
					</span>
                </span>
                <span class="padding-text" style="display: inline-block; padding-left: 20px; padding-right: 20px;">สังกัดหน่วยงาน</span>
                <span class="padding-text" style="display: inline-block;width: 140px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;position: relative;">
					'.'&nbsp;'.'
                </span>
                <span class="padding-text" style="display: inline-block; padding-left: 5px; padding-right: 5px;">ส่วน</span>
                <span class="padding-text" style="display: inline-block;width: 170px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;">
					'.'&nbsp;'.'
					<span style="position: absolute;width: 130px;">
						'.$data["position"].'
					</span>
                </span>
            </div>
            <div  class="line-margin">
                <span class="padding-text" style="display: inline-block; padding-right: 20px;">โทรศัพท์มือถือ</span>
                <span class="padding-text" style="display: inline-block;width: 100px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;">
					'.'&nbsp;'.'
					<span style="position: absolute;width: 130px;">
						'.$data["tel"].'
					</span>
                </span>
                <span class="padding-text" style="display: inline-block; padding-left: 20px; padding-right: 20px;">คู่สมรสชื่อ(ถ้ามี)</span>
                <span class="padding-text" style="display: inline-block;width: 284px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;">
					'.'&nbsp;'.'
					<span style="position: absolute;width: 130px;">
						'.$data["married"].'
					</span>
                </span>
            </div>
            <div  class="line-margin" style="font-weight: bold;">
				<span class="padding-text">ขอเสนอคำขอกู้เงินสามัญ เพื่อคณะกรรมการดำเนินการสหกรณ์ฯ โปรดพิจารณาดังนี้</span>
            </div>
            <div  class="line-margin" style="margin-top: 4px;">
                <span class="padding-text" style="display: inline-block; padding-right: 20px;">ข้อ 1. ข้าพเจ้าขอกู้เงินสหกรณ์ฯ จำนวน</span>
                <span class="padding-text" style="display: inline-block;width: 96px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;">
						'.'&nbsp;'.'
						<span style="position: absolute;width: 130px;">
							'.number_format($data["request_amt"],2).'
						</span>
                </span>
                <span class="padding-text" style="display: inline-block; padding-right: 10px;">บาท</span
                <span class="padding-text" style="display: inline-block;width: 266px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;margin-left: 16px;">
						'.'&nbsp;'.'
						<span style="position: absolute;width: 130px;">
							'.$lib->baht_text($data["request_amt"]).'
						</span>
                </span>
                <span class="padding-text" style="display: inline-block; padding-right: 4px;">ตัวอักษร</span
            </div>
            <div  class="line-margin">
                <span class="padding-text" style="display: inline-block; padding-right: 4px;padding-left: 32px;">
						โดยจะนำไปใช้เพื่อ (ชี้แจงเหตุจำเป็นในการกู้เงิน)
                </span>
                <span class="padding-text" style="display: inline-block;width: 266px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;margin-left: 100px;">
					'.'&nbsp;'.'
					<span style="position: absolute;width: 130px;">
						'.$data["objective"].'
					</span>
                </span>
            </div>
            <div  class="line-margin">
                <span class="padding-text" style="display: inline-block; padding-right: 4px;">ข้อ 2. ถ้าข้าพเจ้าได้รับเงินกู้แล้ว
						ข้าพเจ้าจะขอส่งเงินต้นคืนเป็นงวดรายเดือน ในอัตรางวดละ</span>
                <span class="padding-text" style="display: inline-block;width: 140px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;margin-left: 49px;">
						'.'&nbsp;'.'
						<span style="position: absolute;width: 130px;">
							'.$data["period_payment"].'
						</span>
                </span>
                <span class="padding-text" style="display: inline-block;">บาท</span>
            </div>
            <div class="line-margin">
                <span  class="padding-text" style="display: inline-block; padding-right: 46px;padding-left: 32px;">พร้อมดอกเบี้ยตามอัตราที่สหกรณ์ฯ กำหนด เป็นจำนวนรวม
				</span>
                <span class="padding-text" style="display: inline-block;width: 60px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;">
						'.'&nbsp;'.'
						<span style="position: absolute;width: 130px;">
							'.$data["period"].'
						</span>
                </span>
                <span class="padding-text" style="display: inline-block; padding-right: 4px;">งวด (สูงสุด 72 งวด
                    แต่ไม่เกินอายุงานของปีที่เกษียณอายุ,</span>
            </div>
            <div  class="line-margin">
                <span class="padding-text" style="display: inline-block; padding-right: 4px;padding-left: 32px;">สำหรับโครงการพิเศษ
                    ระยะเวลาขึ้นอยู่กับเงื่อนไขของโครงการนั้นๆ แต่ต้องแต่ไม่เกินอายุงานของปีที่เกษียณอายุนับถึงวันที่ 31
                    ธันวาคม)</span>
            </div>
            <div  class="line-margin" style="margin-top: 4px;">
                <span class="padding-text" style="display: inline-block;">ข้อ 3.</span>
                <span class="padding-text" style="display: inline-block;transform: translateY(2px);"><input type="checkbox" /></span>
                <span class="padding-text" style="display: inline-block; padding-right: 55px;">ข้าพเจ้า<span
                        style="font-weight: bold;text-decoration: underline;">ต้องการ</span>ที่จะให้ทางสหกรณ์ฯ
                    Refinance‎ สัญญาเลขที่</span>
                <span class="padding-text" style="display: inline-block;width: 200px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;">
						'.'&nbsp;'.'
						<span style="position: absolute;width: 130px;">
							'.$data["contract_no"].'
						</span>
                </span>
            </div>
            <div class="line-margin">
                <span class="padding-text" style="display: inline-block;transform: translateY(2px);padding-left: 32px;"><input
                        type="checkbox" /></span>
                <span class="padding-text" style="display: inline-block; padding-right: 4px;">ข้าพเจ้า<span
                        style="font-weight: bold;text-decoration: underline;">ต้องการ</span>ที่จะเปิดสัญญาเงินกู้ในข้อ
                    1. ใหม่</span>
            </div>
            <div  class="line-margin" style="margin-top: 4px;display: block;">
				<span class="padding-text" style="display: inline-block;">ข้อ 4.</span>
				<span class="padding-text" style="display: inline-block;">ข้าพเจ้าต้องการรับกู้เงินกับทางสหกรณ์ฯ</span>
				<span class="padding-text" style="display: inline-block;transform: translateY(2px);margin-left: 30px;"><input type="checkbox" /></span>
				<span class="padding-text" style="display: inline-block; padding-right: 4px;font-size: 11pt;">ในรอบวันที่ 15 ของเดือน(ส่งเอกสาร
						ภายในวันที่ 30 ของเดือน) นัดเซ็นสัญญาภายในวันที่ 12 ของเดือน</span>
            </div>
			<div  class="line-margin" style="margin-top: 4px;display: block;padding-left: 260px;">
                    <div class="padding-text">
                        <span style="display: inline-block;transform: translateY(2px);"><input type="checkbox" /></span>
                        <span style="display: inline-block; padding-right: 4px;font-size: 11pt;"">ในรอบวันที่ 28 ของเดือน(ส่งเอกสาร
								ภายในวันที่ 15 ของเดือน) นัดเซ็นสัญญาภายในวันที่ 25 ของเดือน</span>
                    </div>
			</div>
            <div  class="line-margin">
                <span class="padding-text" style="display: inline-block;">โอนเงินเข้า</span>
				<span class="padding-text" style="display: inline-block;transform: translateY(2px);">'.($data["bank_code"] == "002" ? '<input type="checkbox" checked />' : '<input type="checkbox" />').'
				</span>
				<span class="padding-text" style="display: inline-block; padding-right: 4px;">ธนาคารกรุงเทพ</span>
            </div>
            <div class="line-margin" style="padding-left: 56px;">
				<span class="padding-text" style="display: inline-block;transform: translateY(2px);">'.($data["bank_code"] == "004" ? '<input type="checkbox" checked />' : '<input type="checkbox" />').'</span>
				<span class="padding-text" style="display: inline-block; padding-right: 4px;">ธนาคารกสิกรไทย</span>
				
					<span class="padding-text" style="display: inline-block; padding-right: 40px; padding-right: 16px;">เลขที่บัญชี</span>
					<span class="padding-text" style="display: inline-block;width: 120px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;">
						'.'&nbsp;'.'
						<span style="position: absolute;width: 130px;">
							'.$data["deptaccount_no_bank"].'
						</span>
					</span>
					<span class="padding-text" style="display: inline-block; padding-right: 4px;">หากไม่ใช่บัญชีเงินเดือน กรุณาแนบหน้า
						Bookbank (ต้องเป็นบัญชีผู้กู้)</span>
			</div>
            <div class="line-margin" style="padding-left: 56px;">
				<span class="padding-text" style="display: inline-block;transform: translateY(2px);">'.($data["bank_code"] == "014" ? '<input type="checkbox" checked />' : '<input type="checkbox" />').'</span>
				<span class="padding-text" style="display: inline-block; padding-right: 4px;">ธนาคารไทยพาณิชย์</span>
			</div>
			<div style="height: 550px;">
			</div>
			<div style="position: absolute; bottom: 0px;">
            <div style="width: 100%; display: table;">
                <!-- Left -->
                <div style="display: table-cell; vertical-align: top; border-right: 1px solid #000000; width: 55%;">
                    <div style="border-bottom: 1px solid #000000;border-top: 1px solid #000000; border-left: 1px solid #000000; width: 100%;">
                        <div style="font-weight: bold;padding-left: 2px;background-color: yellow;margin-top: 1px;">
                            <span style="text-decoration: underline;">กรุณากรอกข้อมูลตรวจสอบหลักฐานก่อนการยื่นกู้</span>
							<span style="color: red;text-decoration: underline;"> (สำหรับสมาชิก) </span>
                        </div>
                        <div style="padding-left: 5px;">
                            <span style="display: inline-block;"><span
                                    style="font-weight: bold;text-decoration: underline;">กรณีที่ 1 </span>
                                กู้ไม่เกินทุนเรือนหุ้นหรือไม่เกินเงินออม</span>
                        </div>
						<div style="padding-top: 6px;">
							<div style="padding-left: 5px;">
									<div>
										<span style="display: inline-block;padding-right: 150px;">
											1. คำขอกู้เงินสามัญ 1 ชุด
										</span>
										<span style="display: inline-block;transform: translateY(2px);"><input
												type="checkbox" /></span>
										<span style="display: inline-block; padding-left: 16px;">ครบถ้วน</span>
									</div>
									<div>
										<span style="display: inline-block;">
											2. สำเนาบัตรประชาชนผู้กู้ 1 ใบ (ต้องแนบทุกครั้ง)
										</span>
										<span style="display: inline-block;transform: translateY(2px);margin-left: 32px;"><input
												type="checkbox" /></span>
										<span style="display: inline-block; padding-left: 16px;">ครบถ้วน</span>
									</div>
							</div>
                        </div>
                        <div style="padding-left: 5px;">
                            <span style="display: inline-block;"><span style="font-weight: bold;text-decoration: underline;">กรณีที่ 2 </span>
                                กู้เกินทุนเรือนหุ้นหรือเกินเงินออมต้องเปิดบัญชีค้ำเงินกู้</span>
                        </div>
                        <div style="display: table;width: 100%;">
                            <div style="display: table-cell;width: 50%;border-right: 1px solid #5B9BD5;">
                                <div class="line-margin" style="text-align: center;border-top: 1px solid #5B9BD5;border-bottom: 1px solid #5B9BD5;">
                                    ไม่เคยเปิดบัญชี
                                </div>
                                <div class="line-margin" style="white-space: nowrap;padding-left: 5px;">
                                    <div style="display: inline-block;width: 90%;">
                                        1. คำขอกู้เงินสามัญ 1 ชุด
                                    </div>
                                    <div style="display: inline-block;width: 10%;text-align: right;">
                                        <input type="checkbox" style="transform: translateY(3px);" />
                                    </div>
                                </div>
                                <div class="line-margin" style="white-space: nowrap;padding-left: 5px;">
                                    <div style="display: inline-block;width: 90%;">
                                        2. สำเนาบัตรประชาชนผู้กู้ 3 ใบ
                                    </div>
                                    <div style="display: inline-block;width: 10%;text-align: right;">
                                        <input type="checkbox" style="transform: translateY(3px);" />
                                    </div>
                                </div>
                                <div class="line-margin" style="white-space: nowrap;padding-left: 5px;">
                                    <div style="display: inline-block;width: 90%;">
                                        3. เงินเปิดบัญชี 2,000 บาท
                                    </div>
                                    <div style="display: inline-block;width: 10%;text-align: right;">
                                        <input type="checkbox" style="transform: translateY(3px);" />
                                    </div>
                                </div>
                                <div class="line-margin" style="white-space: nowrap;padding-left: 5px;">
                                    <div style="display: inline-block;width: 90%;">
                                        4. สำเนาทะเบียนบ้านผู้กู้ 1 ใบ
                                    </div>
                                    <div style="display: inline-block;width: 10%;text-align: right;">
                                        <input type="checkbox" style="transform: translateY(3px);" />
                                    </div>
                                </div>
                                <div class="line-margin" style="white-space: nowrap;padding-left: 5px;">
                                    (เปิดบัญชีวันเซ็นสัญญา)
                                </div>
                            </div>
                            <div style="display: table-cell;width: 50%;margin-right: 3px;margin-left: -4px;">
                                <div class="line-margin" style="text-align: center;border-top: 1px solid #5B9BD5;border-bottom: 1px solid #5B9BD5;">
                                    เคยเปิดบัญชีแล้ว
                                </div>
                                <div class="line-margin" style="white-space: nowrap;padding-left: 5px;">
                                    <div style="display: inline-block;width: 80%;">
											1. คำขอกู้สามัญ 1 ชุด
                                    </div>
                                    <div style="display: inline-block;width: 20%;text-align: right;">
                                        <input type="checkbox" style="transform: translateY(3px);" />
                                    </div>
                                </div>
                                <div class="line-margin" style="white-space: nowrap;padding-left: 5px;">
                                    <div style="display: inline-block;width: 80%;">
											2.สำเนาบัตรประชาชนผู้กู้ 1ใบ
                                    </div>
                                    <div style="display: inline-block;width: 20%;text-align: right;">
                                        <input type="checkbox" style="transform: translateY(3px);" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="border-bottom: 1px solid #000000;border-left: 1px solid #000000;width: 100%;">
                        <div style="padding-left: 2px;background-color: yellow;">
                            <span style="font-weight: bold;color: red;text-decoration: underline;">กรุณากรอกข้อมูล (สำหรับสมาชิก)</span>
                        </div>
                        <div class="line-margin" style="padding-left: 2px;">
                            ท่านต้องการเข้าร่วมโครงการดอกเบี้ยพิเศษหรือไม่
                        </div>
                        <div class="line-margin" style="display: table;white-space: nowrap;width: 100%;">
                            <div style="display: table-cell;width: 50%;">
                                <span style="display: inline-block;transform: translateY(2px);padding-left: 36px;">
								'.($data["extra_credit_project"] ? 
									'<input checked type="checkbox" />' 
									: '<input type="checkbox" />').'
                                </span>
                                <span style="display: inline-block; padding-left: 16px;">เข้าโครงการพิเศษ</span>
                            </div>
                            <div style="display: table-cell;width: 50%;">
                                <span style="display: inline-block;transform: translateY(2px);">
								'.($data["extra_credit_project"] ? 
									'<input type="checkbox" />' 
									: '<input checked type="checkbox" />').'
								</span>
                                <span style="display: inline-block; padding-left: 16px;">ไม่เข้าโครงการพิเศษ</span>
                            </div>
                        </div>
                        <div class="line-margin" style="padding-left: 2px;">
                            กรณีเข้าร่วมโครงการพิเศษกรุณาแนบหนังสือรับรองขอสินเชื่อพิเศษ
                        </div>
                        <div class="line-margin" style="padding-left: 2px;">
                            และเอกสารประกอบการเข้าโครงการตามเงื่อนไขในหนังสือรับรองขอเข้าโครงการ
                        </div>
                    </div>
                    <div style="width: 100%;">
                        <div style="margin-top: 24px;">
                            <span class="line-margin">ลงชื่อ............................................................ผู้กู้</span>
                        </div>
                        <div>
                            <span class="line-margin">ตำแหน่ง......................................................</span>
                        </div>   
                        <div style="margin-top: 24px;">
                            <span class="line-margin">ลงชื่อ............................................................พยาน (ผชส./บ.3 ขึ้นไป)</span>
                        </div>
                        <div>
                            <span class="line-margin" style="padding-left: 16px;">(</span><span style="padding-left: 170px;">)</span>
                        </div>
                        <div class="line-margin" style="font-weight: bold;">
                            หมายเหตุ  : ผู้กู้ระดับ ผชส. หรือ บ. 3 ขึ้นไป ไม่ต้องเซ็นชื่อพยาน
                        </div>
                    </div>
                </div>
                <!-- Right -->
                <div
                    style="display: table-cell;margin-left: -4px;margin-right: -2px;width: 45%;">
                    <div style="border-bottom: 1px solid #000000;border-top: 1px solid #000000;width: 100%;">
                        <div style="font-weight: bold;padding-left: 2px;">
                            <span style="text-decoration: underline;">ส่วนที่ 1</span> ข้อมูลประกอบการพิจารณาเงินกู้ (สำหรับเจ้าหน้าที่)
                        </div>
                        <div>
                            <span class="line-margin" style="display: inline-block;">วงเงินที่ขอกู้
								<span style="position: absolute;padding-left: 5px;">
									'.number_format($data["request_amt"],2).'
								</span>
							</span>
							<span class="line-margin" style="display: inline-block;">
								..........................
							</span>
							<span class="line-margin" style="display: inline-block; padding-right: 4px;">บาท</span>
                        </div>
                        <div>
                            <span class="line-margin" style="display: inline-block; padding-right: 4px;padding-left: 36px;font-weight: bold;text-decoration: underline;">หลักประกันการกู้</span>
                        </div>
                        <div class="line-margin">
                            <div style="display: inline-block; padding-right: 2px;padding-left: 2px;width: 50%;">ทุนเรือนหุ้น</div>
                            <div style="display: inline-block; padding-right: 2px;padding-left: 2px;text-align: right;width: 46%;">=.........................บาท<span style="position: absolute;left: 685px;text-align: left;">
									'.$data["share_bf"].'
								</span>
							</div>
                        </div>
                        <div class="line-margin">
                            <div style="display: inline-block; padding-right: 2px;padding-left: 2px;width: 50%;">เงินสะสมกองทุนสำรองเลี้ยงชีพ </div>
                            <div style="display: inline-block; padding-right: 2px;padding-left: 2px;text-align: right;width: 46%;">=.........................บาท<span style="position: absolute;left: 685px;text-align: left;">
								'.$data["share"].'
							</span></div>
                        </div>
                        <div class="line-margin">
                            <span style="display: inline-block; padding-right: 2px;padding-left: 2px;padding-top: 20px;">(ส่วนของพนักงาน)ณ <span style="position: absolute;padding-left: 5px;">
									'.$data["emp_part"].'
								</span></span><span>.............................</span>
                        </div>
                        <div class="line-margin">
                            <span style="display: inline-block; padding-right: 2px;text-indent: 36px;width: 50%;">รวมหลักประกัน</span>
                            <span style="display: inline-block; padding-right: 2px;padding-left: 2px;text-align: right;width: 46%;">=.........................บาท<span style="position: absolute;left: 685px;text-align: left;">
								'.$data["insure"].'
							</span></span>
                        </div>
                        <div class="line-margin">
                            <span style="display: inline-block; padding-right: 4px;padding-left: 36px;font-weight: bold;text-decoration: underline;">หนี้ค้างชำระของผู้กู้</span>
                        </div>
                        <div class="line-margin">
                            <span style="display: inline-block; padding-right: 2px;padding-left: 2px;">1. เงินกู้ฉุกเฉิน ..........................บาท</span>
                        </div>
                        <div class="line-margin">
                            <span style="display: inline-block; padding-right: 2px;padding-left: 2px;">2. เงินกู้สามัญชำระงวดที่ ..................คงเหลือ....................บาท</span>
                        </div>
                        <div class="line-margin" style="padding-top: 35px;padding-bottom: 21px;">
                            <span style="display: inline-block; padding-right: 2px;padding-left: 2px;width: 50%;">รวมหนี้ค้างชำระทั้งหมด</span>
                            <span style="display: inline-block; padding-right: 2px;padding-left: 2px;text-align: right;width: 46%;">=.........................บาท</span>
                        </div>
                    </div>
                    <div style="width: 100%;">
                        <div style="font-weight: bold;padding-left: 2px;">
                            <span style="text-decoration: underline;">ส่วนที่ 2</span> การพิจารณาของคณะกรรมการเงินกู้
                        </div>
                        <div>
                            <span style="display: inline-block; padding-right: 4px;padding-left: 36px;font-weight: bold;text-decoration: underline;">ความเห็นคณะกรรมการเงินกู้</span>
                        </div>
                        <div style="padding-top: 8px">
                            <span style="display: inline-block;transform: translateY(2px);padding-left: 36px;"><input
                                    type="checkbox" /></span>
                            <span style="display: inline-block; padding-left: 8px;">อนุมัติตามที่ขอ</span>
                        </div>
                        <div>
                            <span style="display: inline-block;transform: translateY(2px);padding-left: 36px;"><input
                                    type="checkbox" /></span>
                            <span style="display: inline-block; padding-left: 8px;">อนุมัติในวงเงิน..........................บาท  โดยหักหนี้แล้ว
                            </span>
                        </div>
                        <div>
                            <span style="display: inline-block;transform: translateY(2px);padding-left: 36px;"><input
                                    type="checkbox" /></span>
                            <span style="display: inline-block; padding-left: 8px;">ไม่อนุมัติ</span>
                            <span style="display: inline-block; padding-left: 16px;">เหตุผล.................................................</span>
                        </div>
                        <div style="margin-top: 24px;padding-left: 36px;">
                            <span>ลงชื่อ............................................................ผู้อนุมัติ</span>
                        </div>
                        <div style="padding-left: 90px;font-weight: bold;">
                            <span>ประธานคณะกรรมการเงินกู้</span>
                        </div>
                        <div style="font-weight: bold;padding-left: 2px;">
                            ตามมติคณะกรรมการเงินกู้ครั้งที่.............  วันที่.............
                        </div>
                    </div>
                </div>
            </div>
            </div>
            <!-- Page 2 -->
            <div style="width: 100%;padding-top: 30px;">
                <div class="line-margin" style="font-weight: bold;">
                    คำยินยอมของคู่สมรส (ใช้เฉพาะกรณีที่ผู้ขอกู้มีคู่สมรส)
                </div>
                <div class="line-margin" style="margin-top: 24px;">
                    <span  style="display: inline-block;">ข้าพเจ้านาย/ นาง/นางสาว</span>
                    <span
                        style="display: inline-block;width: 140px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;margin-left: 16px;">
						'.'&nbsp;'.'
						<span style="position: absolute;width: 130px;">
							'.$data["married"].'
						</span>
                    </span>
                </div>
                <div class="line-margin">
                    ยินยอมให้ผู้กู้ กู้เงินจากสหกรณ์ออมทรัพย์พนักงานสยามคูโบต้า จำกัด ตามหนังสือขอกู้ข้างต้น
                </div>
                <div class="line-margin" style="padding-left: 55%;margin-top: 24px;">
                    ลงชื่อ....................................................... คู่สมรส
                </div>
                <div class="line-margin" style="padding-left: 55%;">
                    <span  style="display: inline-block;">วันที่</span>
                    <span
                        style="display: inline-block;width: 155px;padding-left: 4px;padding-right: 4px; border: 1px dashed blue;margin-left: 8px;">
						'.'&nbsp;'.'
						<span style="position: absolute;width: 130px;">
							'.$today.'
						</span>
                    </span>
                </div>
                <div class="line-margin" style="margin-top: 24px;">
                    *** โปรดแนบสำเนาบัตรประชาชนของคู่สมรส  พร้อมลงชื่อรับรองสำเนาถูกต้อง
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