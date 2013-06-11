var Main = {
   passagesValidation: [], theme: '', curCulture: { },
   reEscape: new RegExp('(\\' + ['/', '.', '*', '+', '?', '|', '(', ')', '[', ']', '{', '}', '\\'].join('|\\') + ')', 'g'),
   cultureValidation: [],  ajaxParams: {}
};

var Cultures = {
   buttonClicked: function(){
       if(/culture_save/.test(this.className) || /culture_update/.test(this.className)){ Cultures.saveCulture(); }
       else if(/culture_cancel/.test(this.className)){
          Cultures.clearCultureData();
       }
   },

   changedSelection: function(){},

   clearCultureData: function(){
      $.each(Main.cultureValidation, function(i, data){
          if(data.id !== undefined) $('#'+ data.id).val(data.defaultVal[0]);
          else if(data.name !== undefined) $('[name='+ data.name +']').val(data.defaultVal[0]);
       });
       $('#footer_links').html('<button type="button" class="btn btn-medium btn-primary culture_save" value="save">Save Culture</button>\n\
         <button type="button" class="btn btn-medium btn-primary culture_cancel">Cancel</button>');
      Cultures.colorInputWithData();
      Main.curCulture = {};
      $('#storeNo').focus();
   },

   saveCulture: function(){
      if(Cultures.validateInput(Main.cultureValidation) === true){ return; }

      //seems all is well, lets add the stabilate data
      $.each(Main.cultureValidation, function(i, data){
         //get the value that we want to validate against!
         if(data.id !== undefined) Main.curCulture[data.id] = $('#'+ data.id).val();
         else if(data.name !== undefined) Main.curCulture[data.name] = $('[name='+ data.name +']').val();
      });

      //all is well, lets send this data to the database
      var params = 'cur_culture='+ escape($.toJSON(Main.curCulture)) +'&action=save';
      Notification.show({create:true, hide:false, updateText:false, text:'Saving the entered results...', error:false});
      $.ajax({
         type:"POST", url:'mod_ajax.php?page=cultures&do=browse', dataType:'json', async: false, data: params,
         error:function(){
            Notification.show({create:false, hide:true, updateText:true, text:'There was an error while communicating with the server', error:true});
            return false;
         },
         success: function(data){
            var mssg;
            if(data.error) mssg = data.data+ ' Please try again.';
            else mssg = 'Stabilate saved succesfully';
            Notification.show({create:false, hide:true, updateText:true, text:mssg, error:data.error});
            if(!data.error){
               Cultures.clearCultureData();
               Cultures.colorInputWithData();
               Main.curCulture = { };
            }
            $('#storeNo').focus();
         }
      });
   },

   colorInputWithData: function(){
      $.each(Main.cultureValidation, function(i, data){
         var object, val;
         if(data.id !== undefined) object = $('#'+ data.id);
         else if(data.name !== undefined) object = $('[name='+ data.name +']');
         val = object.val();
         if($.inArray(val, data.defaultVal) !== 0) object.css({color: '#300CD7'});
         else if($.inArray(val, data.defaultVal) === 0) object.css({color: '#555555'});
      });
   },

   fillCultureData: function(selected, data){
      $.each(Main.cultureValidation, function(i, dt){
          if(dt.id !== undefined) $('#'+ dt.id).val(data[dt.id]);
          else if(dt.name !== undefined) $('[name='+ dt.name +']').val(data[dt.name]);
      });
      Cultures.colorInputWithData();
      Main.curCulture = {id:data.id};
      $('#footer_links').html("<button class='btn btn-medium btn-primary culture_update' type='button' value='save'>Update Cultures</button>\n\
         <button class='btn btn-medium btn-primary culture_cancel' type='button'>Cancel</button>"
      );
   },

   fnFormatResult: function(value, data, currentValue){
      var pattern = '(' + currentValue.replace(Main.reEscape, '\\$1') + ')';
      return value.replace(new RegExp(pattern, 'gi'), '<strong>$1<\/strong>');
   },

    /**
     * Validate the entered passage details
     *
     * @param     Object   validates   The object with the validation details
     * @returns   Boolean  Returns false if there are no validation errors, else it returns false
     */
    validateInput: function(validates){
       var value, errors = [], mssg;

       $.each(validates, function(i, data){
          //get the value that we want to validate against!
          if(data.id !== undefined) value = $('#'+ data.id).val();
          else if(data.name !== undefined) value = $('[name='+ data.name +']').val();

          //check whether it needs to have something and it has something
          if(data.mandatory && $.inArray(value, data.defaultVal) === 0){
               errors[errors.length] = (data.emptyMessage !== '') ? data.emptyMessage : data.wrongValMessage ;
          }
          //check that what is entered is actually something good
          if($.inArray(value, data.defaultVal) !== 0 && !RegExp(data.valueRegex, "gi").test(value)){
            errors[errors.length] = (data.wrongValMessage !== '') ? data.wrongValMessage : data.emptyMessage;
          }
       });
       if(errors.length === 0 ) return false;
       else{
          mssg = errors.join("<br />");
          Notification.show({create:true, hide:true, updateText:false, text:mssg, error:true});
          return true;
       }
    }
};