<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

$channal = "Mobile";

$html = '
<style>

@font-face {
  font-family: panasonic;
  src: url(ji-milady.ttf);
}
@font-face {
    font-family: "THSarabun";
    src: url("THSarabun.ttf");
    
}
@font-face {
  font-family: "THSarabun-bold";
  src: url("THSarabun Bold.ttf");
}
* {
  font-family: THSarabun;
}
body {
  padding: 0;
  font-size:16pt
  line-height: 13px;
}
p{
    margin:0
}
.text-detail{
  margin:-3px 0px 0px 0px;
}
.text-head{
  font-size:16pt;
  font-weight:bold;
}
.text-discipt{
  font-size:8pt;
  margin-top:-7px;
  
}
.text-discipt-indent{
  text-indent:70px;
}
.text-center{
  text-align:center;
}
.text-right{
  text-align:right;
}
.font-bold{
  font-weight:bold
}

.checkbox{
  margin-top:7px;
}
table{
  border-collapse: collapse;
}

.border-dotted{
  border-right:1px solid;
  border-bottom:1px dotted  ;
}
td{
  border-right:1px solid;
  font-size:16pt
  height:20px;
  line-height: 13px;
  white-space: normal;
}
border-right-none{
  border-right:none;
}
th{
  line-height:13px;
  border-top:none;
}
.border-bottom-solid{
  border-bottom:1px solid;
}
.border-right-solid{
  border-right:1px solid;
}
.text-indent{
  text-indent:13px;
}

.padding-rihgt{
  padding-right:20px;
}

.checkbox-margin-left{
  margin-left:40px;
}

.nowrap{
  white-space: nowrap;
}

</style>
';



$html .= '  <div style="height:1020px; margin:-20px; -20px -20px -20px ">';

$html .= '
    <div class="text-detail">
      <b> ฉบับที่ 1</b> (สําหรับผู้ถูกหักภาษี ณ ที่จ่าย ใช้แนบพร้อมกับแบบแสดงรายการภาษี) 
    </div>
    <div class="text-detail">
        <b>ฉบับที่ 2</b> (สําหรับผู้ถูกหักภาษี ณ ที่จ่าย เก็บไว้เป็นหลักฐาน)
    </div>
    <div class="text-head" style="text-align:center;">
        หนังสือรับรองการหักภาษี ณ ที่จ่าย
    </div>
    <div class="text-detail" style="text-align:center; margin-top:-5px;">
        50 ทวิ แห่งประมวลรัษฎากร
    </div>
';

$html .= '
 <div  style="position: absolute; right: -18px; top: 45px;" >
    เล่มที่........................... 
 </div>
 <div style="position: absolute; right: -18px; top: 67px;">
    เล่มที่........................... 
 </div>
';


$html .= '
<div style="border:1px solid; width:100%;  border-radius:5px; padding:5px; position:absolute; top:97px; ">
';
$html .= '
      <div class="text-detail font-bold">
        ผู้มีหน้าที่หักภาษี ณ ที่จ่าย :
      </div>
      <div class="font-bold" style="position: absolute; right:3px; top:0px;">
          เลขประจําตัวผู้เสียภาษีอากร (13) หลัก*.................................................................................................
      </div>
      <div class="text-detail font-bold" style="margin-top:5px; ">
          ชื่อ............................................................................................................................................เลขประจําตัวผู้เสียภาษีอากร.............................................................................
      </div>
      <div class="text-discipt text-discipt-indent" style="width:410px;">
        (ให้ระบุว่าเป็น บุคคล นิติบุคคล บริษัท สมาคม หรือคณะบุคคล)
      </div>

      <div class="text-detail font-bold">
        ที่อยู่...................................................................................................................................................................................................................................................................
      </div>
      <div class="text-discipt text-discipt-indent">
        (ให้ระบุ ชื่ออาคาร/หมู่บ้าน ห้องเลขที่ ชั้นที่ เลขที่ ตรอก/ซอย หมู่ที่ ถนน ตําบล/แขวง อําเภอ/เขต จังหวัด)
      </div>
  ';

