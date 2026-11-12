<?php
include_once 'db_connect.php';
if(isset($_GET['id'])){
	$id = (int)$_GET['id'];
	if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
		$res = $conn->query("SELECT * FROM uploads where id = ".$id);
		if ($res && $res instanceof mysqli_result && ($qry = $res->fetch_array())) {
			foreach($qry as $k => $v){
				if($k=='title')
					$k = 'mtitle';
				$$k = $v;
			}
		}
	}
}
include 'new_music.php';
?>
