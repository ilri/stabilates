var Main = {};

var TickMaterial = {
   buttonClicked: function(){
      if(/_(update|save)$/.test(this.className)){ TickMaterial.saveTickMaterial(this.className); }
      else if(/_cancel$/.test(this.className)){ TickMaterial.clearTickMaterialData(); }
   },

   /**
    * Clears the metadata of the tick material that is currently being displayed
    *
    * @returns {undefined}
    */
   clearTickMaterialData: function(){
       $.each(Main.tickStabilatesValidation, function(i, data){
          if(data.id !== undefined) $('#'+ data.id).val(data.defaultVal[0]);
          else if(data.name !== undefined) $('[name='+ data.name +']').val(data.defaultVal[0]);
       });
       TickMaterial.curStabilateId = undefined;
      $('#footer_links').html("<button class='btn btn-medium btn-primary tick_material_save' type='button' value='save'>Save</button>\n\
         <button class='btn btn-medium btn-primary tick_material_cancel' type='button'>Cancel</button>");
      Stabilates.colorInputWithData(Main.tickStabilatesValidation);
   },

   /**
    * Saves the entered tick material
    * @returns {unresolved}
    */
   saveTickMaterial: function(className){
      //check whether we have all the data for this stabilate
      if(Stabilates.validateInput(Main.tickStabilatesValidation) === true){ return; }
      var action = (/_(update)$/.test(className)) ? 'update' : (/_(save)$/.test(className)) ? 'save': '';

      //seems all is well, lets add the stabilate data
      $.each(Main.tickStabilatesValidation, function(i, data){
         //get the value that we want to validate against!
         if(data.id !== undefined) Main.curStabilate[data.id] = $.trim($('#'+ data.id).val());
         else if(data.name !== undefined) Main.curStabilate[data.name] = $.trim($('[name='+ data.name +']').val());
      });

      //all is well, lets send this data to the database
      var params = 'cur_stabilate='+ escape($.toJSON(Main.curStabilate)) +'&action='+action;
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
               Stabilates.colorInputWithData(Main.tickStabilatesValidation);
               Main.curStabilateId = undefined;
            }
            $('#stabilateNo').focus();
         }
      });
   },

   changedSelection: function(){
      if(this.id === 'frozenMaterialId'){
         if($('option:selected').text() === 'Add New'){
            $('.frozen_material').html("<input type='text' id='frozenMaterialId' placeholder='Medium used' class='input-medium' /><img src='images/close.jpg' class='close' />");
            $('#frozenMaterialId').focus();
         }
      }
   },

   /**
    * Fetches the metadata for a tick stabilate
    *
    * @returns {undefined}
    */
   fetchStabilateData: function(value, data){
      //now fetch all the data for this stabilate
      Notification.show({create:true, hide:false, updateText:false, text:'Fetching the stabilate metadata...', error:false});
      $.ajax({
         type:"POST", url:'mod_ajax.php?page=tick_materials&do=stabilate_metadata', dataType:'json', async: false, data: {stabilate_id: data.id},
         error:function(){
            Notification.show({create:false, hide:true, updateText:true, text:'There was an error while communicating with the server', error:true});
            return false;
         },
         success: function(data){
            var mssg;
            if(data.error) mssg = data.data;
            else mssg = 'Metadata fetched succesfully';
            if(!data.error) TickMaterial.fillStabilateMeta(data.data);
            Notification.show({create:false, hide:true, updateText:true, text:mssg, error:data.error});
            $('#stabilateNo').focus();
         }
      });
   },

   /**
    * Fills in the metadata for a tick stabilate
    *
    * @param   object   data  An object with the metadata for the current stabilate
    * @returns {undefined}
    */
   fillStabilateMeta: function(data){
      $('#stabilateNo').val(data.stabilate_no);
      $('#parasite').val(data.parasite_name);
      $('#frozenMaterialId').val(data.frozenMaterialId);
      $('#infectionOriginId').val(data.origin);
      $('#volumePreparedId').val(data.vol_prepared);
      $('#mediumUsedId').val(data.medium_used);
      $('#noStoredId').val(data.no_stored);
      var d1 = new Date(data.date_prepared);
      var d = d1.getDate(),  m = d1.getMonth()+1, y = d1.getFullYear();
      var dates = (d <= 9 ? '0' + d : d) + '-' + (m<=9 ? '0' + m : m) + '-' + y;
      $('[name=preparationDateId]').val(dates);
      $('#stockId').val(data.stock);
      $('#sourceId').val(data.source);
      $('#speciesId').val(data.species);
      $('#cryoProtectantId').val(data.cryoprotectant);
      $('#unitVolumeId').val(data.unit);
      $('#colorId').val(data.colour);
      $('#ticksGroundId').val(data.ticks_ground);
      $('#infectionRateId').val(data.mean_infect);
      $('#noTicksId').val(data.ticks_ml);
      $('#infectedTickId').val(data.infected_acin);
      $('#storageLocationId').val(data.location);
      $('#remarksId').val(data.remarks);
      $('#experimentNoId').val(data.experiment_no);
      if(data.testing_date && data.testing_date !== 'null'){
         d1 = new Date(data.testing_date);
         d = d1.getDate(),  m = d1.getMonth()+1, y = d1.getFullYear();
         dates = (d <= 9 ? '0' + d : d) + '-' + (m<=9 ? '0' + m : m) + '-' + y;
         $('[name=experimentDateId]').val(dates);
      }
      Stabilates.colorInputWithData(Main.tickStabilatesValidation);

      //show the update button
      Main.curStabilate = {id:data.id};
      $('#footer_links').html("<button class='btn btn-medium btn-primary tick_material_update' type='button' value='update'>Update</button>\n\
         <button class='btn btn-medium btn-primary tick_material_cancel' type='button'>Cancel</button>");
   }
};