<?php
/**
 * This module will have the general functions that appertains to the system
 *
 * @category   Stabilates
 * @package    Main
 * @author     Kihara Absolomon <a.kihara@cgiar.org>
 * @since      v0.1
 */
class Stabilates extends DBase {

   /**
    * @var Object An object with the database functions and properties. Implemented here to avoid having a million & 1 database connections
    */
   public $Dbase;

   public $addinfo;

   /**
    * @var  array    An array with the stabilate parentage
    */
   private $parentage = array();

   public $footerLinks = '';

   /**
    * @var  integer  The multiplying factor in determining the size of the circle. An arbitrary number
    */
   private $circleFactor = 732;

   /**
    * @var  string   Just a string to show who is logged in
    */
   public $whoisme = '';

   public function  __construct() {
      $this->Dbase = new DBase('mysql');
      $this->Dbase->InitializeConnection();
      if($this->Dbase->dbcon->connect_error || (isset($this->Dbase->dbcon->errno) && $this->Dbase->dbcon->errno!=0)) {
         die('Something wicked happened when connecting to the dbase.');
      }
      $this->Dbase->InitializeLogs();

      //if we are looking to download a file, log in first
      if(Config::$downloadFile){
         $res = $this->Dbase->ConfirmUser($_GET['u'], $_GET['t'], $_GET['cl_pass']);
         if($res == 0){   //this is a valid user
            //get his/her data and add them to the session data
            $res = $this->GetCurrentUserDetails();
            if($res == 1){
               $this->LoginPage('Sorry, There was an error while fetching data from the database. Please try again later');
               return;
            }
            //initialize the session variables
            $_SESSION['surname'] = $res['sname']; $_SESSION['onames'] = $res['onames']; $_SESSION['user_level'] = $res['user_level'];
            $_SESSION['user_id'] = $res['user_id'];
         }
         else die('Permission Denied. You do not have permission to access this module');
      }
   }

   /**
    * Controls the program execution
    */
   public function TrafficController(){
      if(OPTIONS_REQUESTED_MODULE != 'login' && !Config::$downloadFile){  //when we are normally browsing, check that we have the right credentials
         //we hope that we have still have the right credentials
         $this->Dbase->ManageSession();
         $this->whoisme = "{$_SESSION['surname']} {$_SESSION['onames']}, {$_SESSION['user_level']}";
      }
      if(OPTIONS_REQUESTED_MODULE == 'logout'){
         $this->Dbase->LogOut(); $this->Dbase->session['restart'] = true;
      }
      if(!Config::$downloadFile && ($this->Dbase->session['error'] || $this->Dbase->session['timeout'])){
         if(OPTIONS_REQUEST_TYPE == 'normal'){
            $this->LoginPage($this->Dbase->session['message'], $_SESSION['username']);
            return;
         }
         elseif(OPTIONS_REQUEST_TYPE == 'ajax') die('-1' . $this->Dbase->session['message']);
      }
//      echo '<pre>'. print_r($_SESSION, true) .'</pre>';
      if(!isset($_SESSION['user_id']) && !in_array(OPTIONS_REQUESTED_MODULE, array('login', 'logout', ''))){
         if(OPTIONS_REQUEST_TYPE == 'ajax') die('-1' .OPTIONS_MSSG_INVALID_SESSION);
         else{
            $this->LoginPage(OPTIONS_MSSG_INVALID_SESSION);
            return;
         }
      }
      if(!in_array(OPTIONS_REQUESTED_MODULE, array('login', ''))){
         if($this->WhoIsMe()){
            $this->footerLinks = '';      //clear the footer links
            return;
         }
      }

      //Set the default footer links
      $this->footerLinks = "";
      if(OPTIONS_REQUESTED_MODULE == '') $this->LoginPage();
      elseif(OPTIONS_REQUESTED_MODULE == 'logout') $this->LogOutCurrentUser();
      elseif(OPTIONS_REQUESTED_MODULE == 'login')  $this->ValidateUser();
      elseif(OPTIONS_REQUESTED_MODULE == 'home') $this->StabilatesHomePage();
      elseif(OPTIONS_REQUESTED_MODULE == 'stabilates'){
         if(OPTIONS_REQUEST_TYPE == 'normal'){
            echo "<script type='text/javascript'>$('#top_links .back_link').html('<a href=\'?page=home\'>Back</a>');</script>";
            echo "<script type='text/javascript' src='js/stabilates.js'></script>";
         }

         if(isset($_GET['query'])) $this->FetchData();
         elseif(OPTIONS_REQUESTED_ACTION == 'save') $this->SaveStabilates();
         elseif(OPTIONS_REQUESTED_ACTION == 'save_passage') $this->SavePassages();
         elseif(OPTIONS_REQUESTED_ACTION == 'list_stabilates') $this->FetchData();
         elseif(OPTIONS_REQUESTED_ACTION == 'stabilate_data') $this->FetchData();
         elseif(OPTIONS_REQUESTED_ACTION == 'yellow_form') $this->CreateStabilatesYellowForm();
         elseif(OPTIONS_REQUESTED_ACTION == 'stabilate_history') $this->StabilateHistory();
         elseif(OPTIONS_REQUESTED_ACTION == 'stabilate_full_history') $this->StabilateFullHistory();
         elseif(OPTIONS_REQUESTED_SUB_MODULE == 'browse') $this->BrowseStabilates();
         elseif(OPTIONS_REQUESTED_SUB_MODULE == 'fetch') $this->FetchData();
         elseif(OPTIONS_REQUESTED_SUB_MODULE == 'passages') $this->FetchData();
         elseif(OPTIONS_REQUESTED_SUB_MODULE == 'stats') $this->StabilatesStats();
         elseif(OPTIONS_REQUESTED_SUB_MODULE == 'list') $this->ListStabilates();
         elseif(OPTIONS_REQUESTED_SUB_MODULE == 'yellow_form') $this->StabilatesYellowForm();
         elseif(in_array(OPTIONS_REQUESTED_SUB_MODULE, array('parasite_stats', 'host_stats', 'country_stats'))) $this->FetchData();
      }
      elseif(OPTIONS_REQUESTED_MODULE == 'cultures'){
         require_once 'mod_cultures.php';
         $Cultures = new Cultures($this->Dbase);
         $Cultures->TrafficController();

      }
      elseif(OPTIONS_REQUESTED_MODULE == 'users'){
         require_once 'mod_users.php';
         $Users = new Users($this->Dbase);
         $Users->TrafficController();
      }
      else{
         $this->Dbase->CreateLogEntry(print_r($_POST, true), 'debug');
         $this->Dbase->CreateLogEntry(print_r($_GET, true), 'debug');
         $this->StabilatesHomePage(OPTIONS_MSSG_MODULE_UNKNOWN);
      }

   }

   /**
    * Crates the login page for users to log into the system
    *
    * @param   string   $addinfo    (Optional) Any additional information that we might want to display to the users
    * @param   string   $username   (Optional) In case there was an error in the previous login attemp, now we want to try again
    */
   public function LoginPage($addinfo = '', $username = ''){
      $this->footerLinks = '';
      $count = (!isset($_POST['count'])) ? 0 : $_POST['count']+1 ;
      $hidden = "<input type='hidden' name='count' value='$count' />";
      if($addinfo == '') $addinfo = 'Please enter your username and password to access the biorepository resources.';
      if(OPTIONS_REQUEST_TYPE == 'normal') echo "<script type='text/javascript' src='". OPTIONS_COMMON_FOLDER_PATH ."jquery.md5.js'></script>";
      if($count == Config::$psswdSettings['maxNoofTries']){
         $this->LockAccount();
         $addinfo .= "<br />You have had $count attempts. <b>Your account is disabled.</b>" . Config::$contact;
      }
      elseif($count == Config::$psswdSettings['maxNoofTries'] -1){
         $addinfo .= "<br />You have had $count attempts. You have 1 more attempt to log in before your account is disabled.";
      }
?>
<script type='text/javascript' src='js/stabilates.js'></script>
<div id='main' class='login'>
   <form action="?page=login" name='login_form' method='POST'>
      <div id='login_page'>
         <div id='addinfo'><?php echo $addinfo; ?></div>
         <table>
            <tr><td>Username</td><td><input type="text" name="username" size="15"/></td></tr>
            <tr><td>Password</td><td><input type="password" name="password" size="15" /></td></tr>
            <input type="hidden" name="md5_pass" />
            <input type="hidden" name="ldap_pass" />
         </table>
         <div class='buttons'><input type="submit" name="login" value="Log In" />   <input type="reset" value="Cancel" /></div>
     </div>
     <?php echo $hidden; ?>
  </form>
</div>
<?php
      if(OPTIONS_REQUEST_TYPE == 'normal'){
         echo "<script type='text/javascript'>
                 $('[name=login]').bind('click', Stabilates.submitLogin);
                 $('[name=username]').focus();
             </script>";
      }
   }

