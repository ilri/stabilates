<?php

/**
 * A class that will manage all that relates to the tick materials
 *
 * @package    Tick Materials
 * @author     Kihara Absolomon <a.kihara@cgiar.org>
 * @author     Emmanuel Telewa <e.telewa@cgiar.org>
 * @since      v0.1
 *
 */
class TickMaterials extends Dbase{

    public $Dbase;

    /**
     * The constructor of the class...does nothing but calls the parent constructor
     */
    public function __construct($Dbase) {
        $this->Dbase = $Dbase;
    }

    /**
     * Controls the program execution pertaining to this class
     */
    public function TrafficController() {
        //include the user js functions if need be
        if (OPTIONS_REQUEST_TYPE == 'normal') {
            echo "<script type='text/javascript'>$('#top_links .back_link').html('<a href=\'?page=home\' id=\'backLink\'>Back</a>');</script>";
            echo "<script type='text/javascript' src='js/tick_material.js'></script>";
        }
        if (isset($_GET['query'])) $this->FetchData();
        elseif (OPTIONS_REQUESTED_ACTION == 'save' || OPTIONS_REQUESTED_ACTION == 'update') $this->SaveStabilates();
        elseif (OPTIONS_REQUESTED_SUB_MODULE == 'browse') $this->BrowseTickMaterialHome();
        elseif (OPTIONS_REQUESTED_SUB_MODULE == 'stabilate_metadata') $this->FetchData();
    }

