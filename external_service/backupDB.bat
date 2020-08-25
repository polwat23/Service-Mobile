@echo off


del D:\Mobile\service-ptt\external_service\backupDB_ptt.zip

cd C:\Program Files\MariaDB 10.4\bin

C:

mysqldump.exe -hlocalhost -P3306 -u root -p@PTT2020 mobile_ptt > D:\Mobile\service-ptt\external_service\backupDB_ptt.sql

"C:\Program Files\7-Zip\7z.exe" a -r D:\Mobile\service-ptt\external_service\backupDB_ptt.zip D:\Mobile\service-ptt\external_service\backupDB_ptt.sql

del D:\Mobile\service-ptt\external_service\backupDB_ptt.sql

cd C:\Program Files (x86)\WinSCP

C:

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put D:\Mobile\service-ptt\external_service\backupDB_ptt.zip" "exit"

del D:\Mobile\service-ptt\external_service\backupDB_ptt.zip
