<?php

/**
 * A class that will manage all that relates to the users
 *
 * ChangeLog
 * v0.2
 * - Recoded to use PDO objects
 *
 * @package    Users
 * @author     Kihara Absolomon <soloincc@movert.co.ke>
 * @since      v0.1
 *
 * @todo    Validate data received from the client side
 * @todo    Prevent SQL injection
 * @todo    Create the password recovery module
 * @todo    Create the module for users to be able to update their profiles
 *
 */
class Users{

   public $Dbase;

   /**
    * The constructor of the class...does nothing but calls the parent constructor
    */
   public function __construct($Dbase){
      $this->Dbase = $Dbase;
   }

   /**
    * Controls the program execution pertaining to this class
    */
   public function TrafficController(){
      //include the user js functions if need be
      if(OPTIONS_REQUEST_TYPE == 'normal'){
         echo "<script type='text/javascript' src='js/users.js'>
           </script><script type='text/javascript' src='". OPTIONS_COMMON_FOLDER_PATH ."jquery/jquery.md5.js'></script>";
         echo "<script type='text/javascript'>$('#top_links .back_link').html('<a href=\'?page=home\' id=\'backLink\'>Back</a>');</script>";
      }
      if(OPTIONS_REQUESTED_ACTION == 'getuserlevels') $this->GetUserLevels();
      elseif(OPTIONS_REQUESTED_ACTION == 'getusers') $this->GetSystemUsers();
      elseif(OPTIONS_REQUESTED_ACTION == 'getdepts') $this->GetSections();
      elseif(OPTIONS_REQUESTED_ACTION == 'getuserdetails') $this->FetchUserDetails();
      elseif(OPTIONS_REQUESTED_ACTION == 'getroledetails') $this->FetchRoleDetails();
      elseif(OPTIONS_REQUESTED_ACTION == 'saveuser') $this->SaveUser();
      elseif(OPTIONS_REQUESTED_ACTION == 'updateuser') $this->UpdateUser();
      elseif(OPTIONS_REQUESTED_ACTION == 'delete') $this->DeleteUser();
      elseif(OPTIONS_REQUESTED_ACTION == 'alteraccess') $this->AlterAccess();
      elseif(OPTIONS_REQUESTED_ACTION == 'update_credits') $this->SaveNewCredits();
      elseif(OPTIONS_REQUESTED_ACTION == 'list') $this->ListUsers();
      elseif(OPTIONS_REQUESTED_ACTION == 'list_roles') $this->ListRoles();
      elseif(OPTIONS_REQUESTED_ACTION == 'saverole') $this->SaveNewRole('save');
      elseif(OPTIONS_REQUESTED_ACTION == 'editrole') $this->SaveNewRole('edit');
      elseif(OPTIONS_REQUESTED_ACTION == 'revokeroles') $this->RevokeRoles();
      elseif(OPTIONS_REQUESTED_SUB_MODULE == 'change_credits') $this->ChangeCreditsHomePage();
      elseif(OPTIONS_REQUESTED_SUB_MODULE == 'browse') $this->Browse();
      elseif(OPTIONS_REQUESTED_SUB_MODULE == 'assigned_roles') $this->AssignRoles();
      elseif(OPTIONS_REQUESTED_SUB_MODULE == '') $this->HomePage();
   }

   /**
    * Creates the users module home page
    *
    * @param   string   $addinfo    Any additional information that we might need to pass to the user
    */
   private function HomePage($addinfo = ''){
      $addinfo = ($addinfo == '') ? '' : "<div id='addinfo'>$addinfo</div>" ;
?>
<div>
   <h2 class='center'>Users Home Page</h2>
   <?php echo $addinfo; ?>
   <ul>
      <li><a href='?page=users&do=browse'>Users</a></li>
      <li><a href='?page=users&do=assign_roles'>Assign Users Additional Roles</a></li>
      <?php
         echo $this->DocumentationLink();
         echo $this->ChangeCredentialsLink();
       ?>
   </ul>
</div>
<?php
   }

   private function Browse($addinfo = ''){
      global $Lis;
      $Lis->FlexigridFiles();
?>
<div id='users'>
   <?php echo $addinfo; ?>
   <div id='user_central'></div>
</div>
<script type='text/javascript'>

   $('[name=save]').bind('click', {action: 'add', id: ''}, User.saveUser);
   $('#back_link .back').html('<a href=\'?page=home\'>Back</a>');
   Main.passwordSettings = ". json_encode(Config::$psswdSettings) ."
   Main.userNameSettings = ". json_encode(Config::$userNameSettings) ."
   $(document).ready(function(){User.initiateUsersGrid(); });
</script>
<?php
   }

