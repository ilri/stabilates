var Main = {
   theme: ''
};

var Stabilates = {
   submitLogin: function(){
       var userName = $('[name=username]').val(), password = $('[name=password]').val();
       if(userName === ''){
          alert('Please enter your username!');
          return false;
       }
       if(password === ''){
          alert('Please enter your password!');
          return false;
       }

       //we have all that we need, lets submit this data to the server
       $('[name=md5_pass]').val($.md5(password));
       $('[name=password]').val('');
       return true;
    }
};