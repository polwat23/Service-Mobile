@echo off


del D:\Mobile\Service-NSTH\external_service\backupDB_nsth.zip

cd C:\Program Files\MariaDB 10.4\bin
C:
mysqldump.exe -hlocalhost -P3306 -u root -p@NSTH2020 mobile_nsth > D:\Mobile\Service-NSTH\external_service\backupDB_nsth.sql

"C:\Program Files\7-Zip\7z.exe" a -r D:\Mobile\Service-NSTH\external_service\backupDB_nsth.zip D:\Mobile\Service-NSTH\external_service\backupDB_nsth.sql

del D:\Mobile\Service-NSTH\external_service\backupDB_nsth.sql

ftp -i -s:D:\Mobile\Service-NSTH\external_service\ftp_upload.bat

del D:\Mobile\Service-NSTH\external_service\backupDB_nsth.zip