    /**
     * Creates the users module home page
     *
     * @param   string   $addinfo    Any additional information that we might need to pass to the user
     */
    private function BrowseTickMaterialHome($addinfo = '') {
        $error = '';
        $query = "select id, material_name from tick_frozen_material";
        $res = $this->Dbase->ExecuteQuery($query);
        if ($res != 1) {
            $ids = array(); $vals = array();
            $ids[] = '-1'; $vals[] = 'Add New';
            foreach ($res as $t) {
                $ids[] = $t['id'];
                $vals[] = $t['material_name'];
            }
            $settings = array('items' => $vals, 'values' => $ids, 'firstValue' => 'Select One', 'name' => 'material_name', 'id' => 'frozenMaterialId', 'selected' => 1);
            $frozenMaterialCombo = GeneralTasks::PopulateCombo($settings);
        }
        else $error = $this->Dbase->lastError;

        //species
        $query = "select id, material_name from tick_frozen_material";
        $res = $this->Dbase->ExecuteQuery($query);
        if ($res != 1) {
            $ids = array(); $vals = array();
            $ids[] = '-1'; $vals[] = 'Add New';
            foreach ($res as $t) {
                $ids[] = $t['id'];
                $vals[] = $t['material_name'];
            }
            $settings = array('items' => $vals, 'values' => $ids, 'firstValue' => 'Select One', 'name' => 'material_name', 'id' => 'frozenMaterialId', 'selected' => 1);
            $frozenMaterialCombo = GeneralTasks::PopulateCombo($settings);
        }
        else $error = $this->Dbase->lastError;

        echo "<script type='text/javascript' src='". OPTIONS_COMMON_FOLDER_PATH ."jquery/jquery.autocomplete/jquery.autocomplete.js'></script>";
        echo "<link rel='stylesheet' type='text/css' href='". OPTIONS_COMMON_FOLDER_PATH ."jquery/jquery.autocomplete/styles.css' />";

        ?>
        <link rel="stylesheet" href="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/styles/jqx.base.css" type="text/css" />
        <script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxcore.js"></script>
        <script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxcalendar.js"></script>

        <script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxdatetimeinput.js"></script>
        <script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/globalization/jquery.global.js"></script>

        <form class='form-horizontal' id="tick_material">
            <fieldset id='tick_stabilates'>
                <legend>Tick Material Record</legend>
                <div class="left">
                    <div class="control-group">
                        <label class="control-label" for="stabilateNo">Stabilate No</label>
                        <div class="controls">
                            <input type="text" id="stabilateNo" placeholder="Stabilate No" class='input-medium'>&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="parasiteId">Parasite</label>
                        <div class="controls">
                            <input type="text" id="parasite" placeholder="Parasite" class='input-large'>&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="frozenMaterialId">Material Frozen</label>
                        <div class="controls frozen_material">
                            <?php echo $frozenMaterialCombo; ?>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="infectionOriginId">Origin of Infection</label>
                        <div class="controls">
                            <input type="text" id="infectionOriginId" placeholder="Origin Of Infection" class='input-medium' />&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />
                        </div>
                    </div>
                    <div class="control-group input-append">
                        <label class="control-label" for="volumePreparedId">Volume Prepared</label>
                        <div class="controls">
                           <input type="text" id="volumePreparedId" placeholder="Volume" class='input-mini' /><span class="add-on">ml</span>&nbsp; i.e. Before dispensing
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="mediumUsedId">Medium Used</label>
                        <div class="controls">
                            <input type="text" id="mediumUsedId" placeholder="Medium used" class='input-large' />
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="noStoredId">No Stored</label>
                        <div class="controls">
                            <input type="text" id="noStoredId" placeholder="No. Stored(Vials/Straws)" class='input-mini' />&nbsp;Vials/Straws
                        </div>
                    </div>
                </div>
                <div class="right">
                    <div class="control-group">
                        <label class="control-label" for="preparationDateId">Date Prepared</label>
                        <div class="controls">
                           <div id='preparationDateId'>&nbsp;</div>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="stockId">Stock</label>
                        <div class="controls">
                            <input type="text" id="stockId" placeholder="stock" class='input-large' />
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="sourceId">Source</label>
                        <div class="controls">
                            <input type="text" id="sourceId" placeholder="Source" class='input-large' /> (Animal Nos.)
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label inline" for="speciesId">Species</label>
                        <div class="controls">
                            <input type="text" id="speciesId" placeholder="stock" class='input-medium' />
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="cryoProtectantId">Cryo Protectant</label>
                        <div class="controls">
                            <input type="text" id="cryoProtectantId" placeholder="Cryo Protectant" class='input-medium' />
                        </div>
                    </div>
                    <div class="control-group input-append">
                        <label class="control-label" for="unitVolumeId">Unit Volume</label>
                        <div class="controls">
                            <input type="text" id="unitVolumeId" placeholder="Volume" class='input-mini' /><span class="add-on">ml</span>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="colorId">Color</label>
                        <div class="controls">
                            <input type="text" id="colorId" placeholder="Color" class='input-small' />
                        </div>
                    </div>
                </div>
            </fieldset>
            <fieldset id='guts'>
                <legend>For Guts Stabilates Only</legend>
                <div class='left'>
                    <div class="control-group">
                        <label class="control-label" for="ticksGroundId">No. of ticks ground</label>
                        <div class="controls">
                            <input type="text" id="ticksGroundId" placeholder="No." class='input-mini' />
                        </div>
                    </div>
                    <div class="control-group input-append">
                        <label class="control-label" for="infectionRateId">Mean Infection Rate</label>
                        <div class="controls">
                            <input type="text" id="infectionRateId" placeholder="No." class='input-mini' /><span class="add-on">%</span>
                        </div>
                    </div>
                </div>
                <div class='right'>
                    <div class="control-group input-append">
                        <label class="control-label" for="noTicksId">No. Ticks/ML</label>
                        <div class="controls">
                            <input type="text" id="noTicksId" placeholder="No." class='input-mini' /><span class="add-on">Ticks/ml</span>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="infectedTickId">Infected Acini/Tick</label>
                        <div class="controls">
                            <input type="text" id="infectedTickId" placeholder="No." class='input-mini'>
                        </div>
                    </div>
                </div>
            </fieldset>
            <fieldset id="ticks_location">
               <legend>Storage Location and Reference</legend>
                <div class='left'>
                    <div class="control-group">
                        <label class="control-label" for="storageLocationId">Location and Reference</label>
                        <div class="controls">
                            <input type="text" id='storageLocationId' placeHolder='Location and Reference' class="input-xxlarge" />
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="remarksId">Remarks</label>
                        <div class="controls">
                            <textarea id='remarksId' placeHolder='Remarks' style="width:673px; height:100px;"></textarea>
                        </div>
                    </div>
                </div>
            </fieldset>
            <fieldset id="stabilate_testing">
               <legend>Stabilate Testing</legend>
                <div class="control-group left">
                    <label class="control-label" for="experimentNoId">Experiment No.</label>
                    <div class="controls">
                        <input type="text" id="experimentNoId" placeholder="No" class='input-medium' />
                    </div>
                </div>
                <div class="control-group left">
                    <label class="control-label" for="experimentDateId">Date</label>
                    <div class="controls">
                        <div id='experimentDateId'>&nbsp;</div>
                    </div>
                </div>
            </fieldset>
            <div id='footer_links'>
                <button class="btn btn-medium btn-primary tick_material_save" type="button" value="save">Save</button>
                <button class="btn btn-medium btn-primary tick_material_cancel" type="button">Cancel</button>
            </div>
        </form>

        <script type='text/javascript'>
            $(document).ready(function() {
                var date_inputs = ['experimentDateId', 'preparationDateId'];
                $.each(date_inputs, function(i, dateInput) {
                    $('#' + dateInput).jqxDateTimeInput({width: '150px', height: '25px', theme: Main.theme, formatString: "dd-MM-yyyy",
                        minDate: new $.jqx._jqxDateTimeInput.getDateTime(new Date(1960, 0, 1)),
                        maxDate: new $.jqx._jqxDateTimeInput.getDateTime(new Date(2011, 0, 1)),
                        value: new $.jqx._jqxDateTimeInput.getDateTime(new Date(1960, 0, 1))
                    });
                });
                Main.tickStabilatesValidation = <?php echo json_encode(Config::$tickMaterialValidation); ?>;
                $('[type=button]').live('click', TickMaterial.buttonClicked);
                $('select').live('change', TickMaterial.changedSelection);
                $('#stabilateNo').focus();
            });
        </script>
        <?php
        echo "<script type='text/javascript' src='" . OPTIONS_COMMON_FOLDER_PATH . "jquery/jquery.autocomplete/jquery.autocomplete.js'></script>";
//        echo "<link rel='stylesheet' type='text/css' href='" . OPTIONS_COMMON_FOLDER_PATH . "jquery/jquery.autocomplete/styles.css' />";

        $ac = array(
            array('sub_module' => 'stabilate_no', 'id' => 'stabilateNo'),
            array('sub_module' => 'parasite', 'id' => 'parasite'),
            array('sub_module' => 'storage_medium', 'id' => 'mediumUsedId'),
            array('sub_module' => 'cryo_protectant', 'id' => 'cryoProtectantId')
        );

        echo "<script type='text/javascript'>";
        foreach ($ac as $t) {
            $settings = array('inputId' => $t['id'], 'reqModule' => 'tick_materials', 'reqSubModule' => $t['sub_module']);
            if ($t['id'] == 'stabilateNo') $settings['selectFunction'] = 'TickMaterial.fetchStabilateData';
            $this->InitiateAutoComplete($settings);
        }
        echo "</script>";
    }

