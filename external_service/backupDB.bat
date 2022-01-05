@echo off

for /f "skip=1" %%x in ('wmic os get localdatetime') do if not defined MyDate set MyDate=%%x
for /f %%x in ('wmic path win32_localtime get /format:list ^| findstr "="') do set %%x
set fmonth=00%Month%
set fday=00%Day%
set today=%Year%%fmonth:~-2%%fday:~-2%

set DAY_OF_LIFE=3
set DBHOST=localhost
set DBPORT=3306
set DBUSER=root
set DBPASS=@KTSCC2020
set DBNAME=mobile_ktscc
set PROJECT_PATH=C:\Mobile\service-ktscc\
set BACKUP_PATH=%PROJECT_PATH%backup\

IF NOT EXIST %BACKUP_PATH% mkdir %BACKUP_PATH%

"C:\Program Files\MariaDB 10.5\bin\mysqldump.exe" -h%DBHOST% -P%DBPORT% -u %DBUSER% -p%DBPASS% %DBNAME% > %BACKUP_PATH%%DBNAME%.sql

"C:\Program Files\7-Zip\7z.exe" a -r %BACKUP_PATH%%today%_db.zip %BACKUP_PATH%%DBNAME%.sql

del %BACKUP_PATH%%DBNAME%.sql

"C:\Program Files\7-Zip\7z.exe" a -r %BACKUP_PATH%%today%_resource.zip %PROJECT_PATH%resource\alias_account_dept %PROJECT_PATH%resource\announce %PROJECT_PATH%resource\avatar %PROJECT_PATH%resource\gallery %PROJECT_PATH%resource\news

forfiles /P %BACKUP_PATH% /S /M *.* /D -%DAY_OF_LIFE% /C "cmd /c del @path"

forfiles /P %BACKUP_PATH% /S /M *_doc.zip /C "cmd /c del @path"

IF EXIST %PROJECT_PATH%resource\reqloan_doc "C:\Program Files\7-Zip\7z.exe" a -r %BACKUP_PATH%%today%_doc.zip %PROJECT_PATH%resource\reqloan_doc


::@pause