@echo off


del C:\Mobile\service-mhs\external_service\backupDB_mhs.zip

cd C:\Program Files\MariaDB 10.5\bin

C:

mysqldump.exe -hlocalhost -P3306 -u root -p@MHS2021 mobile_mhs > C:\Mobile\service-mhs\external_service\backupDB_mhs.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\service-mhs\external_service\backupDB_mhs.zip C:\Mobile\service-mhs\external_service\backupDB_mhs.sql

del C:\Mobile\service-mhs\external_service\backupDB_mhs.sql

cd C:\Program Files (x86)\WinSCP

C:

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put C:\Mobile\service-mhs\external_service\backupDB_mhs.zip" "exit"

del C:\Mobile\service-mhs\external_service\backupDB_mhs.zip
