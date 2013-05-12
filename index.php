<?php require_once 'modules/mod_startup.php'; ?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title>Laboratory Information System</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
     <link rel='stylesheet' type='text/css' href='css/lis.css' />
     <link rel='stylesheet' type='text/css' href='css/customMessageBox.css' />
     <script type='text/javascript' src='<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery-1.7.1.min.js'></script>
     <script type='text/javascript' src='<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery.json.js'></script>
     <script type='text/javascript' src='<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery.regex.js'></script>
     <script type='text/javascript' src='<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>customMessageBox.js'></script>
     <script type='text/javascript' src='<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>notification.js'></script>
     <script type='text/javascript' src='<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>common_v0.2.js'></script>
     <script type='text/javascript' src='<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>sprintf.js'></script>
     <script type='text/javascript' src='js/lis.js'></script>
  </head>
  <body>
     <div id='lis'>
        <div id="header">
	    <div id='knh_logo'><a href='?page=home'><img src='images/knh.jpg' alt='KNH Logo' /></a></div>
           <div id='ccc_info'><br /><br />
               <h2>KENYATTA NATIONAL HOSPITAL</h2>
               <h3>COMPREHENSIVE CARE CENTRE</h3>
               <h4>P. O. BOX 20723, NAIROBI, KENYA.</h4><br />
               <h3 class="listitle">Laboratory Information System (LIS)</h3>
           </div>
           <div id='lis_logo'><img src='images/header.jpg' alt='KNH Logo' /></div>
        </div>
        <div id="main_div"><?php $Lis->TrafficController(); ?></div>
        <div id='footer_links'>
           <?php if (OPTIONS_REQUESTED_MODULE != 'login' && (is_null($Lis->Dbase->session['error']) || is_null($Lis->Dbase->session['timeout'])))
               echo $Lis->footerLinks;
            ?>
        </div>
        <div id="footer">&copy;2011 Laboratory Information System</div>
     </div>
     <div id='credits'>
        Designed By: Waruhari Philomena<br />
        Developed By: <a href='http://soloincc.movert.co.ke'>Kihara Absolomon</a>, <a href='http://movert.co.ke'>Movert</a>
     </div>
  </body>
</html>