   /**
    * Creates the home page for the users after they login
    */
   public function StabilatesHomePage($addinfo = ''){
      //include the samples functions if need be
      if($_SESSION['user_level'] == 'Super Administrator') $this->SysAdminsHomePage($addinfo);
      else if($_SESSION['user_level'] == 'Administrator') $this->AdminsHomePage($addinfo);
      else if($_SESSION['user_level'] == 'Visitor') $this->VisitorsHomePage($addinfo);
      echo "<script type='text/javascript'>$('.back_link').html('&nbsp;');</script>";
   }

   /**
    * Validates the user credentials as received from the client
    */
   private function ValidateUser(){
      $username = $_POST['username']; $password = $_POST['md5_pass']; $cl_pass = $_POST['ldap_pass'];
      //check if we have the user have specified the credentials
      if($username == '' || $password == ''){
         if($username == '') $this->LoginPage("Incorrect login credentials. Please specify a username to log in to the system.");
         elseif($password == '') $this->LoginPage('Incorrect login credentials. Please specify a password to log in to the system.', $username);
         return;
      }
      //now check that the specified username and password are actually correct
      //at this case we assume that we md5 our password when it is being sent from the client side
      $res = $this->Dbase->ConfirmUser($username, $password, $cl_pass);
      if($res == 1){
         $this->LoginPage('Error! There was an error while authenticating the user.');
         return;
      }
      elseif($res == 3){
         $this->CreateLogEntry("No account with the username '$username'.", 'info');
         $this->LoginPage("Sorry, there is no account with '$username' as the username.<br />Please log in to access the system.");
         return;
      }
      elseif($res == 4){
         $this->CreateLogEntry("Disabled account with the username '$username'.", 'info');
         $this->LoginPage("Sorry, the account with '$username' as the username is disabled.<br />" . Config::$contact);
         return;
      }
      elseif($res == 2){
         $this->CreateLogEntry("Login failed for user: '$username'.", 'info');
         $this->LoginPage('Sorry, the password that you have entered is not correct.<br />Please log in to access the system.');
         return;
      }
      elseif($res == 0){   //this is a valid user
         //get his/her data and add them to the session data
         $res = $this->GetCurrentUserDetails();
         if($res == 1){
            $this->LoginPage('Sorry, There was an error while fetching data from the database. Please try again later');
            return;
         }
         //initialize the session variables
         $_SESSION['surname'] = $res['sname']; $_SESSION['onames'] = $res['onames']; $_SESSION['user_level'] = $res['user_level'];
         $_SESSION['user_id'] = $res['user_id']; $_SESSION['password'] = $password; $_SESSION['username'] = $username; $_SESSION['cl_pass'] = $cl_pass;
         $this->WhoIsMe();
         $this->StabilatesHomePage();
         return;
      }
   }

   /**
    * Confirms the credentials of the person who is logged in and then displays a link on top of the person who is logged in
    *
    * @return integer   Returns 1 incase of an error or the person has wrong credentials, else returns 0
    */
   private function WhoIsMe(){
      if(OPTIONS_REQUEST_TYPE == 'ajax' || OPTIONS_REQUESTED_MODULE == 'logout'){
         $this->footerLinks = '';      //clear the footer links
         if(OPTIONS_REQUESTED_MODULE == 'logout') return 0;
      }

      //before displaying the current user, lets confirm that the user credentials are ok and the session is not expired
      $res = $this->Dbase->ConfirmUser($_SESSION['username'], $_SESSION['password'], $_SESSION['cl_pass']);
      if($res == 1){
         if(OPTIONS_REQUEST_TYPE == 'ajax') die('-1Error! There was an error while authenticating the user.');
         else $this->LoginPage('Error! There was an error while authenticating the user.');
         return 1;
      }
      elseif($res == 2){
         if(OPTIONS_REQUEST_TYPE == 'ajax') die('-1Sorry, You do not have enough privileges to access the system.');
         $this->LoginPage('Sorry, You do not have enough privileges to access the system.');
         return 1;
      }

      //display the credentials of the person who is logged in
		$userLevel = (isset($_SESSION['user_type'])) ? $_SESSION['user_type'] : $_SESSION['user_level'];
      Config::$curUser = "{$_SESSION['surname']} {$_SESSION['onames']}, $userLevel";
      if(OPTIONS_REQUEST_TYPE == 'normal')
         echo "<div id='top_links'>
         <div class='back_link'>Back</div>
         <div id='whoisme'>{$_SESSION['surname']} {$_SESSION['onames']}, $userLevel&nbsp;&nbsp;<a href='?page=logout'>Log Out</a>, <a href='documentation.html'>Help</a></div>
        </div>\n";
      return 0;
   }

   /**
    * Fetch the details of the person who is logged in
    *
    * @return  mixed    Returns 1 in case an error ocurred, else it returns an array with the logged in user credentials
    */
   public function GetCurrentUserDetails(){
      $query = "select a.id as user_id, a.sname, a.onames, a.login, b.name as user_level from ".Config::$config['session_dbase'].".users as a
               inner join ".Config::$config['session_dbase'].".user_levels as b on a.user_level=b.id  WHERE a.id=:id AND a.allowed=:allowed";
      $result = $this->Dbase->ExecuteQuery($query, array('id' => $this->Dbase->currentUserId, 'allowed' => 1));
      if($result == 1){
         $this->Dbase->CreateLogEntry("There was an error while fetching data from the database.", 'fatal', true);
         $this->Dbase->lastError = "There was an error while fetching data from the session database.<br />Please try again later.";
         return 1;
      }
      return $result[0];
   }

   /**
    * Logs out the current user
    */
   private function LogOutCurrentUser(){
      $this->LogOut();
      $this->LoginPage();
   }

   /**
    * Creates the home page for the systems admins
    */
   private function SysAdminsHomePage($addinfo = ''){
      $addinfo = ($addinfo == '') ? '' : "<div id='addinfo'>$addinfo</div>" ;
?>
<div id="home">
   <h2 class='center'>Super Administrator Home Page</h2>
   <?php echo $addinfo; ?>
   <ul>
      <li><a href='?page=users&do=browse'>Users</a></li>
      <li><a href='?page=stabilates&do=browse'>Stabilates</a></li>
      <li><a href='?page=stabilates&do=stats'>Stabilates Statistics</a></li>
      <li><a href='?page=stabilates&do=list'>List Saved Stabilates</a></li>
      <li><a href='?page=stabilates&do=yellow_form'>Stabilates Yellow Form</a></li>
      <li><a href='?page=cultures&do=browse'>Cultures</a></li>
      <?php
         echo $this->ChangeCredentialsLink();
       ?>
   </ul>
</div>
<?php
   }

   /**
    * Creates the home page for the systems admins
    */
   private function VisitorsHomePage($addinfo = ''){
      $addinfo = ($addinfo == '') ? '' : "<div id='addinfo'>$addinfo</div>" ;
?>
<div id="home">
   <h2 class='center'>Biorepository Visitor's Home Page</h2>
   <?php echo $addinfo; ?>
   <ul>
      <li><a href='?page=stabilates&do=stats'>Stabilates Statistics</a></li>
      <li><a href='?page=stabilates&do=list'>List Saved Stabilates</a></li>
   </ul>
</div>
<?php
   }