   private function ListUsers(){
      //design the criteria
      $vars = array();
      $criteria = array('a.login != :login'); $vars['login'] = Config::$superuser['login'];
      if($_POST['query'] != ''){
         $criteria[] = "{$_POST['qtype']} like :qtype";
         $vars['qtype'] = "%{$_POST['query']}%";
      }
      if(count($criteria) != 0){
         $crits = 'where';
         foreach($criteria as $c){
            $crits .= ($crits == 'where') ? " $c" : " and $c";
         }
      }
      else $crits = '';

      //lets check if some order is required
      if(isset($_POST['sortname'])){
         if($_POST['sortname'] == 'ulevel') $orderCol = 'b.name';
         elseif($_POST['sortname'] == 'sname') $orderCol = 'a.sname';
         elseif($_POST['sortname'] == 'login') $orderCol = 'a.login';
         elseif($_POST['sortname'] == 'dept') $orderCol = 'c.name';

         $order = "order by $orderCol {$_POST['sortorder']}";
      }
      else $order = '';

      $q = 'select a.id, a.login, a.sname, a.onames, b.name as level, a.allowed as allowed, d.name as dept'
         .' from users as a inner join user_levels as b on a.user_level=b.id inner join sections as c on a.section=c.id inner join departments as d on c.department=d.id'
         ." $crits $order limit %d, %d";

      $query = sprintf($q, 0, 1232122);
      $allData = $this->Dbase->ExecuteQuery($query, $vars);
      $start = ($_POST['page']-1)*$_POST['rp'];
      $query = sprintf($q, $start, $_POST['rp']);
      $res = $this->Dbase->ExecuteQuery($query, $vars);

      if($res == 1 || $allData == 1) return 1;
      $content = '';
      foreach($res as $t){
         $allow = ($t['allowed'] == 1) ? 'Yes' : 'No';
         $chkbox = "<input type='checkbox' name='chk_{$t['id']}' />";
         $actions = "<a href='javascript:;' class='edit_user'>Edit</a><a href='javascript:;' class='delete_user'>Delete</a>";
         $rows[] = array('cell' => array($chkbox, $t['sname'], $t['onames'], $t['login'], $t['level'], $t['dept'], $allow, $actions));
      }
      $content = array(
         'total' => count($allData),
         'page' => $_POST['page'],
         'rows' => $rows
      );
      die(json_encode($content));
   }

   /**
    * Given a username and password it validates whether the credentials are for a valid user
    *
    * @param   string      $username   The user's username
    * @param   string      $password   The corresponding password. The password is a string of a hashed password which is used to authenticate without further hashing
    * @return  string|int  Returns a string with the error message in case of an error, else it returns 0 when all is fine
    */
   public function ValidateUser($username, $password){
      //check if we have the user have specified the credentials
      if($username == '' || $password == ''){
         if($username == '' && $password == '') return "Incorrect login credentials.<br />Please specify a username and password to log in to the system.";
         elseif($username == '') return "Incorrect login credentials.<br />Please specify a username to log in to the system.";
         elseif($password == '') return 'Incorrect login credentials.<br />Please specify a password to log in to the system.';
      }
      //now check that the specified username and password are actually correct
      //at this case we assume that we md5 our password when it is being sent from the client side
//      echo '<pre>'. print_r($this, true) .'</pre>';
      $res = $this->Dbase->ConfirmUser($username, $password);
//      $this->Dbase->CreateLogEntry('', 'debug', true);
      if($res == 1) return $this->Dbase->lastError;
      elseif($res == 3){
         $this->Dbase->CreateLogEntry("No account with the username '$username'.", 'info');
         return "Sorry, there is no account with '$username' as the username.<br />Please log in to access the system.";
         ;
      }
      elseif($res == 4){
         $this->Dbase->CreateLogEntry("Disabled account with the username '$username'.", 'info');
         return "Sorry, the account with '$username' as the username is disabled.<br />" . Config::$contact;
      }
      elseif($res == 2){
         $this->Dbase->CreateLogEntry("Login failed for user: '$username'.", 'info');
         return 'Sorry, the password that you have entered is not correct.<br />Please enter your password again to access the system.';
      }
      elseif($res == 0){   //this is a valid user
         //get his/her data and add them to the session data
         $res = $this->GetCurrentUserDetails();
         if($res == 1) return 'Sorry, There was an error while fetching data from the database. Please try again later';

         //get any additional roles
         $res1 = $this->GetAdditionalRolesDetails();
         if($res1 == 1) return 'Sorry, There was an error while fetching data from the database. Please try again later';

         //initialize the session variables
         $_SESSION['surname'] = $res['sname']; $_SESSION['onames'] = $res['onames']; $_SESSION['user_type'] = $res['user_type'];
         $_SESSION['user_id'] = $res['user_id']; $_SESSION['password'] = $password; $_SESSION['username'] = $username;
         $_SESSION['section'] = $res['section']; $_SESSION['department'] = $res['department'];

         //additional roles
         $_SESSION['delegated_role'] = $res1['role']; $_SESSION['delegated_dept'] = $res1['dept'];
         return 0;
      }
   }

