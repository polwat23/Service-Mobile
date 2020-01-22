@echo off
::set /p Build=<test2.txt
::%Build%
for /f "delims=" %%x in (test.txt) do %%x
%Build%
::schtasks.exe /CREATE /TN "RUN TEST SCHT" /TR "C:\File Mobile\Demo\Service-Demo-Test\testcase\test_scht_cmd.php" /SC once /SD 2020/01/25 /ST 23:50 /ru Administrators /rp @Gensoft2018 /rl HIGHEST

pause