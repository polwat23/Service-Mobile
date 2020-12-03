@echo off


del C:\Mobile\service-tak\external_service\backupDB_tak.zip

cd C:\Program Files\MariaDB 10.4\bin

C:

mysqldump.exe -hlocalhost -P3306 -u root -p@TAK2020 mobile_tak > C:\Mobile\service-tak\external_service\backupDB_tak.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\service-tak\external_service\backupDB_tak.zip C:\Mobile\service-tak\external_service\backupDB_tak.sql

del C:\Mobile\service-tak\external_service\backupDB_tak.sql

cd C:\Program Files (x86)\WinSCP

C:

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put C:\Mobile\service-tak\external_service\backupDB_tak.zip" "exit"

del C:\Mobile\service-tak\external_service\backupDB_tak.zip
