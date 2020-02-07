<?php
/**
 * A single file web interface to backup and restore databases and files.
 * PHP calls external bash/batch scripts to perform the tasks.
 * 
 * @author https://github.com/dtabirca
 * @since 2016
 */

session_start();

# helpers
function human_filesize($bytes, $decimals = 2) {
  $sz = 'BKMGTP';
  $factor = floor((strlen($bytes) - 1) / 3);
  return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}

# paths
// linux
// define("PUBLICWEB_FOLDER", "/var/www/");
// define("BACKUP_FOLDER", "/var/local/app/backups");
// define("LOGS_FOLDER", "/var/local/app/logs");
// win
define("PUBLICWEB_FOLDER", "D:\\xampp\\htdocs\\");
define("BACKUP_FOLDER", "D:\\xampp\\htdocs\\phpwebackup\\backups\\");
define("LOGS_FOLDER", "D:\\xampp\\htdocs\\phpwebackup\\logs\\");
// gitbash win
// define("PUBLICWEB_FOLDER", "/d/xampp/htdocs/");
// define("BACKUP_FOLDER", "/d/xampp/htdocs/phpwebackup/backups/");
// define("LOGS_FOLDER", "/d/xampp/htdocs/phpwebackup/logs/");

# database
$db_host   = "localhost";
$db_port   = '3306';
$db_user   = "root";
$db_pass   = "";

# is win os?
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $WIN_OS = TRUE;
} else {
   $WIN_OS = FALSE;
}

# get databases
if (!empty($password)) $password = '-p"{$password}"';
if ($WIN_OS){
	$cmd = 'cd /xampp/mysql/bin && mysql -h ' . $db_host . ' -P ' . $db_port . ' -u ' . $db_user . ' ' . $db_pass . ' -e "SHOW DATABASES;"';
} else{
	$cmd = 'mysql -h ' . $db_host . ' -P ' . $db_port . ' -u ' . $db_user . ' ' . $db_pass . ' -e "SHOW DATABASES;"';
}
try{
	exec($cmd, $output);
} catch(Exception $e) {
	// shell not allowed
}
# filter list
$db_filter = array('information_schema', 'mysql', 'performance_schema', 'phpmyadmin');
$databases = array_diff($output, $db_filter);
array_shift($databases);// remove the header

