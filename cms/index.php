<?php require('./dat/main.php');?>
<!doctype html>
<html lang="en">

  <head>
  
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
  
    <title>The 2214 Saga Visual Novel</title>
    <meta name="description" content="The 2214 Saga reboot!  From web comic to interactive visual novel format." />
    
    <base href="<?php echo $base ?>/" />
  
    <link rel="stylesheet" type="text/css" href="cms.css" />
    <script type="text/javascript" src="cms.js"></script>
    
    <link rel="icon" href="../img/favicon.ico" type="image/x-icon" />
    
    <link rel="alternate" type="application/rss+xml" title="RSS" href="../rss/" />

  </head>
  <body>
    <?php echo $html['main']; ?>
  </body>
</html>