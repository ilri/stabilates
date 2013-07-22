var Main = {
   theme: '', curTickMaterial: { },
   reEscape: new RegExp('(\\' + ['/', '.', '*', '+', '?', '|', '(', ')', '[', ']', '{', '}', '\\'].join('|\\') + ')', 'g'),
   tickMaterialValidation: [],  ajaxParams: {}
};

var TickMaterial = {
   buttonClicked: function(){
       if(/tick_material_save/.test(this.className) || /tick_material_update/.test(this.className)){ TickMaterial.saveTickMaterial(); }
       else if(/tick_material_cancel/.test(this.className)){
          TickMaterial.clearTickMaterialData();
       }
   },

   changedSelection: function(){},

   clearTickMaterialData: function(){
      $.each(Main.tickMaterialValidation, function(i, data){
          if(data.id !== undefined) $('#'+ data.id).val(data.defaultVal[0]);
          else if(data.name !== undefined) $('[name='+ data.name +']').val(data.defaultVal[0]);
       });
       $('#footer_links').html('<button type="button" class="btn btn-medium btn-primary tick_material_save" value="save">Save TickMaterial</button>\n\
         <button type="button" class="btn btn-medium btn-primary tick_material_cancel">Cancel</button>');
      TickMaterial.colorInputWithData();
      Main.curTickMaterial = {};
      $('#stabilateNo').focus();
   },

   saveTickMaterial: function(){
      if(TickMaterial.validateInput(Main.tickMaterialValidation) === true){ return; }

      //seems all is well, lets add the stabilate data
      $.each(Main.tickMaterialValidation, function(i, data){
         //get the value that we want to validate against!
         if(data.id !== undefined) Main.curTickMaterial[data.id] = $('#'+ data.id).val();
         else if(data.name !== undefined) Main.curTickMaterial[data.name] = $('[name='+ data.name +']').val();
      });
      //all is well, lets send this data to the database
      var params = 'cur_tick_material='+ escape($.toJSON(Main.curTickMaterial)) +'&action=save';
      Notification.show({create:true, hide:false, updateText:false, text:'Saving the entered results...', error:false});
      $.ajax({
         type:"POST", url:'mod_ajax.php?page=tick_materials&do=browse', dataType:'json', async: false, data: params,
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
               TickMaterial.clearTickMaterialData();
               TickMaterial.colorInputWithData();
               Main.curTickMaterial = { };
            }
            $('#stabilateNo').focus();
         }
      });
   },

   colorInputWithData: function(){
      $.each(Main.tickMaterialValidation, function(i, data){
         var object, val;
         if(data.id !== undefined) object = $('#'+ data.id);
         else if(data.name !== undefined) object = $('[name='+ data.name +']');
         val = object.val();
         if($.inArray(val, data.defaultVal) !== 0) object.css({color: '#300CD7'});
         else if($.inArray(val, data.defaultVal) === 0) object.css({color: '#555555'});
      });
   },

   fillTickMaterialData: function(selected, data){
      $.each(Main.tickMaterialValidation, function(i, dt){
          if(dt.id !== undefined) $('#'+ dt.id).val(data[dt.id]);
          else if(dt.name !== undefined) $('[name='+ dt.name +']').val(data[dt.name]);
      });
      TickMaterial.colorInputWithData();
      Main.curTickMaterial = {id:data.id};
      $('#footer_links').html("<button class='btn btn-medium btn-primary tick_material_update' type='button' value='save'>Update TickMaterial</button>\n\
         <button class='btn btn-medium btn-primary tick_material_cancel' type='button'>Cancel</button>"
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