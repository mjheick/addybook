<?php
/**
 * Load address book and sort by the following:
 * family, first, stuff
 */
$addressbook_path = './';
$addressbook_filename = 'address_book.yml';
$addybook = yaml_parse(file_get_contents($addressbook_path . $addressbook_filename));
function addybook_sorter($a, $b)
{
	$a_family = $a['family-name'];
	$b_family = $b['family-name'];
	// alphasort
	for ($x = 0; $x < strlen($a_family); $x++)
	{
		if ($x > strlen($b_family))
		{
			break;
		}
		$a_ord = ord(strtolower(substr($a_family, $x, 1)));
		$b_ord = ord(strtolower(substr($b_family, $x, 1)));
		if ($a_ord < $b_ord)
		{
			return -1;
		}
		if ($a_ord > $b_ord)
		{
			return 1;
		}
	}

	// if we're down here, we're equal so far.
	$a_family = $a['first-name'];
	$b_family = $b['first-name'];
	// alphasort
	for ($x = 0; $x < strlen($a_family); $x++)
	{
		if ($x > strlen($b_family))
		{
			break;
		}
		$a_ord = ord(strtolower(substr($a_family, $x, 1)));
		$b_ord = ord(strtolower(substr($b_family, $x, 1)));
		if ($a_ord < $b_ord)
		{
			return -1;
		}
		if ($a_ord > $b_ord)
		{
			return 1;
		}
	}
	return 0;
}
usort($addybook, "addybook_sorter");

/* Are we POSTING? */
$id = isset($_POST['id']) ? trim($_POST['id']) : null;
if (!is_null($id))
{
	/* Check if we're posting everything. */
	$fields = ['family-name', 'first-name', 'address', 'city', 'state', 'zip', 'home-phone', 'notes'];
	foreach ($fields as $field)
	{
		if (!isset($_POST[$field]))
		{
			header('Location: ' . $_SERVER['PHP_SELF'] . '#error', 302);
			die();
		}
	}
	// lets make the array
	$item = array(
		'family-name' => trim($_POST['family-name']),
		'first-name' => trim($_POST['first-name']),
		'address' => trim($_POST['address']),
		'city' => trim($_POST['city']),
		'state' => trim($_POST['state']),
		'zip' => trim($_POST['zip']),
		'home-phone' => trim($_POST['home-phone']),
		'notes' => trim($_POST['notes']),
	);
	/* Before we edit we check if we have a "backup" of current */
	if (!file_exists($addressbook_path . 'backup/' . $addressbook_filename . '-' . date("Ymd")))
	{
		@file_put_contents($addressbook_path . 'backup/' . $addressbook_filename . '-' . date("Ymd"), yaml_emit($addybook));
	}

	if (strlen($id) > 0) /* editing */
	{
		$item['entry'] = $addybook[$id]['entry']; /* Preserve old data */
		$item['last-update'] = date("Y-m-d");
		$addybook[$id] = $item;

	} else { /* Add new */
		$item['entry'] = date("Y-m-d");
		$item['last-update'] = date("Y-m-d");
		$addybook[] = $item;

	}
	file_put_contents($addressbook_path . $addressbook_filename, yaml_emit($addybook));
	header('Location: ' . $_SERVER['PHP_SELF'] . '#item' . $id, 302);
	die();
}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Address Book</title>
		<meta name='viewport' content='width=device-width, initial-scale=1'>
		<style>
/* Invert colors */
body {
	background: black;
	color: white;
}
a {
	color: lightgreen;
}
#personform {
	display: none;
}
div.container {
	border: 1px solid white;
	padding:  4px;
	margin: 10px 0px;
}
		</style>
		<script>