// ข้อมูล ผู้มีหน้าที่หักภาษี ณ ทีจ่าย -> เลขประจําตัวผู้เสียภาษีอากร | ชื่อ | เลขประจําตัวผู้เสียภาษีอากร | ที่อยู่ 
$html .= '
      <div style="position:absolute;   right:10px; top:-2px; width:235px;" class="font-bold">
        0994000167008
      </div>
      <div style="position:absolute;   left:33px; top:29px; width:235px;" class="font-bold">
         สหกรณ์ออมทรัพย์มหาวิทยาลัยมหิดล จำกัด
      </div>
      <div style="position:absolute;   right:5px; top:29px; width:195px; " class="font-bold">
          0994000167008
      </div>
      <div style="position:absolute;   left:33px; top:59px; width:100%;" class="font-bold">
          เลขที่ 2 อาคารศรีสวรินทิรา ชั้น G และ ชั้น 6 ถ.วังหลัง แขวงศิริราช เขตบางกอกน้อย กรุงเทพมหานคร 10700
      </div>
  ';

$html .= '
</div>
';

$html .= '
<div style="border:1px solid; width:100%;  border-radius:5px; padding:5px; position:absolute; top:198px;">';


$html .= '
    <div class="text-detail font-bold ">
      ผู้ถูกหักภาษี ณ ที่จ่าย :
    </div>
    <div style="position: absolute; right:3px; top:0px;" class="font-bold">
      เลขประจําตัวผู้เสียภาษีอากร (13) หลัก*................................................................................................
    </div>
    <div class="text-detail font-bold" style="margin-top:5px;">
      ชื่อ............................................................................................................................................เลขประจําตัวผู้เสียภาษีอากร.............................................................................
    </div>
    <div class="text-discipt text-discipt-indent">
      (ให้ระบุว่าเป็น บุคคล นิติบุคคล บริษัท สมาคม หรือคณะบุคคล)
    </div>

    <div class="text-detail font-bold font-bold">
    ที่อยู่...................................................................................................................................................................................................................................................................
    </div>
    <div class="text-discipt text-discipt-indent">
    (ให้ระบุ ชื่ออาคาร/หมู่บ้าน ห้องเลขที่ ชั้นที่ เลขที่ ตรอก/ซอย หมู่ที่ ถนน ตําบล/แขวง อําเภอ/เขต จังหวัด)
    </div>
    <hr style="border:0.5px solid;">
    <div style="height:25px;">
      <div style="margin-top:-9px" class="font-bold">
         ลำดับที่ 
      </div>
      <div style="position:absolute; top:96px; left:210px;" class="font-bold">
          ใบแนบ
      </div>
    </div>
    <div style="margin-top:10px; position:absolute; top:120px; left:5px;" class="text-discipt">
      (ให้สามารถอ้างอิงหรือสอบยันกันได้ระหว่างลําดับที่ตามหนังสือรับรองฯ กับแบบยื่นรายการหักภาษีหักที่จ่าย)
    </div>
    ';

// ข้อมูล ผู้ถูกหักภาษี ณ ทีจ่าย -> เลขประจําตัวผู้เสียภาษีอากร | ชื่อ | เลขประจําตัวผู้เสียภาษีอากร | ที่อยู่ 
$html .= '
      <div style="position:absolute;   right:10px; top:-2px; width:235px;" class="font-bold">
          1102000907749
      </div>
      <div style="position:absolute;   left:33px; top:29px; width:235px;" class="font-bold">
          นายอภิเชฏฐ์ ภูนุชอภัย (00044196)
      </div>
      <div style="position:absolute;   right:5px; top:29px; width:195px; " class="font-bold">
          1102000907749
      </div>
      <div style="position:absolute;   left:33px; top:59px; width:100%;" class="font-bold">
          379 หม่บ้าน ทรัพย์จรัญ ซอย แก้วเงินทอง 2 ถ.จรัญสนิทวงศ์ ู แขวงคลองชักพระ เขตตลิ่งชัน กรุงเทพมหานคร 10170
      </div>
  ';


