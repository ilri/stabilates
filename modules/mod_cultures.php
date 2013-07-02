<?php

/**
 * A class that will manage all that relates to the cultures
 *
 * @package    Users
 * @author     Kihara Absolomon <a.kihara@cgiar.org>
 * @since      v0.1
 *
 */
class Cultures{

   public $Dbase;

   /**
    * The constructor of the class...does nothing but calls the parent constructor
    */
   public function __construct($Dbase){
      $this->Dbase = $Dbase;
   }

   /**
    * Controls the program execution pertaining to this class
    */
   public function TrafficController(){
      //include the user js functions if need be
      if(OPTIONS_REQUEST_TYPE == 'normal'){
         echo "<script type='text/javascript'>$('#top_links .back_link').html('<a href=\'?page=home\' id=\'backLink\'>Back</a>');</script>";
         echo "<script type='text/javascript' src='js/cultures.js'></script>";
      }
      if(isset($_GET['query'])) $this->FetchData();
      elseif(OPTIONS_REQUESTED_ACTION== 'save') $this->SaveCulture();
      elseif(OPTIONS_REQUESTED_SUB_MODULE == 'browse') $this->BrowseCulturesHome();
   }

   /**
    * Creates the users module home page
    *
    * @param   string   $addinfo    Any additional information that we might need to pass to the user
    */
   private function BrowseCulturesHome($addinfo = ''){
      $error = '';
      $query = "select id, cell_name from cell_types";
      $res = $this->Dbase->ExecuteQuery($query);
      if($res != 1){
         $ids=array(); $vals=array();
         foreach($res as $t){
            $ids[]=$t['id']; $vals[]=$t['cell_name'];
         }
         $settings = array('items' => $vals, 'values' => $ids, 'firstValue' => 'Select One', 'name' => 'cell_name', 'id' => 'cellId', 'selected' => 1);
         $cellTypesCombo = GeneralTasks::PopulateCombo($settings);
      }
      else $error = $this->Dbase->lastError;
?>
<link rel="stylesheet" href="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/styles/jqx.base.css" type="text/css" />
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxcore.js"></script>
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxcalendar.js"></script>

<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/jqxdatetimeinput.js"></script>
<script type="text/javascript" src="<?php echo OPTIONS_COMMON_FOLDER_PATH; ?>jquery/jqwidgets/globalization/jquery.global.js"></script>

<style>
   .form-horizontal .control-label { width: 140px; }
   .form-horizontal .controls { margin-left: 160px; }
</style>

<form class='form-horizontal'>
<fieldset class='cultures'>
   <legend>Cell Cultures - Frozen Stocks</legend>
   <div class=''>
      <div class="control-group">
         <label class="control-label" for="stabilateNo">Storage #</label>
         <div class="controls">
            <input type="text" id="storeNo" placeholder="Store" class='input-medium'>&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="cellTypeId">Cell Type</label>
         <div class="controls">
            <?php echo $cellTypesCombo; ?>
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="cellTypeComments">Cell Type Comments</label>
         <div class="controls">
            <input type="text" id="cell_type_comments" placeholder="Cell Type Comments" class='input-large'>
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="animalId">Animal ID</label>
         <div class="controls">
            <input type="text" id="animalId" placeholder="Animal ID" class='input-small'>
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="historyId">History</label>
         <div class="controls">
            <textarea rows="3" id='historyId' placeHolder='History'></textarea>
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="date_stored">Date Stored</label>
         <div class="controls">
            <div id='date_stored'></div>
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="noOfVials">No. of Vials</label>
         <div class="controls">
            <input type="text" id="noOfVials" placeholder="Vials" class='input-mini'>
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="growthMedium">Growth Medium</label>
         <div class="controls">
            <input type="text" id="growthMedium" placeholder="Growth Medium" class='input-xlarge'>
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="storageMedium">Storage Medium</label>
         <div class="controls">
            <input type="text" id="storageMedium" placeholder="Storage Medium" class='input-xlarge'>
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for='relatedStabilates'>Related Cultures</label>
         <div class='controls'>
            <textarea rows="3" id='relatedCultures' placeholder="Related Cultures"></textarea>
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for='cultureComments'>Culture Comments</label>
         <div class='controls'>
            <textarea rows="3" id='cultureComments' placeholder="Culture Comments"></textarea>
         </div>
      </div>
   </div>
</fieldset>

<div id='footer_links'>
   <button class="btn btn-medium btn-primary culture_save" type="button" value="save">Save Cultures</button>
   <button class="btn btn-medium btn-primary culture_cancel" type="button">Cancel</button>
</div>
</form>

<script type='text/javascript'>
$(document).ready(function () {
   $('#date_stored').jqxDateTimeInput({ width: '150px', height: '25px', theme: Main.theme, formatString: "dd-MM-yyyy",
      minDate: new $.jqx._jqxDateTimeInput.getDateTime(new Date(1960, 0, 1)),
      maxDate: new $.jqx._jqxDateTimeInput.getDateTime(new Date(2003, 0, 1)),
      value: new $.jqx._jqxDateTimeInput.getDateTime(new Date(1900, 0, 1))
   });
   $('[type=button]').live('click', Cultures.buttonClicked);
   $('[type=select]').live('change', Cultures.changedSelection);

   $('#storeNo').focus().blur(function(){ this.value = this.value.toUpperCase(); });
   Main.cultureValidation = <?php echo json_encode(Config::$cultureValidation); ?>;
});
</script>
<?php
      echo "<script type='text/javascript' src='". OPTIONS_COMMON_FOLDER_PATH ."jquery/jquery.autocomplete/jquery.autocomplete.js'></script>";
      echo "<link rel='stylesheet' type='text/css' href='". OPTIONS_COMMON_FOLDER_PATH ."jquery/jquery.autocomplete/styles.css' />";

      $ac = array(
         array('sub_module' => 'store_number', 'id' => 'storeNo'),
         array('sub_module' => 'animal_id', 'id' => 'animalId'),
         array('sub_module' => 'growth_medium', 'id' => 'growthMedium'),
         array('sub_module' => 'storage_medium', 'id' => 'storageMedium')
      );

      echo "<script type='text/javascript'>";
      foreach($ac as $t){
         $settings = array('inputId' => $t['id'], 'reqModule' => 'cultures', 'reqSubModule' => $t['sub_module']);
         if($t['id'] == 'storeNo')  $settings['selectFunction'] = 'Cultures.fillCultureData';
         $this->InitiateAutoComplete($settings);
      }
      echo "</script>";
   }