   /**
    * Creates the table details of all users defined in the system
    *
    * @return  mixed    Returns 1 in case of an error, else it returns a HTML string that displays the user data
    */
   private function AllUsers(){
      //get the list of all users from the db, bts skip the bibs_admin
      $query = 'select a.id, a.login, a.sname, a.onames, b.name as level, a.allowed as allowed, d.name as dept'
         .' from users as a inner join user_levels as b on a.user_level=b.id inner join sections as c on a.section=c.id inner join departments as d on c.department=d.id'
         .' where a.login != :login order by a.sname';
//      echo '<pre>'. print_r($this, true) .'</pre>';
      $res = $this->Dbase->ExecuteQuery($query, array('login' => Config::$superuser['login']));
      if($res == 1) return 1;
      $i = 1;
      $content = '';
      foreach($res as $t){
         $chkbox = "<input type='checkbox' name='chk_{$t['id']}' />";
         $allowed = ($t['allowed']) ? 'Yes' : 'No';
         $actions = "<a href='javascript:;' class='edit_user'>Edit</a><a href='javascript:;' class='delete_user'>Delete</a>";
         $content .= "<tr><td>$chkbox</td><td>{$t['login']}</td><td>{$t['sname']}</td><td>{$t['onames']}</td><td>{$t['level']}</td><td class='center'>$allowed</td><td class='user_actions'>$actions</td></tr>";
      }
      $content .= "
<script type='text/javascript'>
   $('.edit_user').bind('click', User.editUser);
   $('.delete_user').bind('click', User.confirmDelete);
   Main.passwordSettings = ". json_encode(Config::$psswdSettings) ."
   Main.userNameSettings = ". json_encode(Config::$userNameSettings) ."
</script>";
      return $content;
   }

   /**
    * Saves the current user
    *
    * @todo Validate the passed data
    */
   private function SaveUser(){
      //save the current user as passed from the client side
      $dt = json_decode($_POST['user_data'], true);
      $level = $this->Dbase->GetSingleRowValue('user_levels', 'name', 'id', $dt['ulevel']);
      if($level == 1) die(json_encode(array('error' => true, 'data' => OPTIONS_MSSG_SAVE_ERROR)));

      //add the user credentials to the users database
      $allowed = ($dt['allowed']) ? 1 : 0;
      $salt = "{$dt['sname']}_{$dt['onames']}_$level";
      $this->Dbase->query = "insert into users(login, sname, onames, user_level, salt, allowed, psswd) values('{$dt['login']}', '{$dt['sname']}',
         '{$dt['onames']}', '{$dt['ulevel']}', '$salt', $allowed, ";
      if(Config::$psswdSettings['useSalt']) $this->Dbase->query .= "sha1(concat('$salt', '{$dt['pass']}'))";
      else $this->Dbase->query .= "'{$dt['pass']}'";
      $this->Dbase->query .= ")";

      $result = $this->Dbase->dbcon->query($this->Dbase->query);
      if(!$result){
         $this->Dbase->CreateLogEntry("There was an error while inserting data to the users table.", 'fatal', true);
          die(json_encode(array('error' => true, 'data' => OPTIONS_MSSG_SAVE_ERROR)));
      }
      $res = $this->AllUsers();
      if($res == 1) die(json_encode(array('error' => true, 'data' => OPTIONS_MSSG_SAVE_ERROR)));
      else die(json_encode(array('error' => false, 'data' => $res)));
   }