    /**
     * Spits out the javascript that initiates the autocomplete feature once the DOM has finished loading
     */
    private function InitiateAutoComplete($settings) {
        if ($settings['formatResult'] == '') $settings['formatResult'] = 'Common.formatAutoCompleteSuggestions';
        if ($settings['visibleSuggestions'] == '') $settings['visibleSuggestions'] = true;
        if ($settings['beforeNewQuery'] == '') $settings['beforeNewQuery'] = 'undefined';
        if (!isset($settings['selectFunction'])) $settings['selectFunction'] = 'function(){}';
        ?>
        //bind the search to autocomplete
        $(function(){
        var <?php echo $settings['inputId']; ?>_settings = {
        serviceUrl:'mod_ajax.php', minChars:2, maxHeight:400, width:250,
        zIndex: 9999, deferRequestBy: 300, //miliseconds
        params: { page: '<?php echo $settings['reqModule']; ?>', 'do': '<?php echo $settings['reqSubModule']; ?>' }, //aditional parameters
        noCache: true, //default is false, set to true to disable caching
        onSelect: <?php echo $settings['selectFunction'] ?>,
        formatResult: <?php echo $settings['formatResult']; ?>,
        beforeNewQuery: <?php echo $settings['beforeNewQuery']; ?>,
        visibleSuggestions: <?php echo $settings['visibleSuggestions']; ?>
        };
        $('#<?php echo $settings['inputId']; ?>').autocomplete(<?php echo $settings['inputId']; ?>_settings);
        });
        <?php
    }

