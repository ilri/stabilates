var Main = {
   passagesValidation: [], theme: '', curStabilate: { passages: [] },
   reEscape: new RegExp('(\\' + ['/', '.', '*', '+', '?', '|', '(', ')', '[', ']', '{', '}', '\\'].join('|\\') + ')', 'g'),
   stabilatesValidation: []
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
    },

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
                  Main.curPassageId = undefined;
                   $('#passage_actions').html('<li><button class="btn btn-medium btn-primary passage_save" type="button">Save Passage</button></li>\n\
                  <li><button class="btn btn-medium btn-primary passage_cancel" type="button">Cancel</button></li>');
                  $('#inoculumTypeContainter').html("<span>Select the inoculum type</span>");
                  Stabilates.colorInputWithData();
               }
               $('#inoculumTypeId').focus();
            }
         });
       }
       else if(/passage_cancel/.test(this.className)){
          Stabilates.clearPassagesData();
       }
       else if(/stabilate_save/.test(this.className)){ Stabilates.saveStabilate(); }
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
       Stabilates.initiatePassageDetails();
       $('#passages_tab').jqxTabs('select', 0);
    },

    clearStabilatesData: function(){
       $.each(Main.stabilatesValidation, function(i, data){
          if(data.id !== undefined) $('#'+ data.id).val(data.defaultVal[0]);
          else if(data.name !== undefined) $('[name='+ data.name +']').val(data.defaultVal[0]);
       });
       $('#footer_links').html('<button type="button" class="btn btn-medium btn-primary stabilate_save" value="save">Save Stabilate</button>\n\
   <button type="button" class="btn btn-medium btn-primary stabilate_cancel">Cancel</button>');
       Stabilates.colorInputWithData();
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
       var params, inoculumType;

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
             //create an auto complete text input
             $('#inoculumTypeContainter').html("<input type='text' id='inoculumSourceId' placeholder='parent_stabilate' class='input-medium'>");
             var settings = {
                  serviceUrl:'mod_ajax.php', minChars:1, maxHeight:400, width:150,
                  zIndex: 9999, deferRequestBy: 300, //miliseconds
                  params: { page: 'stabilates', 'do': 'browse' }, //aditional parameters
                  noCache: true, //default is false, set to true to disable caching
                  onSelect: function(value, data){ Main.inoculumSourceId = data.id; },
                  formatResult: Stabilates.fnFormatResult,
                  beforeNewQuery: undefined,
                  visibleSuggestions: true
             };
             $('#passageNo').val('01');
             $('#inoculumSourceId').focus().autocomplete(settings);
             return;
          }
          else if(inoculumType === 'Passage'){
             params += '&stabilate_ref='+ Main.curStabilateId;
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
     * Fill the stabilates meta-data on selecting a particular stabilate
     *
     * @param  string   value    The selected id
     * @param  object   data     The object with the selected data
     * @returns {undefined}
     */
    fillStabilatesData: function(value, data){
       //stabilates info
       $('#stabilateNo').val(data.stab_no);
       $('#hostId').val(data.host);
       $('#localityId').val(data.locality);
       $('#isolation_date').val(data.isolation_date);
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

       Stabilates.colorInputWithData();
       Main.curStabilateId = data.id;
       //for editing stabilates
       Main.curStabilate = {};
       Main.curStabilate.id = data.id;
       Stabilates.initiatePassageDetails(data.id);
       //show the passages tab
       $('#passages_tab').jqxTabs('select', 1);
       $('#footer_links').html('<button type="button" class="btn btn-medium btn-primary stabilate_save" value="save">Update Stabilate</button>\n\
         <button type="button" class="btn btn-medium btn-primary stabilate_cancel">Cancel</button>');
    },

    initiateAutoComplete: function(){},

    fnFormatResult: function(value, data, currentValue){
      var pattern = '(' + currentValue.replace(Main.reEscape, '\\$1') + ')';
      return value.replace(new RegExp(pattern, 'gi'), '<strong>$1<\/strong>');
   },

   colorInputWithData: function(){
      $.each(Main.passagesValidation, function(i, data){
         var object, val;
         if(data.id !== undefined) object = $('#'+ data.id);
         else if(data.name !== undefined) object = $('[name='+ data.name +']');
         val = object.val();
         if($.inArray(val, data.defaultVal) !== 0) object.css({color: '#300CD7'});
         else if($.inArray(val, data.defaultVal) === 0) object.css({color: '#D70C19'});
      });
      $.each(Main.stabilatesValidation, function(i, data){
         var object, val;
         if(data.id !== undefined) object = $('#'+ data.id);
         else if(data.name !== undefined) object = $('[name='+ data.name +']');
         val = object.val();
         if($.inArray(val, data.defaultVal) !== 0) object.css({color: '#300CD7'});
         else if($.inArray(val, data.defaultVal) === 0) object.css({color: '#D70C19'});
      });
   },

   initiatePassageDetails: function(stabilateId){
      var grid = $($('#saved_passages').children()[0]);
      //initiate the patients results grid
      var url = 'mod_ajax.php?page=stabilates&do=passages';
      var source = {
         datatype: 'json', datafields: [ {name: 'passage_id'}, {name: 'passage_no'}, {name: 'inoculum_name'}, {name: 'inoculum_ref'}, {name: 'species_name'}, {name: 'idays'}, {name: 'no_infected'}, {name: 'rfreq'}, {name: 'rdate'}, {name: 'comments'}],
         id: 'id', root: 'data', async: true, type: 'POST', data: {action: 'browse', stabilate_id: stabilateId}, url: url
      };
      var resultsAdapter = new $.jqx.dataAdapter(source);
      if (grid.length === 0) {
         $("#saved_passages").jqxGrid({
            width: 980,
            height: 310,
            source: resultsAdapter,
            theme: Main.theme,
            rowdetails: false,
            rowsheight: 20,
            columns: [
               {text: 'Passage Id', datafield: 'passage_id', width: 10, hidden: true},
               {text: 'Pass. #', datafield: 'passage_no', width: 70},
               {text: 'Pass Parent', datafield: 'inoculum_ref', width: 100},
               {text: 'Inoculum Type', datafield: 'inoculum_name', width: 100},
               {text: 'Species', datafield: 'species_name', width: 150},
               {text: 'Days', datafield: 'idays', width: 60},
               {text: '# Infected', datafield: 'no_infected', width: 100},
               {text: 'Rad. Freq', datafield: 'rfreq', width: 80},
               {text: 'Rad. Date', datafield: 'rdate', width: 120},
               {text: 'Comments', datafield: 'comments', width: 200}
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
      if($('#passageNo').val() !== ''){
         $('.passage_save').click();
      }

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
               Stabilates.colorInputWithData();
               Main.curStabilate = undefined;
            }
            $('#stabilateNo').focus();
         }
      });
   },

   startPassageEdit: function(event){
      var rowIndex = event.args.rowindex;
      var data = $('#saved_passages').jqxGrid('getrowdata', rowIndex);

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
      Main.curPassageId = data.uid;
      Stabilates.colorInputWithData();

      $('#passageNo').val(data.passage_no).focus();
      $('#passages_tab').jqxTabs('select', 0);
   }
};