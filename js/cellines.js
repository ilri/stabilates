var Main = {
    theme: '', curCelline: {},
    reEscape: new RegExp('(\\' + ['/', '.', '*', '+', '?', '|', '(', ')', '[', ']', '{', '}', '\\'].join('|\\') + ')', 'g'),
    cellineValidation: [], ajaxParams: {}
};

var Cellines = {
    buttonClicked: function() {
        if (/celline_save/.test(this.className) || /celline_update/.test(this.className)) {
            Cellines.saveCelline();
        }
        else if (/celline_cancel/.test(this.className)) {
            Cellines.clearCellineData();
        }
        else if (/celline_delete/.test(this.className)) {
            Cellines.deleteCelline();
        }
        else if (/add_new_method_cancel/.test(this.className)) {
            //delete the stuff in the combolocation
            $("#freezingMethodComboLocation").html('');

            // now change the id back to what it should be
            $('#freezingMethodId1').attr('id', 'freezingMethodId');

            //save the drop down to a var first
            var myDoc = $("#tempComboLocation").html();

            //delete the element
            $("#tempComboLocation").remove();

            //now insert the data to the old div
            $(myDoc).appendTo("#freezingMethodComboLocation");
        }
    },
    changedSelection: function() {
        if (((this.id) === "freezingMethodId" && $('#' + this.id + " option:selected").text() === 'Add New')) {

            //change its id first
            $('#freezingMethodId').attr('id', 'freezingMethodId1');

            //save the drop down to a var first
            var myDoc = $("#freezingMethodComboLocation").html();

            //replace it now
            $("#freezingMethodComboLocation").html("\
            <input type='text' id='freezingMethodId' placeholder='Freezing method' class='input-medium'  />&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />\n\
            <button type='button' class='btn btn-medium btn-primary add_new_method_cancel'>Cancel</button>'\n\
            ");

            //create a temp location after body
            $('<div id ="tempComboLocation" style = "visibility: hidden" ></div>').appendTo("body");

            // add the drop down after the body- it has a different id
            $(myDoc).appendTo("#tempComboLocation");
        }
    },
    clearCellineData: function() {
        $.each(Main.cellineValidation, function(i, data) {
            if (data.id !== undefined)
                $('#' + data.id).val(data.defaultVal[0]);
            else if (data.name !== undefined)
                $('[name=' + data.name + ']').val(data.defaultVal[0]);
        });
        $('#footer_links').html('<button type="button" class="btn btn-medium btn-primary celline_save" value="save">Save Celline</button>\n\
         <button type="button" class="btn btn-medium btn-primary celline_cancel">Cancel</button>');
        Cellines.colorInputWithData();
        Main.curCelline = {};
        $('#cellineFrozenId').focus();
    },
    saveCelline: function() {
        if (Cellines.validateInput(Main.cellineValidation) === true) {
            return;
        }
        
        //seems all is well, lets add the stabilate data
        $.each(Main.cellineValidation, function(i, data) {
            //get the value that we want to validate against!
            if (data.id !== undefined)
                Main.curCelline[data.id] = $('#' + data.id).val();
            else if (data.name !== undefined)
                Main.curCelline[data.name] = $('[name=' + data.name + ']').val();
        });

        //all is well, lets send this data to the database
        var params = 'cur_celline=' + escape($.toJSON(Main.curCelline)) + '&action=save';
        Notification.show({create: true, hide: false, updateText: false, text: 'Saving the entered results...', error: false});
        $.ajax({
            type: "POST", url: 'mod_ajax.php?page=cellines&do=browse', dataType: 'json', async: false, data: params,
            error: function() {
                Notification.show({create: false, hide: true, updateText: true, text: 'There was an error while communicating with the server', error: true});
                return false;
            },
            success: function(data) {
                var mssg;
                if (data.error)
                    mssg = data.data + ' Please try again.';
                else
                    mssg = 'Cell line saved succesfully';
                Notification.show({create: false, hide: true, updateText: true, text: mssg, error: data.error});
                if (!data.error) {
                    Cellines.clearCellineData();
                    Cellines.colorInputWithData();
                    Main.curCelline = {};
                }
                $('#cellineFrozenId').focus();
            }
        });
    },
    deleteCelline: function() {
        //seems all is well, lets add the stabilate data
        $.each(Main.cellineValidation, function(i, data) {
            //get the value that we want to validate against!
            if (data.id !== undefined)
                Main.curCelline[data.id] = $('#' + data.id).val();
            else if (data.name !== undefined)
                Main.curCelline[data.name] = $('[name=' + data.name + ']').val();
        });

        //all is well, lets send this data to the database
        var params = 'cur_celline=' + escape($.toJSON(Main.curCelline)) + '&action=delete';
        Notification.show({create: true, hide: false, updateText: false, text: 'Deleting the celline...', error: false});
        $.ajax({
            type: "POST", url: 'mod_ajax.php?page=cellines&do=browse', dataType: 'json', async: false, data: params,
            error: function() {
                Notification.show({create: false, hide: true, updateText: true, text: 'There was an error while communicating with the server', error: true});
                return false;
            },
            success: function(data) {
                var mssg;
                if (data.error)
                    mssg = data.data + ' Please try again.';
                else
                    mssg = 'Cell line deleted succesfully';
                Notification.show({create: false, hide: true, updateText: true, text: mssg, error: data.error});
                if (!data.error) {
                    Cellines.clearCellineData();
                    Cellines.colorInputWithData();
                    Main.curCelline = {};
                }
                $('#cellineFrozenId').focus();
            }
        });
    },
    colorInputWithData: function() {
        $.each(Main.cellineValidation, function(i, data) {
            var object, val;
            if (data.id !== undefined)
                object = $('#' + data.id);
            else if (data.name !== undefined)
                object = $('[name=' + data.name + ']');
            val = object.val();
            if ($.inArray(val, data.defaultVal) !== 0)
                object.css({color: '#300CD7'});
            else if ($.inArray(val, data.defaultVal) === 0)
                object.css({color: '#555555'});
        });
    },
    fillCellineData: function(selected, data) {
        $.each(Main.cellineValidation, function(i, dt) {
            if (dt.id !== undefined)
                $('#' + dt.id).val(data[dt.id]);
            else if (dt.name !== undefined)
                $('[name=' + dt.name + ']').val(data[dt.name]);
        });
        Cellines.colorInputWithData();
        Main.curCelline = {id: data.id};
        $('#footer_links').html("<button class='btn btn-medium btn-primary celline_update' type='button' value='save'>Update Cellines</button>\n\
         <button class='btn btn-medium btn-primary celline_delete' type='button'>Delete</button>\n\
         <button class='btn btn-medium btn-primary celline_cancel' type='button'>Cancel</button>"
                );
    },
    fnFormatResult: function(value, data, currentValue) {
        var pattern = '(' + currentValue.replace(Main.reEscape, '\\$1') + ')';
        return value.replace(new RegExp(pattern, 'gi'), '<strong>$1<\/strong>');
    },
    /**
     * Validate the entered passage details
     *
     * @param     Object   validates   The object with the validation details
     * @returns   Boolean  Returns false if there are no validation errors, else it returns false
     */
    validateInput: function(validates) {
        var value, errors = [], mssg;

        $.each(validates, function(i, data) {
            //get the value that we want to validate against!
            if (data.id !== undefined)
                value = $('#' + data.id).val();
            else if (data.name !== undefined)
                value = $('[name=' + data.name + ']').val();

            //check whether it needs to have something and it has something
            if (data.mandatory && $.inArray(value, data.defaultVal) === 0) {
                errors[errors.length] = (data.emptyMessage !== '') ? data.emptyMessage : data.wrongValMessage;
            }
            //check that what is entered is actually something good
            if ($.inArray(value, data.defaultVal) !== 0 && !RegExp(data.valueRegex, "gi").test(value)) {
                errors[errors.length] = (data.wrongValMessage !== '') ? data.wrongValMessage : data.emptyMessage;
            }
        });
        if (errors.length === 0)
            return false;
        else {
            mssg = errors.join("<br />");
            Notification.show({create: true, hide: true, updateText: false, text: mssg, error: true});
            return true;
        }
    },
   /**
    * Initiates the stabilates grid for listing the entered stabilates and their passages and other metadata
    * @returns {undefined}
    */
   initiateCellinesList: function(){
      var source = {
         datatype: 'json', datafields: [{name: 'id'}, {name: 'cell_id'}, {name: 'animalNo'}, {name: 'parasiteName'}, {name: 'cloneNo'}, {name: 'freezingDateId'}, {name: 'frozenById'}, {name: 'cloneNo'}, {name: 'trayId'}, {name: 'bblocation'}],
         id: 'id', root: 'data', async: true, type: 'POST', data: {action: 'list_cellines'}, url: 'mod_ajax.php?page=cellines&do=list'
      };
      var stabilatesAdapter = new $.jqx.dataAdapter(source);
      $("#list_cell_lines").jqxGrid({
         width: 1000,
         height: 510,
         source: stabilatesAdapter,
         showfilterrow: true,
         filterable: true,
         theme: Main.theme,
         rowdetails: false,
         rowsheight: 20,
         columns: [
            {text: 'Id', datafield: 'id', width: 10, hidden: true},
            {text: 'Cell Id', datafield: 'cell_id', width: 130},
            {text: 'Animal Id', datafield: 'animalNo',filtertype: 'checkedlist', width: 120},
            {text: 'Parasite Name', datafield: 'parasiteName', filtertype: 'checkedlist', width: 110},
            {text: 'Clone', datafield: 'cloneNo', width: 120},
            {text: 'Date frozen', datafield: 'freezingDateId', width: 160},
            {text: 'Frozen By', datafield: 'frozenById',filtertype: 'checkedlist', width: 120},
            {text: 'Location', datafield: 'trayId',filtertype: 'checkedlist', width: 110},
            {text: 'BioBank Location', datafield: 'bblocation', width: 130}
         ]
      });
   }
};