   /**
    * Updates the users details
    *
    * @todo Validate the passed data
    */
   private function UpdateUser(){
      //update the current user as passed from the client side
      $dt = json_decode($_POST['user_data'], true);

      //update the users credentials to the users database
      $allowed = ($dt['allowed']) ? 1 : 0;
      if($dt['pass'] != ''){
         $query = 'select name from user_levels where id =:id';
         $level = $this->Dbase->ExecuteQuery($query, array('id' => $dt['ulevel']));
         if($level == 1) die(json_encode(array('error' => true, 'data' => OPTIONS_MSSG_UPDATE_ERROR)));
         $level = $level[0]['name'];

         $salt = "{$dt['sname']}_{$dt['onames']}_$level";
         //start the transaction
         if( !$this->Dbase->StartTrans() ) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
         $lockQuery = 'lock tables users write';
         $query = "update users set login=:login, sname=:sname, onames=:onames, user_level=:ulevel, allowed=:allowed, section=:dept, salt=:salt, psswd=";
         //$q = "update users set login='{$dt['login']}', sname='{$dt['sname']}', onames='{$dt['onames']}', user_level='{$dt['ulevel']}', salt='$salt', allowed=$allowed, psswd=";
         if(Config::$psswdSettings['useSalt']) $query .= "sha1(concat(:salt, :pass))";
         else $query .= ":pass";
         $query .= "where id=:id";

         $vals = array('login' => $dt['login'], 'sname' => $dt['sname'], 'onames' => $dt['onames'], 'ulevel' => $dt['ulevel'], 'allowed' => $allowed, 'dept' => $dt['dept'], 'id' => $dt['userId'], 'salt' => $salt, 'pass' => $dt['pass']);
      }
      else{
         $query = "update users set login=:login, sname=:sname, onames=:onames, user_level=:ulevel, allowed=:allowed, section=:dept where id=:id";
         $vals = array('login' => $dt['login'], 'sname' => $dt['sname'], 'onames' => $dt['onames'], 'ulevel' => $dt['ulevel'], 'allowed' => $allowed, 'dept' => $dt['dept'], 'id' => $dt['userId']);
      }

      $res = $this->Dbase->UpdateRecords($query, $vals, $lockQuery);
		if($res){
			$this->Dbase->RollBackTrans();
			die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
		}
      else{
         $this->Dbase->CommitTrans();
         die(json_encode(array('error' => false, 'data' => 'No Error')));
      }
      $res = $this->Dbase->ExecuteQuery("Unlock tables");
      if($res == 1) return $this->Dbase->lastError;
   }

   /**
    * Fetches all the defined user levels.
    *
    * This function will mostly be called via an ajax request. Instead of returning some data, it jst dies passing to the client side the necessary info
    */
   private function GetUserLevels(){
     $cols = array('id', 'name');
     $res = $this->Dbase->GetColumnValues('user_levels', $cols);
     if($res == 1) die(json_encode(array('error' => true, 'data' => OPTIONS_MSSG_FETCH_ERROR)));
     die(json_encode(array('error' => false, 'data' => $res)));
   }

   /**
    * Fetches all the users.
    *
    * This function will mostly be called via an ajax request. Instead of returning some data, it jst dies passing to the client side the necessary info
    */
   private function GetUsers(){
      $query = "select id, concat(sname, ', ', onames) as name from users";
      $res = $this->Dbase->ExecuteQuery($query);
      if($res == 1) die(json_encode(array('error' => true, 'data' => OPTIONS_MSSG_FETCH_ERROR)));
      die(json_encode(array('error' => false, 'data' => $res)));
   }

   /**
    * Fetches a list of the sections.
    *
    * This function will mostly be called via an ajax request. Instead of returning some data, it jst dies passing to the client side the necessary info
    * @since   v0.2
    */
   private function GetSections(){
      $query = "select a.id, a.name as sec, b.name as dept from sections as a inner join departments as b on a.department=b.id";
      $res = $this->Dbase->ExecuteQuery($query);
      if($res == 1) die(json_encode(array('error' => true, 'data' => OPTIONS_MSSG_FETCH_ERROR)));
      $data = array();
      foreach($res as $t) $data[] = array('id' => $t['id'], 'name' => $t['sec']. ', ' .$t['dept']);
      die(json_encode(array('error' => false, 'data' => $data)));
   }

   /**
    * Fetches the specified user details
    *
    * Dies with a JSON with the results
    */
   private function FetchUserDetails(){
      if(!is_numeric($_POST['userId'])) die(json_encode(array('error' => 'Invalid user id. stop tampering with the program')));
      $query = "select id, login, sname, onames, user_level, allowed, section from users where id = :id";
      $res = $this->Dbase->ExecuteQuery($query, array('id' => $_POST['userId']));
      if($res == 1) die('-1' . OPTIONS_MSSG_FETCH_ERROR);
      die(json_encode($res[0]));
   }

