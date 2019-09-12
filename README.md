### Service Mobile

> #### Installation

* ##### Database

    * Oracle Database 12c or Newer versions


* ##### Apache

    * Xampp version 7.1.26 or Newer versions

* ##### Version control

    * Git lastest versions

> #### Git command

##### 1. Git config ทำครั้งเดียวตอนลง Git
    git config --global user.name "zcrimxelz"
    git config --global user.email "zcrimxelz@gmail.com"

##### 2. Git clone ดึงไฟล์ทั้งหมดลงมาจาก Gitlab แยกตาม Branch สหกรณ์
    cd to destination folder
    git clone -b <branch> https://gitlab.com/dev_gensoft/service-mobile.git .

##### 3. Git add staged นำไฟล์เข้าสู่สถานะรอบันทึก (ต้อง cd เข้า Folder destination ก่อน)
    git add *

##### 4. Git commit บันทึกการเปลี่ยนแปลงของไฟล์
    git commit -m "Message commit"

##### 5. Git pull ดึงไฟล์ update ล่าสุดจาก Gitlab
    git pull origin branch

##### 6. Git push อัพเดทไฟล์จาก Local ขึ้น Gitlab
    git push origin branch


