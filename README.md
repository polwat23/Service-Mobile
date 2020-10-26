### Service Mobile

> #### Installation

Link : [Package Installer](https://mobilecloud.coopsiam.com/installer/)

* ##### Database

    * Oracle Client 12c or Newer versions
    
    * MongoDB Enterpise lastest versions
    
    * MariaDB version 10.4.8 or Newer versions


* ##### Language

    * PHP version 7.3.11 or Lastest versions (recommend)

* ##### Version control

    * Git lastest versions


> #### Git command

##### 1. Git config ทำครั้งเดียวตอนลง Git
    git config --global user.name "dev_gensoft"
    git config --global user.email "developer@gensoft.co.th"
    git config --global credential.helper 'cache --timeout=60'

##### 2. Git clone ดึงไฟล์ทั้งหมดลงมาจาก Gitlab แยกตาม Branch สหกรณ์
    cd to destination folder
    git clone -b <branch> --single-branch https://gitlab.com/dev_gensoft/service-mobile.git .

##### 3. Git add staged นำไฟล์เข้าสู่สถานะรอบันทึก (ต้อง cd เข้า Folder destination ก่อน)
    git add *

##### 4. Git commit บันทึกการเปลี่ยนแปลงของไฟล์
    git commit -m "Message commit"

##### 5. Git pull ดึงไฟล์ update ล่าสุดจาก Gitlab
    git pull origin <branch>

##### 6. Git push อัพเดทไฟล์จาก Local ขึ้น Gitlab
    git push origin <branch>


> #### MongoDB First time install

##### 1. Set Env system path mongo
    C:\Program Files\MongoDB\Server\4.2\bin

##### 2. Run mongod
    Open CMD and typing without quote "mongod --dbpath=C:/Gcoop/dataMongo" Don't close this CMD

##### 3. Run mongo create Authen
    Open another CMD and typing without quote "mongo"

##### 4. Create Authen user
    1. use <name_database>
    2. db.createUser({
        user: "developer",
        pwd: "@Gensoft2018",
        roles: [{
            role: "readWrite",   db: "<name_database>"
        }]
    })
    3. Exit mongod and typing without quote "mongod --auth --dbpath=C:/Gcoop/dataMongo"
    4. Open MongoDB compass and Select Authenication to Username/Password
    5. Fill username : "developer" , password : "@Gensoft2018", Authen Database : "<name_database>" Click Conenct
