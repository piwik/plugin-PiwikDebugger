<?php
include("headers.php");
include("settings.php");

// If we have an action to perform
if (!$demoMode && isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] && isset($_GET['action'])) {

	// Get our old paths & user settings
	$oldLocal = $ICEcoder["githubLocalPaths"];
	$oldRemote = $ICEcoder["githubRemotePaths"];
	$settingsContents = file_get_contents($settingsFile,false,$context);

	// ========
	// CHOOSING
	// ========
	if ($_GET['action']=="choose") {

		$pathPair = numClean($_GET['pathPair']);

		$chosenLocal = $oldLocal[$pathPair];
		$chosenRemote = $oldRemote[$pathPair];

		$pathValid = false;

		if (!is_dir($docRoot.$chosenLocal)) {
			if (is_writable($docRoot)) {
				mkdir($docRoot.$chosenLocal, 0705);
			} else {
				echo "<script>top.ICEcoder.message('Sorry, cannot create folder at\\n".$chosenLocal."');</script>";
			}
		}

		if (is_dir($docRoot.$chosenLocal)) {
			$pathValid = true;
		}

		if ($pathValid) {

			// New setting for the root path
			$settingsNew = '"root"			=> "'.$chosenLocal.'",'.PHP_EOL;

			// Identify the bit to replace
			$repPosStart = strpos($settingsContents,'"root"');
			$repPosEnd = strpos($settingsContents,'"checkUpdates"');

			// Compile our new settings
			$settingsContents = substr($settingsContents,0,$repPosStart).$settingsNew.substr($settingsContents,$repPosEnd,strlen($settingsContents));

			// Now update the config file
			if (is_writeable($settingsFile)) {
				$fh = fopen($settingsFile, 'w');
				fwrite($fh, $settingsContents);
				fclose($fh);
				// Hide this popup and reload file manager
				echo "<script>top.ICEcoder.showHide('hide',top.document.getElementById('blackMask'));top.ICEcoder.refreshFileManager();</script>";
			} else {
				echo "<script>top.ICEcoder.message('Cannot update config file. Please set public write permissions on lib/".$settingsFile." and try again');</script>";
			}
			
		}

	}

	// ======
	// ADDING
	// ======

	if ($_GET['action']=="add") {

		// Start creating a new chunk for the github paths
		$settingsNew = '"githubLocalPaths"	=> array(';

		// Add the new one
		if ($_POST['githubLocalPathNEW'] != "" && $_POST['githubRemotePathNEW'] != "") {
			$settingsNew .= '"'.xssClean($_POST['githubLocalPathNEW'],"html").'",';
		}

		// Then set all the old local paths
		for ($i=0; $i<count($oldLocal); $i++) {
			$settingsNew .= '"'.$oldLocal[$i].'",';
		}
		// Rtrim off the last comma
		$settingsNew = rtrim($settingsNew,',');
		$settingsNew .= '),'.PHP_EOL;

		// Now do the same for the remote paths
		$settingsNew .= '"githubRemotePaths"	=> array(';

		// Add the new one
		if ($_POST['githubLocalPathNEW'] != "" && $_POST['githubRemotePathNEW'] != "") {
			$settingsNew .= '"'.xssClean($_POST['githubRemotePathNEW'],"html").'",';
		}

		// Then set all the old remote paths
		for ($i=0; $i<count($oldRemote); $i++) {
			$settingsNew .= '"'.$oldRemote[$i].'",';
		}
		// Rtrim off the last comma
		$settingsNew = rtrim($settingsNew,',');
		$settingsNew .= '),'.PHP_EOL;

	}

	// ===================
	// UPDATING & REMOVING
	// ===================

	if ($_GET['action']=="update") {

		// Start creating a new chunk for the github paths
		$settingsNew = '"githubLocalPaths"	=> array(';

		// Redo the arrays using the form data
		for ($i=0; $i<count($oldLocal); $i++) {
			if ($_POST['githubLocalPath'.$i] != "") {
				$settingsNew .= '"'.xssClean($_POST['githubLocalPath'.$i],"html").'",';
			}
		}
		// Rtrim off the last comma
		$settingsNew = rtrim($settingsNew,',');
		$settingsNew .= '),'.PHP_EOL;

		// Now do the same for the remote paths
		$settingsNew .= '"githubRemotePaths"	=> array(';

		// Redo the arrays using the form data
		for ($i=0; $i<count($oldRemote); $i++) {
			if ($_POST['githubRemotePath'.$i] != "") {
				$settingsNew .= '"'.xssClean($_POST['githubRemotePath'.$i],"html").'",';
			}
		}
		// Rtrim off the last comma
		$settingsNew = rtrim($settingsNew,',');
		$settingsNew .= '),'.PHP_EOL;
	}

	if ($_GET['action']!="choose") {
		// Now we have a new settingsNew string to use
		// we can update the path arrays in the settings file

		// Identify the bit to replace
		$repPosStart = strpos($settingsContents,'"githubLocalPaths"');
		$repPosEnd = strpos($settingsContents,'"previousFiles"');

		// Compile our new settings
		$settingsContents = substr($settingsContents,0,$repPosStart).$settingsNew.substr($settingsContents,$repPosEnd,strlen($settingsContents));

		// Now update the config file
		if (is_writeable($settingsFile)) {
			$fh = fopen($settingsFile, 'w');
			fwrite($fh, $settingsContents);
			fclose($fh);
			// Finally, reload the iFrame screen for the user
			header("Location: github-manager.php?updatedGithubPaths&csrf=".$_SESSION["csrf"]);
			echo "<script>window.location='github-manager.php?updatedGithubPaths&csrf='+top.ICEcoder.csrf;</script>";
			die('saving github paths...');
		} else {
			echo "<script>top.ICEcoder.message('Cannot update config file. Please set public write permissions on lib/".$settingsFile." and try again');</script>";
		}
	}
}
?>
<!DOCTYPE html>

