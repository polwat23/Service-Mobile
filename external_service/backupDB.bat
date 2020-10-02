@echo off


del C:\Mobile\service-pjt\external_service\backupDB_pjt.zip

cd C:\Program Files\MariaDB 10.4\bin & mysqldump.exe -hlocalhost -P3306 -u root -p@PJT2020 mobile_pjt > C:\Mobile\service-pjt\external_service\backupDB_pjt.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\service-pjt\external_service\backupDB_pjt.zip C:\Mobile\service-pjt\external_service\backupDB_pjt.sql

del C:\Mobile\service-pjt\external_service\backupDB_pjt.sql

cd C:\Program Files (x86)\WinSCP

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put C:\Mobile\service-pjt\external_service\backupDB_pjt.zip" "exit"

del C:\Mobile\service-pjt\external_service\backupDB_pjt.zip

