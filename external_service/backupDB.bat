@echo off


del C:\Mobile\service-stou\external_service\backupDB_stou.zip

cd C:\Program Files\MariaDB 10.5\bin

C:

mysqldump.exe -hlocalhost -P3306 -u root -p@STOU2021 mobile_stou > C:\Mobile\service-stou\external_service\backupDB_stou.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\service-stou\external_service\backupDB_stou.zip C:\Mobile\service-stou\external_service\backupDB_stou.sql

del C:\Mobile\service-stou\external_service\backupDB_stou.sql

cd C:\Program Files (x86)\WinSCP

C:

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put C:\Mobile\service-stou\external_service\backupDB_stou.zip" "exit"

del C:\Mobile\service-stou\external_service\backupDB_stou.zip
