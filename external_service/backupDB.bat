@echo off


del C:\Mobile\service-ktscc\external_service\backupDB_ktscc.zip

cd C:\Program Files\MariaDB 10.5\bin

C:

mysqldump.exe -hlocalhost -P3306 -u root -p@KTSCC2020 mobile_ktscc > C:\Mobile\service-ktscc\external_service\backupDB_ktscc.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\service-ktscc\external_service\backupDB_ktscc.zip C:\Mobile\service-ktscc\external_service\backupDB_ktscc.sql

del C:\Mobile\service-ktscc\external_service\backupDB_ktscc.sql

cd C:\Program Files (x86)\WinSCP

C:

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put C:\Mobile\service-ktscc\external_service\backupDB_ktscc.zip" "exit"

del C:\Mobile\service-ktscc\external_service\backupDB_ktscc.zip