$html .= '
</div>
';



//ใบแนบ
$html .= '
<div style="position:absolute; top:288px; right:0;">
    <input type="checkbox" class="checkbox" >  (1) ภ.ง.ด.1ก  
    <input type="checkbox" class="checkbox">   (2) ภ.ง.ด.1ก พิเศษ  
    <input type="checkbox" class="checkbox">   (3) ภ.ง.ด.2 
    <input type="checkbox" class="checkbox">  (4) ภ.ง.ด.3
</div>
<div style="position:absolute; top:308px; right:91px;">
  <input type="checkbox" class="checkbox" checked > (5) ภ.ง.ด.2ก
  <input type="checkbox" class="checkbox">   (6) ภ.ง.ด.3ก 
  <input type="checkbox" class="checkbox">   (7) ภ.ง.ด.53 
</div>
';
//ตารางการจ่าย
$html .= '
<div style="position:absolute; top:339px; left:-20px; border-left:1px solid; border-right:1px solid; border-top:1px solid;  solid; border-radius:5px; margin-right:20px; ">
   <table >
      <thead>
        <tr>
            <th class="text-center border-bottom-solid border-right-solid" >ประเภทเงินได้พึงประเมินที่จ่าย</th> 
            <th class="text-center border-bottom-solid border-right-solid" style="width:90px;">วัน เดือน  หรือปีภาษี ที่จ่าย</th> 
            <th class="text-center border-bottom-solid border-right-solid" style="width:100px;" >จํานวนเงินที่จ่าย</th> 
            <th class="text-center border-bottom-solid style="width:100px;" ><div>ภาษีที่พัก</div> <div>และนําส่งไว้</div></th>
        </tr>
      </thead>
      <tbody>
        <tr>
            <td>
                1. เงินเดือน ค่าจ้าง เบี้ยเลี้ยง โบนัส ฯลฯ ตามมาตรา 40 (1) 
            </td>
            <td class="border-dotted text-center font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold " style="border-right:none;"></td>
        </tr>
        <tr>
            <td>
              2. ค่าธรรมเนียม ค่านายหน้า ฯลฯ ตามมาตรา 40 (2) 
            </td>
            <td class="border-dotted" font-bold></td>
            <td class="border-dotted text-right padding-rihgt font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold" style="border-right:none;"></td>
      </tr>
      <tr>
            <td>
              3. ค่าแห่งลิขสิทธิ์ ฯลฯ ตามมาตรา 40 (3) 
            </td>
            <td class="border-dotted text-center font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold" ></td>
            <td class="border-dotted text-right padding-rihgt font-bold" style="border-right:none;"></td>
      </tr>
      <tr>
            <td>
              4. (ก) ดอกเบี้ย ฯลฯ ตามมาตรา 40 (4) (ก) 
            </td>
            <td class="border-dotted text-center font-bold">2563</td>
            <td class="border-dotted text-right padding-rihgt font-bold">26,000.04</td>
            <td class="border-dotted text-right padding-rihgt font-bold" style="border-right:none;">3,900.00</td>
      </tr>
            <tr>
            <td class="text-indent">
              (ข) เงินปันผล เงินส่วนแบ่งกําไร ฯลฯ ตามมาตรา 40 (4) (ข)
            </td>
            <td class="text-center font-bold"></td>
            <td class="text-right padding-rihgt font-bold"></td>
            <td class="text-right padding-rihgt font-bold" style="border-right:none;"></td>
      </tr>
       <tr>
            <td style="padding-left:30px; letter-spacing:0.5px;">
              (1) กรณีผู้ได้รับเงินปันผลได้รับเครดิตภาษี โดยจ่ายจากกําไรสุทธิของกิจการที่ต้องเสีย
            <div style="text-indent:18px; "; >ภาษีเงินได้นิติบุคคลในอัตราดังนี้</div> 
            </td>
            <td class="text-center font-bold"></td>
            <td class="text-right padding-rihgt font-bold"></td>
            <td class="text-right padding-rihgt font-bold" style="border-right:none;"></td>
      </tr>
      <tr>
            <td style="text-indent:46px">
            (1.1) อัตราร้อยละ 30 ของกําไรสุทธิ 
            </td>
            <td class="border-dotted text-center font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold" style="border-right:none;"></td>
      </tr>
      <tr>
            <td style="text-indent:46px">
              (1.2) อัตราร้อยละ 25 ของกําไรสุทธิ 
            </td>
            <td class="border-dotted text-center font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold" style="border-right:none;"></td>
      </tr>
      <tr>
            <td style="text-indent:46px">
              (1.3) อัตราร้อยละ 20 ของกําไรสุทธิ
            </td>
            <td class="border-dotted text-center font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold" style="border-right:none;"></td>
      </tr>
      <tr>
            <td style="text-indent:46px">
               (1.4) อัตราอื่นๆ (ระบุ) ................... ของกําไรสุทธิ 
               <div style="position:fixed; left:98px; top:537px;  width:100px; padding-left:0; " class="text-left">9</div>
            </td>
            <td class="border-dotted text-center font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold" style="border-right:none;"></td>
      </tr>
      <tr>
            <td style="text-indent:13px">
              (2) กรณีผู้ได้รับเงินปันผลไม่ได้รับเครดิตภาษี เนื่องจากจ่ายจาก
            </td>
            <td class="border-dotted text-center font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold" style="border-right:none;"></td>
      </tr>
      <tr>
            <td style="text-indent:46px">
                  (2.1) กําไรสุทธิของกิจการที่ได้รับยกเว้นภาษีเงินได้นิติบุคคล  

            </td>
            <td class="border-dotted text-center font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold" style="border-right:none;"></td>
      </tr>

      <tr>
            <td style="text-indent:46px">
             <div>
                  (2.2) เงินปันผลหรือเงินส่วนแบ่งของกําไรที่ได้รับยกเว้นไม่ต้องนํามารวมคํานวณเป็นรายได้
             </div>
       
              <div style="text-indent:70px">
                 เพื่อเสียภาษีเงินได้นิติบุคคล 
               </div>
            </td>
            <td class="border-dotted text-center font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold" style="border-right:none;"></td>
      </tr>
      <tr>
            <td style="text-indent:46px">
            <div style="letter-spacing:0.3px;">
               (2.3) กําไรสุทธิส่วนที่ได้หักผลขาดทุนสุทธิยกมาไม่เกิน 5 ปี ก่อนรอบระยะเวลาบัญชี
            </div>
              <div style="text-indent:70px">
                 ปีปัจจุบัน
              </div>
            </td>
            <td class="border-dotted text-center font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold" style="border-right:none;"></td>
      </tr>
      <tr>
            <td style="text-indent:46px">
               (2.4) กําไรที่รับรู้ทางบัญชีโดยวิธีส่วนได้เสีย (equity method)
            </td>
            <td class="border-dotted text-center font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold" style="border-right:none;"></td>
      </tr>
      <tr>
            <td style="text-indent:46px">
              (2.5) อื่นๆ (ระบุ)...................................................................................................................
              <div style="position:fixed; left:65px; top:680px;   padding-left:0; " class="text-left">
                อื่นๆ 
              </div>
            <td class="border-dotted text-center font-bold"></td>
            <td class="border-dotted textr-ight padding-rihgt font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold" style="border-right:none;"></td>
      </tr>
      <tr>
            <td>
              <div>
                5. การจ่ายเงินได้ที่ต้องหักภาษี ณ ที่จ่าย ตามคําสั่งกรมสรรพากรที่ออก
              </dvi>
              <div class="text-indent">
                ตามมาตรา 3 เตรส (ระบุ)..................................................................................................................
              </div>
  
              <div style="position:fixed; left:110px; top:714px;  width:100px; padding-left:0; " class="text-left">
                  ข้อ5
              </div>
              <div style="padding-left:13px;  width:100%;  padding-right:16px; text-align:justify; ">
              (เช่น รางวัล ส่วนลดหรือประโยชน์ใดๆ เนื่องจากการส่งเสริมการขาย รางวัลในการประกวด การแข่งขัน การชิงโชค ค่าแสดงของนัก แสดงสาธารณะ ค่าบริการ ค่าจ้างทําของ ค่าโฆษณา ค่าเช่า ค่าขนส่ง ค่าเบี้ยประกันวินาศภัย ฯลฯ) 
              </div>
            </td>
            <td class="border-dotted text-center font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold" style="border-right:none;"></td> 
      </tr>
      <tr>
            <td class="">
               6. อื่นๆ (ระบุ).........................................................................................................................................
               <div style="position:fixed; left:50px; top:788px;  width:100px; padding-left:0; " class="text-left">
                  ข้อ 6 ระบุ
               </div>               
            </td>
            <td class="border-dotted text-center font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold"></td>
            <td class="border-dotted text-right padding-rihgt font-bold" style="border-right:none;"></td>
      </tr>
      <tr>
          <td class="border-right-solid">&nbsp;</td>
          <td class="border-right-solid text-center font-bold"></td>
          <td class="border-right-solid text-right padding-rihgt font-bold"></td>
          <td class="border-right-solid text-right padding-rihgt font-bold" style="border-right:none;"></td>

      </tr>
      <tr>
          <td colspan="2"  style="text-align:right;">รวมเงินที่จ่ายและภาษีที่หักนําส่ง </td>
          
          <td class="text-right padding-rihgt font-bold" style="border-bottom:solid 2px">26,000.04</td>
          <td class="text-right padding-rihgt font-bold" style="border-bottom:solid 2px" style="border-right:none;">3,900.00</td>

      </tr>
      </tbody>
   </table>     
  </div>