   /**
    * Spits out the javascript that initiates the autocomplete feature once the DOM has finished loading
    */
   private function InitiateAutoComplete($settings){
      if($settings['formatResult'] == '') $settings['formatResult'] = 'Cultures.fnFormatResult';
      if($settings['visibleSuggestions'] == '') $settings['visibleSuggestions'] = true;
      if($settings['beforeNewQuery'] == '') $settings['beforeNewQuery'] = 'undefined';
      if(!isset($settings['selectFunction'])) $settings['selectFunction'] = 'function(){}';
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

   private function FetchData(){
      if(OPTIONS_REQUESTED_SUB_MODULE == 'store_number'){
         $toFetch = array();
         foreach(Config::$form_db_map as $name => $column){
            if(preg_match('/^cultures\./', $column)) $toFetch[] = "$column as $name";
         }
         $query = 'select id, '. implode(', ', $toFetch).', culture_name as val, date_format(date_stored, "%d-%m-%Y") as date_stored from cultures where culture_name like :query';
      }
      elseif(OPTIONS_REQUESTED_SUB_MODULE == 'animal_id') $query = 'select animal_id as val from cultures where animal_id like :query group by animal_id';
      elseif(OPTIONS_REQUESTED_SUB_MODULE == 'growth_medium') $query = 'select growth_medium as val from cultures where growth_medium like :query group by growth_medium';
      elseif(OPTIONS_REQUESTED_SUB_MODULE == 'storage_medium') $query = 'select storage_medium as val from cultures where storage_medium like :query group by storage_medium';

      $res = $this->Dbase->ExecuteQuery($query, array('query' => "%{$_GET['query']}%"));
      if($res == 1) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
      $suggestions = array();
      foreach($res as $t){
         $suggestions[] = $t['val'];
         $data[] = $t;
      }
      $data = array('error' => false, 'query' => $_GET['query'], 'suggestions' => $suggestions, 'data' => $data);
      die(json_encode($data));
   }

   private function SaveCulture(){
      $dt = json_decode($_POST['cur_culture'], true);
//      $this->Dbase->CreateLogEntry('<pre>'. print_r($dt, true) .'</pre>', 'debug');
      $errors = array();
      $set = array();
      $vals = array();
      $insert_vals = array();
      $insert_cols = array();

      //run the input validation
      foreach(Config::$cultureValidation as $cur){
         //get the current selector. The selector can either be the element id or element name for this particular input
         if(isset($dt[$cur['id']])) $selector = $cur['id'];
         elseif(isset($dt[$cur['name']])) $selector = $cur['name'];
         //get the current value based on the current selector
         $cur_val = $dt[$selector];

         if($cur_val != '' && !in_array($cur_val, $cur['defaultVal'])){
            //we actually have something.... validate it
            if(preg_match("/{$cur['valueRegex']}/i", $cur_val) === 0){
               //we have problems
               $errors[] = $cur['wrongValMessage'];
            }
            else{
               if($dt[$selector] === 'date_stored') $cur_val = date("Y-m-d", strtotime($cur_val));
               //clean bill of health, so build our update/insert query
               if(!key_exists($selector, $vals)){     //some values are being picked twice and I cannot understand why! this is going to prevent
                  $set[] = Config::$form_db_map[$selector]. "=:$selector";
                  $vals[$selector] = $cur_val;
                  $insert_vals[] = ":$selector";
                  $insert_cols[] = Config::$form_db_map[$selector];
               }
            }
         }
         else{
            //we have nothing
//            echo "{$cur['id']}; {$cur['name']} ==> $cur_val</br>";
         }
      }
      if(key_exists('date_stored', $vals)){
         $vals['date_stored'] = date("Y-m-d", strtotime($vals['date_stored']));
//         echo date("Y-m-d", strtotime($vals['date_stored']));
      }

      if(count($errors) != 0 ) die(json_encode(array('error' => true, 'data' => implode("<br />", $errors))));

      $lockQuery = "lock table cultures write";
      $this->Dbase->StartTrans();
      if(isset($dt['id'])){
         //we wanna update a stabilate
         $vals['id'] = $dt['id'];
         $query = 'update cultures set '. implode(', ', $set) .' where id=:id';
      }
      else{
         //we wanna save a new stabilates
         $insert_cols[] = 'added_by'; $insert_vals[] = ':added_by'; $vals['added_by'] = $_SESSION['user_id'];
         $query = 'insert into cultures('. implode(', ', $insert_cols) .') values('. implode(', ', $insert_vals) .')';
      }
      $res = $this->Dbase->UpdateRecords($query, $vals, $lockQuery);
      if($res == 1){
         $this->Dbase->RollBackTrans();
         die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
      }
      //commit the transaction and unlock the tables
      if( !$this->Dbase->CommitTrans() ) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));
      $res = $this->Dbase->ExecuteQuery("Unlock tables");
      if($res == 1) die(json_encode(array('error' => true, 'data' => $this->Dbase->lastError)));

      //we are all good
      die(json_encode(array('error' => false, 'data' => 'Data saved well')));
   }
}
?>