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

   public $footerLinks = '';

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
         $res = $this->Dbase->ConfirmUser($_GET['u'], $_GET['t']);
         if($res == 0){   //this is a valid user
            //get his/her data and add them to the session data
            $res = $this->GetCurrentUserDetails();
            if($res == 1){
               $this->LoginPage('Sorry, There was an error while fetching data from the database. Please try again later');
               return;
            }
            //initialize the session variables
            $_SESSION['surname'] = $res['sname']; $_SESSION['onames'] = $res['onames']; $_SESSION['user_type'] = $res['user_type'];
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
      $this->footerLinks = "<a href='?page=home'>Home</a>";
      if(OPTIONS_REQUESTED_MODULE == '') $this->LoginPage();
      elseif(OPTIONS_REQUESTED_MODULE == 'logout') $this->LogOutCurrentUser();
      elseif(OPTIONS_REQUESTED_MODULE == 'login')  $this->ValidateUser();
      elseif(OPTIONS_REQUESTED_MODULE == 'home') $this->StabilatesHomePage();
      elseif(OPTIONS_REQUESTED_MODULE == 'lab_tests'){
         require_once 'mod_lab_tests.php';
         $Lab = new LabTests();
         $Lab->TrafficController();
      }
      elseif(OPTIONS_REQUESTED_MODULE == 'patients'){
         require_once 'mod_patients.php';
         $Patient = new Patients();
         $Patient->TrafficController();
      }
      elseif(OPTIONS_REQUESTED_MODULE == 'users'){
         require_once 'mod_users.php';
         $Users = new Users();
         $Users->TrafficController();
      }
      elseif(OPTIONS_REQUESTED_MODULE == 'settings'){
         require_once 'mod_settings.php';
         $Settings = new Settings();
         $Settings->TrafficController();
      }
      elseif(OPTIONS_REQUESTED_MODULE == 'reports'){
         require_once 'mod_reports.php';
         $Reports = new Reports();
         $Reports->TrafficController();
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
<div id='main' class='login'>
   <form action="?page=login" name='login_form' method='POST'>
      <div id='login_page'>
         <div id='addinfo'><?php echo $addinfo; ?></div>
         <table>
            <tr><td>Username</td><td><input type="text" name="username" size="15"/></td></tr>
            <tr><td>Password</td><td><input type="password" name="password" size="15" /></td></tr>
            <input type="hidden" name="md5_pass" />
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
      if(OPTIONS_REQUEST_TYPE == 'normal'){
         echo "<script type='text/javascript' src='js/samples.js'></script>";
      }
      if($_SESSION['user_type'] == 'Super Administrator') $this->SysAdminsHomePage($addinfo);
      elseif($_SESSION['user_type'] == 'Laboratory Manager') $this->LabManagerHomePage($addinfo);
      elseif($_SESSION['user_type'] == 'Laboratory Technologist') $this->LabTechnologistHomePage($addinfo);
      elseif($_SESSION['user_type'] == 'Laboratory Clerk') $this->LabClerkHomePage($addinfo);
      elseif($_SESSION['user_type'] == 'Student') $this->StudentHomePage($addinfo);
      echo "<script type='text/javascript'>$('.back_link').html('&nbsp;');</script>";
   }

   /**
    * Validates the user credentials as received from the client
    */
   private function ValidateUser(){
      $username = $_POST['username']; $password = $_POST['md5_pass'];
      //check if we have the user have specified the credentials
      if($username == '' || $password == ''){
         if($username == '') $this->LoginPage("Incorrect login credentials. Please specify a username to log in to the system.");
         elseif($password == '') $this->LoginPage('Incorrect login credentials. Please specify a password to log in to the system.', $username);
         return;
      }
      //now check that the specified username and password are actually correct
      //at this case we assume that we md5 our password when it is being sent from the client side
      $res = $this->Dbase->ConfirmUser($username, $password);
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
         $_SESSION['surname'] = $res['sname']; $_SESSION['onames'] = $res['onames']; $_SESSION['user_type'] = $res['user_type'];
         $_SESSION['user_id'] = $res['user_id']; $_SESSION['password'] = $password; $_SESSION['username'] = $username;
         $this->WhoIsMe();
//         print_r($_SESSION);
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
      $res = $this->Dbase->ConfirmUser($_SESSION['username'], $_SESSION['password']);
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
      Config::$curUser = "{$_SESSION['surname']} {$_SESSION['onames']}, {$_SESSION['user_type']}";
      if(OPTIONS_REQUEST_TYPE == 'normal')
         echo "<div id='top_links'>
         <div class='back_link'>Back</div>
         <div id='whoisme'>{$_SESSION['surname']} {$_SESSION['onames']}, {$_SESSION['user_type']}&nbsp;&nbsp;<a href='?page=logout'>Log Out</a>, <a href='documentation.html'>Help</a></div>
        </div>\n";
      return 0;
   }

   /**
    * Fetch the details of the person who is logged in
    *
    * @return  mixed    Returns 1 in case an error ocurred, else it returns an array with the logged in user credentials
    */
   public function GetCurrentUserDetails(){
      $query = "select a.id as user_id, a.sname, a.onames, a.login, b.name as user_type from ".Config::$config['session_dbase'].".users as a
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
<div>
   <h2 class='center'>Super Administrator Home Page</h2>
   <?php echo $addinfo; ?>
   <ul>
      <li><a href='?page=users&do=browse'>Users</a></li>
      <li><a href='?page=stabilates&do=browse'>Stabilates</a></li>
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
   public function InitiateAutoComplete(){
      echo "
         <script type='text/javascript'>
            //things to do after the DOM has loaded
            $(function(){
               //bind the search to autocomplete
               $('#patientsSearch').autocomplete({
                  serviceUrl:'mod_ajax.php', minChars:1, maxHeight:400, width:250,
                  zIndex: 9999, deferRequestBy: 0, //miliseconds
                  params: { page: 'patients', 'do': 'fetchPatients' }, //aditional parameters
                  noCache: true, //default is false, set to true to disable caching
                  onSelect: function(value, data){
                     $('#sample_id').html(\"&nbsp;<input type='textbox' name='sample_barcode' size='8' />\");
                     $('[name=sample_barcode]').focus();
                     Samples.curPatient = data;
                     Samples.curSamples = [];
                  }
               });
            });

            function fnFormatResult(value, data, currentValue) {
               var pattern = '(' + currentValue.replace(reEscape, '\\$1') + ')';
               return value.replace(new RegExp(pattern, 'gi'), '<strong>$1<\/strong>');
            }
         </script>
      ";
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
}
?>