   /**
    * Creates the home page for the systems admins
    */
   private function AdminsHomePage($addinfo = ''){
      $addinfo = ($addinfo == '') ? '' : "<div id='addinfo'>$addinfo</div>" ;
?>
<div id="home">
   <h2 class='center'>Administrator's Home Page</h2>
   <?php echo $addinfo; ?>
   <ul>
      <li><a href='?page=stabilates&do=browse'>Stabilates</a></li>
      <li><a href='?page=stabilates&do=stats'>Stabilates Statistics</a></li>
      <li><a href='?page=stabilates&do=list'>List Saved Stabilates</a></li>
      <li><a href='?page=cultures&do=browse'>Cultures</a></li>
      <?php
         echo $this->ChangeCredentialsLink();
       ?>
   </ul>
</div>
<?php
   }

   /**
    * The link for changing the username and/or password
    *
    * @return  string   Returns a HTML with the link for changing the credentials
    */
   protected function ChangeCredentialsLink(){
      return "<li><a href='?page=users&do=change_credits'>Change username/password</a></li>";
   }

   /**
    * Causes the files used to create the date time picker to be displayed
    */
   public function DateTimePickerFiles(){
      echo '
      <link rel="stylesheet" type="text/css" href="'. OPTIONS_COMMON_FOLDER_PATH .'freqdec_datePicker/datepicker.css" />
      <script src="'. OPTIONS_COMMON_FOLDER_PATH .'freqdec_datePicker/datepicker.js" type="text/javascript"></script>';
   }

   /**
    * Disables a user account
    *
    * @return  mixed    Returns a string with the error message incase of an error, else it returns 0
    */
   private function LockAccount(){
      $res = $this->Dbase->UpdateRecords('users', 'allowed', 0, 'login', $_POST['username']);
      if(!$res) return OPTIONS_MSSG_UPDATE_ERROR;
      else return 0;
   }

   /**
    * Spits out the javascript that initiates the autocomplete feature once the DOM has finished loading
    */
   public function InitiateAutoComplete($settings){
      //$inputId, $reqModule, $reqSubModule, $selectFunction, $formatResult = '', $visibleSuggestions = '', $beforeNewQuery = ''
//      if($formatResult != '') $formatResult = ", onSearchComplete: $formatResult";
      if($settings['formatResult'] == '') $settings['formatResult'] = 'Stabilates.fnFormatResult';
      if($settings['visibleSuggestions'] == '') $settings['visibleSuggestions'] = true;
      if($settings['beforeNewQuery'] == '') $settings['beforeNewQuery'] = 'undefined';
      if(!isset($settings['selectFunction'])) $settings['selectFunction'] = 'function(){}';
?>
<script type='text/javascript'>
   //bind the search to autocomplete
   $(function(){
      var settings = {
         serviceUrl:'mod_ajax.php', minChars:2, maxHeight:400, width:150,
         zIndex: 9999, deferRequestBy: 300, //miliseconds
         params: { page: '<?php echo $settings['reqModule']; ?>', 'do': '<?php echo $settings['reqSubModule']; ?>' }, //aditional parameters
         noCache: true, //default is false, set to true to disable caching
         onSelect: <?php echo $settings['selectFunction'] ?>,
         formatResult: <?php echo $settings['formatResult']; ?>,
         beforeNewQuery: <?php echo $settings['beforeNewQuery']; ?>,
         visibleSuggestions: <?php echo $settings['visibleSuggestions']; ?>
      };
//      settings.params['extras'] = <?php echo $settings['extras']; ?>;
      $('#<?php echo $settings['inputId']; ?>').autocomplete(settings);
   });
</script>
<?php
   }

   /**
    * Generates the necessary pdf and offers it for download
    */
   public function GenerateAndDownloadPDF(){
      $url = str_replace('&', '\&', 'http://localhost' . $_SERVER['REQUEST_URI'] .'&gen=false');
      GeneralTasks::CreateDirIfNotExists(Config::$uploads['destinationFolder']);
      $ofile = Config::$uploads['destinationFolder'] ."{$_COOKIE['lis']}.pdf";
      $command = "/usr/bin/xvfb-run /usr/bin/wkhtmltopdf -q $url $ofile";
      exec($command, $output, $return);
      $this->Dbase->CreateLogEntry($command, 'debug');

      Header('Content-Type: application/pdf');
      $date = date('Ymd_hi');
      Header("Content-Disposition: attachment; filename=". OPTIONS_REQUESTED_SUB_MODULE ."_$date.pdf");
      if(headers_sent()) $this->StabilatesHomePage('Some data has already been output to browser, can\'t send PDF file');
      readfile($ofile);
      unlink($ofile);
      die();
   }

