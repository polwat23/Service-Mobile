@echo off


del C:\Mobile\service-ssru\external_service\backupDB_ssru.zip

cd C:\Program Files\MariaDB 10.4\bin

C:

mysqldump.exe -hlocalhost -P3306 -u root -p@SSRU2021 mobile_ssru > C:\Mobile\service-ssru\external_service\backupDB_ssru.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\service-ssru\external_service\backupDB_ssru.zip C:\Mobile\service-ssru\external_service\backupDB_ssru.sql

del C:\Mobile\service-ssru\external_service\backupDB_ssru.sql

cd C:\Program Files (x86)\WinSCP

C:

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put C:\Mobile\service-ssru\external_service\backupDB_ssru.zip" "exit"

del C:\Mobile\service-ssru\external_service\backupDB_ssru.zip
