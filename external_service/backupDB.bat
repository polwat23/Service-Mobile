@echo off


del C:\Mobile\service-stk\external_service\backupDB_stk.zip

cd C:\Program Files\MariaDB 10.4\bin

C:

mysqldump.exe -hlocalhost -P3306 -u root -p@STK2021 mobile_stk > C:\Mobile\service-stk\external_service\backupDB_stk.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\service-stk\external_service\backupDB_stk.zip C:\Mobile\service-stk\external_service\backupDB_stk.sql

del C:\Mobile\service-stk\external_service\backupDB_stk.sql

cd C:\Program Files (x86)\WinSCP

C:

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put C:\Mobile\service-stk\external_service\backupDB_stk.zip" "exit"

del C:\Mobile\service-stk\external_service\backupDB_stk.zip

pause