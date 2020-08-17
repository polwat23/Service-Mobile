::@echo off


del D:\EgatscMobile\Service-Egat\external_service\backupDB_egat.zip
c:
cd C:\Program Files\MariaDB 10.4\bin & mysqldump.exe -hlocalhost -P3306 -u root -p@Egat2020 mobile_egat > D:\EgatscMobile\Service-Egat\external_service\backupDB_egat.sql

"C:\Program Files\7-Zip\7z.exe" a -r D:\EgatscMobile\Service-Egat\external_service\backupDB_egat.zip D:\EgatscMobile\Service-Egat\external_service\backupDB_egat.sql

del D:\EgatscMobile\Service-Egat\external_service\backupDB_egat.sql

cd C:\Program Files (x86)\WinSCP
c:
winscp.exe /command "open ftp://ftp_backup:@Gensoft2018@203.154.140.14/incoming -rawsettings ProxyMethod=2 ProxyHost=proxy.egat.co.th ProxyPort=8080" "put D:\EgatscMobile\Service-Egat\external_service\backupDB_egat.zip" "exit"

del D:\EgatscMobile\Service-Egat\external_service\backupDB_egat.zip

pause