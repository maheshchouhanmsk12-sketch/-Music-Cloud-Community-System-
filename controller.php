<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <?php
            $title = isset($_GET['page']) ? ucwords(str_replace("_", ' ', $_GET['page'])) : "Home";
             ?>
            <h1 class="m-0 text-gradient-primary"><?php echo $title ?></h1>
          </div><!-- /.col -->

        </div><!-- /.row -->
            <hr class="border-primary">
      </div><!-- /.container-fluid -->
<?php 
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
if (file_exists($page . '.php')) {
    include $page . '.php';
} else {
    include '404.html';
}
?>
<script>
	$(function () {
  bsCustomFileInput.init();
 	$('.select2').select2({
	    placeholder:"Please select here",
	    width: "100%"
	  });
    $('.summernote').summernote({
        height: 300,
        toolbar: [
            [ 'style', [ 'style' ] ],
            [ 'font', [ 'bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear'] ],
            [ 'fontname', [ 'fontname' ] ],
            [ 'fontsize', [ 'fontsize' ] ],
            [ 'color', [ 'color' ] ],
            [ 'para', [ 'ol', 'ul', 'paragraph', 'height' ] ],
            [ 'table', [ 'table' ] ],
            [ 'view', [ 'undo', 'redo', 'fullscreen', 'codeview', 'help' ] ]
        ]
    })

     $('.datetimepicker').datetimepicker({
		  format:'Y/m/d H:i',
		})
     

 $(".switch-toggle").bootstrapToggle();
$('.number').on('input keyup keypress',function(){
        var val = $(this).val()
        val = val.replace(/[^0-9]/, '');
        val = val.replace(/,/g, '');
        val = val > 0 ? parseFloat(val).toLocaleString("en-US") : 0;
        $(this).val(val)
    })
  })
</script>