    /**
     * Fetches various data from the database.
     *
     * @todo   Refactor the code to recognise the last instered id
     */
    private function FetchData() {
        if (OPTIONS_REQUESTED_SUB_MODULE == 'stabilate_no') $query = 'select id, stabilate_no as val from tick_stabilates where stabilate_no like :query';
        elseif (OPTIONS_REQUESTED_SUB_MODULE == 'parasite') $query = 'select id, parasite_name as val from tick_parasites where parasite_name like :query';
        elseif (OPTIONS_REQUESTED_SUB_MODULE == 'stabilate_metadata'){
            $query = 'select a.*, b.parasite_name, c.id as frozenMaterialId
               from tick_stabilates as a inner join tick_parasites as b on a.parasite_id = b.id inner join tick_frozen_material as c on a.frozen_material_id=c.id
               where a.id = :stabilate_id';
            $res = $this->Dbase->ExecuteQuery($query, array('stabilate_id' => $_POST['stabilate_id']));
            if ($res == 1) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
            die(json_encode(array('error' => false, 'data' => $res[0])));
        }
        elseif (OPTIONS_REQUESTED_SUB_MODULE == 'growth_medium') $query = 'select growth_medium as val from cultures where growth_medium like :query group by growth_medium';
        elseif (OPTIONS_REQUESTED_SUB_MODULE == 'storage_medium') $query = 'select storage_medium as val from cultures where storage_medium like :query group by storage_medium';

        $res = $this->Dbase->ExecuteQuery($query, array('query' => "%{$_GET['query']}%"));
        if ($res == 1) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
        $suggestions = array();
        foreach ($res as $t) {
            $suggestions[] = $t['val'];
            $data[] = $t;
        }
        $data = array('error' => false, 'query' => $_GET['query'], 'suggestions' => $suggestions, 'data' => $data);
        die(json_encode($data));
    }

