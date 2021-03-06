<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.View.Layouts
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

$cakeDescription = __d('cake_dev', 'New Project');
$cakeVersion = __d('cake_dev', 'CakePHP %s', Configure::version())
?>
<!DOCTYPE html>
<html class="<?php if($this->params['controller'] == 'users' && $this->params['action'] == 'login'){ echo 'login-page'; } ?>">
<head>
<link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
<style>
#needslogin { display: none }
</style>
	<?php echo $this->Html->charset(); ?>
	<title>
		<?php echo 'CMGR' ?>:
		<?php echo $this->fetch('title'); ?>
	</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link href='//fonts.googleapis.com/css?family=Raleway:400,300,600' rel='stylesheet' type='text/css'>
	<?php
		echo $this->Html->meta('icon');

    echo $this->Html->css('normalize');
        echo $this->Html->css('main');

        // echo $this->Html->css('main');

		echo $this->Html->script('jquery.min');
        echo $this->Html->script('modernizr');
        echo $this->Html->script('placeholder');
                echo $this->Html->script('fastclick');


    echo $this->Html->script('scripts');

		echo $this->fetch('meta');
		echo $this->fetch('css');
		echo $this->fetch('script');
	?>

</head>
<body data-controller="<?php echo @$dataController; ?>">




  <div class="contain-to-grid sticky">
      <?php echo $this->element('nav'); ?>


</div>

<!--     <div class="overlaybackground"><div id="commentcontainer"></div></div>
    <div id="slideout">
    </div> -->
	<div id="container">

		<div id="content">

			<div class="container large-12 medium-9 small-12 columns">

			<?php echo $this->Session->flash(); ?>

			<?php echo $this->fetch('content'); ?>
		</div>

		<div id="footer">
			
		</div>

	</div>
	<?php echo $this->element('sql_dump'); ?>
  <script>
  $(document).foundation();
  $(document).foundation('reflow', 'off-canvas');
</script>
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-67842236-1', 'auto');
  ga('send', 'pageview');

</script>

</body>
</html>
