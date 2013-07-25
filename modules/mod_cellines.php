<?php

/**
 * A class that will manage all that relates to the Cellines
 *
 * @package    Cellines
 * @author     Kihara Absolomon <a.kihara@cgiar.org>
 * @author     Emmanuel Telewa <e.telewa@cgiar.org>
 * @since      v0.1
 *
 */
class Cellines extends Dbase {

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
            echo "<script type='text/javascript' src='js/cellines.js'></script>";
        }
        
        if (isset($_GET['query']))
            $this->FetchData();
        elseif (OPTIONS_REQUESTED_ACTION == 'save')
            $this->SaveCelline();
        elseif (OPTIONS_REQUESTED_ACTION == 'delete')
            $this->DeleteCelline();
        else if (OPTIONS_REQUESTED_ACTION== 'list_cellines')
            $this->FetchData();
        elseif (OPTIONS_REQUESTED_SUB_MODULE == 'browse')
            $this->BrowseCellinesHome();
        else if (OPTIONS_REQUESTED_SUB_MODULE == 'list')
            $this->ListCellines();
        
    }

    /**
     * Creates the users module home page
     *
     * @param   string   $addinfo    Any additional information that we might need to pass to the user
     */
    private function BrowseCellinesHome($addinfo = '') {
        //users
        $error = '';
        $query = "select * from misc_db.users where sname is not null and onames is not null and project = 12 order by sname";
        $res = $this->Dbase->ExecuteQuery($query);
        if ($res != 1) {
            $vals = array();
            $ids = array();
            foreach ($res as $t) {
                $ids[] = $t['id'];
                $vals[] = $t['onames'] . ' ' . $t['sname'];
            }
            $settings = array('items' => $vals, 'values' => $ids, 'firstValue' => 'Select One', 'name' => 'frozen_by', 'id' => 'frozenById');
            $frozenByCombo = GeneralTasks::PopulateCombo($settings);
        }
        else
            $error = $this->Dbase->lastError;

        //freezing methods
        $query = "select * from freezer order by freezingMethodId";
        $res = $this->Dbase->ExecuteQuery($query);
        if ($res != 1) {
            $ids = array();
            $vals = array();
            $ids[] = 'Add New';
            $vals[] = "Add New";
            //<input type="text" id="freezingMethodId" placeholder="Freezing method" class='input-medium'  />&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />

            foreach ($res as $t) {
                $ids[] = $t['id'];
                $vals[] = $t['freezingMethodId'];
            }
            $settings = array('items' => $vals, 'values' => $ids, 'firstValue' => 'Select One', 'name' => 'freezingMethodId', 'id' => 'freezingMethodId');
            $freezingMethodCombo = GeneralTasks::PopulateCombo($settings);
        }
        else
            $error = $this->Dbase->lastError;
        ?>


        <link rel="stylesheet" href="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/styles/jqx.base.css" type="text/css" />
        <script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxcore.js"></script>
        <script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxcalendar.js"></script>

        <script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxdatetimeinput.js"></script>
        <script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/globalization/jquery.global.js"></script>

        <form class='form-horizontal'>
            <fieldset id='cellines'>
                <legend>Cell Line Details</legend>
                <div class="left">
                    <div class="control-group">
                        <label class="control-label" for="freezingDateId">Freezing date</label>
                        <div class="controls">
                            <div id='freezingDateId'>&nbsp;</div>
                        </div>
                    </div>

                    <!--div class="control-group">
                        <label class="control-label" for="cellineFrozenId">Cell Line Id</label>
                        <div class="controls">
                            <input type="text" id="cellineFrozenId" placeholder="Cell Line Id" class='input-medium' />&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />
                        </div>
                    </div-->

                    <div class="control-group">
                        <label class="control-label" for="animalNo">Animal No</label>
                        <div class="controls">
                            <input type="text" id="animalNo" placeholder="Animal No" class='input-small' />&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="parasiteName">Parasite Name</label>
                        <div class="controls">
                            <input type="text" id="parasiteName" placeholder="Parasite Name" class='input-small' />&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="cloneNo">Clone No</label>
                        <div class="controls">
                            <input type="text" id="cloneNo" placeholder="Clone No" class='input-small' />
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="freezingMethodId">Freezing method</label>
                        <div class="controls" id ="freezingMethodComboLocation">
                            <?php echo $freezingMethodCombo; ?>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="noVailsFrozenId">No of Vials Frozen</label>
                        <div class="controls">
                            <input type="text" id="noVailsFrozenId" placeholder="No" class='input-mini' />&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="frozenById">Frozen By</label>
                        <div class="controls">
                            <?php echo $frozenByCombo; ?>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="trayId">Tray Id</label>
                        <div class="controls">
                            <input type="text" id="trayId" placeholder="Tray Id" class='input-mini'  />&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="positionTrayId">Position in Tray</label>
                        <div class="controls">
                            <input type="text" id="positionTrayId" placeholder="Position in Tray" class='input-medium'  />&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for='cellineComments'>Cell Line Comments</label>
                        <div class='controls'>
                            <textarea rows="3" id='cellineComments' placeholder="Cell Line Comments"></textarea>
                        </div>
                    </div>
                </div>
            </fieldset>
            <div id='footer_links'>
                <button class="btn btn-medium btn-primary celline_save" type="button" value="save">Save Cellines</button>
                <button class="btn btn-medium btn-primary celline_cancel" type="button">Cancel</button>
            </div>
        </form>

        <script type='text/javascript'>
            $(document).ready(function() {
                var date_inputs = ['freezingDateId'];
                $.each(date_inputs, function(i, dateInput) {
                    $('#' + dateInput).jqxDateTimeInput({width: '150px', height: '25px', theme: Main.theme, formatString: "dd-MM-yyyy",
                        minDate: new $.jqx._jqxDateTimeInput.getDateTime(new Date(1960, 0, 1)),
                        maxDate: new $.jqx._jqxDateTimeInput.getDateTime(new Date(2011, 0, 1)),
                        value: new $.jqx._jqxDateTimeInput.getDateTime(new Date())
                    });
                });
                $('[type=button]').live('click', Cellines.buttonClicked);
                $('[type=select]').live('change', Cellines.changedSelection);
                $('#freezingMethodId').live('change', Cellines.changedSelection);

                Main.cellineValidation = <?php echo json_encode(Config::$cellinesValidation); ?>;
            });
        </script>
        <?php
        echo "<script type='text/javascript' src='" . OPTIONS_COMMON_FOLDER_PATH . "jquery/jquery.autocomplete/jquery.autocomplete.js'></script>";
        echo "<link rel='stylesheet' type='text/css' href='" . OPTIONS_COMMON_FOLDER_PATH . "jquery/jquery.autocomplete/styles.css' />";

        $ac = array(
            array('sub_module' => 'frozen_cell_line', 'id' => 'animalNo'),
            array('sub_module' => 'freezing_method_id', 'id' => 'freezingMethodId')
        );

        echo "<script type='text/javascript'>";
        foreach ($ac as $t) {
            $settings = array('inputId' => $t['id'], 'reqModule' => 'cellines', 'reqSubModule' => $t['sub_module']);
            if ($t['id'] == 'animalNo')
                $settings['selectFunction'] = 'Cellines.fillCellineData';
            $this->InitiateAutoComplete($settings);
        }
        echo "</script>";
    }

    /**
     * Spits out the javascript that initiates the autocomplete feature once the DOM has finished loading
     */
    private function InitiateAutoComplete($settings) {
        if ($settings['formatResult'] == '')
            $settings['formatResult'] = 'Cellines.fnFormatResult';
        if ($settings['visibleSuggestions'] == '')
            $settings['visibleSuggestions'] = true;
        if ($settings['beforeNewQuery'] == '')
            $settings['beforeNewQuery'] = 'undefined';
        if (!isset($settings['selectFunction']))
            $settings['selectFunction'] = 'function(){}';
        ?>
        //bind the search to autocomplete
        $(function(){
        var <?php echo $settings['inputId']; ?>_settings = {
        serviceUrl:'mod_ajax.php', minChars:2, maxHeight:400, width:350,
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

    private function FetchData() {
        if (OPTIONS_REQUESTED_SUB_MODULE == 'frozen_cell_line') {
            $toFetch = array();
            foreach (Config::$form_db_map_cell_lines as $name => $column) {
                if (preg_match('/^cell_lines\./', $column))
                    $toFetch[] = "$column as $name";
            }
            $query = 'select id, ' . implode(', ', $toFetch) . ',animalNo  as val, date_format(freezingDateId, "%d-%m-%Y") as freezingDateId from cell_lines where animalNo like :query';
        }
        else if (OPTIONS_REQUESTED_ACTION == 'list_cellines') {      //Fetch the list of all the cell lines that we have entered          
            $query = 'select stabilates.cell_lines.id, concat(animalNo,parasiteName) as cell_id, animalNo,parasiteName,cloneNo,freezingMethodId,noVailsFrozenId, misc_db.users.sname as frozenById,concat (cell_lines.trayId,":",cell_lines.positionTrayId ) as trayId,freezingDateId,concat(tray_details.tankId,">",tray_details.sectorId,">",tray_details.towerId,">",tray_details.positionTowerId) as bblocation from stabilates.tray_details, stabilates.cell_lines INNER JOIN misc_db.users on ( cell_lines.frozenById = misc_db.users.id) order by stabilates.cell_lines.id';
            $res = $this->Dbase->ExecuteQuery($query);
            if ($res == 1)
                die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
            header("Content-type: application/json");
            die('{"data":' . json_encode($res) . '}');
        }
        elseif (OPTIONS_REQUESTED_SUB_MODULE == 'freezing_method_id') {
            $query = 'select id, freezingMethodId as val from freezer where freezingMethodId like :query';
        }
        

        $res = $this->Dbase->ExecuteQuery($query, array('query' => "%{$_GET['query']}%"));
        if ($res == 1)
            die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
        $suggestions = array();
        foreach ($res as $t) {
            $suggestions[] = $t['val'];
            $data[] = $t;
        }
        $data = array('error' => false, 'query' => $_GET['query'], 'suggestions' => $suggestions, 'data' => $data);
        die(json_encode($data));
    }

    private function SaveCelline() {
        $dt = json_decode($_POST['cur_celline'], true);

        //$this->Dbase->CreateLogEntry('<pre>'. print_r($dt, true) .'</pre>', 'debug');

        $errors = array();
        $set = array();
        $vals = array();
        $insert_vals = array();
        $insert_cols = array();
        $newMethod = "";

        //run the input validation
        foreach (Config::$cellinesValidation as $cur) {
            //get the current selector. The selector can either be the element id or element name for this particular input
            if (isset($dt[$cur['id']]))
                $selector = $cur['id'];
            elseif (isset($dt[$cur['name']]))
                $selector = $cur['name'];
            //get the current value based on the current selector
            $cur_val = $dt[$selector];

            if ($cur_val != '' && !in_array($cur_val, $cur['defaultVal'])) {
                //we actually have something.... validate it
                if (preg_match("/{$cur['valueRegex']}/i", $cur_val) === 0) {
                    //we have problems
                    $errors[] = $cur['wrongValMessage'];
                } else {
                    if ($dt[$selector] === 'freezingDateId')
                        $cur_val = date("Y-m-d", strtotime($cur_val));

                    //clean bill of health, so build our update/insert query
                    if (!key_exists($selector, $vals)) {     //some values are being picked twice and I cannot understand why! this is going to prevent
                        $set[] = Config::$form_db_map_cell_lines[$selector] . "=:$selector";
                        $vals[$selector] = $cur_val;
                        $insert_vals[] = ":$selector";
                        $insert_cols[] = Config::$form_db_map_cell_lines[$selector];
                    }
                }
            } else {
                //we have nothing
//            echo "{$cur['id']}; {$cur['name']} ==> $cur_val</br>";
            }
        }
        if (key_exists('freezingDateId', $vals)) {
            $vals['freezingDateId'] = date("Y-m-d", strtotime($vals['freezingDateId']));
        }

        if (key_exists('freezingMethodId', $vals)) {
            $newMethod = '"' . $vals['freezingMethodId'] . '"';
        }

        if (count($errors) != 0) {
            die(json_encode(array('error' => true, 'data' => implode("<br />", $errors))));
        }

        $lockQuery = "lock table cell_lines write, freezer write";

        $this->Dbase->StartTrans();
        //start freezeer business
        //check if the method used here already exists in the freezer table. If not, add it.
        $query = "select * from freezer where freezingMethodId = $newMethod or id = $newMethod"; //when do you use id instead of name?

        $res = $this->Dbase->ExecuteQuery($query);
        $exists = 0;
        if ($res != 1) {
            if (count($res) > 0) {
                $exists = 1;
            } else {
                //$this->CreateLogEntry("count : " . count($res));
            }
        }
        else
            $error = $this->Dbase->lastError;

        if ($exists == 0) {//the is is not defined so its definitly not in the db
            $vals_method = array();
            $insert_vals_method = array();
            $insert_cols_method = array();

            $insert_cols_method[] = 'added_by';
            $insert_vals_method[] = ':added_by';
            $vals_method['added_by'] = $_SESSION['user_id'];
            $insert_cols_method[] = 'time_added';
            $insert_vals_method[] = ':time_added';
            $vals_method['time_added'] = date('Y-m-d H:i:s');
            $query = 'insert into freezer (freezingMethodId, ' . implode(', ', $insert_cols_method) . ') values( ' . $newMethod . ',' . implode(', ', $insert_vals_method) . ')';

            $res_method = $this->Dbase->UpdateRecords($query, $vals_method, $lockQuery);

            //now change the current id to the inserted
            $query = "select * from freezer where freezingMethodId = $newMethod";
            $res = $this->Dbase->ExecuteQuery($query);
            $exists = 0;
            if ($res != 1) {
                if (count($res) > 0) {
                    $exists = 1;
                    //$this->CreateLogEntry("count : " . count($res) . "query : " . $query);
                }
            } else {
                $error = $this->Dbase->lastError;
                //$this->CreateLogEntry("count : " . count($res) . "query : " . $query);
            }

            if ($exists == 1) {

                foreach ($res as $t) {
                    $ids_method = $t['id'];
                    $vals_method = $t['freezingMethodId'];
                }
                $newMethod = $ids_method; //insert the id instead                
                $vals['freezingMethodId'] = $newMethod;
            }
        }
        //end freezer business


        if (isset($dt['id'])) {
            //we wanna update a cell_line
            $vals['id'] = $dt['id'];
            $query = 'update cell_lines set ' . implode(', ', $set) . ' where id=:id';
        } else {
            //we wanna save a new cell line

            $insert_cols[] = 'added_by';
            $insert_vals[] = ':added_by';
            $vals['added_by'] = $_SESSION['user_id'];
            $insert_cols[] = 'time_added';
            $insert_vals[] = ':time_added';
            $vals['time_added'] = date('Y-m-d H:i:s');
            $query = 'insert into cell_lines(' . implode(', ', $insert_cols) . ') values(' . implode(', ', $insert_vals) . ')';
        }
        $res = $this->Dbase->UpdateRecords($query, $vals, $lockQuery);

        if (($res == 1) or ($res_method == 1)) {
            $this->Dbase->RollBackTrans();
            die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
        }
        //commit the transaction and unlock the tables
        if (!$this->Dbase->CommitTrans())
            die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
        $res = $this->Dbase->ExecuteQuery("Unlock tables");
        if ($res == 1)
            die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));

        //we are all good
        die(json_encode(array('error' => false, 'data' => 'Data saved well')));
    }

    private function DeleteCelline() {
        $dt = json_decode($_POST['cur_celline'], true);

//      $this->Dbase->CreateLogEntry('<pre>'. print_r($dt, true) .'</pre>', 'debug');
        $errors = array();
        $set = array();
        $vals = array();
        $insert_vals = array();
        $insert_cols = array();

        //run the input validation
        foreach (Config::$cellinesValidation as $cur) {
            //get the current selector. The selector can either be the element id or element name for this particular input
            if (isset($dt[$cur['id']]))
                $selector = $cur['id'];
            elseif (isset($dt[$cur['name']]))
                $selector = $cur['name'];
            //get the current value based on the current selector
            $cur_val = $dt[$selector];

            if ($cur_val != '' && !in_array($cur_val, $cur['defaultVal'])) {
                //we actually have something.... validate it
                if (preg_match("/{$cur['valueRegex']}/i", $cur_val) === 0) {
                    //we have problems
                    $errors[] = $cur['wrongValMessage'];
                } else {
                    if ($dt[$selector] === 'freezingDateId')
                        $cur_val = date("Y-m-d", strtotime($cur_val));
                    //clean bill of health, so build our update/insert query
                    if (!key_exists($selector, $vals)) {     //some values are being picked twice and I cannot understand why! this is going to prevent
                        $set[] = Config::$form_db_map_cell_lines[$selector] . "=:$selector";
                        $vals[$selector] = $cur_val;
                        $insert_vals[] = ":$selector";
                        $insert_cols[] = Config::$form_db_map_cell_lines[$selector];
                    }
                }
            } else {
                //we have nothing
//            echo "{$cur['id']}; {$cur['name']} ==> $cur_val</br>";
            }
        }
        if (key_exists('freezingDateId', $vals)) {
            $vals['freezingDateId'] = date("Y-m-d", strtotime($vals['freezingDateId']));
//         echo date("Y-m-d", strtotime($vals['date_stored']));
        }

        if (count($errors) != 0)
            die(json_encode(array('error' => true, 'data' => implode("<br />", $errors))));

        $lockQuery = "lock table cell_lines write";
        $this->Dbase->StartTrans();

        if (isset($dt['id']) and (OPTIONS_REQUESTED_ACTION != 'delete')) {
            //we wanna update a stabilate
            $vals['id'] = $dt['id'];
            $query = 'update cell_lines set ' . implode(', ', $set) . ' where id=:id';
            $res = $this->Dbase->UpdateRecords($query, $vals, $lockQuery);
        } elseif (isset($dt['id']) and (OPTIONS_REQUESTED_ACTION == 'delete')) {
            //we wanna to delete the current celline
            $vals['id'] = $dt['id'];
            //create a 
            $query = 'delete from cell_lines where  id = ' . $vals[id];
            $res = $this->Dbase->DeleteRecords($query, $lockQuery);
        } else {

            //we wanna save a new cell line
            $insert_cols[] = 'added_by';
            $insert_vals[] = ':added_by';
            $vals['added_by'] = $_SESSION['user_id'];
            $insert_cols[] = 'time_added';
            $insert_vals[] = ':time_added';
            $vals['time_added'] = date('Y-m-d H:i:s');
            $query = 'insert into cell_lines(' . implode(', ', $insert_cols) . ') values(' . implode(', ', $insert_vals) . ')';

            $res = $this->Dbase->UpdateRecords($query, $vals, $lockQuery);
        }
        if ($res == 1) {
            $this->Dbase->RollBackTrans();
            die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
        }
        //commit the transaction and unlock the tables
        if (!$this->Dbase->CommitTrans())
            die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
        $res = $this->Dbase->ExecuteQuery("Unlock tables");
        if ($res == 1)
            die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));

        //we are all good
        die(json_encode(array('error' => false, 'data' => 'Data saved well')));
    }

    /**
     * Show the cell lines that have been entered
     */
    private function ListCellines() {
        ?>
        <link rel="stylesheet" href="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/styles/jqx.base.css" type="text/css" />
        <script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxcore.js"></script>
        <script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxcalendar.js"></script>
        <script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxtabs.js"></script>

        <script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxgrid.js"></script>
        <script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxgrid.filter.js"></script>
        <script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxdata.js"></script>
        <script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxscrollbar.js"></script>
        <script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxgrid.selection.js"></script>
        <script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxbuttons.js"></script>
        <script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxmenu.js"></script>
        <script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxdropdownlist.js"></script>
        <script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxlistbox.js"></script>
        <script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxcheckbox.js"></script>
        <div id='list_cell_lines'></div>
        <script type='text/javascript'>
            $(document).ready(function() {
                Cellines.initiateCellinesList();
            });
        </script>
        <?php
    }

}
?>