   /**
    * Creates the stabilates data entry home page, for entering the stabilates
    *
    * @return type
    */
   private function BrowseStabilates(){
      $error = '';

      //hosts
      $query = "select id, host_name from hosts";
      $res = $this->Dbase->ExecuteQuery($query);
      if($res != 1){
         $ids=array(); $vals=array();
         foreach($res as $t){
            $ids[]=$t['id']; $vals[]=$t['host_name'];
         }
         $settings = array('items' => $vals, 'values' => $ids, 'firstValue' => 'Select One', 'name' => 'host_name', 'id' => 'hostId');
         $hostCombo = GeneralTasks::PopulateCombo($settings);
      }
      else $error = $this->Dbase->lastError;

      //locality
      $query = "select locality from stabilates group by locality";
      $res = $this->Dbase->ExecuteQuery($query);
      if($res != 1){
         $vals = array();
         foreach($res as $t) $vals[]=$t['locality'];
         $settings = array('items' => $vals, 'values' => $vals, 'firstValue' => 'Select One', 'name' => 'locality', 'id' => 'localityId');
         $localityCombo = GeneralTasks::PopulateCombo($settings);
      }
      else $error = $this->Dbase->lastError;

      //variable antigen
      $query = "select id, antigen from variable_antigen";
      $res = $this->Dbase->ExecuteQuery($query);
      if($res != 1){
         $ids=array(); $vals=array();
         foreach($res as $t){
            $ids[]=$t['id']; $vals[]=$t['antigen'];
         }
         $settings = array('items' => $vals, 'values' => $ids, 'firstValue' => 'Select One', 'name' => 'variableAntigen', 'id' => 'variableAntigenId');
         $variableAntigenCombo = GeneralTasks::PopulateCombo($settings);
      }
      else $error = $this->Dbase->lastError;

      //locality
      $query = "select * from infection_host order by host_name";
      $res = $this->Dbase->ExecuteQuery($query);
      if($res != 1){
         $vals = array(); $ids = array();
         foreach($res as $t){
            $ids[]=$t['id']; $vals[]=$t['host_name'];
         }
         $settings = array('items' => $vals, 'values' => $ids, 'firstValue' => 'Select One', 'name' => 'infection_host', 'id' => 'infectionHostId');
         $infectionHostCombo = GeneralTasks::PopulateCombo($settings);
      }
      else $error = $this->Dbase->lastError;

      //parasites
      $query = "select id, parasite_name from parasites";
      $res = $this->Dbase->ExecuteQuery($query);
      if($res != 1){
         $ids=array(); $vals=array();
         foreach($res as $t){
            $ids[]=$t['id']; $vals[]=$t['parasite_name'];
         }
         $settings = array('items' => $vals, 'values' => $ids, 'firstValue' => 'Select One', 'name' => 'parasite', 'id' => 'parasiteId');
         $parasitesCombo = GeneralTasks::PopulateCombo($settings);
      }
      else $error = $this->Dbase->lastError;

      //isolation method
      $query = "select * from isolation_methods order by method_name";
      $res = $this->Dbase->ExecuteQuery($query);
      if($res != 1){
         $vals = array(); $ids = array();
         foreach($res as $t){
            $ids[]=$t['id']; $vals[]=$t['method_name'];
         }
         $settings = array('items' => $vals, 'values' => $ids, 'firstValue' => 'Select One', 'name' => 'isolation_method', 'id' => 'isolationMethodId');
         $isolationMethodCombo = GeneralTasks::PopulateCombo($settings);
      }
      else $error = $this->Dbase->lastError;

      //preserved types
      $vals = array('Capilaries', 'Vials');
      $settings = array('items' => $vals, 'firstValue' => 'Select One', 'name' => 'preservedType', 'id' => 'preservedTypeId');
      $preservedTypeCombo = GeneralTasks::PopulateCombo($settings);

      //isolation method
      $query = "select * from users order by user_names";
      $res = $this->Dbase->ExecuteQuery($query);
      if($res != 1){
         $vals = array(); $ids = array();
         foreach($res as $t){
            $ids[]=$t['id']; $vals[]=$t['user_names'];
         }
         $settings = array('items' => $vals, 'values' => $ids, 'firstValue' => 'Select One', 'name' => 'frozen_by', 'id' => 'frozenById');
         $frozenByCombo = GeneralTasks::PopulateCombo($settings);
      }
      else $error = $this->Dbase->lastError;

      //preservation method
      $query = "select * from preservation_methods order by method_name";
      $res = $this->Dbase->ExecuteQuery($query);
      if($res != 1){
         $vals = array(); $ids = array();
         foreach($res as $t){
            $ids[]=$t['id']; $vals[]=$t['method_name'];
         }
         $settings = array('items' => $vals, 'values' => $ids, 'firstValue' => 'Select One', 'name' => 'freezing_method', 'id' => 'freezingMethodId');
         $freezingMethodCombo = GeneralTasks::PopulateCombo($settings);
      }
      else $error = $this->Dbase->lastError;

      //infected species
      $query = "select id, species_name from infected_species";
      $res = $this->Dbase->ExecuteQuery($query);
      if($res != 1){
         $ids=array(); $vals=array();
         foreach($res as $t){
            $ids[]=$t['id']; $vals[]=$t['species_name'];
         }
         $settings = array('items' => $vals, 'values' => $ids, 'firstValue' => 'Select One', 'name' => 'infectedSpecies', 'id' => 'infectedSpeciesId');
         $infectedSpeciesCombo = GeneralTasks::PopulateCombo($settings);
      }
      else $error = $this->Dbase->lastError;

      //origin country
      $query = "select * from origin_countries";
      $res = $this->Dbase->ExecuteQuery($query);
      if($res != 1){
         $ids=array(); $vals=array();
         foreach($res as $t){
            $ids[]=$t['id']; $vals[]=$t['country_name'];
         }
         $settings = array('items' => $vals, 'values' => $ids, 'firstValue' => 'Select One', 'name' => 'originCountry', 'id' => 'originCountryId');
         $originCountryCombo = GeneralTasks::PopulateCombo($settings);
      }
      else $error = $this->Dbase->lastError;

      //inoculum types
      $query = "select id, inoculum_name from inoculum order by inoculum_name";
      $res = $this->Dbase->ExecuteQuery($query);
      if($res != 1){
         $ids=array(); $vals=array();
         foreach($res as $t){
            $ids[]=$t['id']; $vals[]=$t['inoculum_name'];
         }
         $settings = array('items' => $vals, 'values' => $ids, 'firstValue' => 'Select One', 'name' => 'inoculumType', 'id' => 'inoculumTypeId');
         $inoculumTypeCombo = GeneralTasks::PopulateCombo($settings);
      }
      else $error = $this->Dbase->lastError;

      //inoculum source

      if($error != ''){
         $this->StabilatesHomePage($error);
         return;
      }
?>
<link rel="stylesheet" href="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/styles/jqx.base.css" type="text/css" />
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxcore.js"></script>
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxcalendar.js"></script>
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxtabs.js"></script>

<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxgrid.js"></script>
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxdata.js"></script>
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxlistbox.js"></script>
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxscrollbar.js"></script>
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxgrid.selection.js"></script>
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxbuttons.js"></script>

<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxdatetimeinput.js"></script>
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/globalization/jquery.global.js"></script>

<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>d3/d3.min.js"></script>
<script type="text/javascript" src="js/d3.geom.js"></script>
<script type="text/javascript" src="js/d3.layout.js"></script>

<script type="text/javascript" src="js/d3_visualization.js"></script>

<div id="addinfo">&nbsp;</div>
<form class='form-horizontal'>
<fieldset class='stabilates'>
   <legend>Stabilates</legend>
   <div class='left'>
      <div class="control-group">
         <label class="control-label" for="stabilateNo">Stabilate&nbsp;&nbsp;<a href="javascript:;" class="view_form">Form</a></label>
         <div class="stab_input controls">
            <input type="text" id="stabilateNo" placeholder="Stabilate" class='input-medium'>&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />
         </div>
         <div class="stab_links"><a href="javascript:;" class="view_history">History</a><br /><a href="javascript:;" class="view_full_history">Full History</a></div>
      </div>
      <div class="control-group">
         <label class="control-label" for="hostId">Host</label>
         <div class="controls">
            <?php echo $hostCombo; ?>&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="localityId">Locality</label>
         <div class="controls">
            <?php echo $localityCombo; ?>
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="isolation_date">Date</label>
         <div class="controls">
            <div id='isolation_date'></div>
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="isolatedBy">Isolated By</label>
         <div class="controls">
            <input type="text" id="isolatedBy" placeholder="Isolated By" class='input-medium'>
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="varAntigen">Var. Antigen</label>
         <div class="controls">
            <?php echo $variableAntigenCombo; ?>
         </div>
      </div>
   </div>

   <div class='center'>
      <div class="control-group">
         <label class="control-label" for="parasite">Parasite</label>
         <div class="controls">
            <?php echo $parasitesCombo; ?>&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="hostInfection">Infection in Host</label>
         <div class="controls">
            <?php echo $infectionHostCombo; ?>&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="parentStabilate">Origin Country</label>
         <div class="controls">
            <?php echo $originCountryCombo; ?>&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="hostNo">Host No.</label>
         <div class="controls">
            <input type="text" id="hostNo" placeholder="Host No." class='input-medium'>
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="expNo">Experiment No.</label>
         <div class="controls">
            <input type="text" id="expNo" placeholder="Experiment No." class='input-medium'>
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="isolationMethod">Isolation Method</label>
         <div class="controls">
            <?php echo $isolationMethodCombo; ?>&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />
         </div>
      </div>

   </div>

   <div class='right'>
      <label class="text-center">Stabilate Comments</label>
      <textarea rows="5" width="300px" height="170px" id='stabilateComments'></textarea>
   </div>
  </fieldset>

<div id="passages_tab">
 <ul>
   <li style="margin-left: 30px;">Passage Entry</li>
   <li>Saved Passages</li>
</ul>
<div class='passages'>
   <ul class="entered_passages"></ul>
   <div class='left'>
      <div class="control-group">
         <label class="control-label" for="passageNo">Passage No</label>
         <div class="controls">
            <input type="text" id="passageNo" placeholder="Passage No" class='input-mini'>
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="inoculumType">Inoculum Type</label>
         <div class="controls">
            <?php echo $inoculumTypeCombo; ?>
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="inoculumSource">Inoculum Source</label>
         <div class="controls" id="inoculumTypeContainter">
            <span>Select the inoculum type</span>
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="radiationFreq">Radiation Freq.</label>
         <div class="controls">
            <input type="text" id="radiationFreq" placeholder="Freq" class='input-mini'>
         </div>
      </div>
   </div>
   <div class='center'>
      <div class="control-group">
         <label class="control-label" for="infectedSpecies">Infected Species</label>
         <div class="controls">
            <?php echo $infectedSpeciesCombo; ?>
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="infectedDays">Infected Days</label>
         <div class="controls">
            <input type="text" id="infectedDays" placeholder="Days" class='input-mini'>
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="noOfInfectedSpecies">No Infected Species</label>
         <div class="controls">
            <input type="text" id="noOfInfectedSpecies" placeholder="Count" class='input-mini'>
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="radiation_date">Radiation Date</label>
         <div class="controls">
            <div id='radiation_date'></div>
         </div>
      </div>
   </div>
   <div class='right'>
      <label class="text-center">Passage Comments</label>
      <textarea id="passageComments" rows="5" width="300px" height="170px"></textarea>
   </div>

   <ul class="nav nav-list" id="passage_actions">
      <li><button class="btn btn-medium btn-primary passage_save" type="button">Save Passage</button></li>
      <li><button class="btn btn-medium btn-primary passage_cancel" type="button">Cancel</button></li>
   </ul>
</div>

<div id="saved_passages">
   Entered passages
</div>
</div>

<fieldset class='preservation'>
   <legend>Preservation</legend>
   <div>
      <div class="control-group left">
         <label class="control-label" for="presDate">Preservation Date</label>
         <div class="controls">
            <div id='preservation_date'></div>
         </div>
      </div>
      <div class="control-group" style='margin-left: 5%; float: left;'>
         <label class="control-label" for="preservedNo">No. Preserved</label>
         <div class="controls">
            <input type="text" id="preservedNo" placeholder="Count" class='input-mini'>
         </div>
      </div>
      <div class="control-group" style='margin-left: 5%;'>
         <label class="control-label" for="preservedType">Preserved Type</label>
         <div class="controls">
            <?php echo $preservedTypeCombo; ?>
         </div>
      </div>
   </div>
   <div>
      <div class="control-group left">
         <label class="control-label" for="preservedBy" style='width:85px;'>Preserved By</label>
         <div class="controls" style='margin-left:95px;'>
            <?php echo $frozenByCombo; ?>&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />
         </div>
      </div>
      <div class="control-group" style='margin-left: 18%; float: left; width:50%;'>
         <label class="control-label" for="preservationMethod" style='width:135px;'>Preservation Method</label>
         <div class="controls" style='margin-left:15px;'>
            <?php echo $freezingMethodCombo; ?>&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />
         </div>
      </div>
   </div>
</fieldset>

<fieldset class='strain'>
   <legend>Strain Data</legend>
      <div class="control-group">
         <label class="control-label" for="strainCount" style='width:125px;'>Strain Count</label>
         <div class="controls">
            <input type="text" id="strainCount" placeholder="Strain Count" class='input-xxlarge' style='margin-left:15px;'>
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="strainMorphology" style='width:125px;'>Strain Morphology</label>
         <div class="controls">
            <input type="text" id="strainMorphology" placeholder="Strain Morphology" class='input-xxlarge' style='margin-left:15px;'>
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="strainInfectivity" style='width:125px;'>Strain Infectivity</label>
         <div class="controls">
            <input type="text" id="strainInfectivity" placeholder="Strain Infectivity" class='input-xxlarge' style='margin-left:15px;'>
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="strainPathogenicity" style='width:125px;'>Strain Pathogenicity</label>
         <div class="controls">
            <input type="text" id="strainPathogenicity" placeholder="Strain Pathogenicity" class='input-xxlarge' style='margin-left:15px;'>
         </div>
      </div>
</fieldset>

<fieldset class='synonyms'>
   <legend>Synonyms</legend>
   <div id="stab_synonyms">
      <div class="control-group">
         <label class="control-label" for="synonym" style='margin-left:15px;width:auto;'>Synonym</label>
         <div class="controls" style='margin-left:85px;'>
            <input type="text" id="synonym" placeholder="Stabilate Synonym" class='input-medium'>
         </div>
      </div>
      <div class="control-group">
         <label class="text-center">List of Synonyms</label>
         <div class="controls" style='margin-left:75px;'>
            <div id="synonym_list"></div>
         </div>
      </div>
   </div>
</fieldset>

<div id='footer_links'>
   <button class="btn btn-medium btn-primary stabilate_save" type="button" value="save">Save Stabilate</button>
   <button class="btn btn-medium btn-primary stabilate_cancel" type="button">Cancel</button>
</div>
</form>

<script type='text/javascript'>
$(document).ready(function () {
   var date_inputs = ['isolation_date', 'radiation_date', 'preservation_date'];
   $.each(date_inputs, function(i, dateInput){
      $('#'+ dateInput).jqxDateTimeInput({ width: '150px', height: '25px', theme: Main.theme, formatString: "yyyy-MM-dd",
         minDate: new $.jqx._jqxDateTimeInput.getDateTime(new Date(1960, 0, 1)),
         maxDate: new $.jqx._jqxDateTimeInput.getDateTime(new Date(2003, 0, 1)),
         value: new $.jqx._jqxDateTimeInput.getDateTime(new Date(1900, 0, 1))
      });
   });
   $('[type=button]').live('click', Stabilates.buttonClicked);
   $('#inoculumTypeId').live('change', Stabilates.inoculumTypeChange);
   $("#synonym_list").jqxListBox({ source: [], width: 200, height: 150, theme: Main.theme });
   $('#synonym').keyup(Stabilates.addSynonym);
   Stabilates.initiatePassageDetails();

   $('.view_form').click(Stabilates.viewYellowForm);
   $('.view_history').click(Stabilates.viewStabilateHistory);
   $('.view_full_history').click(Stabilates.viewStabilateFullHistory);
   $('#passages_tab').jqxTabs({ width: '100%', height: 310, position: 'top', theme: Main.theme });
   $('#passages_tab').live('selecting', function (event) {
      if(event.args.item === 1){
         //we have selected the passages tab... reload the data
         Stabilates.initiatePassageDetails(Main.curStabilateId);
      }
   });
   $('#stabilateNo').focus();
   Main.passagesValidation = <?php echo json_encode(Config::$passageValidation); ?>;
   Main.stabilatesValidation = <?php echo json_encode(Config::$stabilatesValidation); ?>;
});
</script>
<?php
      $this->AutoCompleteFiles();
      $settings = array('inputId' => 'stabilateNo', 'reqModule' => 'stabilates', 'reqSubModule' => 'browse', 'selectFunction' => 'Stabilates.fetchStabilatesData');
      $this->InitiateAutoComplete($settings);

      $settings = array('inputId' => 'parent_stabilate', 'reqModule' => 'stabilates', 'reqSubModule' => 'browse');
      $this->InitiateAutoComplete($settings);
   }