<html>
<head>
<title>ICEcoder <?php echo $ICEcoder["versionNo"];?> GitHub manager</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" type="text/css" href="github-manager.css">
</head>

<body class="githubManager">

<h1>github paths</h1>

<div style="display: inline-block; width: 620px; height: 340px; overflow-y: auto">
	<?php
	$pathsLocal = $ICEcoder['githubLocalPaths'];
	$pathsRemote = $ICEcoder['githubRemotePaths'];
	if (count($pathsLocal) > 0) {
	?>
	<div style="display: inline-block; width: 600px; margin-bottom: 30px">
		<h2>Choose existing path</h2><br>

		<form id="githubUpdateForm" action="github-manager.php?action=update" method="POST">
			<table>
			<tr>
			<td style="padding-left: 5px">Local path</td>
			<td style="padding-left: 5px">Remote GitHub path</td>
			</tr>
			<?php
			for ($i=0; $i<count($pathsLocal); $i++) {
				echo '<tr>';
				echo '<td style="padding: 0 10px 8px 0"><input type="text" name="githubLocalPath'.$i.'" value="'.$pathsLocal[$i].'" style="width: 250px"></td>';
				echo '<td style="padding: 0 10px 8px 0"><input type="text" name="githubRemotePath'.$i.'" value="'.$pathsRemote[$i].'" style="width: 250px"></td>';
				echo '<td style="padding: 2px 0 8px 0"><div style="display: inline-block; padding: 5px; background: #2187e7; color: #fff; font-size: 12px; cursor: pointer" onclick="window.location=\'github-manager.php?action=choose&pathPair='.$i.'&csrf='.$_SESSION["csrf"].'\'">Choose</div></td>';
				echo '</tr>';
			}
			echo '<tr>';
			echo '<td style="padding-top: 7px; color: #444">Set local and remote path to blanks to remove</td>';
			echo '<td style="padding: 3px 10px 8px 0; text-align: right"><div style="display: inline-block; padding: 5px; background: #2187e7; color: #fff; font-size: 12px; cursor: pointer" onclick="document.getElementById(\'githubUpdateForm\').submit()">Update</div></td>';
			echo '</tr>';
			?>
			</table>
			<input type="hidden" name="csrf" value="<?php echo $_SESSION["csrf"]; ?>">
		</form>
	</div>
	<?php
	;};
	?>

	<div style="display: inline-block; width: 600px">
		<h2>Add new path</h2><br>

		<form id="githubAddForm" action="github-manager.php?action=add" method="POST">
			<table>
			<tr>
			<td style="padding-left: 5px">Local path</td>
			<td style="padding-left: 5px">Remote GitHub path</td>
			</tr>
			<tr>
			<td style="padding: 0 10px 8px 0"><input type="text" name="githubLocalPathNEW" value="" style="width: 250px"></td>
			<td style="padding: 0 0 8px 0"><input type="text" name="githubRemotePathNEW" value="" style="width: 250px"></td>
			</tr>
			<tr>
			<td colspan="2" style="padding: 3px 0 8px 0; text-align: right"><div style="display: inline-block; padding: 5px; background: #2187e7; color: #fff; font-size: 12px; cursor: pointer" onclick="document.getElementById('githubAddForm').submit()">Add</div></td>
			</tr>
			</table>
			<input type="hidden" name="csrf" value="<?php echo $_SESSION["csrf"]; ?>">
		</form>
	</div>

	<h2 style="margin-bottom: 10px">Usage Info:</h2>

	<p style="color: #888; margin: 0 10px 10px 0">Enter relative local paths (eg /server/myfiles) and absolute GitHub paths (eg https://github.com/user/repo or https://github.com/user/repo/tree/branch for branches), as per the examples. With this done you have established the source paths at both locations, as a pair.</p>
	<p style="color: #888; margin: 0 10px 10px 0">You can then choose a path pair and this then becomes your new root path in ICEcoder.</p>
	<p style="color: #888; margin: 0 10px 10px 0">The file manager then displays a new GitHub icon, which you can click on to perform and show a diff check between the 2 sources. These diffs can then be committed and pushed to the remote path at GitHub or cloned to your local path, to sync your files.</p>
	<p style="color: #888; margin: 0 10px 0 0">If you want to set another root path, this can be done in the Help > Settings screen.</p>

</div>

</body>

</html>
