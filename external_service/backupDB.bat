@echo off

set YYYYMMDD=%DATE:~10,4%%DATE:~4,2%%DATE:~7,2%

cd C:\Program Files\MariaDB 10.4\bin & mysqldump.exe -hlocalhost -P3306 -u root -p@MUsaving2020 mobile_mhd_test > C:\Mobile\Service-MHD-Test\resource\backup\backupDB_%YYYYMMDD%.sql

