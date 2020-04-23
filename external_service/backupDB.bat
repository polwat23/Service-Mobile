::@echo off


del C:\Mobile\Service-MHD-Test\external_service\backupDB_mhd.zip

cd C:\Program Files\MariaDB 10.4\bin & mysqldump.exe -hlocalhost -P3306 -u root -p@Egat2020 mobile_egat_test > D:\EgatscMobile\Service-Egat-Test\external_service\backupDB_egat.sql

"C:\Program Files\7-Zip\7z.exe" a -r D:\EgatscMobile\Service-Egat-Test\external_service\backupDB_egat.zip D:\EgatscMobile\Service-Egat-Test\external_service\backupDB_egat.sql

del D:\EgatscMobile\Service-Egat-Test\external_service\backupDB_egat.sql

ftp -i -s:D:\EgatscMobile\Service-Egat-Test\external_service\ftp_upload.bat

del D:\EgatscMobile\Service-Egat-Test\external_service\backupDB_egat.zip