';

//เส้น สตางค์
$html .= '
  <div style="border-left:1px solid;  height:485px;  position:fixed;  left:588px; top:358px;; width:18px;">

  </div>
  <div style="border-left:1px solid;  width:18px; height:485px;  position:fixed;   left:704px; top:358px;">
    
  </div>
';

$html .= '
<div style="position:absolute; bottom:173px; left:-20px; border:1px solid; border-radius:3px; margin-right:20px; width:100%; height:56px;">
   <div style="width:500px; position:absolute; top:18px; left:0px; padding-left:30px; padding-top:5px; padding-bottom:5px;"><b>รวมเงินภาษีที่หักนําส่ง</b> (ตัวอักษร)</div>
   <div style="border:1px solid; width:500px; position:absolute; top:18px; right:1px; padding-left:30px; padding-top:5px; padding-bottom:5px;" class="font-bold">สามพันบาทถ้วน</div>
</div>
';

$html .= '
<div style="position:absolute; bottom:149px; left:-20px; border:1px solid ; border-radius:3px; margin-right:20px; width:100%; padding-top:-3px; padding-left:7px; ">
    <b>เงินที่จ่ายเข้า</b> กบข.กสจ./กองทุนสงเคราะห์ครูโรงเรียนเอกชน..............................บาท กองทุนประกันสังคม..............................บาท กองทุนสำรองเลี้ยงชีพ...............................บาท
