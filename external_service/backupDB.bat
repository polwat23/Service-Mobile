@echo off


del C:\Mobile\Service-NKH\external_service\backupDB_NKH.zip

cd C:\Program Files\MariaDB 10.5\bin
C:
mysqldump.exe -hlocalhost -P3306 -u root -p@NKH2021 mobile_nkh > C:\Mobile\Service-NKH\external_service\backupDB_NKH.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\Service-NKH\external_service\backupDB_NKH.zip C:\Mobile\Service-NKH\external_service\backupDB_NKH.sql

del C:\Mobile\Service-NKH\external_service\backupDB_NKH.sql

cd C:\Program Files (x86)\WinSCP

C:

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put C:\Mobile\service-NKH\external_service\backupDB_NKH.zip" "exit"


del C:\Mobile\Service-NKH\external_service\backupDB_NKH.zip