   /**
    * Deletes a user from the database
    *
    * Dies with a JSON with the results
    */
   private function DeleteUser(){
      $users = json_decode($_POST['userIds']);
      if(is_numeric($users)) $users = array($users);
      $this->Dbase->StartTrans();
      foreach($users as $user){
         if(!is_numeric($user)){
            $this->Dbase->RollBackTrans();
            die(json_encode(array('error' => true, 'data' => 'Invalid user id. Stop tampering with the system!')));
         }
         $res = $this->Dbase->DeleteData('users', 'id', $user);
         if($res == 1){
            $this->Dbase->RollBackTrans();
            die(json_encode(array('error' => true, 'data' => OPTIONS_MSSG_DELETE_ERROR)));
         }
      }
      die(json_encode(array('error' => false)));
   }

   /**
    * Deletes a user from the database
    *
    * Dies with a JSON with the results
    */
   private function AlterAccess(){
      $users = json_decode($_POST['userIds']);
      if(is_numeric($users)) $users = array($users);
      $this->Dbase->StartTrans();
      foreach($users as $user){
         if(!is_numeric($user)){
            $this->Dbase->RollBackTrans();
            die(json_encode(array('error' => true, 'data' => 'Invalid user id. Stop tampering with the system!')));
         }
         $this->Dbase->query = "update users set allowed=allowed^1 where id=$user";
         $result = $this->Dbase->dbcon->query($this->Dbase->query);
         if(!$result){
            $this->Dbase->CreateLogEntry("There was an error while updating data in the users table.", 'fatal', true);
            $this->Dbase->RollBackTrans();
            die(json_encode(array('error' => true, 'data' => OPTIONS_MSSG_UPDATE_ERROR)));
         }
      }
      $res = $this->AllUsers();
      if($res == 1){
         $this->Dbase->RollBackTrans();
         die(json_encode(array('error' => true, 'data' => OPTIONS_MSSG_UPDATE_ERROR)));
      }
      else{
         $this->Dbase->CommitTrans();
         die(json_encode(array('error' => false, 'data' => $res)));
      }
   }

   /**
    * Creates the page that allows the user to change their account credentials
    *
    * @param   string   $addinfo    Any additional information that needs to be displayed on the home page
    */
   private function ChangeCreditsHomePage($addinfo = ''){
      echo "<script type='text/javascript' src='". OPTIONS_COMMON_FOLDER_PATH ."jquery/jquery.md5.js'></script>";
?>

<div id='change_credits'>
   <table>
      <tr><td>Username</td><td><?php echo "{$_SESSION['username']} <a href='javascript:;' id='change_uname'>change</a>"; ?></td></tr>
      <tr><td>Current Password <img class='mandatory' src='images/mandatory.gif' alt='Required' /></td><td><input type='password' name='opassword' size='13' /></td></tr>
      <tr><td>New Password <img class='mandatory' src='images/mandatory.gif' alt='Required' /></td><td><input type='password' name='npassword' size='13' /></td></tr>
      <tr><td>Confirm Password <img class='mandatory' src='images/mandatory.gif' alt='Required' /></td><td><input type='password' name='cpassword' size='13' /></td></tr>
      <tr><td colspan='2' class='center'><input type='button' name='save' value='Update' /><input type='button' name='cancel' value='Cancel' /></td></tr>
   </table>
</div>
<script type='text/javascript'>
   Main.uname = '<?php echo $_SESSION['username']; ?>';
   Main.passwordSettings = <?php echo json_encode(Config::$psswdSettings); ?>;
   Main.userNameSettings = <?php echo json_encode(Config::$userNameSettings); ?>;
   $('#change_uname').bind('click', User.bindChangeUName);
   $('[name=save]').bind('click', User.changeCredentials);
   $('[name=cancel]').bind('click', function(){window.location.href = sprintf("?page=home");});
   $('[name=opassword]').focus();
   $('#back_link .back').html('<a href=\'?page=home\'>Back</a>');
</script>
<?php
   }

