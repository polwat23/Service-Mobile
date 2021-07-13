@echo off


del C:\Mobile\service-slp\external_service\backupDB_slp.zip

cd C:\Program Files\MariaDB 10.5\bin

C:

mysqldump.exe -hlocalhost -P3306 -u root -p@SLP2021 mobile_slp > C:\Mobile\service-slp\external_service\backupDB_slp.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\service-slp\external_service\backupDB_slp.zip C:\Mobile\service-slp\external_service\backupDB_slp.sql

del C:\Mobile\service-slp\external_service\backupDB_slp.sql

cd C:\Program Files (x86)\WinSCP

C:

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put C:\Mobile\service-slp\external_service\backupDB_slp.zip" "exit"

del C:\Mobile\service-slp\external_service\backupDB_slp.zip

pause