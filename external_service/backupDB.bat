@echo off

set YYYYMMDD=%DATE:~10,4%%DATE:~4,2%%DATE:~7,2%

set day=-90
echo >"%temp%\%~n0.vbs" s=DateAdd("d",%day%,now) : d=weekday(s)
echo>>"%temp%\%~n0.vbs" WScript.Echo year(s)^& right(100+month(s),2)^& right(100+day(s),2)
for /f %%a in ('cscript /nologo "%temp%\%~n0.vbs"') do set "result=%%a"
del "%temp%\%~n0.vbs"
set "YYYY=%result:~0,4%"
set "MM=%result:~4,2%"
set "DD=%result:~6,2%"
set "data=%yyyy%%mm%%dd%"

del C:\Mobile\Service-MHD-Test\resource\backup\backupDB_%data%.zip

cd C:\Program Files\MariaDB 10.4\bin & mysqldump.exe -hlocalhost -P3306 -u root -p@MUsaving2020 mobile_mhd_test > C:\Mobile\Service-MHD-Test\resource\backup\backupDB_%YYYYMMDD%.sql

"C:\Program Files\7-Zip\7z.exe" a -r C:\Mobile\Service-MHD-Test\resource\backup\backupDB_%YYYYMMDD%.zip C:\Mobile\Service-MHD-Test\resource\backup\backupDB_%YYYYMMDD%.sql

del C:\Mobile\Service-MHD-Test\resource\backup\backupDB_%YYYYMMDD%.sql


pause