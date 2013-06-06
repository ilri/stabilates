<?php require_once 'modules/mod_startup.php'; ?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title>The Biorepository Stabilates</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
     <link rel='stylesheet' type='text/css' href='<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>bootstrap/css/bootstrap.min.css' />
     <link rel='stylesheet' type='text/css' href='css/stabilates.css' />
     <link rel='stylesheet' type='text/css' href='css/customMessageBox.css' />
     <script type='text/javascript' src='<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jquery-1.8.3.min.js'></script>
     <script type='text/javascript' src='<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jquery.json.js'></script>
     <script type='text/javascript' src='<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jquery.regex.js'></script>
     <script type='text/javascript' src='<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>customMessageBox.js'></script>
     <script type='text/javascript' src='<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>notification.js'></script>
     <script type='text/javascript' src='<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>common_v0.2.js'></script>
     <script type='text/javascript' src='<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>sprintf.js'></script>
     <script type='text/javascript' src='<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>bootstrap/js/bootstrap.min.js'></script>
  </head>
  <body>
     <div id='stabilates'>
        <div id="header"></div>
        <div id="main_div"><?php $Stabilates->TrafficController(); ?></div>
        <div>
           <?php if (OPTIONS_REQUESTED_MODULE != 'login' && (is_null($Stabilates->Dbase->session['error']) || is_null($Stabilates->Dbase->session['timeout'])))
               echo $Stabilates->footerLinks;
            ?>
        </div>
        <div id="footer">&copy;2013 The Biorepository</div>
     </div>
     <div id='credits'>
        Designed and Developed By: Kihara Absolomon<br />
     </div>
  </body>
</html>
