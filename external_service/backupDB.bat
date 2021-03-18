@echo off


del C:\Mobile\service-brm\external_service\backupDB_brm.zip

cd C:\Program Files\MariaDB 10.5\bin & mysqldump.exe -hlocalhost -P3306 -u root -p@BRM2021 mobile_brm > C:\Mobile\service-brm\external_service\backupDB_brm.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\service-brm\external_service\backupDB_brm.zip C:\Mobile\service-brm\external_service\backupDB_brm.sql

del C:\Mobile\service-brm\external_service\backupDB_brm.sql

cd C:\Program Files (x86)\WinSCP

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put C:\Mobile\service-brm\external_service\backupDB_brm.zip" "exit"

del C:\Mobile\service-brm\external_service\backupDB_brm.zip
