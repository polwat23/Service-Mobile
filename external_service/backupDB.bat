@echo off


del C:\Mobile\service-doa\external_service\backupDB_doa.zip

cd C:\Program Files\MariaDB 10.5\bin

C:

mysqldump.exe -hlocalhost -P3306 -u root -p@DOA2020 mobile_doa > C:\Mobile\service-doa\external_service\backupDB_doa.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\service-doa\external_service\backupDB_doa.zip C:\Mobile\service-doa\external_service\backupDB_doa.sql

del C:\Mobile\service-doa\external_service\backupDB_doa.sql

cd C:\Program Files (x86)\WinSCP

C:

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put C:\Mobile\service-doa\external_service\backupDB_doa.zip" "exit"

del C:\Mobile\service-doa\external_service\backupDB_doa.zip