   private function SaveNewCredits(){
      global $Stabilates;
      //check that we have the correct data
      $oldPass = $_POST['oldPass']; $newPass = $_POST['newPass'];

      if($newPass == '') die(json_encode(array('error' => true, 'data' => OPTIONS_MSSG_UNDEFINED_NEW_PASSWORD)));
      //check if the entered old password is correct
      $res = $this->Dbase->ConfirmUser($_SESSION['username'], $oldPass);
      if($res == 1) die(json_encode(array('error' => true, 'data' => OPTIONS_MSSG_FETCH_ERROR)));
      elseif($res == 2) die(json_encode(array('error' => true, 'data' => OPTIONS_MSSG_INCORRECT_OLD_PASSWORD)));
      elseif($res == 3) die(json_encode(array('error' => true, 'data' => "Error! There is no account with '{$_SESSION['username']}' as the username")));
      elseif($res == 4) die(json_encode(array('error' => true, 'data' => "Sorry, the account with {$_SESSION['username']} as the username is disabled.")));

      $query = 'select a.sname, a.onames, a.salt, b.name as ulevel from '. Config::$config['session_dbase'] .'.users as a inner join '. Config::$config['session_dbase'] .'.user_levels as b on a.user_level = b.id where a.login = :login';
      $dt = $this->Dbase->ExecuteQuery($query, array('login' => $_SESSION['username']));
      if($dt == 1) die(json_encode(array('error' => true, 'data' => OPTIONS_MSSG_FETCH_ERROR)));
      $dt = $dt[0];

      $vals = array();
      $query = 'update '. Config::$config['session_dbase'] .'.users set psswd = ';
      if(Config::$psswdSettings['useSalt']){
         $query .= 'sha1(concat(:salt, :newPass))';
         $vals['salt'] = $dt['salt']; $vals['newPass'] = $newPass;
      }
      else{
         $query .= ":newPass";
         $vals['newPass'] = $newPass;
      }

      if(isset($_POST['uname']) && $_POST['uname'] != 'undefined'){
         if($_POST['uname'] == '') die(json_encode(array('error' => true, 'data' => OPTIONS_MSSG_UNDEFINED_NEW_USERNAME)));
         $query .= ', login=:login';
         $username = $_POST['uname']; $vals['login'] = $_POST['uname'];
      }
      else $username = $_SESSION['username'];

      $vals['username'] = $_SESSION['username'];
      $query .= ' where login=:username';

      $result = $this->Dbase->ExecuteQuery($query, $vals);
      if($result == 1){
         $this->Dbase->CreateLogEntry("There was an error while updating the user credentials.", 'fatal', true);
          die(json_encode(array('error' => true, 'data' => OPTIONS_MSSG_SAVE_ERROR)));
      }

      //lets confirm that the details we have entered are ok
      if($this->Dbase->ConfirmUser($username, $newPass)){
          die(json_encode(array('error' => true, 'data' => OPTIONS_MSSG_SAVE_ERROR)));
      }
      $res = $Stabilates->GetCurrentUserDetails();
      if($res == 1) die(json_encode(array('error' => true, 'data' => OPTIONS_MSSG_FETCH_ERROR)));

      //initialize the session variables
      $_SESSION['surname'] = $res['sname']; $_SESSION['onames'] = $res['onames']; $_SESSION['user_type'] = $res['user_type'];
      $_SESSION['user_id'] = $res['user_id']; $_SESSION['password'] = $newPass; $_SESSION['username'] = $res['login'];
      //all is good
      die(json_encode(array('error' => false, 'data' => 'User credentials updated well.')));
   }

   /**
    * Logs out the current user
    */
   public function LogOutCurrentUser(){
      $this->LogOut();
      $this->LoginPage();
   }

   /**
    * Fetch the details of the person who is logged in
    *
    * @return  mixed    Returns 1 in case an error ocurred, else it returns an array with the logged in user credentials
    */
   public function GetCurrentUserDetails(){
      $query = "select a.id as user_id, a.sname, a.onames, a.login, b.name as user_type, c.name as section, d.name as department from users as a
         inner join user_levels as b on a.user_level=b.id inner join sections as c on a.section=c.id inner join departments as d on c.department=d.id
         WHERE a.id = :id AND a.allowed=1";
//      echo '<pre>'. print_r($this, true) .'</pre>';
      $result = $this->Dbase->ExecuteQuery($query, array('id' => $this->Dbase->currentUserId));
      if($result == 1)  return 1;
      else return $result[0];
   }

   /**
    * Fetch the additional roles as defined for this user. Assumes a user can only be delegated 1 role
    *
    * @return  mixed    Returns 1 in case an error ocurred, else it returns an array with the delegated role and section
    */
   private function GetAdditionalRolesDetails(){
      $query = 'select b.name as role, c.name as dept from assigned_roles as a inner join user_levels as b on a.assigned_role=b.id inner join sections as c on a.assigned_section=c.id where a.assignee=:user and active=1';
      $result = $this->Dbase->ExecuteQuery($query, array('user' => $this->Dbase->currentUserId));
      if($result == 1)  return 1;
      else return $result[0];
   }

   /**
    * The home page of delegating user roles
    *
    * @param   string   $addinfo    Additional information to display
    */
   private function AssignRoles($addinfo = ''){
      global $Lis;
      $Lis->FlexigridFiles();
?>
<div id='users'>
   <?php echo $addinfo; ?>
   <div id='assigned_roles'></div>
</div>
<script type='text/javascript'>
   $('#back_link .back').html('<a href=\'?page=home\'>Back</a>');
   $(document).ready(function(){User.initiateAssignedRolesGrid(); });
</script>
<?php
   }