</div>
';
//ข้อมูล เงินที่จ่ายเข้า => กบข.กสจ./กองทุนสงเคราะห์ครูโรงเรียนเอกชน | กองทุนประกันสังคม | กองทุนสำรองเลี้ยงชีพ
$html .='
  <div style="position:fixed; bottom:153px; left:245px;  width:70px;" class="text-right">
      1,234.56
  </div>
  <div style="position:fixed; bottom:153px; left:430px;  width:72px;" class="text-right">
      1,234.56
  </div>
  <div style="position:fixed; bottom:153px; right:7px;  width:72px;" class="text-right">
      1,234.56
  </div>
';

$html .= '
<div style="position:absolute; bottom:125px; left:-20px; border:1px solid;  border-radius:3px; margin-right:20px; width:100%; padding-top:-3px; padding-left:7px; ">
    <b>ผู้จ่ายเงิน</b>   
</div>
';


$html .= '
<div style="position:absolute; bottom:126px; left:40px;  margin-right:20px; width:100%; padding-top:-3px; ">
  <input type="checkbox" class="checkbox checkbox-margin-left"> (1) หัก ณ ที่จ่าย  
  <input type="checkbox" class="checkbox checkbox-margin-left"> (2) ออกให้ตลอดไป 
  <input type="checkbox" class="checkbox checkbox-margin-left"> (3) ออกให้ครั้งเดียว
  <input type="checkbox" class="checkbox checkbox-margin-left"> (4) อื่นๆ (ให้ระบุ).................................................
  <div style="position:absolute; right:109px; top:1px;">
    อื่นๆ
  </div>
