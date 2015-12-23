<?php
require_once "kGoogle.class.php";
$j2t = new J2T();
$j2t->setLink = 'https://plus.google.com/photos/110674564899938367004/albums/5892112910843960289/5892851523964171746?authkey=COm4naGt49f17QE&pid=5892851523964171746&oid=110674564899938367004';
$j2t->setFormat = isset($_GET['format']) ? $_GET['format'] : false;
?>
<?php if('json'!==$j2t->getFormat):?>
<script type="text/javascript" src="jwplayer-7.1.4/jwplayer.js"></script>
<script type="text/javascript">jwplayer.key="J0mvFGAqO9c8xegABEqyIF874U1kTQIhLTD4PHi78Ap1squ97IODAg==";</script>
<div id="myElement">Loading the player...</div>

<script type="text/javascript">
	jwplayer('myElement').setup({
		playlist: [{
			sources: <?php echo $j2t->run(); ?>,
		}],
		modes: [{
			type: "html5"
		},{
			type: "flash",
			src: "jwplayer-7.1.4/jwplayer.flash.swf"
		}],
		primary: "html5",
		provider: "jwplayer-7.1.4/PauMediaProvider.swf",
		width: 720,
		height: 420,
		aspectratio: "16:9"
	});
</script>
<?php else:?>
	 <?php echo $j2t->run(); ?>
<?php endif;?>
