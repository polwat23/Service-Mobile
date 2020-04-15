::@echo off


del C:\Mobile\Service-MHD-Test\external_service\backupDB_mhd.zip

cd C:\Program Files\MariaDB 10.4\bin & mysqldump.exe -hlocalhost -P3306 -u root -p@MUsaving2020 mobile_mhd_test > C:\Mobile\Service-MHD-Test\external_service\backupDB_mhd.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\Service-MHD-Test\external_service\backupDB_mhd.zip C:\Mobile\Service-MHD-Test\external_service\backupDB_mhd.sql

del C:\Mobile\Service-MHD-Test\external_service\backupDB_mhd.sql

ftp -i -s:C:\Mobile\Service-MHD-Test\external_service\ftp_upload.bat

del C:\Mobile\Service-MHD-Test\external_service\backupDB_mhd.zip

