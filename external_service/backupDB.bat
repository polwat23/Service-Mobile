@echo off

del D:\Mobile\service-hnd\external_service\backupDB_hnd.zip

C:

cd C:\Program Files\MariaDB 10.4\bin & mysqldump.exe -hlocalhost -P3306 -u root -p@HND2020 mobile_hnd > D:\Mobile\service-hnd\external_service\backupDB_hnd.sql

"C:\Program Files\7-Zip\7z.exe" a -r D:\Mobile\service-hnd\external_service\backupDB_hnd.zip D:\Mobile\service-hnd\external_service\backupDB_hnd.sql

del D:\Mobile\service-hnd\external_service\backupDB_hnd.sql

cd C:\Program Files (x86)\WinSCP

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put D:\Mobile\service-hnd\external_service\backupDB_hnd.zip" "exit"

del D:\Mobile\service-hnd\external_service\backupDB_hnd.zip