@echo off


del C:\Mobile\service-spt\external_service\backupDB_spt.zip

cd C:\Program Files\MariaDB 10.5\bin

C:

mysqldump.exe -hlocalhost -P3306 -u root -p@SPT2021 mobile_spt > C:\Mobile\service-spt\external_service\backupDB_spt.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\service-spt\external_service\backupDB_spt.zip C:\Mobile\service-spt\external_service\backupDB_spt.sql

del C:\Mobile\service-spt\external_service\backupDB_spt.sql

cd C:\Program Files (x86)\WinSCP

C:

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put C:\Mobile\service-spt\external_service\backupDB_spt.zip" "exit"

del C:\Mobile\service-spt\external_service\backupDB_spt.zip
