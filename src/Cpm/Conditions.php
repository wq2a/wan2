<?php
/* conditions web service -- gets lists of conditions  */
namespace Cpm;
include "db.php";
class Conditions {

  public function conditionsAction() {
    $action='';
    $lang = 'en';
    if (isset($_REQUEST['lang'])) { 
      $lang = $_REQUEST['lang']; 
    }

    if (isset($_REQUEST['action'])) { 
      $action = $_REQUEST['action']; 
    }

    rx_error_log("Request made. action: $action, ");
    rx_error_log($_REQUEST);

    if (!$action || $action == "conditions" ) {
      
      $response = getConditions($lang);
      sendResponse($response);
    }
  }

  /* Gets all pages for survey and returns survey */
  public function getConditions($lang = 'en')
  {
  	$columns = array( 'en' => ', chv_str , chv_str as display', 
                      'es' => ', sp_chv_str as chv_str, sp_chv_str as display'
                    );
    $lang_cols = $columns[$lang];
    $sql = "select icd9, cui, lui, sui, stt, chv_code" . $lang_cols  
    			. " from icd9_to_chv_str where tty=? group by chv_str order by chv_str";
    $result = dbQuery($sql, array('PT'));
    //rx_error_log($result); 
    return $result;
  }


?>

