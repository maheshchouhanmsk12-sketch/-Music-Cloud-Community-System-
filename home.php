<?php 
include('db_connect.php');

$total_genres = 0;
$total_musics = 0;
$total_playlist = 0;
$total_users = 0;
$my_musics = 0;
$my_playlist = 0;

if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $q = $conn->query("SELECT * FROM genres");
    if ($q && $q instanceof mysqli_result) $total_genres = $q->num_rows;

    $q = $conn->query("SELECT * FROM uploads");
    if ($q && $q instanceof mysqli_result) $total_musics = $q->num_rows;

    $q = $conn->query("SELECT * FROM playlist");
    if ($q && $q instanceof mysqli_result) $total_playlist = $q->num_rows;

    $q = $conn->query("SELECT * FROM users where type = 2");
    if ($q && $q instanceof mysqli_result) $total_users = $q->num_rows;

    $uid = isset($_SESSION['login_id']) ? (int)$_SESSION['login_id'] : 0;
    if ($uid > 0) {
        $q = $conn->query("SELECT * FROM uploads where user_id = {$uid}");
        if ($q && $q instanceof mysqli_result) $my_musics = $q->num_rows;

        $q = $conn->query("SELECT * FROM playlist where user_id = {$uid}");
        if ($q && $q instanceof mysqli_result) $my_playlist = $q->num_rows;
    }
}
?>
<!-- Info boxes -->
        <div class="row">
          <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box bg-black border border-primary">
              <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-th-list text-gradient-primary"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Total Genres</span>
                <span class="info-box-number">
                  <?php echo $total_genres; ?>
                </span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <!-- /.col -->
           <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box bg-black border border-primary">
              <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-music text-gradient-primary"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Total Musics</span>
                <span class="info-box-number">
                  <?php echo $total_musics; ?>
                </span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <!-- /.col -->
          <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box bg-black border border-primary">
              <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-list text-gradient-primary"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Total Playlist</span>
                <span class="info-box-number">
                  <?php echo $total_playlist; ?>
                </span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box bg-black border border-primary">
              <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-users text-gradient-primary"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Total Users</span>
                <span class="info-box-number">
                  <?php echo $total_users; ?>
                </span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box bg-black border border-primary">
              <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-music text-gradient-primary"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">My Musics</span>
                <span class="info-box-number">
                  <?php echo $my_musics; ?>
                </span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <!-- /.col -->
          <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box bg-black border border-primary">
              <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-list text-gradient-primary"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">My Playlist</span>
                <span class="info-box-number">
                  <?php echo $my_playlist; ?>
                </span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          
      </div>