   /**
    * Fetches different data for use by the clients
    */
   private function FetchData(){
      $vals = array();
      if(isset($_GET['query'])){
         $query = 'select id, stab_no from stabilates where stab_no like :query';
         $res = $this->Dbase->ExecuteQuery($query, array('query' => "%{$_GET['query']}%"));
         if($res == 1) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
         $suggestions = array();
         foreach($res as $t){
            $suggestions[] = $t['stab_no'];
            $data[] = $t;
         }
         $data = array('error' => false, 'query' => $_GET['query'], 'suggestions' => $suggestions, 'data' => $data);
         die(json_encode($data));
      }
      elseif(OPTIONS_REQUESTED_SUB_MODULE == 'passages' && OPTIONS_REQUESTED_ACTION == 'browse'){
         if(!isset($_POST['stabilate_id'])) die('{"data":'. json_encode(array()) .'}');
         $query = 'select a.*, b.inoculum_name, c.species_name, a.infection_duration as idays, a.number_of_species as no_infected, radiation_freq as rfreq, radiation_date as rdate, passage_comments as comments
            from passages as a inner join inoculum as b on a.inoculum_type=b.id
            inner join infected_species as c on a.infected_species=c.id
            where a.stabilate_ref = :stabilate_ref order by a.passage_no';
         $res = $this->Dbase->ExecuteQuery($query, array('stabilate_ref' => $_POST['stabilate_id']));
         if($res == 1) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));

