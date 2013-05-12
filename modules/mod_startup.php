<?php
/**
 * This is the worker who is never recognised. Includes all the necessary files. Initializes a session if need be. Processes the main GET or POST
 * elements and includes necessary files. Calls the necessary functions/methods
 *
 * @author Kihara Absolomon <soloincc@movert.co.ke>
 * @since v0.1
 */
define('OPTIONS_COMMON_FOLDER_PATH', '../common/');

require_once OPTIONS_COMMON_FOLDER_PATH . 'mod_general_v0.6.php';
require_once 'lis_config';
require_once OPTIONS_COMMON_FOLDER_PATH . 'mod_objectbased_dbase_v0.8.php';
require_once OPTIONS_COMMON_FOLDER_PATH . 'mod_messages_v0.1.php';

//setting the date settings
date_default_timezone_set ('Africa/Nairobi');

//get what the user wants
$server_name=$_SERVER['SERVER_NAME'];
$queryString=$_SERVER['QUERY_STRING'];
$paging = (isset($_GET['page']) && $_GET['page']!='') ? $_GET['page'] : '';
$sub_module = (isset($_GET['do']) && $_GET['do']!='') ? $_GET['do'] : '';
$action = (isset($_POST['action']) && $_POST['action']!='') ? $_POST['action'] : '';
$user = isset($_SESSION['user']) ? $_SESSION['user'] : '';

//check if we want to download
if(isset($_GET['action']) && $_GET['action'] != '' && in_array($paging, Config::$downloadableModules) && in_array($sub_module, Config::$downloadableSubModules)){
   $action = $_GET['action'];
   Config::$downloadFile = true;
}

define('OPTIONS_HOME_PAGE', $_SERVER['PHP_SELF']);
define('OPTIONS_REQUESTED_MODULE', $paging);
define('OPTIONS_CURRENT_USER', $user);
/**
 * @var string    What the user wants
 */
define('OPTIONS_REQUESTED_SUB_MODULE', $sub_module);
define('OPTIONS_REQUESTED_ACTION', $action);
$t = pathinfo($_SERVER['SCRIPT_FILENAME']);
$requestType = ($t['basename'] == 'mod_ajax.php') ? 'ajax' : 'normal';
define('OPTIONS_REQUEST_TYPE', $requestType);

require_once 'mod_lis_general.php';
$Lis = new Lis();

//before doing anything, ensure that all is ok
$res = $Lis->SystemCheck();
if(is_string($res)) die($res);


if(!Config::$downloadFile){
   //lets initiate the sessions
   session_save_path(Config::$config['dbase']);
   session_name('lis');
   $res = $Lis->SessionStart();
}

//log all the requests
$Lis->Dbase->CreateLogEntry("\n\n".  str_repeat('=', 140)."\nNew Request:\n", 'audit');
$Lis->Dbase->CreateLogEntry("POST User request: \n".print_r($_POST, true), 'audit');
$Lis->Dbase->CreateLogEntry("GET User request: \n".print_r($_POST, true), 'audit');

if(Config::$logSettings['loglevel'] == 'extensive'){
   $Lis->Dbase->CreateLogEntry("\n\n".  str_repeat('=', 140)."\nNew Request:\n", 'debug');
   $Lis->Dbase->CreateLogEntry("Session Data: \n".print_r($_SESSION, true), 'debug');
//   $Lis->Dbase->CreateLogEntry("Uploaded files: \n".print_r($_FILES, true), 'debug');
   $Lis->Dbase->CreateLogEntry("Post User request: \n".print_r($_POST, true), 'debug');
//   $Lis->Dbase->CreateLogEntry("Get User request: \n".print_r($_GET, true), 'debug');
//   $Lis->Dbase->CreateLogEntry("Cookies: \n".print_r($_COOKIE, true), 'debug');
//   $Lis->Dbase->CreateLogEntry("\n\n".  str_repeat('=', 140)."\nNew Request:\n", 'audit');
//   $Lis->Dbase->CreateLogEntry("POST User request: \n".print_r($_POST, true), 'audit');
//   $Lis->Dbase->CreateLogEntry("GET User request: \n".print_r($_POST, true), 'audit');
}
?>