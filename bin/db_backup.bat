@echo off
set mysqldumpexe="D:\xampp\mysql\bin\mysqldump.exe"

set BACKUP_FOLDER=%1
set WEBSITE=%2
set DB_HOST=%3
set DB_PORT=%4
set DB_USER=%5
set DB_PASS=%6
set NOW=%DATE%_%TIME:~0,2%%TIME:~3,2%%TIME:~6,2%
set NOW=%NOW: =0%
set BKNAME=%BACKUP_FOLDER%%WEBSITE%.%NOW%.backup.sql
echo %BKNAME%
IF EXIST %mysqldumpexe% (
	IF "%DB_PASS%"=="" (
		%mysqldumpexe% --add-drop-table --routines -h %DB_HOST% -P %DB_PORT% -u "%DB_USER%" %WEBSITE% > %BKNAME%
	) ELSE (
		%mysqldumpexe% --add-drop-table --routines -h %DB_HOST% -P %DB_PORT% -u "%DB_USER%" -p"%DB_PASS%" %WEBSITE% > %BKNAME%
	)
	IF EXIST %BKNAME% (
		powershell.exe -nologo -noprofile -command "Compress-Archive %BKNAME% %BKNAME%.zip"
		del %BKNAME%
	) ELSE (
		echo Cannot create archive
	)
) ELSE ( 
	echo Set the path to mysqldump executable
)