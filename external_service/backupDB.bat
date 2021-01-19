@echo off


del C:\Mobile\Service-MJU\external_service\backupDB_MJU.zip

cd C:\Program Files\MariaDB 10.5\bin & mysqldump.exe -hlocalhost -P3306 -u root -p@MJU2020 mobile_MJU > C:\Mobile\Service-MJU\external_service\backupDB_MJU.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\Service-MJU\external_service\backupDB_MJU.zip C:\Mobile\Service-MJU\external_service\backupDB_MJU.sql

del C:\Mobile\Service-MJU\external_service\backupDB_MJU.sql

cd C:\Program Files (x86)\WinSCP

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put C:\Mobile\service-MJU\external_service\backupDB_MJU.zip" "exit"

del C:\Mobile\Service-MJU\external_service\backupDB_MJU.zip
