@echo off


del C:\Mobile\service-yst\external_service\backupDB_yst.zip

cd C:\Program Files\MariaDB 10.5\bin

C:

mysqldump.exe -hlocalhost -P3306 -u root -p@YST2021 mobile_yst > C:\Mobile\service-yst\external_service\backupDB_yst.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\service-yst\external_service\backupDB_yst.zip C:\Mobile\service-yst\external_service\backupDB_yst.sql

del C:\Mobile\service-yst\external_service\backupDB_yst.sql

cd C:\Program Files (x86)\WinSCP

C:

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put C:\Mobile\service-yst\external_service\backupDB_yst.zip" "exit"

del C:\Mobile\service-yst\external_service\backupDB_yst.zip
