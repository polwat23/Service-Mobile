@echo off


del C:\Mobile\Service-ARD\external_service\backupDB_ard.zip

cd C:\Program Files\MariaDB 10.5\bin & mysqldump.exe -hlocalhost -P3306 -u root -p@Ard2020 mobile_ard > C:\Mobile\Service-ARD\external_service\backupDB_ard.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\Service-ARD\external_service\backupDB_ard.zip C:\Mobile\Service-ARD\external_service\backupDB_ard.sql

del C:\Mobile\Service-ARD\external_service\backupDB_ard.sql

cd C:\Program Files (x86)\WinSCP

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put C:\Mobile\service-ard\external_service\backupDB_ard.zip" "exit"

del C:\Mobile\Service-ARD\external_service\backupDB_ard.zip