# use selected database
$selected = FALSE;
if (isset($_POST['db_select'])){
	$selected = $_POST['db_select'];
	try {
		$db_conn = new PDO('mysql:host=' . $db_host . ';port=' . $db_port . ';dbname=' . $selected . ';', $db_user, $db_pass, array( PDO::ATTR_TIMEOUT => 10 ));
	} catch (PDOException $e) {
		$db_conn = FALSE;
	}
} else if (isset($_SESSION['db_select'])){
	$selected = $_SESSION['db_select'];
} else{
	$selected = $databases[0];
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Web Backup/Restore Tool</title>
<style type="text/css">
	*{font-family: Arial;}
	input, select, div{
		border-radius: 5px;
		border-color: #ccc;
		padding: 5px;
		margin:2px;
	}
	.messages{
		background-color: #EBF4FA;		
	}
	hr{
    	border: 0;
    	height: 1px;
    	background: #ccc;
	}
</style>
</head>
<body>
<?php

# restore database
if (isset($_POST['db_restore'])){
	if ($WIN_OS){
		$cmd = '.\bin\db_restore.bat ' . BACKUP_FOLDER . ' ' . $_POST['restore_from'] . ' ' . $selected . ' ' . $db_host . ' ' . $db_port . ' ' . $db_user . ' ' . $db_pass . '  > ' . LOGS_FOLDER . $selected . '-' . time() . '.database.restore.log';
	} else{
		//C:\"Program Files"\Git\git-bash.exe
		$cmd = '/bin/bash ./bin/db_restore.sh "' . BACKUP_FOLDER . '" "' . $_POST['restore_from'] . '" "' . $selected . '" "' . $db_host . '" "' . $db_port . '" "' . $db_user . '" "' . $db_pass . '" > "' . LOGS_FOLDER . $selected . '-' . time() . '.database.restore.log"';
	}
	exec($cmd . ' 2>&1', $output2, $return2);
	if ($return2===0){
		$message = "Database restored.";	
	}else{
		$message = "Cannot restore DB.";
	}
}

# backup
if (isset($_POST['db_backup'])){
	if ($WIN_OS){
		$cmd = '.\bin\db_backup.bat ' . BACKUP_FOLDER . ' ' . $selected . ' ' . $db_host . ' ' . $db_port . ' ' . $db_user . ' ' . $db_pass . ' > ' . LOGS_FOLDER . $selected . '-' . time() . '.database.backup.log';
	} else{
		// C:\"Program Files"\Git\git-bash.exe
		$cmd = '/bin/bash ./bin/db_backup.sh "' . BACKUP_FOLDER . '" "' . $selected . '" "' . $db_host . '" "' . $db_port . '" "' . $db_user . '" "' . $db_pass . '" > "' . LOGS_FOLDER . $selected . '-' . time() . '.database.backup.log"';
	}
	
	exec($cmd . ' 2>&1', $output2, $return2);
	if ($return2===0){
		$message = "Backup created.";	
	}else{
		$message = "Cannot create backup.";
	}
}

# display messages
if (isset($message)){
	echo '<div class="messages">' . $message . '</div><hr>';
}
?>

<!-- database -->
<h3>Select DB</h3>
<form method="post">
	<select name="db_select" onChange="this.parentNode.submit();">
<?php
foreach ($databases as $value) {
	echo '<option value="' . $value . '" ' . (($selected == $value)?'selected':'') . '>' . $value . '</option>'; 
}
?>
    </select>
</form>	
<hr>

<!-- backup -->
<h3>Create DB Backup</h3>
<form method="post" onSubmit="return confirm ('Please confirm this action.')"><input type="submit" value="Create DB Backup" name="db_backup"></form>
<hr>

<!-- restore -->
<h3>Restore DB from Backup</h3>
<form method="post" onSubmit="return formConfirmation();">
  <select name="restore_from" size="10" id="restore_from">
<?php
$backups = scandir(BACKUP_FOLDER);
if (!empty($backups)){
$options = [];
foreach ($backups as $entry) {
    if ($entry != "." && $entry != ".." && strstr($entry, $selected) &&
    	(($WIN_OS && substr($entry, -4) === '.zip') || (!$WIN_OS && substr($entry, -7) === '.tar.gz')) ) {
        $options['<option value="' . $entry . '">' . $entry . ' [' . human_filesize(filesize(BACKUP_FOLDER . $entry)) . ']</option>' . "\n"] = filemtime( BACKUP_FOLDER . $entry );
    }
}
arsort($options);
$options = array_keys($options);
$options = implode('', $options);
echo $options;
} else{
	echo '<option value="" disabled>No backups available</option>';
}
?></select><br><br>
<input type="submit" value="Restore DB" name="db_restore">
</form>
<hr>

<!-- maintenance -->
<h3>Maintenance Mode<h3>
<form method="post" onSubmit="return confirm ('Please confirm this action.')">
<input type="radio" name="maintenance_mode" value="0" id="maintenance-option-0">
<label for="maintenance-option-0">Off</label>
<input type="radio" name="maintenance_mode" value="1" id="maintenance-option-1">
<label for="maintenance-option-1">On</label>
</form>
<hr>

<!-- backup sources -->
<!--h3>Backup files</h3>
<form method="post" onSubmit="return confirm ('Please confirm this action.')">
	FOR <select name="source_select">
    </select>
    <input type="submit" value="Create Backup" name="db_backup">
</form>
<hr-->

<!-- restore sources-->
<!--h3>Restore files</h3>
<form method="post" onSubmit="return confirm ('Please confirm this action.')">
	<select name="restore_from" size="10" id="">
	</select>
	<input type="submit" value="Restore DB" name="db_restore">
</form-->

<script>
	function formConfirmation(){
		if(document.getElementById('restore_from').selectedIndex!=-1){
			return confirm ('Please confirm this action.');
		}
		else{
			alert('Select a backup archive to restore from.');
			return false;
		}
	}
</script>
</body>
</html>