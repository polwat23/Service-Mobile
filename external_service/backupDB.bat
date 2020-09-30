@echo off


del C:\Mobile\service-moe\external_service\backupDB_moe.zip

cd C:\Program Files\MariaDB 10.4\bin

C:

mysqldump.exe -hlocalhost -P3306 -u root -p@MOE2020 mobile_moe > C:\Mobile\service-moe\external_service\backupDB_moe.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\service-moe\external_service\backupDB_moe.zip C:\Mobile\service-moe\external_service\backupDB_moe.sql

del C:\Mobile\service-moe\external_service\backupDB_moe.sql

cd C:\Program Files (x86)\WinSCP

C:

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put C:\Mobile\service-moe\external_service\backupDB_moe.zip" "exit"

del C:\Mobile\service-moe\external_service\backupDB_moe.zip
