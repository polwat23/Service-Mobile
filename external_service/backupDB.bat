@echo off


del C:\Mobile\service-srn\external_service\backupDB_srn.zip

cd C:\Program Files\MariaDB 10.5\bin

C:

mysqldump.exe -hlocalhost -P3306 -u root -p@SRN2020 mobile_srn > C:\Mobile\service-srn\external_service\backupDB_srn.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\service-srn\external_service\backupDB_srn.zip C:\Mobile\service-srn\external_service\backupDB_srn.sql

del C:\Mobile\service-srn\external_service\backupDB_srn.sql

cd C:\Program Files (x86)\WinSCP

C:

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put C:\Mobile\service-srn\external_service\backupDB_srn.zip" "exit"

del C:\Mobile\service-srn\external_service\backupDB_srn.zip