         header("Content-type: application/json");
         die('{"data":'. json_encode($res) .'}');
      }
      elseif(OPTIONS_REQUESTED_ACTION == 'stabilate_data'){
         //get the stabilate info
         $query = 'select * from stabilates where id = :stabilateId';
         $res = $this->Dbase->ExecuteQuery($query, array('stabilateId' => $_POST['stabilate_id']));
         if($res == 1) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));

         //get the synonyms
         $query = 'select id, stabilate_id, synonym_name as name from stab_synonyms where stabilate_id = :stabilate_ref';
         $synonyms = $this->Dbase->ExecuteQuery($query, array('stabilate_ref' => $_POST['stabilate_id']));
         if($synonyms == 1) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));

         header("Content-type: application/json");
         die(json_encode(array('error' => false, 'data' => $res[0], 'synonyms' => $synonyms)));
      }
      elseif(OPTIONS_REQUESTED_ACTION == 'Passage'){
         $query = "select id, concat('Passage ', passage_no) as name from passages where stabilate_ref=:stabilate_ref";
         $vals['stabilate_ref'] = $_POST['stabilate_ref'];
      }
      elseif(OPTIONS_REQUESTED_ACTION == 'Host'){
         $query = "select id, source_name as name from sources order by source_name";
      }
      elseif(OPTIONS_REQUESTED_SUB_MODULE == 'list' && OPTIONS_REQUESTED_ACTION == 'list_stabilates'){      //Fetch the list of all the stabilates that we have entered
         $query = 'select a.id as stabilate_id, a.stab_no, b.parasite_name, c.host_name, d.country_name, a.isolation_date, "N/A" as ln_location
            from stabilates as a inner join parasites as b on a.parasite_id=b.id inner join hosts as c on a.host=c.id inner join origin_countries as d on a.country=d.id';
         $res = $this->Dbase->ExecuteQuery($query);
         if($res == 1) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
         header("Content-type: application/json");
         die('{"data":'. json_encode($res) .'}');
      }
      elseif(OPTIONS_REQUESTED_MODULE == 'stabilates' && in_array(OPTIONS_REQUESTED_SUB_MODULE, array('parasite_stats', 'host_stats', 'country_stats'))){
         if(OPTIONS_REQUESTED_SUB_MODULE == 'parasite_stats') $query = 'SELECT parasite_name as s_name, count(*) as count FROM `stabilates` as a inner join parasites as b on a.parasite_id=b.id group by parasite_id';
         if(OPTIONS_REQUESTED_SUB_MODULE == 'host_stats') $query = 'SELECT host_name as s_name, count(*) as count FROM `stabilates` as a inner join hosts as b on a.host=b.id group by host_name';
         if(OPTIONS_REQUESTED_SUB_MODULE == 'country_stats') $query = 'SELECT country_name as s_name, count(*) as count FROM `stabilates` as a inner join origin_countries as b on a.country=b.id group by country_name';

         $res = $this->Dbase->ExecuteQuery($query);
         if($res == 1){
            $this->Dbase->RollBackTrans();
            die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
         }
         header("Content-type: application/csv");
         $count = count($res);
         for($i = 0; $i < $count; $i++){
            echo "{$res[$i]['s_name']}, {$res[$i]['count']}";
            if($i != $count-1) echo "\n";
         }
         die();
      }
      $res = $this->Dbase->ExecuteQuery($query, $vals);
      if($res != 1){
         die(json_encode(array('error' => false, 'data' => $res)));
      }
      else die(json_encode(array('error' => false, 'data' => $this->Dbase->lastError)));
   }

   /**
    * Echos the code for including the files that will be used for auto-complete
    */
   public function AutoCompleteFiles(){
      echo "<script type='text/javascript' src='". OPTIONS_COMMON_FOLDER_PATH ."jquery/jquery.autocomplete/jquery.autocomplete.js'></script>";
      echo "<link rel='stylesheet' type='text/css' href='". OPTIONS_COMMON_FOLDER_PATH ."jquery/jquery.autocomplete/styles.css' />";
   }

   private function SaveStabilates(){
      $dt = json_decode($_POST['cur_stabilate'], true);
//      $this->Dbase->CreateLogEntry('<pre>'. print_r($dt, true) .'</pre>', 'debug');
      $errors = array();
      $set = array();
      $vals = array();
      $insert_vals = array();
      $insert_cols = array();

      //run the input validation
      foreach(Config::$stabilatesValidation as $cur){
         //get the current selector. The selector can either be the element id or element name for this particular input
         if(isset($dt[$cur['id']])) $selector = $cur['id'];
         elseif(isset($dt[$cur['name']])) $selector = $cur['name'];
         //get the current value based on the current selector
         $cur_val = $dt[$selector];

         if($cur_val != '' && !in_array($cur_val, $cur['defaultVal'])){
            //we actually have something.... validate it
            if(preg_match("/{$cur['valueRegex']}/", $cur_val) === 0){
               //we have problems
               $errors[] = $cur['wrongValMessage'];
            }
            else{
               //clean bill of health, so build our update/insert query
               if(!key_exists($selector, $vals)){     //some values are being picked twice and I cannot understand why! this is going to prevent
                  $set[] = Config::$form_db_map[$selector]. "=:$selector";
                  $vals[$selector] = $cur_val;
                  $insert_vals[] = ":$selector";
                  $insert_cols[] = Config::$form_db_map[$selector];
               }
            }
         }
         else{
            //we have nothing
//            echo "{$cur['id']}; {$cur['name']} ==> $cur_val</br>";
         }
      }

      if(count($errors) != 0 ) die(json_encode(array('error' => true, 'data' => implode("<br />", $errors))));

      $lockQuery = "lock table stabilates write, stab_synonyms write";
      $this->Dbase->StartTrans();
      if(isset($dt['id'])){
         //we wanna update a stabilate
         $vals['id'] = $dt['id'];
         $query = 'update stabilates set '. implode(', ', $set) .' where id=:id';
         $stabilateId = $dt['id'];
      }
      else{
         //we wanna save a new stabilates
         $query = 'insert into stabilates('. implode(', ', $insert_cols) .') values('. implode(', ', $insert_vals) .')';
      }
      $res = $this->Dbase->UpdateRecords($query, $vals, $lockQuery);
      if($res == 1){
         $this->Dbase->RollBackTrans();
         die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
      }

      if(!isset($dt['id'])){
         $stabilateId = $this->Dbase->ExecuteQuery('select LAST_INSERT_ID() as id');
         if($stabilateId == 1) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
         $stabilateId = $stabilateId[0]['id'];
      }

      //now lets update the synonyms
      $cols = array('stabilate_id', 'synonym_name');
      foreach($dt['synonyms'] as $synonym){
         if(!isset($synonym['id'])){
            $query = 'insert into stab_synonyms(stabilate_id, synonym_name) values(:stabilateId, :synonym)';
            $vals = array('stabilateId' => $stabilateId, 'synonym' => $synonym['name']);
         }
         else{/*Its already in the database... why edit it????*/}

         $res = $this->Dbase->UpdateRecords($query, $vals);
         if($res == 1){
            $this->Dbase->RollBackTrans();
            die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
         }
      }

      //commit the transaction and unlock the tables
      if( !$this->Dbase->CommitTrans() ) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
      $res = $this->Dbase->ExecuteQuery("Unlock tables");
      if($res == 1) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));

      //we are all good
      die(json_encode(array('error' => false, 'data' => 'Data saved well')));
   }

   private function SavePassages(){
      $dt = json_decode($_POST['data'], true);
      $errors = array();
      $set = array();
      $vals = array();
      $insert_vals = array();
      $insert_cols = array();

      //run the input validation
      foreach(Config::$passageValidation as $cur){
         //get the current selector. The selector can either be the element id or element name for this particular input
         if(isset($dt[$cur['id']])) $selector = $cur['id'];
         elseif(isset($dt[$cur['name']])) $selector = $cur['name'];
         //get the current value based on the current selector
         $cur_val = $dt[$selector];

         if($cur_val != '' && !in_array($cur_val, $cur['defaultVal'])){
            //we actually have something.... validate it
            if(preg_match("/{$cur['valueRegex']}/", $cur_val) === 0){
               //we have problems
               $errors[] = $cur['wrongValMessage'];
            }
            else{
               //clean bill of health, so build our update/insert query
               if(isset(Config::$form_db_map[$selector])){
                  $set[] = Config::$form_db_map[$selector]. "=:$selector";
                  $vals[$selector] = $cur_val;
                  $insert_vals[] = ":$selector";
                  $insert_cols[] = Config::$form_db_map[$selector];
               }
            }
         }
         else{ /*Nothing to do*/ }
      }

      //check the reference stabilate
      $query = 'select id, stab_no from stabilates where id=:id';
      $res = $this->Dbase->ExecuteQuery($query, array('id' => $dt['parentStabilateId']));
      if($res == 1){
         $this->Dbase->RollBackTrans();
         die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
      }
      $parentStabilate = $res[0]['id'];


      //now lets check our inoculum source
      $inoculum_ref = '';
      if($dt['inoculumSource'] == 'Stabilate'){
         //ensure that the stabilate selected is in the database
         $query = 'select stab_no from stabilates where id=:id';
         $res = $this->Dbase->ExecuteQuery($query, array('id' => $dt['inoculumSourceId']));
         if($res == 1){
            $this->Dbase->RollBackTrans();
            die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
         }
         $inoculum_ref = $res[0]['stab_no'];
      }
      elseif($dt['inoculumSource'] == 'Passage'){
         //ensure that we have the passage that is being referred to
         $query = 'select passage_no from passages where id=:id';
         $res = $this->Dbase->ExecuteQuery($query, array('id' => $dt['inoculumSourceId']));
         if($res == 1){
            $this->Dbase->RollBackTrans();
            die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
         }
         $inoculum_ref = "Passage {$res[0]['passage_no']}";
      }
      elseif($dt['inoculumSource'] == 'Host'){
         //ensure that the stabilate selected is in the database
         $query = 'select source_name from sources where id=:id';
         $res = $this->Dbase->ExecuteQuery($query, array('id' => $dt['inoculumSourceId']));
         if($res == 1){
            $this->Dbase->RollBackTrans();
            die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
         }
         $inoculum_ref = $res[0]['source_name'];
      }
      else{
         //get the inoculum name from the db and append the added info.
         $query = 'select inoculum_name from inoculum where id=:id';
         $res = $this->Dbase->ExecuteQuery($query, array('id' => $dt['inoculumTypeId']));
         if($res == 1){
            $this->Dbase->RollBackTrans();
            die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
         }
         $inoculum_ref = $dt['inoculumSourceId'];
      }

      if(count($errors) != 0 ) die(json_encode(array('error' => true, 'data' => implode("<br />", $errors))));


      $lockQuery = "lock table passages write";
      $this->Dbase->StartTrans();
      if(isset($dt['id'])){
         //we wanna update a passage
         $vals['id'] = $dt['id'];
         $vals['inoculum_ref'] = $inoculum_ref;
         $vals['stabilate_ref'] = $parentStabilate;
         $set[] = "inoculum_ref=:inoculum_ref";
         $set[] = "stabilate_ref=:stabilate_ref";
         $query = 'update passages set '. implode(', ', $set) .' where id=:id';
      }
      else{
         //we wanna save a new passage
         $insert_cols[] = 'stabilate_ref'; $insert_vals[] = ':stabilate_ref'; $vals['stabilate_ref'] = $parentStabilate;
         $insert_cols[] = 'inoculum_ref'; $insert_vals[] = ':inoculum_ref'; $vals['inoculum_ref'] = $inoculum_ref;
         $insert_cols[] = 'datetime_added'; $insert_vals[] = ':timestamp'; $vals['timestamp'] = date('Y-m-d H:i:s');
         $insert_cols[] = 'added_by'; $insert_vals[] = ':added_by'; $vals['added_by'] = $_SESSION['user_id'];
         $query = 'insert into passages('. implode(', ', $insert_cols) .') values('. implode(', ', $insert_vals) .')';
      }
      $res = $this->Dbase->UpdateRecords($query, $vals, $lockQuery);
      if($res == 1){
         $this->Dbase->RollBackTrans();
         die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
      }
      //commit the transaction and unlock the tables
      if( !$this->Dbase->CommitTrans() ) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
      $res = $this->Dbase->ExecuteQuery("Unlock tables");
      if($res == 1) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));

      //we are all good
      $this->Dbase->RollBackTrans();
      die(json_encode(array('error' => false, 'data' => 'Data saved well')));
   }

   /**
    * Show the stabilates that have been entered
    */
   private function ListStabilates(){
?>
<link rel="stylesheet" href="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/styles/jqx.base.css" type="text/css" />
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxcore.js"></script>
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxcalendar.js"></script>
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxtabs.js"></script>

<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxgrid.js"></script>
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxgrid.filter.js"></script>
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxdata.js"></script>
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxscrollbar.js"></script>
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxgrid.selection.js"></script>
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxbuttons.js"></script>
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxmenu.js"></script>
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxdropdownlist.js"></script>
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxlistbox.js"></script>
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxcheckbox.js"></script>
<div id='list_stabilates'>
</div>
<script type='text/javascript'>
$(document).ready(function () {
   Stabilates.initiateStabilatesList();
});
</script>
<?php
   }

   /**
    * Creates some pie charts with the stabilates stats
    */
   private function StabilatesStats(){
?>
<link rel="stylesheet" href="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/styles/jqx.base.css" type="text/css" />
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxcore.js"></script>
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxchart.js"></script>
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxdata.js"></script>

<div id='parasites'></div>
<div id='hosts'></div>
<div id='countries'></div>
<script type='text/javascript'>
$(document).ready(function () {
   Stabilates.initiateStabilatesStats();
});
</script>
<?php
   }

   private function StabilatesYellowForm(){
      $this->CreateStabilatesYellowForm();
   }

   /**
    * Spits out a mimick of the yellow form
    */
   private function CreateStabilatesYellowForm(){
      //lets fetch the data for the form
      $query = 'select a.*, b.parasite_name, c.host_name, d.country_name, "N/A" as ln_location, e.host_name, f.method_name as isolation_method, g.method_name as preservation_method, h.user_names, i.country_name
         from stabilates as a inner join parasites as b on a.parasite_id=b.id inner join hosts as c on a.host=c.id inner join origin_countries as d on a.country=d.id
         inner join infection_host as e on a.infection_host=e.id inner join isolation_methods as f on a.isolation_method=f.id inner join preservation_methods as g on a.freezing_method=g.id
         inner join users as h on a.frozen_by=h.id inner join origin_countries as i on a.country=i.id
         where a.id = :stabilate_id';
      $res = $this->Dbase->ExecuteQuery($query, array('stabilate_id' => $_POST['stabilate_id']));
      if($res == 1) die($this->Dbase->lastError);

      $st = $res[0];
      if($st['preserved_type'] == 1) $st['preserved_type'] = 'Capillaries';
      elseif($st['preserved_type'] == 2) $st['preserved_type'] = 'Vials';
      foreach($st as $key => $value) $st[$key] = "<span class='data'>{$value}</span>";

?>
<div id="yellow_form">
   <table id="main_table">
      <!-- Isolation -->
      <tr><td>
            <table id="isolation">
               <tr>
                  <td rowspan="4" class="vertical">Isolation</td>
                  <td class="isol_1">Host<?php echo $st['host_name']; ?></td>
                  <td rowspan="2" class="top_align">Infection in Host<?php echo $st['host_name']; ?></td>
               </tr>
               <tr><td class="isol_1">Locality<?php echo "{$st['country_name']}, {$st['locality']}"; ?></td></tr>
               <tr>
                  <td class="isol_1">Date<?php echo $st['isolation_date']; ?></td>
                  <td rowspan="2" class="top_align">Method<?php echo $st['isolation_method']; ?></td>
               </tr>
               <tr><td class="isol_1">Isolated<?php echo $st['isolated']; ?></td></tr>
            </table>
      </td></tr>
      <!-- Maintenance -->
      <tr><td>
            <table id="maintenance">
               <tr><td rowspan="6" class="vertical" width="4px">Maintenance</td><th>Passage No</th><th>Species & Number</th><th>Inoculum</th><th title="Duration of Infection before passage or freezing">Duration</th><th>Notes</th></tr>
               <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
               <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
               <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
               <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
               <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            </table>
         </td></tr>
      <!-- Preservation -->
      <tr><td>
            <table id="preservation">
               <tr><td rowspan="4" class="vertical">Preserv.</td><td>Date<?php echo $st['preservation_date']; ?></td><td>No. Frozen<?php echo "{$st['number_frozen']} {$st['preserved_type']}"; ?></td><td>Frozen By<?php echo $st['user_names']; ?></td></tr>
               <tr><td colspan="3">Method<?php echo $st['preservation_method']; ?></td></tr>
               <tr><td colspan="3">&nbsp;</td></tr>
               <tr><td colspan="3">&nbsp;</td></tr>
            </table>
         </td></tr>
      <!-- Strain Data -->
      <tr><td>
            <table id="strain">
               <tr><td rowspan="7" class="vertical">Strain Data</td><td class="strain_1">Count</td><td>&nbsp;<?php echo $st['strain_count']; ?></td></tr>
               <tr><td rowspan="2" class="strain_1">Morphology</td><td>&nbsp;<?php echo $st['strain_morphology']; ?></td></tr>
               <tr><td>&nbsp;</td></tr>
               <tr><td class="strain_1" rowspan="2">Infectivity</td><td>&nbsp;<?php echo $st['strain_infectivity']; ?></td></tr>
               <tr><td>&nbsp;</td></tr>
               <tr><td class="strain_1" rowspan="2">Pathogenicity</td><td>&nbsp;<?php echo $st['strain_pathogenicity']; ?></td></tr>
               <tr><td>&nbsp;</td></tr>
            </table>
         </td></tr>
      <!-- Other Info -->
      <tr><td>
            <table id="other">
               <tr><th width="15%">Position</th><th>Species</th><th width="20%">ILRI No.</th></tr>
               <tr><td>&nbsp;<?php echo $st['ln_location']; ?></td><td class="center">&nbsp;<?php echo $st['parasite_name']; ?></td><td><?php echo $st['stab_no']; ?></td></tr>
            </table>
         </td></tr>
   </table>
</div>
<?php
   }

   /**
    * Get the narrow history of this stabilate up to the earliest grandfather
    */
   private function StabilateHistory($die = true){
      $stabilateId = $_POST['stabilate_id'];
      $history = array();
      $query = 'select id, stab_no from stabilates where id = :stab';
      $res = $this->Dbase->ExecuteQuery($query, array('stab' => $stabilateId));
      if($res == 1) die('Error');
      $history[] = array('start_id' => $res[0]['id'], 'starting_stabilate' => $res[0]['stab_no']);

      while(1){
         $passes = $this->StabilateParent($stabilateId);
         if(!$passes || count($passes) == 0) break;
         else{
            //get the stabilate id of the parent
            $query = 'select id from stabilates where stab_no = :stab';
            $res = $this->Dbase->ExecuteQuery($query, array('stab' => $passes['parent_stab']));
            if($res == 1) die('Error');
            $stabilateId = $res[0]['id'];

            //add it to the history
            $passes['parent_id'] = $stabilateId;
            $history[] = $passes;
         }
      }
      if($die) die(json_encode(array('error' => false, 'data' => $history)));
      else return $history;
   }

   private function StabilateParent($stabilateId){
      $query = 'select passage_no, inoculum_ref from passages where stabilate_ref = :stab_id order by passage_no';
      $res = $this->Dbase->ExecuteQuery($query, array('stab_id' => $stabilateId));
      if($res == 1) return die('Error');
      elseif(count($res) == 0) return array();

      $passages = array('stab_id' => $stabilateId);
      foreach($res as $t){
         if($t['passage_no'] == 1) $passages['parent_stab'] = $t['inoculum_ref'];
      }
      $passages['count'] = count($res);

      return $passages;
   }

   /**
    * Get a full history of this stabilate, including all the stabilates that it is related to
    */
   private function StabilateFullHistory(){
      /**
       * This is a big one. We have one stabilate and we want to get all the related stabilates. This is tricky in that the stabilate can
       * be in the middle of the stack and we have to get all its parents and the stabilates that are related to the stabilates. At the end
       * we shall have one big structure.
       *
       * Now to the algorithm
       * - We first get the great great grand dad of this stabilates. After getting this grand dad, we start traversing the database and get
       * children and then the children's children and the children's children, etc.
       *
       * - In terms of the data structures, all the stabilates will be stored in an array. Each stabilate is a node in the array having the
       * attributes {level, stabilate_id, children}
       */

      //get the brief history of the stabilate to the earliest grand dad
      $history = $this->StabilateHistory(false);

      //get the first of all stabilates..... mwanzilishi
      end($history);
      $origin = current($history);   //the first of all stabilates..... mwanzilishi

      $moreChildren = true;
      if(count($history) == 1) $this->parentage[$origin['start_id']] = array('stab_id' => $origin['start_id'], 'name' => $origin['starting_stabilate'], 'level' => 0, 'parent_id' => NULL);
      else $this->parentage[$origin['parent_id']] = array('stab_id' => $origin['parent_id'], 'name' => $origin['parent_stab'], 'level' => 0, 'parent_id' => NULL);

      //now get the children of this stabilate
      $level = 0;
      while($moreChildren){
         $moreChildren = false;     //just allow the execution to reach here....
         foreach($this->parentage as $node){
            if($node['level'] == $level){ //we are in the right place to look for the grand children
               $more = $this->StabilateChildren($node['stab_id'], $level+1);
               if($moreChildren == false && $more == true) $moreChildren = true;    //if we find children even in one instance... we are in for another round
            }
         }
         $level++;
      }

      //lets group the nodes in terms of parent->children
      for( ;$level > -1; $level--){
         foreach($this->parentage as $nodeId => $node){
            //get all the nodes at this level and append them to their parents and then delete them from the parentage
            if($level == 0){
               $finalParentage = array('name' => $node['name'], 'children' => $node['children'], 'passages' => 0);
            }
            elseif($node['level'] == $level){
               //add this to its parent as a child
               if(!isset($this->parentage[$node['parent_id']]['children'])) $this->parentage[$node['parent_id']]['children'] = array();
               $this->parentage[$node['parent_id']]['children'][] = $node;
               unset($this->parentage[$nodeId]);
            }
         }
      }

      $finalParentage['children'] = $this->PassagesCount($finalParentage['children'], 0);
      $finalParentage['parent'] = 'Origin';


      //we are all but done here... print our findings
//      die(json_encode(array('error' => false, 'data' => $this->parentage)));
      die(json_encode(array('error' => false, 'data' => $finalParentage)));
   }

   /**
    * A recursive function that calculates the number of cumulative passages for each stabilate. It factors in all the stabilates from the first parent
    *
    * @param   array    $children         An array of the children for this stabilate
    * @param   integer  $parentPassages   The number of passages that the parent stabilate has undergone
    * @return  array    Returns the stabilate children with the correct number of total passages
    */
   private function PassagesCount($children, $parentPassages){
      $new_children = array();
      foreach($children as $node){
         $node['passages'] += $parentPassages;
         $node['size'] = $node['passages'] * $this->circleFactor;
         if(isset($node['children'])){
            $node['children'] = $this->PassagesCount($node['children'], $node['passages']);
         }
         $new_children[] = $node;
      }
      return $new_children;
   }

   /**
    * Fetches the children of the current stabilate
    *
    * @param   integer  $stabilateId   The id of the stabilate that we are interested in
    * @param   type     $level         The level of these stabilates
    * @return  boolean  Returns true if there are more children in the stack, else returns false
    */
   private function StabilateChildren($stabilateId, $level){
      $query = 'select stab_no from stabilates where id = :stab_id';
      $res = $this->Dbase->ExecuteQuery($query, array('stab_id' => $stabilateId));
      if($res == 1) die('Error');
      elseif(count($res) == 0){
         echo '<pre>'. print_r($this->parentage, true) .'</pre>';
         echo "$query, $stabilateId";
      }
      $stab_no = $res[0]['stab_no'];

      $query = "select a.id, a.stab_no, $stabilateId as parent_id, b.stabilate_ref from stabilates as a inner join passages as b on a.id=b.stabilate_ref where b.inoculum_ref = :stab_no";
      $passages_query = 'select count(*) as passages from passages where stabilate_ref = :stab_ref group by stabilate_ref';
      $children = $this->Dbase->ExecuteQuery($query, array('stab_no' => $stab_no));
      if($children == 1) die('Error');
      foreach($children as $child){
         $passages_count = $this->Dbase->ExecuteQuery($passages_query, array('stab_ref' => $child['stabilate_ref']));
         if($passages_count == 1) die('Error');
         $this->parentage[$child['id']] = array('stab_id' => $child['id'], 'name' => $child['stab_no'], 'parent_id' => $stabilateId, 'parent' => $stab_no, 'level' => $level, 'passages' => $passages_count[0]['passages']);
      }
      if(count($children) != 0) return true;
      else return false;
   }
}
?>