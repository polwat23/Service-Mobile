@echo off


del C:\Mobile\service-phy\external_service\backupDB_phy.zip

cd C:\Program Files\MariaDB 10.5\bin

C:

mysqldump.exe -hlocalhost -P3306 -u root -p@PHY2021 mobile_phy > C:\Mobile\service-phy\external_service\backupDB_phy.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\service-phy\external_service\backupDB_phy.zip C:\Mobile\service-phy\external_service\backupDB_phy.sql

del C:\Mobile\service-phy\external_service\backupDB_phy.sql

cd C:\Program Files (x86)\WinSCP

C:

winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming" "put C:\Mobile\service-phy\external_service\backupDB_phy.zip" "exit"

del C:\Mobile\service-phy\external_service\backupDB_phy.zip
