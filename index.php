<?php


if ($_REQUEST['output']) {
	$output = $_REQUEST['output'];
	//print_r($_REQUEST);
	//echo 'output to '.$_GET['output'];
	switch ($output) {
		case 'csvfile':
			output_csv();
			break;
		case 'jsonfile':
			jsonfile();
			break;
		case 'screen':
			output_screen();
			break;
		case 'table':
			$rows = db_get();
			echo build_table($rows);
			break;
		default:
			echo 'error: invalid output "'.$output.'"';
			break;
	}
	exit;
} else if ($_REQUEST['getSavedQueries']) {
	echo 'show saved queries';
	exit;
}



/*
<label for="savedqueries">Saved Queries</label>
<select>
	<option></option>
</select>

$savedqueries = file_get_contents('savedqueries.csv');

*/

?>

<style type="text/css" media="screen">
body {
	background: black;
	color: orange;
}
input[type=text] {
    background: black;
    color: orange;
    font-size: 13px;
    border: 1px solid #444444;
    padding: 3px;
}
#editor, .ace_editor {
	background: #272822;
	border: 1px solid black;
	color: white;
	display: block;
	height: 100px;
	width: 100%;
}
#run {
    display: block;
    margin: 10px 0;
    padding: 5px 10px;
    background: orange;
    color: black;
    border: 1px solid orange;
    font-weight: bold;
    font-size: 17px;
    cursor: pointer;
}	
</style>

<?php if ($result) { ?>
<label for="sql">Result:</label>
<div name="sql" id="sql"><?php echo '<pre>'; print_r($result); echo '</pre>'; ?></div>
<?php } ?>

<h1>RunMySQL</h1>

<form method="get" target="outputwindow">
	<table>
	<tr>
		<td><label for="host">MySQL Host:</label></td>
		<td><input type="text" name="host" value="localhost" id="host" /></td>
	</tr>
	<tr>
		<td><label for="port">Port:</label></td>
		<td><input type="text" name="port" value="3306" id="port" /></td>
	</tr>
	<tr>
		<td><label for="dbname">DB Name:</label></td>
		<td><input type="text" name="dbname" value="dbname" id="dbname" /></td>
	</tr>
	<tr>
		<td><label for="username">Username:</label></td>
		<td><input type="text" name="username" value="username" id="username" /></td>
	</tr>
	<tr>
		<td><label for="password">Password:</label></td>
		<td><input type="text" name="password" value="password" id="password" /></td>
	</tr>
	</table>

	<br><br>

	<label for="editor">SQL Query:</label>
    <div id="editor"></div>
    <textarea name="sql" id="sql" style="display: none;"></textarea>

	<label for="output">Output to:</label>
	<input type="radio" name="output" value="csvfile" id="csvfile" checked="checked" /> <label for="csvfile">CSV File</label>
	<input type="radio" name="output" value="jsonfile" id="jsonfile" /> <label for="jsonfile">JSON File</label>
	<input type="radio" name="output" value="screen" id="screen" /> <label for="screen">Screen</label>
	<input type="radio" name="output" value="table" id="table" /> <label for="table">Table</label>

	<button id="run">Run SQL</button>
</form>

<script src="jquery-3.2.1.js" type="text/javascript" charset="utf-8"></script>
<script src="ace/ace.js" type="text/javascript" charset="utf-8"></script>
<script>
    var editor = ace.edit("editor");
    editor.setTheme("ace/theme/monokai");
    editor.getSession().setMode("ace/mode/sql");
	var textarea = $('textarea[name="sql"]');
	textarea.val(editor.getSession().getValue());
	editor.getSession().on("change", function () {
	    textarea.val(editor.getSession().getValue());
	});

	host.value = localStorage.host || 'localhost'
	port.value = localStorage.port || '3306'
	dbname.value = localStorage.dbname || 'dbname'
	username.value = localStorage.username || 'username'
	password.value = localStorage.password || 'password'
	editor.setValue(localStorage.sql || 'SELECT * FROM table LIMIT 5')

	$('form').submit(function(){
		host.port = host.value
		localStorage.port = port.value
		localStorage.dbname = dbname.value
		localStorage.username = username.value
		localStorage.password = password.value
		localStorage.sql = editor.getValue()
	})
</script>

<?php

function db_get() {
	$host = $_REQUEST['host'];
	$port = $_REQUEST['port'];
	$username = $_REQUEST['username'];
	$password = $_REQUEST['password'];
	$dbname = $_REQUEST['dbname'];
	$sql = $_REQUEST['sql'];

	try {
		$dbo = new PDO('mysql:host='.$host.';port='.$port.';dbname='.$dbname, $username, $password);
		$qry = $dbo->prepare($sql);

		$sentheader = false;
		$qry->execute();
		$rows = $qry->fetchAll(PDO::FETCH_ASSOC);
		return $rows;
	} catch (Exception $e) {
		echo 'error: ' . $e->getMessage();
	}
}

function jsonfile() {
	header('Content-Type: application/json');
	header('Content-Disposition: attachment; filename="export.json"');
	header('Pragma: no-cache');    
	header('Expires: 0');
	$rows = db_get();
	$json = json_encode($rows, JSON_PRETTY_PRINT);
	echo $json;
}

function output_csv(){
	$rows = db_get();
	header('Content-Type: text/csv');
	header('Content-Disposition: attachment; filename="export.csv"');
	header('Pragma: no-cache');    
	header('Expires: 0');
	$fp = fopen('php://output', 'w');
	$headers = array_keys($rows[0]);
	fputcsv($fp, $headers); // put the headers
	foreach ($rows as $row) {
		fputcsv($fp, array_values($row));
	}
	fclose($fp);
}

function output_screen(){
	$rows = db_get();
	echo '<pre>';
	print_r($rows);
	echo '</pre>';
}

function build_table($array){
    // start table
    $html = '<table>';
    // header row
    $html .= '<tr>';
    foreach($array[0] as $key=>$value){
            $html .= '<th>' . htmlspecialchars($key) . '</th>';
        }
    $html .= '</tr>';

    // data rows
    foreach( $array as $key=>$value){
        $html .= '<tr>';
        foreach($value as $key2=>$value2){
            $html .= '<td>' . htmlspecialchars($value2) . '</td>';
        }
        $html .= '</tr>';
    }

    // finish table and return it

    $html .= '</table>';
    return $html;
}

?>