@echo off
set mysqlexe="D:\xampp\mysql\bin\mysql.exe"

set BACKUP_FOLDER=%1
set ARCHIVE=%2
set WEBSITE=%3
set DB_HOST=%4
set DB_PORT=%5
set DB_USER=%6
set DB_PASS=%7
set BACKUPF=%ARCHIVE:~0,-4%

cd %BACKUP_FOLDER%
IF EXIST %ARCHIVE% (
	powershell.exe -nologo -noprofile -command "Expand-Archive -Force -LiteralPath %ARCHIVE% -DestinationPath."
) ELSE (
	echo Cannot extract from archive
)

IF EXIST %mysqlexe% (
	IF "%DB_PASS%"=="" (
		%mysqlexe% -h %DB_HOST% -P %DB_PORT% -u "%DB_USER%" %WEBSITE% < %BACKUPF%
	) ELSE (
		%mysqlexe% -h %DB_HOST% -P %DB_PORT% -u "%DB_USER%" -p"%DB_PASS%" %WEBSITE% < %BACKUPF%
	)
) ELSE ( 
	echo Set the path to mysqldump executable
)

del %BACKUPF%; 