   /**
    * Fetches a list of all the designated roles in the database
    *
    * Dies with a JSON with the results
    */
   private function ListRoles(){
      $vars = array();
      $criteria = array();
      if($_POST['query'] != ''){
         $criteria[] = "{$_POST['qtype']} like :qtype";
         $vars['qtype'] = "%{$_POST['query']}%";
      }
      if(count($criteria) != 0){
         $crits = 'where';
         foreach($criteria as $c){
            $crits .= ($crits == 'where') ? " $c" : " and $c";
         }
      }
      else $crits = '';

      //get all users
      $users = array();
      $query = "select id, sname, onames from users";
      $tmp = $this->Dbase->ExecuteQuery($query);
      if($tmp == 1) json_encode(array('error' => true, 'data' => $this->Dbase->lastError));
      foreach($tmp as $u) $users[$u['id']] = "{$u['sname']}, {$u['onames']}";

      //get all user levels
      $userLevels = array();
      $query = "select id, name from user_levels";
      $tmp = $this->Dbase->ExecuteQuery($query);
      if($tmp == 1) json_encode(array('error' => true, 'data' => $this->Dbase->lastError));
      foreach($tmp as $u) $userLevels[$u['id']] = $u['name'];

      //get all departments
      $sections = array();
      $query = "select a.id, a.name as section, b.name as dept from sections as a inner join departments as b on a.department=b.id";
      $tmp = $this->Dbase->ExecuteQuery($query);
      if($tmp == 1) json_encode(array('error' => true, 'data' => $this->Dbase->lastError));
      foreach($tmp as $u) $sections[$u['id']] = "{$u['section']}, {$u['dept']}";

      $q = "select * from assigned_roles $crits order by active limit %d, %d";

      $query = sprintf($q, 0, 1232122);
      $allData = $this->Dbase->ExecuteQuery($query, $vars);
      $start = ($_POST['page']-1)*$_POST['rp'];
      $query = sprintf($q, $start, $_POST['rp']);
      $res = $this->Dbase->ExecuteQuery($query, $vars);


      if($res == 1 || $allData == 1) return 1;
      $content = '';
      foreach($res as $t){
         $active = ($t['active'] == 1) ? 'Yes' : 'No';
         $chkbox = "<input type='checkbox' name='chk_{$t['id']}' />";
         $actions = ($t['active'] == 1) ? "<a href='javascript:;' class='edit_role'>Edit</a>" : '';
         $rows[] = array('cell' => array('chk' => $chkbox,
            'assignee' => $users[$t['assignee']],
            'assigned_role' => $userLevels[$t['assigned_role']],
            'assigned_section' => $sections[$t['assigned_section']],
            'assigner' => $users[$t['assigner']],
            'assigner_role' => $userLevels[$t['assigner_role']],
            'assigner_dept' => $sections[$t['assigner_dept']],
            'time_assigned' => $t['time_assigned'],
            'time_revoked' => $t['time_revoked'],
            'revoker' => $users[$t['revoker']],
            'revoker_role' => $userLevels[$t['revoker_role']],
            'active' => $active, 'actions' => $actions));
      }
      $content = array(
         'total' => count($allData),
         'page' => $_POST['page'],
         'rows' => $rows
      );
      die(json_encode($content));
   }

   /**
    * Fetches all the system users
    *
    * This function will mostly be called via an ajax request. Instead of returning some data, it jst dies passing to the client side the necessary info
    */
   private function GetSystemUsers(){
     $query = "select a.id, concat(a.sname, ', ', a.onames) as name, b.name as dept from users as a inner join departments as b on a.department = b.id where login != :login and login != :me";
     $res = $this->Dbase->ExecuteQuery($query, array('login' => Config::$superuser['login'], 'me' => $_SESSION['username']));
     if($res == 1) die(json_encode(array('error' => true, 'data' => OPTIONS_MSSG_FETCH_ERROR)));
     die(json_encode(array('error' => false, 'data' => $res)));
   }

