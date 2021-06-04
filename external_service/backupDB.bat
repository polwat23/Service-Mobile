@echo off


del C:\Mobile\Service-hbr\external_service\backupDB_hbr.zip

cd C:\Program Files\MariaDB 10.5\bin & mysqldump.exe -hlocalhost -P3306 -u root -p@HBR2021 mobile_hbr > C:\Mobile\Service-hbr\external_service\backupDB_hbr.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\Service-hbr\external_service\backupDB_hbr.zip C:\Mobile\Service-hbr\external_service\backupDB_hbr.sql

del C:\Mobile\Service-hbr\external_service\backupDB_hbr.sql

cd C:\Program Files (x86)\WinSCP

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put C:\Mobile\service-hbr\external_service\backupDB_hbr.zip" "exit"

del C:\Mobile\Service-hbr\external_service\backupDB_hbr.zip
