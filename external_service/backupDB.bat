@echo off


del C:\Mobile\service-dol\external_service\backupDB_dol.zip

cd C:\Program Files\MariaDB 10.5\bin & mysqldump.exe -hlocalhost -P3307 -u root -p@DOL2021 mobile_dol > C:\Mobile\service-dol\external_service\backupDB_dol.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\service-dol\external_service\backupDB_dol.zip C:\Mobile\service-dol\external_service\backupDB_dol.sql

del C:\Mobile\service-dol\external_service\backupDB_dol.sql

cd C:\Program Files (x86)\WinSCP

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put C:\Mobile\service-dol\external_service\backupDB_dol.zip" "exit"

del C:\Mobile\service-dol\external_service\backupDB_dol.zip