    /**
     * Save or update a saved stabilate
     */
    private function SaveStabilates() {
        $dt = json_decode($_POST['cur_stabilate'], true);
        $this->Dbase->CreateLogEntry('<pre>'. print_r($dt, true) .'</pre>', 'debug');
        $errors = array();
        $set = array();
        $vals = array();
        $insert_vals = array();
        $insert_cols = array();

        //run the input validation
        foreach (Config::$tickMaterialValidation as $cur) {
            //get the current selector. The selector can either be the element id or element name for this particular input
            if (isset($dt[$cur['id']])) $selector = $cur['id'];
            elseif (isset($dt[$cur['name']])) $selector = $cur['name'];
            //get the current value based on the current selector
            $cur_val = $dt[$selector];

            if ($cur_val != '' && !in_array($cur_val, $cur['defaultVal'])) {
                //we actually have something.... validate it
                if (preg_match("/{$cur['valueRegex']}/i", $cur_val) === 0) {
                    //we have problems
                    $errors[] = $cur['wrongValMessage'];
                } else {
                    if ($selector === 'experimentDateId' || $selector === 'preparationDateId') $cur_val = date("Y-m-d", strtotime($cur_val));
                    //clean bill of health, so build our update/insert query
                    if (!key_exists($selector, $vals)) {     //some values are being picked twice and I cannot understand why! this is going to prevent
                        $set[] = Config::$tick_stabilate_lookup[$selector] . "=:$selector";
                        $vals[$selector] = $cur_val;
                        $insert_vals[] = ":$selector";
                        $insert_cols[] = Config::$tick_stabilate_lookup[$selector];
                    }
                }
            } else {
                //we have nothing
//            echo "{$cur['id']}; {$cur['name']} ==> $cur_val</br>";
            }
        }

        $lockQuery = "lock table tick_stabilates write, tick_frozen_material write, tick_parasites write";
        $this->Dbase->StartTrans();
        if (key_exists('parasite', $vals)){
           //get the parasite id of this stabilate
           $query = 'select id from tick_parasites where parasite_name = :parasite';
           $parasiteId = $this->Dbase->ExecuteQuery($query, array('parasite' => $vals['parasite']));
           if($parasiteId == -1) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
           if(count($parasiteId) == 0){
              //this parasite does not exist..... lets add it to the system
              $query = 'insert into tick_parasites(parasite_name) values(:name)';
              $parasiteId = $this->Dbase->ExecuteQuery($query, array('name' => $vals['parasite']));
              if($parasiteId == 0) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
              $query = 'select id from tick_parasites where parasite_name = :parasite';
              $parasiteId = $this->Dbase->ExecuteQuery($query, array('parasite' => $vals['parasite']));
              if($parasiteId == -1) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
           }
           $vals['parasite'] = $parasiteId[0]['id'];
        }
        if (key_exists('speciesId', $vals)){
           //get the parasite id of this stabilate
           $query = 'select id from tick_species where species_name = :species';
           $speciesId = $this->Dbase->ExecuteQuery($query, array('species' => $vals['speciesId']));
           if($speciesId == -1) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
           if(count($speciesId) == 0){
              //add this new species and get the returned id
              $query = 'insert into tick_species(species_name) values(:species)';
              $speciesId = $this->Dbase->ExecuteQuery($query, array('species' => $vals['speciesId']));
              if($speciesId == -1) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
              $query = 'select id from tick_species where species_name = :species';
              $speciesId = $this->Dbase->ExecuteQuery($query, array('species' => $vals['speciesId']));
              if($speciesId == -1) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
              $vals['speciesId'] = $speciesId[0]['id'];
           }
           else $vals['speciesId'] = $speciesId[0]['id'];
        }
        if (count($errors) != 0) die(json_encode(array('error' => true, 'data' => implode("<br />", $errors))));

        if (isset($dt['id'])) {
            //we wanna update a stabilate.... lets check how the saved data compares with our data
            $query = 'select * from tick_stabilates where id=:id';
            $savedData = $this->Dbase->ExecuteQuery($query, array('id' => $dt['id']));
            if($savedData == 1) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
            $cols2ignore = array('id', 'parasite', 'material_frozen');
            $returned = $this->CompareSavedWithData2Update($savedData[0], $vals, $insert_cols, $cols2ignore);
            $vals = $returned['vals'];
            $vals['id'] = $dt['id'];
            $set = array_merge($set, $returned['set_addons']);
            $query = 'update tick_stabilates set ' . implode(', ', $set) . ' where id=:id';
//            die($query);
        } else {
            //we wanna save a new stabilates
            $insert_cols[] = 'added_by';
            $insert_vals[] = ':added_by';
            $vals['added_by'] = $_SESSION['user_id'];
            $query = 'insert into tick_stabilates(' . implode(', ', $insert_cols) . ') values(' . implode(', ', $insert_vals) . ')';
        }
        $res = $this->Dbase->UpdateRecords($query, $vals, $lockQuery);
        if ($res == 1) {
            $this->Dbase->RollBackTrans();
            die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
        }
        //commit the transaction and unlock the tables
        if (!$this->Dbase->CommitTrans()) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
        $res = $this->Dbase->ExecuteQuery("Unlock tables");
        if ($res == 1) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));

        //we are all good
        die(json_encode(array('error' => false, 'data' => 'Data saved well')));
    }

    /**
     * Compares the data that we are saving vis a vis the saved data. If there is a column with some data and it is not among the columns being updated.... know its being deleted... so delete it
     *
     * @param  array    $saved         An array with the saved data
     * @param  array    $vals2update   An array with a list of values that are being updated
     * @param  array    $cols2update   An array with the columns that we are updating
     * @return array    Returns an array with the updated list of columns and values that should be updated
     */
    private function CompareSavedWithData2Update($saved, $vals2update, $cols2update, $cols2ignore){
//       echo '<pre>'. print_r($saved, true) .'</pre>';
//       echo '<pre>'. print_r($vals2update, true) .'</pre>';
//       echo '<pre>'. print_r($cols2update, true) .'</pre>';
       //remove the table prefix from the list of columns to update
       foreach($cols2update as $key => $col){
          $colParts = array();
          preg_match('/^tick_stabilates\.(.+)$/', $col, $colParts);
          $cols2update[$key] = $colParts[1];
       }
//       echo '<pre>'. print_r($cols2update, true) .'</pre>';

       $setAddOns = array();
       foreach($saved as $colName => $val){
          if(isset($val) && $val != NULL && $val != '' && !in_array($colName, $cols2ignore)){    //we have something that is already set....
             if(!in_array($colName, $cols2update)){
                //we dont have it among the list of our columns to update..... seems we need to delete it
                $cols2update[] = $colName;
                $vals2update["up_{$colName}"] = '';
                $setAddOns[] = "$colName = :up_{$colName}";
             }
          }
       }

       //rebuild the array that will create the set statement
       return array('vals' => $vals2update, 'cols' => $cols2update, 'set_addons' => $setAddOns);
    }
}
?>