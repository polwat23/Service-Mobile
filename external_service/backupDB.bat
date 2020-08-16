@echo off


del C:\Mobile\service-crh\external_service\backupDB_crh.zip

cd C:\Program Files\MariaDB 10.4\bin & mysqldump.exe -hlocalhost -P3306 -u root -p@CRH2020 mobile_crh > C:\Mobile\service-crh\external_service\backupDB_crh.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\service-crh\external_service\backupDB_crh.zip C:\Mobile\service-crh\external_service\backupDB_crh.sql

del C:\Mobile\service-crh\external_service\backupDB_crh.sql

cd C:\Program Files (x86)\WinSCP

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put C:\Mobile\service-crh\external_service\backupDB_crh.zip" "exit"

del C:\Mobile\service-crh\external_service\backupDB_crh.zip

