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
      if(OPTIONS_REQUESTED_SUB_MODULE == 'add') $this->AddCulturesHome();
   }

   /**
    * Creates the users module home page
    *
    * @param   string   $addinfo    Any additional information that we might need to pass to the user
    */
   private function AddCulturesHome($addinfo = ''){
      $error = '';
      //hosts
      $query = "select id, host_name from hosts";
      $res = $this->Dbase->ExecuteQuery($query);
      if($res != 1){
         $ids=array(); $vals=array();
         foreach($res as $t){
            $ids[]=$t['id']; $vals[]=$t['host_name'];
         }
         $settings = array('items' => $vals, 'values' => $ids, 'firstValue' => 'Select One', 'name' => 'host_name', 'id' => 'hostId');
         $hostCombo = GeneralTasks::PopulateCombo($settings);
      }
      else $error = $this->Dbase->lastError;
?>
<form class='form-horizontal'>
<fieldset class='cultures'>
   <legend>Cell Cultures - Frozen Stocks</legend>
   <div class=''>
      <div class="control-group">
         <label class="control-label" for="stabilateNo">Store #</label>
         <div class="controls">
            <input type="text" id="storeNo" placeholder="Store" class='input-medium'>&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />
         </div>
      </div>
      <div class="control-group">
         <label class="control-label" for="cellTypeId">Cell Type</label>
         <div class="controls">
            <?php echo $hostCombo; ?>&nbsp;&nbsp;<img class='mandatory' src='images/mandatory.gif' alt='Required' />
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
            <input type="text" id="noOfVials" placeholder="No of Vials" class='input-mini'>
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
<?php
   }
}
?>