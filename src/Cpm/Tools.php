<?php
namespace Cpm;
include "db.php";
class Tools {
  /* Tools class for cpm to manage metadata and get list of tools from database and such 
  */


  /* Get list of available surveys */
  public function getToolList($lang = 'en')
  {
    return $this->getToolsMeta(null, $lang);
  }


  /* Gets meta data for a survey or all surveys if name isn't passed */
  public function getToolsMeta($name = null, $lang = 'en') {
    $columns = array( 'en' => ',title, header, `desc`', 
                      'es' => ',sp_title as title, sp_header as header, sp_desc as `desc`'
                    );
    $lang_cols = $columns[$lang];
    $sql = "select tid, name, logo, url, next, review " . $lang_cols  . " from tool ";
    if ($name) { 
      $sql .= " where name = ?";
      $result = dbQuery('cpmv', $sql, array($name));
      if (!$result) { return null; }
      return $result[0];
      
    }
    else { 
      $sql .= " order by title asc";
      $result = dbQuery('cpmv', $sql);
      return $result;
    }
    
  }

}


?>

