@echo off


del C:\Mobile\service-nkt\external_service\backupDB_nkt.zip

cd C:\Program Files\MariaDB 10.5\bin

C:

mysqldump.exe -hlocalhost -P3306 -u root -p@NKT2020 mobile_nkt > C:\Mobile\service-nkt\external_service\backupDB_nkt.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\service-nkt\external_service\backupDB_nkt.zip C:\Mobile\service-nkt\external_service\backupDB_nkt.sql

del C:\Mobile\service-nkt\external_service\backupDB_nkt.sql

cd C:\Program Files (x86)\WinSCP

C:

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put C:\Mobile\service-nkt\external_service\backupDB_nkt.zip" "exit"

del C:\Mobile\service-nkt\external_service\backupDB_nkt.zip
