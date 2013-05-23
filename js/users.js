
/*
 * Contains all the custom function that I always need the most
 *
 * @category	KNH LIMS
 * @package 	User
 * @author     Kihara Absolomon <soloincc@movert.co.ke>
 * @version	0.1
 *
 * @todo    Automatically check for the availability of the username when creating a new user
 * @todo    Interface update sucks when the save/update button is pressed severally and there is an error
 * @todo    Improve the user validation
 */

var User = {

	 /**
	  * Initiates the process of creating a new user
	  */
	 newUser: function(){
       User.getSysData();
       var data = User.formatUserDetails();
       User.userInterface(data, 'add')
	 },

    /**
     * Gets the defined user levels and wait for the request to be completed before proceeding
     */
    getSysData: function(){
       var data2fetch = new Array(
         {holder: Main.userLevels, action: 'action=getuserlevels'},
         {holder: Main.departments, action: 'action=getdepts'}
       );
		 //fetch the user levels, if we dont have them
       $.each(data2fetch, function(i, tmp){
         if(this.holder == undefined){
            Notification.show({create:true, hide:false, updateText:false, text:'Fetching data from the server...', error:false});
            $.ajax({
               type:"POST", url:'mod_ajax.php?page=users&do=browse', dataType:'json', async: false, data: this.action,
               error:function(){
                  Notification.show({create:false, hide:true, updateText:true, text:'There was an error while communicating with the server', error:true});
               },
               success: function(data){
                  if(data.error){
                     Notification.show({create:false, hide:true, updateText:true, text:data.data, error:true});
                  }
                  else{
                     data2fetch[i].holder = data.data;
                     Notification.show({create:false, hide:true, updateText:true, text:'Fetched succesfully', error:false});
                  }
               }
           });
         }
       });
       Main.userLevels = data2fetch[0].holder;
       Main.departments = data2fetch[1].holder;
    },

    /**
     * Formats the passed user data to appropriate values so that they can be used in a web page
     *
     * @param {array} data An array with the user details that we want to prepare for adding
     */
   formatUserDetails: function(data){
      var settings = {name: 'ulevel', id: 'ulevelId', data: Main.userLevels, initValue: 'Select One'};
      var deptSettings = {name: 'dept', id: 'deptId', data: Main.departments, initValue: 'Select One'};
      if(data === undefined){	//initiate all the fields to the default fields
         data = {login: '', sname: '', onames: '',
            level: Common.generateCombo(settings),
            dept: Common.generateCombo(deptSettings),
            allowed: "<input type='checkbox' name='allowed' checked>"
         };
      }
      else{   //we have some data, lets format it
         settings.selected = data.user_level;
         deptSettings.selected = data.department;
         var checked = (parseInt(data.allowed)) ? 'checked' : '';
         data.level = Common.generateCombo(settings);
         data.dept = Common.generateCombo(deptSettings);
         data.allowed = "<input type='checkbox' name='allowed' "+checked+'>';
      }
      return data;
   },

     /**
     * Creates the user pop up interface
      *
      * @param    {array}      data    The user data to be displayed, especially when editing the users
      * @param    {string}     action  The current action that we are doing. Either add or edit
      * @returns  {Nothing}
      */
    userInterface: function(data, action){
      var mand = (action === 'add') ? "<img class='mandatory' src='images/mandatory.gif' alt='Required' />" : '';
      var content = "<div>\n\
			<table>\n\
			<tr><td>Username<img class='mandatory' src='images/mandatory.gif' alt='Required' /></td><td><input type='text' name='username' size='14' value='"+data.login+"' /></td></tr>\n\
			<tr><td>Surname<img class='mandatory' src='images/mandatory.gif' alt='Required' /></td><td><input type='text' name='sname' size='14' value='"+data.sname+"' /></td></tr>\n\
			<tr><td>Other Names</td><td><input type='text' name='onames' size='14' value='"+data.onames+"' /></td></tr>\n\
			<tr><td>Password"+mand+"</td><td><input type='password' name='npassword' size='14' value='' /></td></tr>\n\
			<tr><td>Confirm Password"+mand+"</td><td><input type='password' name='cpassword' size='14' value='' /></td></tr>\n\
			<tr><td>User Level<img class='mandatory' src='images/mandatory.gif' alt='Required' /></td><td>"+data.level+"</td></tr>\n\
			<tr><td>Department<img class='mandatory' src='images/mandatory.gif' alt='Required' /></td><td>"+data.dept+"</td></tr>\n\
			<tr><td>Allowed</td><td>"+data.allowed+"</td></tr>\n\
			<tr><td colspan='2' id='user_info'><hr />Fields marked with a <img class='mandatory' src='images/mandatory.gif' alt='Required' /> are mandatory.</td></tr>\n\
		</table></div>";
      var ok = (action === 'add') ? 'Save' : 'Update';
      var title = (action === 'add') ? 'Enter the new user credentials' : 'Update the user credentials';
      CustomMssgBox.createMessageBox({okText: ok, cancelText: 'Cancel', message: content, vars: {id: data.id, action: action},
         callBack: User.saveUser, cancelButton: true, customTitle: title});
      $('[name=username]').focus();
   },

    /**
     * Saves a user details to the database
     */
    saveUser: function(sender, value, vars){
       if(!value){
          sender.close();
          return;
       }
       $('.user_error').parent().remove();
       $('#alertWindow input:text, #ulevelId').css({border: 'none'});
       var res = User.confirmUserDetails(vars.action);
       if(res == 1) return;
       var action, caption;
       if(vars.action == 'add'){
          caption = 'added';
          action = 'saveuser';
       }
       else{
         action = 'updateuser';
         caption = 'updated';
         res.userId = vars.id;
       }
       //else we ready to save this user
       sender.close();
       Notification.show({create:true, hide:false, updateText:false, text:'Saving the new user...', error:false});
       res.pass = (res.pass != '') ? $.md5(res.pass) : '';
       var params = 'user_data=' + $.toJSON(res) + '&action=' + action;
       Main.ajaxParams.successMssg = 'The user has been '+caption+' successfully.'
       Main.ajaxParams.div2Update = 'tbody';
       $.ajax({
          type:"POST", url:'mod_ajax.php?page=users&do=browse', dataType:'json', data: params,
			error:function(x, y, z){
            Notification.show({create:true, hide:true, updateText:true, text:'There was an error while communicating with the server', error:true});
			},
			success: function(data){
            var message;
            if(data.error == false){
               message = (Main.ajaxParams.successMssg != undefined) ? Main.ajaxParams.successMssg : 'The changes have been successfully saved.';
               $("#user_central").flexOptions({params: [{name: 'action', value: 'list'}, {name: 'extras', value: {date: 'date'}}]}).flexReload();
            }
            else{
               message = data.data;
            }
            if($('#notification_box') != undefined) Notification.show({create:false, hide:true, updateText:true, text:message, error:data.error});
         }
        });
    },

    /**
     * Gets the entered user details and ensure that they are ok, else highlight the fields needing action
     */
    confirmUserDetails: function(action){
       var login = $('[name=username]').val().trim(), sname = $('[name=sname]').val(), onames = $('[name=onames]').val();
       var pass = $('[name=npassword]').val(), pass1 = $('[name=cpassword]').val(), ulevel = $('#ulevelId').val();
       var allowed = $('[name=allowed]')[0].checked, error=false, dept = $('#deptId').val();
       var errorString = "<tr><td colspan='2' class='user_error'>%s</td></tr>";

       //login
       if(login == ''){
          error = true;
          $('[name=username]').focus().css({border: '2px solid red'}).parent().parent().after(sprintf(errorString, 'Please enter a username'));

       }
       else if(!Common.validate(login, 'login')){
          error = true;
          $('[name=username]').focus().css({border: '2px solid red'}).parent().parent().after(sprintf(errorString, 'Please enter a valid username'));
       }

       //surname
       if(sname == ''){
          error = true;
          $('[name=sname]').focus().css({border: '2px solid red'}).parent().parent().after(sprintf(errorString, 'Please enter the users surname'));

       }
       else if(!Common.validate(sname, 'names')){
          error = true;
          $('[name=sname]').focus().css({border: '2px solid red'}).parent().parent().after(sprintf(errorString, 'Please enter a valid surname'));
       }

       //password
       if((pass == '' || pass1 == '') && action != 'edit'){    //we need a password and none is defined
          error = true;
          $('[name=password]').focus().css({border: '2px solid red'}).parent().parent().after(sprintf(errorString, 'Please enter the password for this user'));

       }
       else if(((pass != '' || pass1 != '') && action == 'edit') || action == 'add'){     //we need to check that the entered passwords are according to the rules
          if(User.validatePassword(pass, pass1)) error = true;
       }

       //confirmation password
       if(pass1 == '' && action != 'edit'){
          error = true;
          $('[name=cpassword]').focus().css({border: '2px solid red'}).parent().parent().after(sprintf(errorString, 'Please enter the confirmation password'));

       }

       //usertype
       if(ulevel == 0){
          error = true;
          $('#ulevelId').focus().css({border: '2px solid red'}).parent().parent().after(sprintf(errorString, 'Please specify the user level for the current user'));
       }

       //department
       if(dept == 0){
          error = true;
          $('#deptId').focus().css({border: '2px solid red'}).parent().parent().after(sprintf(errorString, 'Please specify the department in which the current user works in.'));
       }

       if(error){
          $('#user_info').html('<hr />You have errors in the fields in red.').css({color: 'red'});
          return 1;
       }
       return {login: login, sname: sname, onames: onames, pass: pass, ulevel: ulevel, allowed: allowed, dept: dept};
    },

    /**
     * Starts the editing process
     */
    editUser: function(){
       //get the user details for this user
       var t = this.parentNode.parentNode.parentNode.childNodes[0].childNodes[0].childNodes[0].name;
       var id = t.substr(t.indexOf('_')+1), curUser = undefined;
       User.getSysData();

       Notification.show({create:true, hide:false, updateText:false, text:'Fetching data from the server...', error:false});
       $.ajax({
         type:"POST", url:'mod_ajax.php?page=users&do=browse', dataType:'json', data: sprintf('userId=%s&action=getuserdetails', id), async: false,
         error: Notification.serverCommunicationError,
         success: function(data){
            if(data.error){
               Notification.show({create:true, hide:true, updateText:false, text:data.error, error:true});
            }
            else curUser = data;
         }
      });
      Notification.hide();
      if(curUser == undefined) return;
      var data = User.formatUserDetails(curUser);
      User.userInterface(data, 'edit');
    },

    /**
     * Starts the user deletion process
     */
    confirmDelete: function(){
      //get the user id for this user
      var t = this.parentNode.parentNode.parentNode.childNodes[0].childNodes[0].childNodes[0].name;
      var id = t.substr(t.indexOf('_')+1), user;
      user = $.trim(this.parentNode.parentNode.parentNode.childNodes[1].childNodes[0].innerHTML);
      CustomMssgBox.createMessageBox({
         okText: 'Yes', cancelText: 'No', message: "Are you sure you want to delete '"+ user +"' from the database",
         vars: {id: new Array(id)}, callBack: User.deleteUsers, cancelButton: true, customTitle: 'Confirm user delete'
      });
    },

    checkAll: function(){
      var checked = $('.hDivBox :regex(name, ^chkAll$)')[0].checked;
      $.each($(':regex(name, ^chk_)'), function(){
         this.checked = checked;
      });
    },

    initiateBatchDelete: function(){
       if($(':regex(name, ^chk_):checked').length == 0){
         CustomMssgBox.createMessageBox({
            okText: 'Ok', message: "Please select at least one user to delete.", callBack: Common.closeMessageBox, customTitle: 'No users selected'
         });
         return;
      }
      var users = new Array();
      $.each($(':regex(name, ^chk_):checked'), function(){
         if(this.name != 'chkAll') users[users.length] = this.name.substr(this.name.indexOf('_')+1);
      });
      CustomMssgBox.createMessageBox({
         okText: 'Yes', cancelText: 'No', message: "Are you sure you want to delete the selected users from the database?",
         callBack: User.deleteUsers, cancelButton: true, customTitle: 'Confirm users delete', vars: {id: users}
      });
    },

    /**
     * Creates the delete user request
     */
    deleteUsers: function(sender, value, vars){
       sender.close();
       if(!value) return;
       Main.ajaxParams.successMssg = "The user(s) have been deleted successfully.";
       Notification.show({create:true, hide:false, text:'Please wait while we delete the users from the database...'});
       Main.ajaxParams.div2Update = 'tbody';
       $.ajax({
          type:"POST", url:'mod_ajax.php?page=users&do=browse', dataType:'json', data: 'action=delete&userIds='+$.toJSON(vars.id),
			error:function(x, y, z){
            Notification.show({create:false, hide:true, updateText:true, text:'There was an error while communicating with the server', error:true});
			},
			success: function(data){
            var message;
            if(data.error == false){
               message = (Main.ajaxParams.successMssg != undefined) ? Main.ajaxParams.successMssg : 'The changes have been successfully saved.';
               $("#user_central").flexOptions({params: [{name: 'action', value: 'list'}, {name: 'extras', value: {date: 'date'}}]}).flexReload();
            }
            else{
               message = data.data;
            }
            if($('#notification_box') != undefined) Notification.show({create:false, hide:true, updateText:true, text:message, error:data.error});
          }
        });
    },

    /**
     * Initiates the process of altering the users access
     */
    initiateAccessChange: function(){
       if($(':regex(name, ^chk_):checked').length == 0){
         CustomMssgBox.createMessageBox({
            okText: 'Ok', message: "Please select at least one user to modify access.", callBack: Common.closeMessageBox, customTitle: 'No users selected'
         });
         return;
      }
      var users = new Array();
      $.each($(':regex(name, ^chk_):checked'), function(){
         if(this.name != 'chkAll') users[users.length] = this.name.substr(this.name.indexOf('_')+1);
      });
      CustomMssgBox.createMessageBox({
         okText: 'Yes', cancelText: 'No', message: "Are you sure you want to alter the access of the selected users?",
         callBack: User.alterUserAccess, cancelButton: true, customTitle: 'Confirm alter access', vars: {id: users}
      });
    },

    alterUserAccess: function(sender, value, vars){
       sender.close();
       if(!value) return;
       Main.ajaxParams.successMssg = "The user's access have been updated successfully.";
       Notification.show({create:true, hide:false, text:"Please wait while we update the user's access..."});
       Main.ajaxParams.div2Update = 'tbody';
       $.ajax({
          type:"POST", url:'mod_ajax.php?page=users&do=browse', dataType:'json', data: 'action=alteraccess&userIds='+$.toJSON(vars.id),
			error:function(x, y, z){
            Notification.show({create:false, hide:true, updateText:true, text:'There was an error while communicating with the server', error:true});
			},
			success: function(data){
            var message;
            if(data.error == false){
               message = (Main.ajaxParams.successMssg != undefined) ? Main.ajaxParams.successMssg : 'The changes have been successfully saved.';
               $("#user_central").flexOptions({params: [{name: 'action', value: 'list'}, {name: 'extras', value: {date: 'date'}}]}).flexReload();
            }
            else{
               message = data.data;
            }
            if($('#notification_box') != undefined) Notification.show({create:false, hide:true, updateText:true, text:message, error:data.error});
         }
        });
    },

    /**
     * Binds the different actions in the change credits interface
     */
    bindChangeUName: function(){
      this.parentNode.innerHTML = "<input type='text' name='uname' size='10' /> <a href='javascript:;' id='cancel_uname'>cancel</a>";
      $('#cancel_uname').bind('click', function(){
         this.parentNode.innerHTML = Main.uname + " <a href='javascript:;' id='change_uname'>change</a>";
         $('#change_uname').bind('click', User.bindChangeUName);
      });
    },

    /**
     * Initiate the process of saving the changed credits for the user
     */
    changeCredentials: function(){
       var uname = Main.uname, olPass = $('[name=opassword]').val(), newPass = $('[name=npassword]').val(), confPass = $('[name=cpassword]').val();
       var errorString = "<tr><td colspan='2' class='user_error'>%s</td></tr>";
       $('.user_error').parent().remove();
       $('input:text, input:password').css({border: '1px solid black'});
       //check for username changes
       if(uname !== undefined){
          if(uname === '') $('[name=uname]').focus().css({border: '2px solid red'}).val('').parent().parent().after(sprintf(errorString, 'Please enter the new username.'));
          if(!/^[a-z0-9_\.]+$/.test(uname)) $('[name=uname]').focus().css({border: '2px solid red'}).val('').parent().parent().after(sprintf(errorString, 'A new username should only contain a-z0-9_. characters.'));
          if(uname.length < Main.userNameSettings.minLength) $('[name=uname]').focus().css({border: '2px solid red'}).val('').parent().parent().after(sprintf(errorString, sprintf('A new username should be atleast %s characters long.', Main.userNameSettings.minLength)));
       }
       //check if the current password is defined
       if(olPass === '') $('[name=opassword]').focus().css({border: '2px solid red'}).parent().parent().after(sprintf(errorString, 'Please enter your current password.'));
       var res = User.validatePassword(newPass, confPass);
       if(res) return;

       //now save these new credentials
       Notification.show({create:true, hide:false, updateText:false, text:'Updating the new user credentials...', error:false});
       Main.ajaxParams.div2Update = 'change_credits';
       var params = sprintf('action=update_credits&oldPass=%s&uname=%s&newPass=%s', $.md5(olPass), uname, $.md5(newPass));
       $.ajax({type: 'POST', url: 'mod_ajax.php?page=users&do=change_credits', dataType: 'json', data: params,
			error:function(x, y, z){
            Notification.show({create:false, hide:true, updateText:true, text:'There was an error while communicating with the server', error:true});
			},
			success: function(data){
            var mssg;
               if(data.error) mssg = data.data;
               else mssg = 'Passage saved succesfully';
               Notification.show({create:false, hide:true, updateText:true, text:mssg, error:data.error});
               if(!data.error){
                  window.location = document.getElementById('backLink').href;
               }
         }
       });
    },

    cancelChangeCredentials: function(){

    },

    /**
     * Validates a password as entered by the user
     */
    validatePassword: function(newPass, confPass){
       var errorString = "<tr><td colspan='2' class='user_error'>%s</td></tr>";
       //check whether we have all the recipe necessary for us to update the user credentials

       if(newPass == '') $('[name=npassword]').focus().css({border: '2px solid red'}).parent().parent().after(sprintf(errorString, 'Please enter your new password.'));
       //passwords match
       if(newPass != confPass){
          $('[name=npassword]').focus().css({border: '2px solid red'}).val('').parent().parent().after(sprintf(errorString, 'The passwords dont match.'));
          $('[name=cpassword]').css({border: '2px solid red'}).val('');
       }
       //alpha characters
       if(Main.passwordSettings.alphaChars && !/[a-z]+/i.test(newPass)){
          $('[name=npassword]').focus().css({border: '2px solid red'}).val('').parent().parent().after(sprintf(errorString, 'A new password must have atleast 1 alpha(a-z) character.'));
          $('[name=cpassword]').css({border: '2px solid red'}).val('');
       }
       //numeric characters
       if(Main.passwordSettings.numericChars && !/[0-9]+/.test(newPass)){
          $('[name=npassword]').focus().css({border: '2px solid red'}).val('').parent().parent().after(sprintf(errorString, 'A new password must have atleast 1 numeric(0-9) character.'));
          $('[name=cpassword]').css({border: '2px solid red'}).val('');
       }
       //special characters
       if(Main.passwordSettings.specialChars && !/[!@#$%^&*()_\-+=]+/i.test(newPass)){
          $('[name=npassword]').focus().css({border: '2px solid red'}).val('').parent().parent().after(sprintf(errorString, 'A new password must have atleast 1 special(!@#$%^&*()_-+=) character.'));
          $('[name=cpassword]').css({border: '2px solid red'}).val('');
       }
       //minimum length
       if(newPass.length < Main.passwordSettings.minLength){
          $('[name=npassword]').focus().css({border: '2px solid red'}).val('').parent().parent().after(sprintf(errorString, sprintf('A new password should be atleast %s characters long.', Main.passwordSettings.minLength)));
          $('[name=cpassword]').css({border: '2px solid red'}).val('');
       }
       if($('.user_error').length != 0) return 1;
       return 0;

    },

    /**
     * Initiates the users grid. Allows for the various users manipulation
     */
    initiateUsersGrid: function(){
       $("#user_central").flexigrid({
         url: 'mod_ajax.php?page=users&do=browse',
         dataType: 'json',
         colModel : [
            {display: "<input type='checkbox' name='chkAll' />", name : 'chk', width : 20, sortable : false, align: 'center'},
            {display: 'Surname', name : 'sname', width : 90, sortable : true, align: 'left'},
            {display: 'Other Names', name : 'onames', width : 120, sortable : false, align: 'left'},
            {display: 'Login', name : 'login', width : 70, sortable : true, align: 'left'},
            {display: 'User Level', name : 'ulevel', width : 120, sortable : true, align: 'left'},
            {display: 'Department', name : 'dept', width : 90, sortable : true, align: 'left'},
            {display: 'Allowed', name : 'allowed', width : 60, sortable : false, align: 'center'},
            {display: 'Actions', name : 'actions', width : 100, sortable : false, align: 'center'}
            ],
         buttons : [
            {name: 'New', bclass: 'add', onpress: User.newUser},
            {name: 'Delete', bclass: 'delete_users', onpress: User.initiateBatchDelete},
            {name: 'Allow/Deny', bclass: 'grant_deny_access', onpress: User.initiateAccessChange}
         ],
         searchitems : [
            {display: 'Surname', name : 'sname'}
         ],
         sortname: "sname",
         sortorder: "asc",
         usepager: true,
         title: 'System Users',
         useRp: true,
         rp: 20,
         showTableToggleBtn: false,
         rpOptions: [20, 30, 50], //allowed per-page values
         width: 870,
         singleSelect: true,
         height: 360,
         autoload: false
      }).flexOptions({params: [{name: 'action', value: 'list'}, {name: 'extras', value: {date: 'date'}}]}).flexReload();

      $('[name=chkAll]').live('click', User.checkAll);
      $(':regex(class, ^edit_user$)').live('click', User.editUser);
      $(':regex(class, ^delete_user$)').live('click', User.confirmDelete);
    },

    /**
     * Initiates the assigned roles table
     */
    initiateAssignedRolesGrid: function(){
       $("#assigned_roles").flexigrid({
         url: 'mod_ajax.php?page=users&do=assigned_roles',
         dataType: 'json',
         colModel : [
            {display: "<input type='checkbox' name='chkAll' />", name : 'chk', width : 20, sortable : false, align: 'center'},
            {display: 'Active', name : 'active', width : 40, sortable : true, align: 'left'},
            {display: 'Actions', name : 'actions', width : 40, sortable : false, align: 'center'},
            {display: 'Assignee', name : 'assignee', width : 90, sortable : true, align: 'left'},
            {display: 'Assigned Role', name : 'assigned_role', width : 120, sortable : true, align: 'left'},
            {display: 'Assigned Section', name : 'assigned_section', width : 70, sortable : true, align: 'left'},
            {display: 'Assigner', name : 'assigner', width : 120, sortable : true, align: 'left'},
            {display: 'Assigner Role', name : 'assigner_role', width : 120, sortable : true, align: 'left'},
            {display: 'Assigner Department', name : 'assigner_dept', width : 90, sortable : true, align: 'left'},
            {display: 'Time Assigned', name : 'time_assigned', width : 110, sortable : true, align: 'left'},
            {display: 'Time Revoked', name : 'time_revoked', width : 110, sortable : true, align: 'left'},
            {display: 'Revoker', name : 'revoker', width : 100, sortable : true, align: 'left'},
            {display: 'Revoker Role', name : 'revoker_role', width : 100, sortable : true, align: 'left'}
            ],
         buttons : [
            {name: 'New', bclass: 'add', onpress: User.newRole},
            {name: 'Revoke', bclass: 'revoke_role', onpress: User.revokeRole}
         ],
         searchitems : [
            {display: 'Assigner', name : 'assigner'}
         ],
         sortname: "active",
         sortorder: "asc",
         usepager: true,
         title: 'Assigned Roles',
         useRp: true,
         rp: 10,
         showTableToggleBtn: false,
         rpOptions: [10, 20], //allowed per-page values
         width: 870,
         singleSelect: true,
         height: 160,
         autoload: false
      }).flexOptions({params: [{name: 'action', value: 'list_roles'}, {name: 'extras', value: {date: 'date'}}]}).flexReload();

      $('[name=chkAll]').live('click', User.checkAll);
      $(':regex(class, ^edit_role$)').live('click', User.editRole);
      $(':regex(class, ^revoke_role$)').live('click', User.initiateRevokeRoles);
    },

    /**
     * Fetches the data that we need for roles manipulation
     */
    getRolesData: function(){
       var data2fetch = new Array(
         {holder: Main.userLevels, action: 'action=getuserlevels'},
         {holder: Main.departments, action: 'action=getdepts'},
         {holder: Main.users, action: 'action=getusers'}
       );
		 //fetch the user levels, if we dont have them
       $.each(data2fetch, function(i, tmp){
         if(this.holder == undefined){
            Notification.show({create:true, hide:false, updateText:false, text:'Fetching data from the server...', error:false});
            $.ajax({
               type:"POST", url:'mod_ajax.php?page=users&do=assigned_roles', dataType:'json', async: false, data: this.action,
               error:function(){
                  Notification.show({create:false, hide:true, updateText:true, text:'There was an error while communicating with the server', error:true});
               },
               success: function(data){
                  if(data.error){
                     Notification.show({create:false, hide:true, updateText:true, text:data.data, error:true});
                  }
                  else{
                     data2fetch[i].holder = data.data;
                     Notification.show({create:false, hide:true, updateText:true, text:'Fetched succesfully', error:false});
                  }
               }
           });
         }
       });
       Main.userLevels = data2fetch[0].holder;
       Main.departments = data2fetch[1].holder;
       Main.users = data2fetch[2].holder;
    },

    /**
     * Initiates the process of editing a role
     */
    editRole: function(){
       //get the raw roles data
       User.getRolesData();
       //get the details of this role
       var t = this.parentNode.parentNode.parentNode.childNodes[0].childNodes[0].childNodes[0].name;
       var id = t.substr(t.indexOf('_')+1), curRole = undefined;
       User.getSysData();

       Notification.show({create:true, hide:false, updateText:false, text:'Fetching data from the server...', error:false});
       $.ajax({
         type:"POST", url:'mod_ajax.php?page=users&do=assigned_roles', dataType:'json', data: sprintf('roleId=%s&action=getroledetails', id), async: false,
         error: Notification.serverCommunicationError,
         success: function(data){
            if(data.error){
               Notification.show({create:true, hide:true, updateText:false, text:data.error, error:true});
            }
            else curRole = data;
         }
      });
      Notification.hide();
      if(curRole == undefined) return;
      var data = User.formatRoleDetails(curRole);
      User.rolesInterface(data, 'edit');
    },

    /**
     * Prepares the roles details for use in creating the roles interface
     */
    formatRoleDetails: function(data){
      var roleSettings = {name: 'role', id: 'roleId', data: Main.userLevels, initValue: 'Select One'};
      var userSettings = {name: 'user', id: 'userId', data: Main.users, initValue: 'Select One'};
      var deptSettings = {name: 'dept', id: 'deptId', data: Main.departments, initValue: 'Select One'};
      if(data == undefined){	//initiate all the fields to the default fields
         data = {
            assignedUser: Common.generateCombo(userSettings),
            myrole: "<span>Yes<input type='radio' name='myrole' value='yes' />&nbsp;&nbsp;&nbsp;No<input type='radio' name='myrole' value='no' /></span>",
            assignedRole: Common.generateCombo(roleSettings),
            assignedSection: Common.generateCombo(deptSettings)
         };
      }
      else{   //we have some data, lets format it
         roleSettings.selected = data.assigned_role;
         deptSettings.selected = data.assigned_section;
         userSettings.selected = data.assignee;

         var yesSelected = '', noSelected = '';
         if(parseInt(data.myLevel)){
            yesSelected = 'selected';noSelected = '';
         }
         else {
            yesSelected = '';noSelected = 'selected';
         }
         data.assignedRole = Common.generateCombo(roleSettings);
         data.assignedSection = Common.generateCombo(deptSettings);
         data.assignedUser = Common.generateCombo(userSettings);
         data.myrole = "<span>Yes<input type='radio' name='myrole' value='yes' "+ yesSelected +" />&nbsp;&nbsp;&nbsp;No<input type='radio' name='myrole' value='yes' "+ noSelected +" /></span>";
      }
      return data;
    },

    /**
     * Creates the roles interface for adding/editing roles. This is a small pop-up.
     */
    rolesInterface: function(data, action){
      var mand = "<img class='mandatory' src='images/mandatory.gif' alt='Required' />";
      var content = "<div>\n\
			<table>\n\
			<tr><td>Assignee"+mand+"</td><td>"+ data.assignedUser +"</td></tr>\n\
			<tr><td>Assignee Role"+mand+"</td><td>"+ data.assignedRole+"</td></tr>\n\
			<tr><td>Assignee Department"+ mand +"</td><td>"+ data.assignedSection +"</td></tr>\n\
			<tr><td>Delegate my Role</td><td>"+ data.myrole +"</td></tr>\n\
			<tr><td colspan='2' id='user_info'><hr />Fields marked with a ("+mand+") are mandatory.</td></tr>\n\
		</table></div>";
      var ok = (action == 'add') ? 'Save' : 'Update';
      var title = (action == 'add') ? 'Assign a new role' : 'Update a delegated role';
      CustomMssgBox.createMessageBox({okText: ok, cancelText: 'Cancel', message: content, vars: {id: data.id, action: action},
         callBack: User.saveRole, cancelButton: true, customTitle: title});

      $('[name=myrole]').change(function(){
         //we wanna delegate my role, so mask out assigned role and department
         if(this.value == 'yes') $('#roleId, #deptId').prop('disabled', true);
         else if(this.value == 'no' && $('#roleId').prop('disabled') == true) $('#roleId, #deptId').prop('disabled', false);
      });
      $('#roleId').focus();
    },

    /**
     * Saves a new/edited role
     */
    saveRole: function(sender, value, vars){
       if(!value){
          sender.close();
          return;
       }
       //get the roles assigned
       var assignee = $('#userId').val(), dept = $('#deptId').val(), role = $('#roleId').val(), delegate = '', error = false;
       $.each($('[name=myrole]'), function(){if(this.checked) delegate = this.value;});
       var errorString = "<tr><td colspan='2' class='user_error'>%s</td></tr>";
       //remove any error markings if we have any
       $('.user_error').parent().remove();
       $('#alertWindow select').css({border: 'none'});

       //assignee
       if(assignee == 0){
          error = true;
          $('#userId').focus().css({border: '2px solid red'}).parent().parent().after(sprintf(errorString, 'Please select the user to delegate a role to.'));
       }
       if(delegate == 'no' || delegate == ''){  //we must have a role and a dept to be delegated to
         //department
         if(dept == 0){
            error = true;
            $('#deptId').focus().css({border: '2px solid red'}).parent().parent().after(sprintf(errorString, 'Please select the department in which the delegated role is to be executed.'));
         }
         //role
         if(role == 0){
            error = true;
            $('#roleId').focus().css({border: '2px solid red'}).parent().parent().after(sprintf(errorString, 'Please select the role the user is meant to play.'));
         }
       }

       if(error){
          $('#user_info').html('<hr />You have errors in the fields in red.').css({color: 'red'});
          return;
       }
       var data = {assignee: assignee, dept: dept, role: role, delegate: delegate};
       if(vars.action == 'edit') data.id = vars.id;
       var action = (vars.action == 'edit') ? 'editrole' : 'saverole';
       var params = 'role_data=' + $.toJSON(data) + '&action='+ action;
       sender.close();

       //we now ready to send the data to the server
       Notification.show({create:true, hide:false, updateText:false, text:"Sending the data for saving to the server...", error:false});
       Main.ajaxParams.successMssg = 'The delegated role has been saved successfully.'
       $.ajax({
          type:"POST", url:'mod_ajax.php?page=users&do=assigned_roles', dataType:'json', data: params,
			error:function(x, y, z){
            Notification.show({create:true, hide:true, updateText:true, text:'There was an error while communicating with the server', error:true});
			},
			success: function(data){
            var message;
            if(data.error == false){
               message = (Main.ajaxParams.successMssg != undefined) ? Main.ajaxParams.successMssg : 'The changes have been successfully saved.';
               $("#assigned_roles").flexOptions({params: [{name: 'action', value: 'list_roles'}, {name: 'extras', value: {date: 'date'}}]}).flexReload();
            }
            else{
               message = data.data;
            }
            if($('#notification_box') != undefined) Notification.show({create:false, hide:true, updateText:true, text:message, error:data.error});
         }
        });
    },

    confirmDeleteRole: function(){},

    /**
     * Initiates the process of delegating a new role
     */
    newRole: function(){
       User.getRolesData();
       var data = User.formatRoleDetails();
       User.rolesInterface(data, 'add')
    },

    /**
     * Initiates the process of revoking delegated roles
     */
    initiateRevokeRoles: function(){
       if($(':regex(name, ^chk_):checked').length == 0){
         CustomMssgBox.createMessageBox({
            okText: 'Ok', message: "Please select at least one role to revoke.", callBack: Common.closeMessageBox, customTitle: 'No roles selected'
         });
         return;
      }
      var users = new Array();
      $.each($(':regex(name, ^chk_):checked'), function(){
         if(this.name != 'chkAll') users[users.length] = this.name.substr(this.name.indexOf('_')+1);
      });
      CustomMssgBox.createMessageBox({
         okText: 'Yes', cancelText: 'No', message: "Are you sure you want to revoke the delegated roles?",
         callBack: User.revokeRoles, cancelButton: true, customTitle: 'Confirm revoke roles', vars: {id: users}
      });
    },

    /**
     * Revokes some of the assigned roles
     */
    revokeRoles: function(sender, value, vars){
       sender.close();
       if(!value) return;
       Main.ajaxParams.successMssg = "The delegated roles have been revoked successfully.";
       Notification.show({create:true, hide:false, text:"Please wait while we revoke the selected delegated roles..."});
       Main.ajaxParams.div2Update = 'tbody';
       $.ajax({
          type:"POST", url:'mod_ajax.php?page=users&do=assigned_roles', dataType:'json', data: 'action=revokeroles&roleIds='+$.toJSON(vars.id),
			error:function(x, y, z){
            Notification.show({create:false, hide:true, updateText:true, text:'There was an error while communicating with the server', error:true});
			},
			success: function(data){
            var message;
            if(data.error == false){
               message = (Main.ajaxParams.successMssg != undefined) ? Main.ajaxParams.successMssg : 'The changes have been successfully saved.';
               $("#assigned_roles").flexOptions({params: [{name: 'action', value: 'list_roles'}, {name: 'extras', value: {date: 'date'}}]}).flexReload();
            }
            else{
               message = data.data;
            }
            if($('#notification_box') != undefined) Notification.show({create:false, hide:true, updateText:true, text:message, error:data.error});
         }
      });
    }
}