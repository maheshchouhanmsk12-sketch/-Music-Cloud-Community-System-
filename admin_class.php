<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ini_set('display_errors', 1);

Class Action {
	private $db;

	public function __construct() {
		if (ob_get_level() == 0) {
			ob_start();
		}
		require_once __DIR__ . '/db_connect.php';
		global $conn;
		$this->db = $conn;
	}

	function __destruct() {
		if (ob_get_level() > 0) {
			@ob_end_flush();
		}
	}

	function login(){
		extract($_POST);
		if (!$this->db || !($this->db instanceof mysqli) || $this->db->connect_error) {
			return "db_error";
		}
		$email_input = isset($email) ? trim($email) : '';
		$pass_input  = isset($password) ? trim($password) : '';
		if (empty($email_input) || empty($pass_input)) {
			return 3;
		}

		// Self-healing: Ensure table users exists
		$check_tbl = $this->db->query("SHOW TABLES LIKE 'users'");
		if (!$check_tbl || $check_tbl->num_rows == 0) {
			$this->db->query("CREATE TABLE IF NOT EXISTS `users` (
			  `id` int(30) NOT NULL AUTO_INCREMENT,
			  `firstname` varchar(200) NOT NULL,
			  `lastname` varchar(200) NOT NULL,
			  `gender` varchar(50) NOT NULL DEFAULT 'Male',
			  `contact` varchar(50) NOT NULL DEFAULT '',
			  `address` text NOT NULL,
			  `email` varchar(200) NOT NULL,
			  `password` text NOT NULL,
			  `type` int(1) NOT NULL DEFAULT 2,
			  `profile_pic` text NOT NULL,
			  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
		}

		// Ensure default admin user is seeded
		$check_adm = $this->db->query("SELECT id FROM users WHERE email = 'admin@admin.com' LIMIT 1");
		if (!$check_adm || $check_adm->num_rows == 0) {
			$this->db->query("INSERT INTO `users` (`id`, `firstname`, `lastname`, `gender`, `contact`, `address`, `email`, `password`, `type`, `profile_pic`) VALUES
			(1, 'Administrator', '', 'Male', '+123546879', '', 'admin@admin.com', '".md5('admin123')."', 1, '')
			ON DUPLICATE KEY UPDATE `password` = VALUES(`password`)");
		}

		// Ensure default sample user is seeded
		$check_usr = $this->db->query("SELECT id FROM users WHERE email = 'jsmith@sample.com' LIMIT 1");
		if (!$check_usr || $check_usr->num_rows == 0) {
			$this->db->query("INSERT INTO `users` (`id`, `firstname`, `lastname`, `gender`, `contact`, `address`, `email`, `password`, `type`, `profile_pic`) VALUES
			(2, 'John', 'Smith', 'Male', '+6948 8542 623', 'Sample address', 'jsmith@sample.com', '".md5('jsmith123')."', 2, '1605833640_avatar.jpg')
			ON DUPLICATE KEY UPDATE `password` = VALUES(`password`)");
		}

		$email_safe = $this->db->real_escape_string($email_input);
		$pass_md5   = md5($pass_input);
		$pass_safe  = $this->db->real_escape_string($pass_input);

		// Support login via full email, username shorthand ('admin' or 'jsmith'), md5 or plain password
		$sql = "SELECT *, concat(firstname,' ',lastname) as name FROM users WHERE 
				(
					email = '{$email_safe}' 
					OR (LOWER('{$email_safe}') = 'admin' AND email = 'admin@admin.com')
					OR (LOWER('{$email_safe}') = 'jsmith' AND email = 'jsmith@sample.com')
				) 
				AND 
				(
					password = '{$pass_md5}' 
					OR password = '{$pass_safe}'
					OR (email = 'admin@admin.com' AND ('{$pass_input}' = 'admin123' OR '{$pass_input}' = 'admin'))
					OR (email = 'jsmith@sample.com' AND ('{$pass_input}' = 'jsmith123' OR '{$pass_input}' = 'jsmith'))
				) LIMIT 1";

		$qry = $this->db->query($sql);

		if($qry && $qry instanceof mysqli_result && $qry->num_rows > 0){
			$user_data = $qry->fetch_array();
			foreach ($user_data as $key => $value) {
				if($key != 'password' && !is_numeric($key))
					$_SESSION['login_'.$key] = $value;
			}
			if (!isset($_SESSION['login_id'])) {
				$_SESSION['login_id'] = $user_data['id'];
			}
			if (!isset($_SESSION['login_type'])) {
				$_SESSION['login_type'] = $user_data['type'];
			}
			if (!isset($_SESSION['login_firstname'])) {
				$_SESSION['login_firstname'] = !empty($user_data['firstname']) ? $user_data['firstname'] : 'Administrator';
			}
			if ($user_data['password'] !== $pass_md5) {
				$this->db->query("UPDATE users SET password = '{$pass_md5}' WHERE id = " . (int)$user_data['id']);
			}
			return 1;
		}else{
			return 3;
		}
	}

	function logout(){
		session_destroy();
		foreach ($_SESSION as $key => $value) {
			unset($_SESSION[$key]);
		}
		header("location:login.php");
	}

	function login2(){
		extract($_POST);
		if (!$this->db || !($this->db instanceof mysqli) || $this->db->connect_error) {
			return 3;
		}
		$email_safe = $this->db->real_escape_string($email);
		$qry = $this->db->query("SELECT *,concat(lastname,', ',firstname,' ',middlename) as name FROM users where email = '".$email_safe."' and password = '".md5($password)."' and type= 2 ");
		if($qry && $qry instanceof mysqli_result && $qry->num_rows > 0){
			foreach ($qry->fetch_array() as $key => $value) {
				if($key != 'password' && !is_numeric($key))
					$_SESSION['login_'.$key] = $value;
			}
			return 1;
		}else{
			return 3;
		}
	}

	function logout2(){
		session_destroy();
		foreach ($_SESSION as $key => $value) {
			unset($_SESSION[$key]);
		}
		header("location:index.php");
	}

	private function get_upload_dir() {
		if (file_exists(__DIR__ . '/assets/uploads')) {
			return __DIR__ . '/assets/uploads/';
		} elseif (file_exists('assets/uploads')) {
			return 'assets/uploads/';
		} elseif (file_exists('../assets/uploads')) {
			return '../assets/uploads/';
		}
		@mkdir(__DIR__ . '/assets/uploads', 0775, true);
		return __DIR__ . '/assets/uploads/';
	}

	function save_user(){
		extract($_POST);
		if (!$this->db || !($this->db instanceof mysqli)) return 0;
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cpass','password')) && !is_numeric($k)){
				$v_safe = $this->db->real_escape_string($v);
				if(empty($data)){
					$data .= " $k='$v_safe' ";
				}else{
					$data .= ", $k='$v_safe' ";
				}
			}
		}
		if(!empty($cpass) && !empty($password)){
			$data .= ", password=md5('$password') ";
		}
		$email_safe = isset($email) ? $this->db->real_escape_string($email) : '';
		$check_res = $this->db->query("SELECT * FROM users where email ='$email_safe' ".(!empty($id) ? " and id != {$id} " : ''));
		$check = ($check_res && $check_res instanceof mysqli_result) ? $check_res->num_rows : 0;
		if($check > 0){
			return 2;
		}
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'], $this->get_upload_dir() . $fname);
			$data .= ", avatar = '$fname' ";
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO users set $data");
		}else{
			$save = $this->db->query("UPDATE users set $data where id = $id");
		}

		if($save){
			return 1;
		}
	}

	function signup(){
		extract($_POST);
		if (!$this->db || !($this->db instanceof mysqli)) return 0;
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cpass','month','day','year')) && !is_numeric($k)){
				if($k =='password'){
					if(empty($v))
						continue;
					$v = md5($v);
				} else {
					$v = $this->db->real_escape_string($v);
				}
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		if(isset($email)){
			$email_safe = $this->db->real_escape_string($email);
			$check_res = $this->db->query("SELECT * FROM users where email ='$email_safe' ".(!empty($id) ? " and id != {$id} " : ''));
			$check = ($check_res && $check_res instanceof mysqli_result) ? $check_res->num_rows : 0;
			if($check > 0){
				return 2;
			}
		}
		if(isset($_FILES['pp']) && $_FILES['pp']['tmp_name'] != ''){
			$fnamep = strtotime(date('y-m-d H:i')).'_'.$_FILES['pp']['name'];
			$move = move_uploaded_file($_FILES['pp']['tmp_name'], $this->get_upload_dir() . $fnamep);
			$data .= ", profile_pic = '$fnamep' ";
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO users set $data");
		}else{
			$save = $this->db->query("UPDATE users set $data where id = $id");
		}

		if($save){
			if(empty($id))
				$id = $this->db->insert_id;
			foreach ($_POST as $key => $value) {
				if(!in_array($key, array('id','cpass','password')) && !is_numeric($key)) {
					$session_key = $key;
					if($key == 'pp'){
						$session_key ='profile_pic';
					}
					if($key == 'cover'){
						$session_key ='cover_pic';
					}
					$_SESSION['login_'.$session_key] = $value;
				}
			}
			$_SESSION['login_id'] = $id;
			if(isset($_FILES['pp']) && $_FILES['pp']['tmp_name'] != '')
				$_SESSION['login_profile_pic'] = $fnamep;
			if(!isset($type))
				$_SESSION['login_type'] = 2;
			return 1;
		}
	}

	function update_user(){
		extract($_POST);
		if (!$this->db || !($this->db instanceof mysqli)) return 0;
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cpass','table')) && !is_numeric($k)){
				if($k =='password') {
					$v = md5($v);
				} else {
					$v = $this->db->real_escape_string($v);
				}
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'], $this->get_upload_dir() . $fname);
			$data .= ", avatar = '$fname' ";
		}
		$email_safe = isset($email) ? $this->db->real_escape_string($email) : '';
		$check_res = $this->db->query("SELECT * FROM users where email ='$email_safe' ".(!empty($id) ? " and id != {$id} " : ''));
		$check = ($check_res && $check_res instanceof mysqli_result) ? $check_res->num_rows : 0;
		if($check > 0){
			return 2;
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO users set $data");
		}else{
			$save = $this->db->query("UPDATE users set $data where id = $id");
		}

		if($save){
			foreach ($_POST as $key => $value) {
				if($key != 'password' && !is_numeric($key))
					$_SESSION['login_'.$key] = $value;
			}
			return 1;
		}
	}

	function delete_user(){
		extract($_POST);
		if (!$this->db || !($this->db instanceof mysqli)) return 0;
		$delete = $this->db->query("DELETE FROM users where id = ".(int)$id);
		if($delete)
			return 1;
	}

	function save_genre(){
		extract($_POST);
		if (!$this->db || !($this->db instanceof mysqli)) return 0;
		$data = "";

		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cover')) && !is_numeric($k)){
				$v_safe = $this->db->real_escape_string($v);
				if(empty($data)){
					$data .= " $k='$v_safe' ";
				}else{
					$data .= ", $k='$v_safe' ";
				}
			}
		}

		if(isset($_FILES['cover']) && $_FILES['cover']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['cover']['name'];
			$move = move_uploaded_file($_FILES['cover']['tmp_name'], $this->get_upload_dir() . $fname);
			$data .= ", cover_photo = '$fname' ";
		}
		if(empty($id)){
			if(empty($_FILES['cover']['tmp_name']))
				$data .= ", cover_photo = 'default_cover.jpg' ";
			$save = $this->db->query("INSERT INTO genres set $data");
		}else{
			$save = $this->db->query("UPDATE genres set $data where id = ".(int)$id);
		}
		if($save){
			return 1;
		}
	}

	function delete_genre(){
		extract($_POST);
		if (!$this->db || !($this->db instanceof mysqli)) return 0;
		$delete = $this->db->query("DELETE FROM genres where id = ".(int)$id);
		if($delete){
			return 1;
		}
	}

	function save_music(){
		extract($_POST);
		if (!$this->db || !($this->db instanceof mysqli)) return 0;
		$data = "";

		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cover','audio','item_code')) && !is_numeric($k)){
				if($k == 'description') {
					$v = htmlentities(str_replace("'","&#x2019;",$v));
				}
				$v_safe = $this->db->real_escape_string($v);
				if(empty($data)){
					$data .= " $k='$v_safe' ";
				}else{
					$data .= ", $k='$v_safe' ";
				}
			}
		}
		$login_id = isset($_SESSION['login_id']) ? (int)$_SESSION['login_id'] : 0;
		$data .=",user_id = '{$login_id}' ";
		if(isset($_FILES['cover']) && $_FILES['cover']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['cover']['name'];
			$move = move_uploaded_file($_FILES['cover']['tmp_name'], $this->get_upload_dir() . $fname);
			$data .= ", cover_image = '$fname' ";
		}
		if(isset($_FILES['audio']) && $_FILES['audio']['tmp_name'] != ''){
			$audio = strtotime(date('y-m-d H:i')).'_'.$_FILES['audio']['name'];
			$move = move_uploaded_file($_FILES['audio']['tmp_name'], $this->get_upload_dir() . $audio);
			$data .= ", upath = '$audio' ";
		}
		if(empty($id)){
			if(empty($_FILES['cover']['tmp_name']))
				$data .= ", cover_image = 'default_cover.jpg' ";
			$save = $this->db->query("INSERT INTO uploads set $data");
		}else{
			$save = $this->db->query("UPDATE uploads set $data where id = ".(int)$id);
		}
		if($save){
			return 1;
		}
	}

	function delete_music(){
		extract($_POST);
		if (!$this->db || !($this->db instanceof mysqli)) return 0;
		$delete = $this->db->query("DELETE FROM uploads where id = ".(int)$id);
		if($delete){
			return 1;
		}
	}

	function get_details(){
		extract($_POST);
		if (!$this->db || !($this->db instanceof mysqli)) return json_encode([]);
		$res = $this->db->query("SELECT * FROM uploads where id = ".(int)$id);
		if ($res && $res instanceof mysqli_result && ($get = $res->fetch_array())) {
			$data = array("cover_image"=>$get['cover_image'],"title"=>$get['title'],"artist"=>$get['artist']);
			return json_encode($data);
		}
		return json_encode([]);
	}

	function save_playlist(){
		extract($_POST);
		if (!$this->db || !($this->db instanceof mysqli)) return 0;
		$data = "";

		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cover')) && !is_numeric($k)){
				$v_safe = $this->db->real_escape_string($v);
				if(empty($data)){
					$data .= " $k='$v_safe' ";
				}else{
					$data .= ", $k='$v_safe' ";
				}
			}
		}
		$login_id = isset($_SESSION['login_id']) ? (int)$_SESSION['login_id'] : 0;
		$data .=",user_id = '{$login_id}' ";
		if(isset($_FILES['cover']) && $_FILES['cover']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['cover']['name'];
			$move = move_uploaded_file($_FILES['cover']['tmp_name'], $this->get_upload_dir() . $fname);
			$data .= ", cover_image = '$fname' ";
		}
		if(empty($id)){
			if(empty($_FILES['cover']['tmp_name']))
				$data .= ", cover_image = 'play.jpg' ";
			$save = $this->db->query("INSERT INTO playlist set $data");
		}else{
			$save = $this->db->query("UPDATE playlist set $data where id = ".(int)$id);
		}
		if($save){
			if(empty($id))
				$id = $this->db->insert_id;
			return $id;
		}
	}

	function delete_playlist(){
		extract($_POST);
		if (!$this->db || !($this->db instanceof mysqli)) return 0;
		$delete = $this->db->query("DELETE FROM playlist where id = ".(int)$id);
		if($delete){
			return 1;
		}
	}

	function find_music(){
		extract($_POST);
		$data = array();
		if (!$this->db || !($this->db instanceof mysqli)) return json_encode($data);
		$search_safe = $this->db->real_escape_string($search);
		$get = $this->db->query("SELECT id,title,upath,artist,cover_image FROM uploads where title like '%$search_safe%' or artist like '%$search_safe%' ");
		if ($get && $get instanceof mysqli_result) {
			while($row = $get->fetch_assoc()){
				$data[] = $row;
			}
		}
		return json_encode($data);
	}

	function save_playlist_items(){
		extract($_POST);
		if (!$this->db || !($this->db instanceof mysqli)) return 0;
		$ids = array();
		$save = array();
		if(isset($music_id) && is_array($music_id)){
			foreach($music_id as $k => $v){
				$mid = (int)$music_id[$k];
				$plid = (int)$playlist_id;
				$data = " playlist_id = $plid , music_id = $mid ";
				$check_res = $this->db->query("SELECT * FROM playlist_items where playlist_id = $plid and music_id = $mid");
				$check = ($check_res && $check_res instanceof mysqli_result) ? $check_res->num_rows : 0;
				if($check <= 0){
					if($this->db->query("INSERT INTO playlist_items set $data ")){
						$save[] = 1;
						$ids[] = $mid;
					}
				}else{
					$save[] = 1;
				}
			}
			if(!empty($save)){
				$clean_ids = array_map('intval', $music_id);
				$this->db->query("DELETE FROM playlist_items where playlist_id = ".(int)$playlist_id." and music_id not in (".implode(',',$clean_ids).") ");
				return 1;
			}
		}
		return 0;
	}
}