function addNew()
{
	// clear out the form fields
	document.getElementById('frm-id').value = "";
	document.getElementById('frm-family-name').value = "";
	document.getElementById('frm-first-name').value = "";
	document.getElementById('frm-address').value = "";
	document.getElementById('frm-city').value = "";
	document.getElementById('frm-state').value = "";
	document.getElementById('frm-zip').value = "";
	document.getElementById('frm-home-phone').value = "";
	document.getElementById('frm-notes').value = "";
	document.getElementById('personform').style.display = "block";
}
function editItem(id)
{
	// fill in the form fields
	document.getElementById('frm-id').value = id;
	document.getElementById('frm-family-name').value = document.getElementById('frm-family-name-' + id).value;
	document.getElementById('frm-first-name').value = document.getElementById('frm-first-name-' + id).value;
	document.getElementById('frm-address').value = document.getElementById('frm-address-' + id).value;
	document.getElementById('frm-city').value = document.getElementById('frm-city-' + id).value;
	document.getElementById('frm-state').value = document.getElementById('frm-state-' + id).value;
	document.getElementById('frm-zip').value = document.getElementById('frm-zip-' + id).value;
	document.getElementById('frm-home-phone').value = document.getElementById('frm-home-phone-' + id).value;
	document.getElementById('frm-notes').value = document.getElementById('frm-notes-' + id).value;
	document.getElementById('personform').style.display = "block";
	window.scrollTo(0, 0);
}
function fastSearch()
{
	let v = document.getElementById('searchy').value;
	if ((v.length == 0) || (v == '')) /* show everything */
	{
		let containers = document.getElementsByClassName('container')
		for (let x = 0; x < containers.length; x++)
		{
			let node = containers[x].getAttribute("id");
			document.getElementById(node).style.display = 'block';
		}
		return;
	}
	let query = v.toLowerCase(); /* compare everything with the same case */
	let s = document.getElementsByClassName('frm-srch'); /* get all search things */
	for (let x = 0; x < s.length; x++)
	{
		/* get the list of items to search through */
		let node = s[x].getAttribute("id");
		let idnum = node.substr(11);
		let srch = document.getElementById(node).value;
		let srchlwr = srch.toLowerCase();
		if (srchlwr.indexOf(query) == -1) /* Hide this entry */
		{
			document.getElementById('item-' + idnum).style.display = 'none';
		}
		else
		{
			document.getElementById('item-' + idnum).style.display = 'block';
		}
	}
}
		</script>
	</head>
	<body>
		<div>Search: <input type="text" id="searchy" value="" onkeyup="javascript:fastSearch();"/></div>
		<div>[<a href="javascript:addNew();">add new</a>]</div>
		<div id="personform">
			<table>
				<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
				<input type="hidden" name="id" id="frm-id" value="" />
				<tr><td>Family Name</td><td><input type="text" name="family-name" id="frm-family-name" value="" /></td></tr>
				<tr><td>Names</td><td><input type="text" name="first-name" id="frm-first-name" value="" /></td></tr>
				<tr><td>Address</td><td><input type="text" name="address" id="frm-address" value="" /></td></tr>
				<tr><td>City</td><td><input type="text" name="city" id="frm-city" value="" /></td></tr>
				<tr><td>State</td><td><input type="text" name="state" id="frm-state" value="" /></td></tr>
				<tr><td>Zip</td><td><input type="text" name="zip" id="frm-zip" value="" /></td></tr>
				<tr><td>Home Phone</td><td><input type="text" name="home-phone" id="frm-home-phone" value="" /></td></tr>
				<tr><td>Notes</td><td><textarea name="notes" id="frm-notes"></textarea></td></tr>
				<tr><td colspan="2">
					<input type="submit" />
					<button onclick="document.getElementById('personform').style.display='none';return false;">Hide</button>
				</td></tr>
				</form>
			</table>
		</div>
		<div>
<?php
foreach ($addybook as $idx => $v)
{
	$name = $v['family-name'] . ", " . $v['first-name'];
	$address = $v['address'] . ", " . $v['city'] . ", " . $v['state'] . " " . $v['zip'];
	echo '<div id="item-' . $idx . '" class="container">';
	echo '<a name="item' . $idx . '" />';
	echo '<div><a href="javascript:editItem(' . $idx . ');">[e]</a>&nbsp;' .  $name . '</div>';
	echo '<div>' . $address . '&nbsp;<a href="https://www.google.com/maps/place/' . urlencode($address) . '" target="_blank">[m]</a></div>';
	if (strlen($v['home-phone']) > 0) {
		echo '<div>' . $v['home-phone'] . '&nbsp;<a href="tel:' . $v['home-phone'] . '">[c]</a></div>';
	}
	if (strlen($v['notes']) > 0) {
		echo '<div>' . $v['notes'] . '</div>';
	}
	echo '<div style="font-size: 75%;">E:' . $v['entry'] . ', LU:' . $v['last-update'] . '</div>';
	echo '<input type="hidden" id="frm-family-name-' . $idx . '" value="' . $v['family-name'] . '" />';
	echo '<input type="hidden" id="frm-first-name-' . $idx . '" value="' . $v['first-name'] . '" />';
	echo '<input type="hidden" id="frm-address-' . $idx . '" value="' . $v['address'] . '" />';
	echo '<input type="hidden" id="frm-city-' . $idx . '" value="' . $v['city'] . '" />';
	echo '<input type="hidden" id="frm-state-' . $idx . '" value="' . $v['state'] . '" />';
	echo '<input type="hidden" id="frm-zip-' . $idx . '" value="' . $v['zip'] . '" />';
	echo '<input type="hidden" id="frm-home-phone-' . $idx . '" value="' . $v['home-phone'] . '" />';
	echo '<input type="hidden" id="frm-notes-' . $idx . '" value="' . $v['notes'] . '" />';
	echo '<input type="hidden" class="frm-srch" id="frm-search-' . $idx . '" value="' . implode(' ', [$v['first-name'], $v['family-name'], $v['address'], $v['city'], $v['state'], $v['zip']]) . '" />';
	echo "</div>";
}
?>
		</div>
	</body>
</html>