   /**
    * Saves a role to the database
    */
   private function SaveNewRole($action){
      $dt = json_decode($_POST['role_data'], true);
      $errors = array();

      //do we have an assignee
      if($dt['assignee'] == 0) $errors[] = "Please specify a user to delegate a role to.";
      //get my role and dept
      $query = "select user_level, section from users where id = :id";
      $res = $this->Dbase->ExecuteQuery($query, array('id' => $_SESSION['user_id']));
      if($res == 1) die(json_encode(array('error' => true, 'data' => $res)));
      $my_role = $res[0]['user_level'];
      $my_dept = $res[0]['section'];
      if($dt['delegate'] == 'yes'){
         $assigned_role = $my_role;
         $assigned_section = $my_dept;
      }
      else{ //we need to have the role and section to delegate
         if($dt['role'] == 0) $errors[] = "Please specify a role to delegate.";
         else $assigned_role = $dt['role'];
         if($dt['dept'] == 0) $errors[] = "Please specify a section where the role is delegated to.";
         else $assigned_section = $dt['dept'];
      }
      if(count($errors) != 0){
         die(json_encode(array('error' => true, 'data' => implode("<br />", $errors))));
      }

      //we good, lets do this
      //set autocommit to 0, in effect starting the transactions
      if( !$this->Dbase->StartTrans() ) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));

      $vals = array('assignee' => $dt['assignee'], 'assigned_role' => $assigned_role, 'assigned_section' => $assigned_section, 'assigner' => $_SESSION['user_id'],
            'assigner_role' => $my_role, 'assigner_dept' => $my_dept, 'time_assigned' => date('Y-m-d H:i:s'), 'active' => 1);
      if($action == 'save'){
         $insertQuery = 'insert into assigned_roles(assignee, assigned_role, assigned_section, assigner, assigner_role, assigner_dept, time_assigned, active) '.
            'values(:assignee, :assigned_role, :assigned_section, :assigner, :assigner_role, :assigner_dept, :time_assigned, :active)';
      }
      else{
         $insertQuery = 'update assigned_roles set assignee=:assignee, assigned_role=:assigned_role, assigned_section=:assigned_section,
            assigner=:assigner, assigner_role=:assigner_role, assigner_dept=:assigner_dept, time_assigned=:time_assigned, active=:active where id=:id';
         $vals['id'] = $dt['id'];
      }
      $lockQuery = "lock table assigned_roles write";

      //insert the data
      if($this->Dbase->UpdateRecords($insertQuery, $vals, $lockQuery)){
         if( !$this->Dbase->RollBackTrans() ) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
         die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
      }

      //commit the transaction and unlock the tables
      if( !$this->Dbase->CommitTrans() ) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
      $res = $this->Dbase->ExecuteQuery("Unlock tables");
      if($res == 1) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));

      //all is well
      die(json_encode(array('error' => false, 'data' => 'No data')));
   }

   /**
    * Fetches the details of a role for editing
    */
   private function FetchRoleDetails(){
      if(!is_numeric($_POST['roleId'])) die(json_encode(array('error' => 'Invalid role id. stop tampering with the program')));
      $query = "select id, assignee, assigned_role, assigned_section from assigned_roles where id = :id";
      $res = $this->Dbase->ExecuteQuery($query, array('id' => $_POST['roleId']));
      if($res == 1) die('-1' . OPTIONS_MSSG_FETCH_ERROR);
      die(json_encode($res[0]));
   }

   /**
    * Revokes some of the assigned roles
    */
   private function RevokeRoles(){
      $roles = json_decode($_POST['roleIds']);
      if(is_numeric($roles)) $roles = array($roles);
      if( !$this->Dbase->StartTrans() ) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
      //get my role and dept
      $query = "select user_level, section from users where id = :id";
      $res = $this->Dbase->ExecuteQuery($query, array('id' => $_SESSION['user_id']));
      if($res == 1) die(json_encode(array('error' => true, 'data' => $res)));
      $my_role = $res[0]['user_level'];

      $lockQuery = 'lock table assigned_roles write';
      $query = 'update assigned_roles set time_revoked=:time, revoker=:revoker, revoker_role=:revoker_role, active=:active where id=:id';
      foreach($roles as $role){
         if(!is_numeric($role)){
            if( !$this->Dbase->RollBackTrans() ) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
            die(json_encode(array('error' => true, 'data' => 'Invalid role id. Stop tampering with the system!')));
         }
         $vals = array('time' => date('Y-m-d H:i:s'), 'revoker' => $_SESSION['user_id'], 'revoker_role' => $my_role, 'active' => 0, 'id' => $role);
         $result = $this->Dbase->UpdateRecords($query, $vals, $lockQuery);
         if($result){
            if( !$this->Dbase->RollBackTrans() ) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
            die(json_encode(array('error' => true, 'data' => OPTIONS_MSSG_UPDATE_ERROR)));
         }
      }

      //commit the transaction and unlock the tables
      if( !$this->Dbase->CommitTrans() ) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
      $res = $this->Dbase->ExecuteQuery("Unlock tables");
      if($res == 1) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));

      //all is well
      die(json_encode(array('error' => false, 'data' => 'No data')));
   }
}
?>