</div>
';

$html .= '
<div  style="position:absolute; bottom:28px; left:-20px;   margin-right:20px; width:240px; height:95px;  padding-left:8px; padding-left:8px;">
  <b>คำเตือน</b>
</div>
<div style="position:absolute; bottom:27px; left:-20px;   margin-right:20px; width:240px; height:95px;  border-radius:3px; border:1px solid  ; padding-left:70px;">
    <div style="letter-spacing:0.2px;">  ผู้มีหน้าที่ออกหนังสือรับรองการหักภาษี ณ ที่จ่าย</div>
    <div style="letter-spacing:0.2px;">ฝ่าฝืนไม่มีปฏิบัติตามมาตรา 50 ทวิ แห่งประมวล</div>
    <div style="letter-spacing:0.6px;">รัษฎากร ต้องรับโทษทางอาญาตามมาตรา 35 แห่งประมวลรัษฎากร</div>
</div>
<div style="position:absolute; bottom:27px; right:-40px;   margin-right:20px; width:426px; height:95px;  border-radius:3px; border:1px solid ;">
    <div style="padding-left:30px;">
    ขอรับรองว่าข้อความและตัวเลขดังกล่าวข้างต้นถูกต้องตรงกับความจริงทุกประการ
    </div> 
    <div style="position:absolute; top:24px; left:80px;">
      ลงชื่อ
    </div> 
    <div style="position:absolute; top:30px; left:115px;">
        <img src="manager.jpg" style="width:100px">
  </div> 
    <div style="padding-left:40px; position:absolute; top:24px; right:150px;">
      ผู้จ่ายเงิน
    </div> 

    <div style="padding-left:40px; position:absolute; top:24px; right:70px;">
        <img src="logo.png" style="width:65px">
    </div> 

    <div class="text-center" padding-top:7px; style="position:absolute; line-height:9px; font-size:14px; top:25px; right:5px; width:60px; height:60px; border:1px solid; border-radius:190%; text-align:center; line-height:12px; ">
        ประทับตรา นิติบุคคล &nbsp; ถ้ามี
    </div> 

    <div style="position:absolute; top:53px; left:80px;">
          .............................................................
    </div> 
    <div style="position:absolute; top:51px; left:80px;  width:155px;" class="text-center font-bold">
        14/01/2564
    </div> 

    <div style="position:absolute; top:70px; left:80px;">
        ( วัน เดือน ปี ที	ออกหนังสือรับรองฯ )
    </div> 
</div>
';


$html .= '
    </div>
  ';


// Footer
$html .= '</div>';

$dompdf = new DOMPDF();
$dompdf->set_paper('A4');
$dompdf->load_html($html);
$dompdf->render();
$dompdf->stream("sample.pdf", array("Attachment" => 0));
