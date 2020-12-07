@echo off


del C:\Mobile\service-msv\external_service\backupDB_msv.zip

cd C:\Program Files\MariaDB 10.4\bin & mysqldump.exe -hlocalhost -P3306 -u root -p@MSV2020 mobile_msv > C:\Mobile\service-msv\external_service\backupDB_msv.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\service-msv\external_service\backupDB_msv.zip C:\Mobile\service-msv\external_service\backupDB_msv.sql

del C:\Mobile\service-msv\external_service\backupDB_msv.sql

cd C:\Program Files (x86)\WinSCP

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put C:\Mobile\service-msv\external_service\backupDB_msv.zip" "exit"

del C:\Mobile\service-msv\external_service\backupDB_msv.zip
