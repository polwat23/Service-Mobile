@echo off


del C:\Mobile\service-muk\external_service\backupDB_muk.zip

cd C:\Program Files\MariaDB 10.5\bin & mysqldump.exe -hlocalhost -P3306 -u root -p@MUK2020 mobile_muk > C:\Mobile\service-muk\external_service\backupDB_muk.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\service-muk\external_service\backupDB_muk.zip C:\Mobile\service-muk\external_service\backupDB_muk.sql

del C:\Mobile\service-muk\external_service\backupDB_muk.sql

cd C:\Program Files (x86)\WinSCP

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put C:\Mobile\service-muk\external_service\backupDB_muk.zip" "exit"

del C:\Mobile\service-muk\external_service\backupDB_muk.zip

