<?php

/**
 * A class that will manage all that relates to the Trays
 *
 * @package    Trays
 * @author     Kihara Absolomon <a.kihara@cgiar.org>
 * @author     Emmanuel Telewa <e.telewa@cgiar.org>
 * @since      v0.1
 *
 */
class TraysStorage extends Dbase {

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
            echo "<script type='text/javascript' src='js/trays_storage.js'></script>";
        }
        if (isset($_GET['query']))
            $this->FetchData();
        elseif (OPTIONS_REQUESTED_ACTION == 'save')
            $this->SaveTray();
        elseif (OPTIONS_REQUESTED_ACTION == 'delete')
            $this->DeleteTray();
        elseif (OPTIONS_REQUESTED_SUB_MODULE == 'browse')
            $this->BrowseTraysHome();
    }

    /**
     * Creates the users module home page
     *
     * @param   string   $addinfo    Any additional information that we might need to pass to the user
     */
    private function BrowseTraysHome($addinfo = '') {
        //Trays
        $query = "select * from cell_lines order by trayId";
        $res = $this->Dbase->ExecuteQuery($query);
        if ($res != 1) {
            $ids = array();
            $vals = array();
            foreach ($res as $t) {
                $ids[] = $t['id'];
                $vals[] = $t['trayId'];
            }
            $settings = array('items' => $vals, 'values' => $ids, 'firstValue' => 'Select One', 'name' => 'trayId', 'id' => 'trayId');
            $trayId = GeneralTasks::PopulateCombo($settings);
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
            <fieldset id='tray_details'>
                <legend>Tray Storage Details</legend>
                <div class="left">
                        <div class="control-group">
                            <label class="control-label" for="trayId">Tray Number</label>
                            <div class="controls">
                                <input type="text" id="trayId" placeholder="Tray number" class='input-small' />&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="tankId">Tank No</label>
                            <div class="controls">
                                <input type="text" id="tankId" placeholder="Tank No" class='input-small' />&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="sectorId">Sector</label>
                            <div class="controls">
                                <input type="text" id="sectorId" placeholder="Sector" class='input-small' />&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="towerId">Tower</label>
                            <div class="controls">
                                <input type="text" id="towerId" placeholder="Tower" class='input-small' />&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="positionTowerId">Position In Tower</label>
                            <div class="controls">
                                <input type="text" id="positionTowerId" placeholder="Tower" class='input-small' />&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />
                            </div>
                        </div>
                </div>
            </fieldset>
            <div id='footer_links'>
                <button class="btn btn-medium btn-primary tray_save" type="button" value="save">Save Tray</button>
                <button class="btn btn-medium btn-primary tray_cancel" type="button">Cancel</button>
            </div>
        </form>

        <script type='text/javascript'>
            $(document).ready(function(){

                $('[type=button]').live('click', Trays.buttonClicked);
                $('[type=select]').live('change', Trays.changedSelection);
                
                $('#sectorId').focus().blur(function() {
                    this.value = this.value.toUpperCase();
                });

                Main.traysValidation = <?php echo json_encode(Config::$traysValidation); ?>;
            });
        </script>
        <?php
        echo "<script type='text/javascript' src='" . OPTIONS_COMMON_FOLDER_PATH . "jquery/jquery.autocomplete/jquery.autocomplete.js'></script>";
        echo "<link rel='stylesheet' type='text/css' href='" . OPTIONS_COMMON_FOLDER_PATH . "jquery/jquery.autocomplete/styles.css' />";

        $ac = array(
            array('sub_module' => 'tray_id', 'id' => 'trayId')
        );

        echo "<script type='text/javascript'>";
        foreach ($ac as $t) {
            $settings = array('inputId' => $t['id'], 'reqModule' => 'trays_storage', 'reqSubModule' => $t['sub_module']);
            if ($t['id'] == 'trayId')
                $settings['selectFunction'] = 'Trays.fillTrayData';
            $this->InitiateAutoComplete($settings);
        }
        echo "</script>";
    }

    /**
     * Spits out the javascript that initiates the autocomplete feature once the DOM has finished loading
     */
    private function InitiateAutoComplete($settings) {
        if ($settings['formatResult'] == '')
            $settings['formatResult'] = 'Trays.fnFormatResult';
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
        if (OPTIONS_REQUESTED_SUB_MODULE == 'tray_id') {
            $toFetch = array();
            foreach (Config::$form_db_map_trays as $name => $column) {
                if (preg_match('/^tray_details\./', $column))
                    $toFetch[] = "$column as $name";
            }
            $query = 'select id, ' . implode(', ', $toFetch) . ',trayId  as val from tray_details where trayId like :query';
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

    private function SaveTray(){
        $dt = json_decode($_POST['cur_tray'], true);
//      $this->Dbase->CreateLogEntry('<pre>'. print_r($dt, true) .'</pre>', 'debug');
        $errors = array();
        $set = array();
        $vals = array();
        $insert_vals = array();
        $insert_cols = array();

        //run the input validation
        foreach (Config::$traysValidation as $cur) {
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
                    //clean bill of health, so build our update/insert query
                    if (!key_exists($selector, $vals)) {     //some values are being picked twice and I cannot understand why! this is going to prevent
                        $set[] = Config::$form_db_map_trays[$selector] . "=:$selector";
                        $vals[$selector] = $cur_val;
                        $insert_vals[] = ":$selector";
                        $insert_cols[] = Config::$form_db_map_trays[$selector];
                    }
                }
            } else {
                //we have nothing
//            echo "{$cur['id']}; {$cur['name']} ==> $cur_val</br>";
            }
        }

        if (count($errors) != 0)
            die(json_encode(array('error' => true, 'data' => implode("<br />", $errors))));

        $lockQuery = "lock table tray_details write";
        $this->Dbase->StartTrans();
        if (isset($dt['id'])) {
            //we wanna update a stabilate
            $vals['id'] = $dt['id'];
            $query = 'update tray_details set ' . implode(', ', $set) . ' where id=:id';
        } else {
            //we wanna save a new stabilates
            $insert_cols[] = 'added_by';
            $insert_vals[] = ':added_by';
            $vals['added_by'] = $_SESSION['user_id'];
            $query = 'insert into tray_details(' . implode(', ', $insert_cols) . ') values(' . implode(', ', $insert_vals) . ')';
        }
        $res = $this->Dbase->UpdateRecords($query, $vals, $lockQuery);
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
}
?>