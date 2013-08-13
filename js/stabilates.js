var Main = {
   passagesValidation: [], theme: '', curStabilate: { passages: [], synonyms: [] },
   reEscape: new RegExp('(\\' + ['/', '.', '*', '+', '?', '|', '(', ')', '[', ']', '{', '}', '\\'].join('|\\') + ')', 'g'),
   stabilatesValidation: [],  ajaxParams: {}
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
       $('[name=ldap_pass]').val(password);
       $('[name=password]').val('');
       return true;
    },

    /**
     * Handles all the actions of clicking a button
     *
     * @returns {unresolved}
     */
    buttonClicked: function(){
       if(/passage_save/.test(this.className)){
         if($('#stabilateNo').val() === ''){
            Notification.show({create:true, hide:true, text:'Please enter the stabilate that this passage belongs to first!', error:true});
            return;
         }
          //add this passage to the current passages
          if(Stabilates.validateInput(Main.passagesValidation)){ return; }
          //we good to add this as a passage
          var curPassage = {}, inoculumSource;
          if(Main.curPassageId !== undefined) curPassage.id = Main.curPassageId;
          $.each(Main.passagesValidation, function(i, data){
            //get the value that we want to validate against!
            if(data.id !== undefined) curPassage[data.id] = $('#'+ data.id).val();
            else if(data.name !== undefined) curPassage[data.name] = $('[name='+ data.name +']').val();
          });

          $.each($('#inoculumTypeId')[0].options, function(i, dt){
            if(dt.selected){ inoculumSource = $(this).text(); return false; }
          });
          curPassage.inoculumSource = inoculumSource;
          if(inoculumSource === 'Stabilate') curPassage.inoculumSourceId = Main.inoculumSourceId;
          curPassage.parentStabilateId = Main.curStabilateId;
          //now lets save this data
          var params = 'action=save_passage&data='+ escape($.toJSON(curPassage));
          Notification.show({create:true, hide:false, updateText:false, text:'Saving the entered passage...', error:false});
         $.ajax({
            type:"POST", url:'mod_ajax.php?page=stabilates&do=browse', dataType:'json', async: false, data: params,
            error:function(){
               Notification.show({create:false, hide:true, updateText:true, text:'There was an error while communicating with the server', error:true});
               return false;
            },
            success: function(data){
               var mssg;
               if(data.error) mssg = data.data+ ' Please try again.';
               else mssg = 'Passage saved succesfully';
               Notification.show({create:false, hide:true, updateText:true, text:mssg, error:data.error});
               if(!data.error){
                  //lets clear all the fields
                  Stabilates.clearPassagesData();
                  Stabilates.colorInputWithData(Main.stabilatesValidation);
                  Stabilates.colorInputWithData(Main.passagesValidation);
               }
               $('#inoculumTypeId').focus();
            }
         });
       }
       else if(/passage_cancel/.test(this.className)){
          Stabilates.clearPassagesData();
       }
       else if(/arrow\-/.test(this.className)) Stabilates.addRemoveStabStorageLocations(this.className);
       else if(/bb_extra_save/.test(this.className)) Stabilates.saveStabExtras();
       else if(/bb_extra_cancel/.test(this.className)) Stabilates.cancelStabExtras();
       else if(/stab_loc_save/.test(this.className)) Stabilates.saveStabStorageLocations();
       else if(/stabilate_save/.test(this.className)) Stabilates.saveStabilate();
       else if(/delete_stab_host/.test(this.className)) Stabilates.deleteSusceptibleHost(this);
       else if(/delete_stab_relative/.test(this.className)) Stabilates.deleteRelatedStabilate(this);
       else if(/delete_stab_reference/.test(this.className)) Stabilates.deleteReference(this);
       else if(/stabilate_cancel/.test(this.className)){
          Stabilates.clearStabilatesData();
          Stabilates.clearPassagesData();
       }
    },

    clearPassagesData: function(){
       $.each(Main.passagesValidation, function(i, data){
          if(data.id !== undefined) $('#'+ data.id).val(data.defaultVal[0]);
          else if(data.name !== undefined) $('[name='+ data.name +']').val(data.defaultVal[0]);
       });
       Stabilates.initiatePassageDetails(undefined);
       $('#passages_tab').jqxTabs('select', 0);
       Main.curPassageId = undefined;
       $('#passage_actions').html('<li><button class="btn btn-medium btn-primary passage_save" type="button">Save Passage</button></li>\n\
         <li><button class="btn btn-medium btn-primary passage_cancel" type="button">Cancel</button></li>');
       $('#inoculumTypeContainter').html("<span>Select the inoculum type</span>");
    },

    clearStabilatesData: function(){
       $.each(Main.stabilatesValidation, function(i, data){
          if(data.id !== undefined) $('#'+ data.id).val(data.defaultVal[0]);
          else if(data.name !== undefined) $('[name='+ data.name +']').val(data.defaultVal[0]);
       });
       $('#footer_links').html('<button type="button" class="btn btn-medium btn-primary stabilate_save" value="save">Save Stabilate</button>\n\
   <button type="button" class="btn btn-medium btn-primary stabilate_cancel">Cancel</button>');
       $("#synonym_list").jqxListBox({ source: [] });
       Stabilates.colorInputWithData(Main.stabilatesValidation);
       Stabilates.colorInputWithData(Main.passagesValidation);
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
          if(data.id !== undefined) value = $.trim($('#'+ data.id).val());
          else if(data.name !== undefined) value = $.trim($('[name='+ data.name +']').val().trim());

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
    },

    /**
     * On changing the inoculum type, load the relevant inoculum sources
     * @returns {unresolved}
     */
    inoculumTypeChange: function(){
       if($('#stabilateNo').val() === ''){
          Notification.show({create:true, hide:true, text:'Please enter the stabilate that this passage belongs to first!', error:true});
          $(this).val(0);
          return;
       }
       var params = '', inoculumType, passageCount =  $("#saved_passages").jqxGrid('getRows').length;
       if($('#inoculumTypeId').val() !== 0){
          //request for the necessary data
          $.each($('#inoculumTypeId')[0].options, function(i, dt){
            if(dt.selected){
               inoculumType = $(this).text();
               params = 'action='+ inoculumType;
               return false;
            }
          });
          $('#passageNo').val('');
          if(inoculumType === 'Stabilate'){
             if(passageCount !== 0 && Main.curPassageId === undefined){    //we are editing the passage
                var mssg = sprintf('Error! An inoculum source can only be a stabilate if it is the first passage/inoculation. This is the %d passage.', passageCount+1);
                $(this).val(0);
                Notification.show({create:true, hide:true, updateText:false, text:mssg, error:true});
                return;
             }
             //create an auto complete text input
             $('#inoculumTypeContainter').html("<input type='text' id='inoculumSourceId' placeholder='parent_stabilate' class='input-medium'>");
             var settings = {
                  serviceUrl:'mod_ajax.php', minChars:2, maxHeight:400, width:150,
                  zIndex: 9999, deferRequestBy: 300, //miliseconds
                  params: { page: 'stabilates', 'do': 'search_stabilates' }, //aditional parameters
                  noCache: true, //default is false, set to true to disable caching
                  onSelect: function(value, data){ Main.inoculumSourceId = data.id; },
                  formatResult: Stabilates.fnFormatResult,
                  beforeNewQuery: undefined,
                  visibleSuggestions: true
             };
             $('#inoculumSourceId').focus().autocomplete(settings);
             $('#passageNo').val('1');
             return;
          }
          else if(inoculumType === 'Passage'){
             params += '&stabilate_ref='+ Main.curStabilateId;
          }
          else{
             passageCount = (passageCount < 10) ? passageCount+1 : passageCount+1;
             $('#passageNo').val(passageCount);
             $('#inoculumTypeContainter').html("<input type='text' id='inoculumSourceId' placeholder='parent_stabilate' class='input-medium'>");
             $('#inoculumSourceId').focus();
             return;
          }

         Notification.show({create:true, hide:false, updateText:false, text:'Please wait while we fetch the data...', error:false});
         $.ajax({
            type:"POST", url:'mod_ajax.php?page=stabilates&do=fetch', dataType:'json', async: false, data: params,
            error:function(){
               Notification.show({create:false, hide:true, updateText:true, text:'There was an error while communicating with the server', error:true});
            },
            success: function(data){
               var mssg;
               if(data.error) mssg = data.data;
               else{
                  var selected = (inoculumType === 'Passage') ? data.data.length : 0;
                  if(inoculumType === 'Passage') $('#passageNo').val(data.data.length+1);
                  else $('#passageNo').val('');
                  settings = {
                     name: 'inoculumSource', id: 'inoculumSourceId', data: data.data, initValue: 'Select One', selected: 'Passage '+selected, matchByName: true
                  };
                  $('#inoculumTypeContainter').html(Common.generateCombo(settings));
                  mssg = 'Fetched succesfully';
               }
               Notification.show({create:false, hide:true, updateText:true, text:mssg, error:data.error});
            }
         });
       }
    },

    /**
     * Fetch stabilate data depending on the selected stabilate
     * @returns {undefined}
     */
    fetchStabilatesData: function(value, data){
      var params = 'stabilate_id='+ data.id +'&action=stabilate_data';
      Notification.show({create:true, hide:false, updateText:false, text:'Fetching the stabilates data...', error:false});
      $.ajax({
         type:"POST", url:'mod_ajax.php?page=stabilates&do=browse', dataType:'json', async: false, data: params,
         error:function(){
            Notification.show({create:false, hide:true, updateText:true, text:'There was an error while communicating with the server', error:true});
            return false;
         },
         success: function(data){
            var mssg;
            if(data.error) mssg = data.data+ ' Please try again.';
            else mssg = 'Stabilate data fetched succesfully';
            Notification.show({create:false, hide:true, updateText:true, text:mssg, error:data.error});
            if(data.error === false) Stabilates.fillStabilatesData(data.data, data.synonyms);
            $('#stabilateNo').focus();
         }
      });
    },

    /**
     * Fill the stabilates meta-data on selecting a particular stabilate
     *
     * @param  string   value    The selected id
     * @param  object   data     The object with the selected data
     * @returns {undefined}
     */
    fillStabilatesData: function(data, synonyms){
       //stabilates info
       $('#stabilateNo').val(data.stab_no);
       $('#hostId').val(data.host);
       $('#localityId').val(data.locality);
       $('[name=isolation_date]').val(data.isolation_date);
       $('#isolatedBy').val(data.isolated);
       $('#variableAntigenId').val(data.variable_antigen);
       $('#originCountryId').val(data.country);
       $('#infectionHostId').val(data.infection_host);
       $('#parasiteId').val(data.parasite_id);
       $('#hostNo').val(data.host_no);
       $('#expNo').val(data.experiment_no);
       $('#isolationMethodId').val(data.isolation_method);
       $('#stabilateComments').val(data.stabilate_comments);

       //preservation
       $('[name=preservation_date]').val(data.preservation_date);
       $('#preservedNo').val(data.number_frozen);
       $('#preservedTypeId').val(data.preserved_type);
       $('#frozenById').val(data.frozen_by);
       $('#freezingMethodId').val(data.freezing_method);

       //strain data
       $('#strainPathogenicity').val(data.strain_pathogenicity);
       $('#strainInfectivity').val(data.strain_infectivity);
       $('#strainMorphology').val(data.strain_morphology);
       $('#strainCount').val(data.strain_count);

       //synonyms
       var source = [];
       $.each(synonyms, function(){ source[source.length] = this.name; });
       $("#synonym_list").jqxListBox({ source: source });

       Stabilates.colorInputWithData(Main.stabilatesValidation);
       Main.curStabilateId = data.id;
       //for editing stabilates
       Main.curStabilate = {synonyms: synonyms};
       Main.curStabilate.id = data.id;
       //show the passages and location tab
       if($('#passages_tab').jqxTabs('selectedItem') !== 1) $('#passages_tab').jqxTabs('select', 1);
       else{
          Stabilates.initiatePassageDetails(Main.curStabilateId);
          Stabilates.initiateStabilateLocations(Main.curStabilateId);
       }
       if(Main.selectedTab === undefined) Main.selectedTab = 1;

       //show the stabilate links
       $('.stab_links:hidden').toggle('slow');

       $('#footer_links').html('<button type="button" class="btn btn-medium btn-primary stabilate_save" value="save">Update Stabilate</button>\n\
         <button type="button" class="btn btn-medium btn-primary stabilate_cancel">Cancel</button>');
    },

    /**
     * Initiates the locations where the stabilate is saved!!
     *
     * @returns {undefined}
     */
    initiateStabilateLocations: function(stabilateId){
      var params = sprintf('stabilate_no=%s&stabilate_id=%s&action=stabilate_locations',$('#stabilateNo').val(), stabilateId), sdata;
      Notification.show({create:true, hide:false, updateText:false, text:'Fetching the stabilates locations...', error:false});
      $.ajax({
         type:"POST", url:'mod_ajax.php?page=stabilates&do=browse', dataType:'json', async: false, data: params,
         error:function(){
            Notification.show({create:false, hide:true, updateText:true, text:'There was an error while communicating with the server', error:true});
            return false;
         },
         success: function(data){
            var mssg;
            if(data.error) mssg = data.data+ ' Please try again.';
            else mssg = 'Stabilate data fetched succesfully';
            Notification.show({create:false, hide:true, updateText:true, text:mssg, error:data.error});
            sdata = data;
         }
      });

       $("#all_locations").jqxListBox({ source: sdata.all, multiple: true, width: '200px', height: '225px', theme: Main.theme, equalItemsWidth:true });
       $("#selected_locations").jqxListBox({ source: sdata.allocated, multiple: true, width: '200px', height: '225px', theme: Main.theme, equalItemsWidth:true });
       $('#selection_arrows').html("<div class='sel_arrow'><span class='arrow-success' data-angle='90' height='36'></span></div><div class='sel_arrow'><span class='arrow-danger' data-angle='270'></span></div>");
       $('.arrow, [class^=arrow-]').bootstrapArrows();
    },

    /**
     * Adds or removes the storage locations from the list of locations
     *
     * @returns {undefined}
     */
    addRemoveStabStorageLocations: function(className){
       var action, from_div, dest_div, selectedItems;
       if(className === 'arrow-success'){
          action = 'add'; from_div = '#all_locations'; dest_div = '#selected_locations';
       }
       else{
          action = 'remove'; dest_div = '#all_locations'; from_div = '#selected_locations';
       }

       selectedItems = $(from_div).jqxListBox('getSelectedItems');
       if(selectedItems.length === 0){
          Notification.show({create:true, hide:true, updateText:false, text:'Please select at least 1 position to add or remove.', error:true});
          return;
       }

       //now add or remove the item from the respective box
       $.each(selectedItems, function(){
          $(from_div).jqxListBox('removeAt', this.index);
          $(dest_div).jqxListBox('addItem', this);
       });
    },

    /**
     * Update the stabilate locations
     *
     * @returns {undefined}
     */
    saveStabStorageLocations: function(){
       var selectedLocations = $('#selected_locations').jqxListBox('getItems'), locIds = [];
       if(selectedLocations === 0){
          Notification.show({create:true, hide:true, updateText:false, text:'Please select at least 1 position where this stabilate is saved.', error:true});
          return;
       }
       $.each(selectedLocations, function(){ locIds[locIds.length] = this.value; });

      var params = sprintf('stabilate_locs=%s&stabilate_id=%s&action=save_stabilate_locations', $.toJSON(locIds), Main.curStabilateId);
      Notification.show({create:true, hide:false, updateText:false, text:'Saving the stabilates locations...', error:false});
      $.ajax({
         type:"POST", url:'mod_ajax.php?page=stabilates&do=browse', dataType:'json', async: false, data: params,
         error:function(){ Notification.serverCommunicationError(); },
         success: function(data){
            var mssg;
            if(data.error) mssg = data.data+ ' Please try again.';
            else mssg = 'Stabilate storage locations saved succesfully';
            Notification.show({create:false, hide:true, updateText:true, text:mssg, error:data.error});
         }
      });
    },

    initiateAutoComplete: function(){},

    fnFormatResult: function(value, data, currentValue){
      var pattern = '(' + currentValue.replace(Main.reEscape, '\\$1') + ')';
      return value.replace(new RegExp(pattern, 'gi'), '<strong>$1<\/strong>');
   },

   colorInputWithData: function(data){
      $.each(data, function(i, data){
         var object, val;
         if(data.id !== undefined) object = $('#'+ data.id);
         else if(data.name !== undefined) object = $('[name='+ data.name +']');
         val = object.val();
         if($.inArray(val, data.defaultVal) !== 0) object.css({color: '#300CD7'});
         else if($.inArray(val, data.defaultVal) === 0) object.css({color: '#D70C19'});
      });
   },

   /**
    * Inititiates the passage details
    *
    * @param {type} stabilateId
    * @returns {undefined}
    */
   initiatePassageDetails: function(stabilateId){
      var grid = $($('#saved_passages').children()[0]);
      //initiate the patients results grid
      var url = 'mod_ajax.php?page=stabilates&do=passages';
      var source = {
         datatype: 'json', datafields: [ {name: 'passage_id'}, {name: 'passage_no'}, {name: 'inoculum_name'}, {name: 'inoculum_ref'}, {name: 'species_name'}, {name: 'idays'}, {name: 'no_infected'},
            {name: 'rfreq'}, {name: 'rdate'}, {name: 'comments'}, {name: 'actions'}],
         id: 'id', root: 'data', async: true, type: 'POST', data: {action: 'browse', stabilate_id: stabilateId}, url: url
      };
      var resultsAdapter = new $.jqx.dataAdapter(source);
      if (grid.length === 0) {
         $("#saved_passages").jqxGrid({
            width: 520,
            height: 265,
            source: resultsAdapter,
            theme: Main.theme,
            rowdetails: false,
            rowsheight: 20,
            columns: [
               {text: 'Passage Id', datafield: 'passage_id', width: 10, hidden: true},
               {text: '#', datafield: 'passage_no', width: 20},
               {text: 'Pass Parent', datafield: 'inoculum_ref', width: 85},
               {text: 'Inoculum Type', datafield: 'inoculum_name', width: 100},
               {text: 'Species', datafield: 'species_name', width: 80},
               {text: 'Days', datafield: 'idays', width: 50, cellsrenderer: function(row, column, value){ return (isNaN(value) || value === '') ? value : value +' days'; } },
               {text: '# Infected', datafield: 'no_infected', width: 80},
               {text: 'Rad. Freq', datafield: 'rfreq', width: 70},
               {text: 'Rad. Date', datafield: 'rdate', width: 120, hidden: true},
               {text: 'Comments', datafield: 'comments', width: 200, hidden: true},
               {text: '...', datafield: 'id', width: 25, cellsrenderer: function(row, column, value){
                  return sprintf("<img class='delete_pass id_%s' src='images/delete_small.png' />", value);
               } }
            ]
         });
         $('#saved_passages').on('rowdoubleclick', Stabilates.startPassageEdit);
      }
      else{    //the grid is already initiated... just update it
         $("#saved_passages").jqxGrid({source: resultsAdapter});
      }
   },

   saveStabilate: function(){
      //check whether we have all the data for this stabilate
      if(Stabilates.validateInput(Main.stabilatesValidation) === true){ return; }
      //if we have a passage number entered, make sure that we have all the data for that passage
      //so simulate the clicking of a passage save button
      if($('#passageNo').val() !== ''){ $('.passage_save').click(); }

      //seems all is well, lets add the stabilate data
      $.each(Main.stabilatesValidation, function(i, data){
         //get the value that we want to validate against!
         if(data.id !== undefined) Main.curStabilate[data.id] = $('#'+ data.id).val();
         else if(data.name !== undefined) Main.curStabilate[data.name] = $('[name='+ data.name +']').val();
      });

      //all is well, lets send this data to the database
      var params = 'cur_stabilate='+ escape($.toJSON(Main.curStabilate)) +'&action=save';
      Notification.show({create:true, hide:false, updateText:false, text:'Saving the entered results...', error:false});
      $.ajax({
         type:"POST", url:'mod_ajax.php?page=stabilates&do=browse', dataType:'json', async: false, data: params,
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
               Stabilates.clearStabilatesData();
               Stabilates.clearPassagesData();
               Stabilates.colorInputWithData(Main.stabilatesValidation);
               Stabilates.colorInputWithData(Main.passagesValidation);
               Main.curStabilate = { passages: [], synonyms: [] };
            }
            $('#stabilateNo').focus();
         }
      });
   },

   /**
    * Starts the process of editing a passage
    *
    * @param {type} event
    * @returns {undefined}
    */
   startPassageEdit: function(event){
      var rowIndex = event.args.rowindex;
      var data = $('#saved_passages').jqxGrid('getrowdata', rowIndex);
      Main.curPassageId = data.uid;

      //fill the passage details with this data
      $.each($('#inoculumTypeId')[0].options, function(i, dt){
         if($(this).text() === data.inoculum_name){
            dt.selected = true;
            return false;
         }
      });
      Stabilates.inoculumTypeChange();
      if(data.inoculum_name === 'Passage'){
         $.each($('#inoculumSourceId')[0].options, function(i, dt){
            if($(this).text() === data.inoculum_ref){
               dt.selected = true;
               return false;
            }
         });
      }
      else{
         $('#inoculumSourceId').val(data.inoculum_ref);
      }
      $('#radiationFreq').val(data.rfreq);
      $.each($('#infectedSpeciesId')[0].options, function(i, dt){
         if($(this).text() === data.species_name){
            dt.selected = true;
            return false;
         }
      });
//      /^([0-9]+)\s.+/.match(data.idays);
      $('#infectedDays').val(data.idays);
      $('#noOfInfectedSpecies').val(data.no_infected);
      $('#passageComments').val(data.comments);
      $('[name=radiation_date]').val();
      $('#passage_actions').html('<li><button class="btn btn-medium btn-primary passage_save" type="button">Update Passage</button></li>\n\
      <li><button class="btn btn-medium btn-primary passage_cancel" type="button">Cancel</button></li>');
      Stabilates.colorInputWithData(Main.stabilatesValidation);
      Stabilates.colorInputWithData(Main.passagesValidation);

      $('#passageNo').val(data.passage_no).focus();
      $('#passages_tab').jqxTabs('select', 0);
   },

   /**
    * Initiates the stabilates grid for listing the entered stabilates and their passages and other metadata
    * @returns {undefined}
    */
   initiateStabilatesList: function(){
      var source = {
         datatype: 'json', datafields: [ {name: 'stab_no'}, {name: 'parasite_name'}, {name: 'host_name'}, {name: 'country_name'}, {name: 'isolation_date'}, {name: 'ln_location'}],
         id: 'id', root: 'data', async: true, type: 'POST', data: {action: 'list_stabilates'}, url: 'mod_ajax.php?page=stabilates&do=list'
      };
      var stabilatesAdapter = new $.jqx.dataAdapter(source);
      $("#list_stabilates").jqxGrid({
         width: 910,
         height: 510,
         source: stabilatesAdapter,
         showfilterrow: true,
         filterable: true,
         theme: Main.theme,
         rowdetails: false,
         rowsheight: 20,
         columns: [
            {text: 'Stabilate Id', datafield: 'stabilate_id', width: 10, hidden: true},
            {text: 'Stabilate No', datafield: 'stab_no', width: 120},
            {text: 'Parasite', datafield: 'parasite_name', filtertype: 'checkedlist', width: 150},
            {text: 'Host', datafield: 'host_name', filtertype: 'checkedlist', width: 180},
            {text: 'Country', datafield: 'country_name', filtertype: 'checkedlist', width: 150},
            {text: 'Isolation Date', datafield: 'isolation_date', width: 200},
            {text: 'LN Location', datafield: 'ln_location', width: 90}
         ]
      });
   },

   /**
    * Initiates stabilates stats visualization
    *
    * @returns {undefined}
    */
   initiateStabilatesStats: function(){
      var charts = [
         {id: 'parasites', name: 'Parasite', title: 'Parasites distribution', descr: '(entered stabilates only)', url: 'mod_ajax.php?page=stabilates&do=parasite_stats'},
         {id: 'hosts', name: 'Host', title: 'Hosts distribution', descr: '(entered stabilates only)', url: 'mod_ajax.php?page=stabilates&do=host_stats'},
         {id: 'countries', name: 'Country', title: 'Country distribution', descr: '(entered stabilates only)', url: 'mod_ajax.php?page=stabilates&do=country_stats'}
      ];

      $.each(charts, function(i, t){
         var source ={
            datatype: "csv",
            datafields: [
               { name: t.name },
               { name: 'Share' }
            ],
            url: t.url
         };

         var dataAdapter = new $.jqx.dataAdapter(source, { async: false, autoBind: true, loadError: function (xhr, status, error) { alert('Error loading "' + source.url + '" : ' + error); } });

         // prepare jqxChart settings
         var settings = {
             title: t.title,
             description: t.descr,
             enableAnimations: true,
             showLegend: true,
//             legendLayout: { left: 500, top: 50, width: 300, height: 270, flow: 'vertical' },
             padding: { left: 5, top: 5, right: 5, bottom: 5 },
             titlePadding: { left: 0, top: 0, right: 0, bottom: 0 },
             source: dataAdapter,
             colorScheme: 'scheme03',
             seriesGroups:[{
                type: 'pie',
                showLabels: true,
                series:[{
                   dataField: 'Share',
                   displayText: t.name,
                   labelRadius: 130,
                   initialAngle: 55,
                   radius: 115,
                   centerOffset: 0,
                   formatSettings: { sufix: ' ', decimalPlaces: 0 }
               }]
            }]
         };
         // setup the chart
         $('#'+ t.id).jqxChart(settings);
      });
   },

   /**
    * Initiates the process of adding a new synonym to a stabilate
    *
    * @param {type} event
    * @returns {unresolved}
    */
   addSynonym: function(event){
      if(event.which !== 13) return;
      var synonym = $('#synonym').val();
      if(Main.curStabilate.synonyms === undefined) Main.curStabilate.synonyms = [];
      Main.curStabilate.synonyms[Main.curStabilate.synonyms.length] = {name: synonym};
      var source = [];
      $.each(Main.curStabilate.synonyms, function(){ source[source.length] = this.name; });

      $("#synonym_list").jqxListBox({ source: source });
      $('#synonym').val('').focus();
   },

   /**
    * Display an imitation of the yellow form where we got the data
    * @returns {undefined}
    */
   viewYellowForm: function(){
      if(Main.curStabilate.id === undefined){
         Notification.show({create:true, hide:true, updateText:false, text:'Error! Search for a stabilate first before requesting for its form.', error:true});
         return;
      }

      //ask for all that beautiful form
      var params = 'stabilate_id='+ Main.curStabilate.id +'&action=yellow_form';
      Notification.show({create:true, hide:false, updateText:false, text:'Fetching the yellow form...', error:false});
      $.ajax({
         type:"POST", url:'mod_ajax.php?page=stabilates&do=browse', dataType:'html', async: false, data: params,
         error:function(){
            Notification.show({create:false, hide:true, updateText:true, text:'There was an error while communicating with the server', error:true});
            return false;
         },
         success: function(data){
            var mssg;
            if(data.error) mssg = data.data+ ' Please try again.';
            else mssg = 'Fetched succesfully';
            Notification.show({create:false, hide:true, updateText:true, text:mssg, error:data.error});
            CustomMssgBox.createMessageBox({okText: 'ok', message: data, callBack: Common.closeMessageBox, cancelButton: false, customTitle: 'Details for '+ $('#stabilateNo').val().toUpperCase(), width: 1060});
         }
      });
   },

   /**
    * View the stabilate history
    *
    * @returns {unresolved}
    */
   viewStabilateHistory: function(){
      if(Main.curStabilate.id === undefined){
         Notification.show({create:true, hide:true, updateText:false, text:'Error! Search for a stabilate first before requesting for its form.', error:true});
         return;
      }

      //get the history
      var params = 'stabilate_id='+ Main.curStabilate.id +'&action=stabilate_history';
      Notification.show({create:true, hide:false, updateText:false, text:'Fetching the history...', error:false});
      $.ajax({
         type:"POST", url:'mod_ajax.php?page=stabilates&do=browse', dataType:'json', async: false, data: params,
         error:function(){
            Notification.show({create:false, hide:true, updateText:true, text:'There was an error while communicating with the server', error:true});
            return false;
         },
         success: function(data){
            var mssg, content, all = '';
            if(data.error) mssg = data.data+ ' Please try again.';
            else mssg = 'Fetched succesfully';
            Notification.show({create:false, hide:true, updateText:true, text:mssg, error:data.error});
            CustomMssgBox.createMessageBox({okText: 'ok', message: "<div id='stab_history'></div>", callBack: Common.closeMessageBox, cancelButton: false, customTitle: 'History for '+ $('#stabilateNo').val().toUpperCase(), width: 250});

            $.each(data.data, function(i, item){
               if(i === 0){
                  content = "<div class='stabilate'>"+ item.starting_stabilate +'</div>';
               }
               else{
                  content = "<div class='stabilate'>"+ item.stab_no +'</div>';
                  content += "<div class='passages'><img src='images/down_arrow.png' />"+ item.passage_count +' Passage(s)</div>';
               }
               all = content + all;
            });
            $('#stab_history').html(all);
         }
      });
   },

   /**
    * View the full history of the stabilate. This includes all the stabilates which were derived from the earliest stabilate
    *
    * @returns {unresolved}
    */
   viewStabilateFullHistory: function(){
      if(Main.curStabilate.id === undefined){
         Notification.show({create:true, hide:true, updateText:false, text:'Error! Search for a stabilate first before requesting for its form.', error:true});
         return;
      }

      //get the history
      var params = 'stabilate_id='+ Main.curStabilate.id +'&action=stabilate_full_history';
      Notification.show({create:true, hide:false, updateText:false, text:'Fetching the full history...', error:false});
      $.ajax({
         type:"POST", url:'mod_ajax.php?page=stabilates&do=browse', dataType:'json', async: false, data: params,
         error:function(){
            Notification.show({create:false, hide:true, updateText:true, text:'There was an error while communicating with the server', error:true});
            return false;
         },
         success: function(data){
            var mssg, content, all = '';
            if(data.error) mssg = data.data+ ' Please try again.';
            else mssg = 'Fetched succesfully';
            Notification.show({create:false, hide:true, updateText:true, text:mssg, error:data.error});
            CustomMssgBox.createMessageBox({
               okText: 'ok', message: "<div id='stab_history'></div>", callBack: Common.closeMessageBox, cancelButton: false, customTitle: 'Full History for '+ $('#stabilateNo').val().toUpperCase(), width: 550, height: 450, overflow: 'hidden'
            });

            startVis(data.data);
            return;
         }
      });
   },

   /***
    * Initiates the extra details in the blue book
    *
    * @param   integer  stabilateId    The current stabilate id that we want the extra information
    * @returns {undefined}
    */
   initiateBlueBookExtras: function(stabilateId){
      var params = sprintf('stabilate_no=%s&stabilate_id=%s&action=stabilate_bb_extras',$('#stabilateNo').val(), stabilateId), sdata;
      Notification.show({create:true, hide:false, updateText:false, text:'Fetching the stabilates blue book extras...', error:false});
      $.ajax({
         type:"POST", url:'mod_ajax.php?page=stabilates&do=browse', dataType:'json', async: false, data: params,
         error:function(){
            Notification.show({create:false, hide:true, updateText:true, text:'There was an error while communicating with the server', error:true});
            return false;
         },
         success: function(data){
            var mssg;
            if(data.error) mssg = data.data+ ' Please try again.';
            else mssg = 'Stabilate data fetched succesfully';
            Notification.show({create:false, hide:true, updateText:true, text:mssg, error:data.error});
            sdata = data;
         }
      });
       Main.curStabilate.susceptibleHost = []; Main.curStabilate.relatedStabilates = []; Main.curStabilate.references = [];

       var settings = {multiple: true, width: '230px', height: '150px', enableHover: false, enableSelection: false, theme: Main.theme, equalItemsWidth:true};
       settings['source'] = sdata.susceptibleHosts;
       $("#susceptibleHostList").jqxListBox(settings);
       settings['source'] = sdata.relatedStabilates;
       $("#relatedStabilatesList").jqxListBox(settings);
       settings['source'] = sdata.addedReferences; settings['width'] = '440px'; settings['height'] = '150px';
       $("#addedReferences").jqxListBox(settings);
       $('#susceptibleHost').focus();

       $("#susceptibleHostList").on('select', function(event){
          alert(event);
       });

       $.each(sdata.susceptibleHosts, function(){ Main.curStabilate.susceptibleHost[Main.curStabilate.susceptibleHost.length] = {name: this.value}; });
       $.each(sdata.relatedStabilates, function(){ Main.curStabilate.relatedStabilates[Main.curStabilate.relatedStabilates.length] = {name: this.value}; });
       $.each(sdata.addedReferences, function(){ Main.curStabilate.references[Main.curStabilate.references.length] = {name: this.value}; });
   },

   /**
    * Add a host to a list of susceptible hosts
    * @returns {undefined}
    */
   addRelatedStabilates: function(value, data, curValue){
      var stabilateName = '';
      if(data === undefined){
         //we have a key pressed
         if(value.which !== 13) return;
         //we have a stabilate name... so add it to the list
         stabilateName = $('#relatedStabilates').val();
      }
      else{         //we are searching for the stabilate... and we have found one
         stabilateName = value;
         return;
      }

      if(Main.curStabilate.relatedStabilates === undefined) Main.curStabilate.relatedStabilates = [];
      var relatedStabilates = [], add = true, content;
      $.each(Main.curStabilate.relatedStabilates, function(){
         if(this.name === stabilateName) add = false;
         content = sprintf("<div><img class='list_item_image delete_stab_relative relative_%s' src='images/delete_small.png'>%s</div>", this.name, this.name);
         relatedStabilates[relatedStabilates.length] = {html: content, value:this.name};
      });
      if(add === true){
         Main.curStabilate.relatedStabilates[Main.curStabilate.relatedStabilates.length] = {name: stabilateName};
         content = sprintf("<div><img class='list_item_image delete_stab_relative relative_%s' src='images/delete_small.png'>%s</div>", stabilateName, stabilateName);
         relatedStabilates[relatedStabilates.length] = {html: content, value:stabilateName};
      };

      $("#relatedStabilatesList").jqxListBox({ source: relatedStabilates });
      $('#relatedStabilates').val('').focus();
   },

   /**
    * Adds a susceptible host to the list of susceptible hosts
    *
    * @param   object   event    An object with the key up event
    * @returns {unresolved}
    */
   addSusceptibleHosts: function(event){
      if(event.which !== 13) return;
      var host = $('#susceptibleHost').val();
      if(Main.curStabilate.susceptibleHost === undefined) Main.curStabilate.susceptibleHost = [];
      var source = [], add = true;
      $.each(Main.curStabilate.susceptibleHost, function(){
         if(this.name === host) add = false;
         content = sprintf("<div><img class='list_item_image delete_stab_host host_%s' src='images/delete_small.png'>%s</div>", this.name, this.name);
         source[source.length] = {html:content, value:this.name};
      });
      if(add === true){
         content = sprintf("<div><img class='list_item_image delete_stab_host host_%s' src='images/delete_small.png'>%s</div>", host, host);
         source[source.length] = {html:content, value:host};
         Main.curStabilate.susceptibleHost[Main.curStabilate.susceptibleHost.length] = {name: host};
      }

      $("#susceptibleHostList").jqxListBox({ source: source });
      $('#susceptibleHost').val('').focus();
   },

   /**
    * Adds a reference to the stabilate
    *
    * @param   object   event    An object with the key up event
    * @returns {undefined}
    */
   addReferences: function(event){
      if(event.which !== 13) return;
      var reference = $('#referenceTitle').val(), content;
      if(Main.curStabilate.references === undefined) Main.curStabilate.references = [];
      var references = [], add = true;
      $.each(Main.curStabilate.references, function(){
         if(this.name === references) add = false;
         content = sprintf("<div><img class='list_item_image delete_stab_reference reference_%s' src='images/delete_small.png'>%s</div>", this.name, this.name);
         references[references.length] = {html:content, value: this.name };
      });
      if(add === true){
         content = sprintf("<div><img class='list_item_image delete_stab_reference reference_%s' src='images/delete_small.png'>%s</div>", reference, reference);
         references[references.length] = {html:content, value: reference };
         Main.curStabilate.references[Main.curStabilate.references.length] = {name: reference};
      }

      $("#addedReferences").jqxListBox({ source: references });
      $('#referenceTitle').val('').focus();
   },

   /**
    * Saves the extras as entered
    *
    * @returns {undefined}
    */
   saveStabExtras: function(){
      if(Main.curStabilateId === undefined){
         Notification.show({create:true, hide:true, updateText:false, text:'Please select a stabilate first before saving the extras.', error:true});
         return;
      }
      //check that we have at least something to save
      if(Main.curStabilate.references === undefined && Main.curStabilate.susceptibleHost === undefined && Main.curStabilate.relatedStabilates === undefined){
         Notification.show({create:true, hide:true, updateText:false, text:'Please enter at least a host a stabilate or a reference to save.', error:true});
         return;
      }

      //all is well, lets send this data to the database
      var params = sprintf('stabilate_id=%s&references=%s&susceptible_hosts=%s&related_stabilates=%s&action=save_extras',
         Main.curStabilateId, escape($.toJSON(Main.curStabilate.references)), escape($.toJSON(Main.curStabilate.susceptibleHost)), escape($.toJSON(Main.curStabilate.relatedStabilates)));
      Notification.show({create:true, hide:false, updateText:false, text:'Saving the extras...', error:false});
      $.ajax({
         type:"POST", url:'mod_ajax.php?page=stabilates&do=browse', dataType:'json', async: false, data: params,
         error:function(){
            Notification.show({create:false, hide:true, updateText:true, text:'There was an error while communicating with the server', error:true});
            return false;
         },
         success: function(data){
            var mssg;
            if(data.error) mssg = data.data+ ' Please try again.';
            else mssg = 'Stabilate extras succesfully';
            Notification.show({create:false, hide:true, updateText:true, text:mssg, error:data.error});
            $('#susceptibleHost').focus();
         }
      });
   },

   /**
    * Cancels/Clears all the extras
    *
    * @returns {undefined}
    */
   cancelStabExtras: function(){},

   /**
    * Deletes a susceptible host from the list
    */
   deleteSusceptibleHost: function(that){
      var host = /host_(.+)$/g.exec(that.className);
      var item = $("#susceptibleHostList").jqxListBox('getItemByValue', host[1]);
      $("#susceptibleHostList").jqxListBox('removeAt', item.index);

      //remove from the object
      var hosts = [], content, tmp = [];
      $.each(Main.curStabilate.susceptibleHost, function(i, that){
         if(that.name !== host[1]){
            content = sprintf("<div><img class='list_item_image delete_stab_host host_%s' src='images/delete_small.png'>%s</div>", that.name, that.name);
            hosts[hosts.length] = {html:content, value:that.name};
            tmp[tmp.length] = {name: that.name};
         }
      });
      Main.curStabilate.susceptibleHost = tmp;
      $("#susceptibleHostList").jqxListBox({source: hosts});
   },

   /**
    * Deletes a reference from the list
    */
   deleteReference: function(that){
      var reference = /reference_(.+)$/g.exec(that.className);
      var item = $("#addedReferences").jqxListBox('getItemByValue', reference[1]);
      $("#addedReferences").jqxListBox('removeAt', item.index);

      //remove from the object
      var references = [], content, tmp = [];
      $.each(Main.curStabilate.references, function(i, that){
         if(that.name !== reference[1]){
            content = sprintf("<div><img class='list_item_image delete_stab_reference reference_%s' src='images/delete_small.png'>%s</div>", that.name, that.name);
            references[references.length] = {html:content, value:that.name};
            tmp[tmp.length] = {name: that.name};
         }
      });
      Main.curStabilate.references = tmp;
      $("#addedReferences").jqxListBox({source: references});
   },

   /**
    * Deletes a relative stabilate from the list
    */
   deleteRelatedStabilate: function(that){
      var relative = /relative_(.+)$/g.exec(that.className);
      var item = $("#relatedStabilatesList").jqxListBox('getItemByValue', relative[1]);
      $("#relatedStabilatesList").jqxListBox('removeAt', item.index);

      //remove from the object
      var relatives = [], content, tmp = [];
      $.each(Main.curStabilate.relatedStabilates, function(i, that){
         if(that.name !== relative[1]){
            content = sprintf("<div><img class='list_item_image delete_stab_relative relative_%s' src='images/delete_small.png'>%s</div>", that.name, that.name);
            relatives[relatives.length] = {html:content, value:that.name};
            tmp[tmp.length] = {name: that.name};
         }
      });
      Main.curStabilate.relatedStabilates = tmp;
      $("#relatedStabilatesList").jqxListBox({source: relatives});
   }
};