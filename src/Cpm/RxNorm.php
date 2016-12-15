<?php
/* RxNorm webservice calls  . We're using the Doctrine Database Abstrac
   Layer */
namespace Cpm;
include "db.php";
class RxNorm {

  public $lang = "en";
  public $db = 'cpmv_rx'; // DB to query with rxnorm tables and data
  public function rxLog($data) {
    rx_error_log($data);
  }
  public function rxAction($action, $params = array()) {
    $term = '';
    $rxcui = 0;
    $lang = 'en';

    if (isset($params['term'])) { $term = $params['term'];}
    if (isset($params['rxcui'])) { $rxcui = $params['rxcui']; }
    if (isset($params['lang'])) { $this->lang = $params['lang']; }


    rx_error_log(array("Request made. action: $action, params: ", $params));

    // Set our diseases function 
    $diseaseFunc = 'rxDiseases';
    $data = null;
    switch ($action) {
      case "rxdfgs":
        $data = $this->rxDfgs(); 
        break;
      case "rxnames": 
        $data = $this->rxNames();
        break;
      case "ingredients": 
        $data = $this->rxIngredients($rxcui);
        break;
      case "diseases": 
        $data = $this->rxDiseases($rxcui);
        break;
      case "medi_diseases": 
        $data = $this->rxMediDiseases($rxcui);
        break;
      case "josh_diseases": 
        $data = $this->rxJoshDiseases($rxcui);
        break;
      case "josh_medi_diseases": 
        $data = $this->rxJoshMediDiseases($rxcui);
        break;
      case "phecode_diseases": 
        $data = $this->rxMediPhecodeDiseases($rxcui);
        break;
       case "medi_josh_diseases": 
        $data = $this->rxMediJoshDiseases($rxcui);
        break;
      case "rxdoses": 
        $data = $this->rxDosesFromCui($rxcui);
        break;
      case "rxdoses_in": 
        $data = $this->rxDosesFromCuiIn($params);
        break;
      case "rxscds": 
        // Specific clinical doses and SBDs for a cui selected from the dose 
        $dose_atv = isset($params['dose']) ? $params['dose'] : null;
        if (!$dose_atv) { 
         $data = ['error' =>"No dose string sent"];
        }
        else {
          $data = $this->rxScds($rxcui, $dose_atv); 
        }
        break;
     
      
    }

    return $data; //sendResponse($data);
  }




  public function getCuiFromTerm($term) {
    if (!$term ) {
      rx_error_log("No term passed to get cui from term "); 
      return 0;
    }
    $rxcui = rxCuiFromName($term); 
    
    return $rxcui;
  }





# End api  # 


# Functions 
 


  public function rxDfgs() { 
    $sql = "select rxcui, str, display, weight from dfgs where ign = 0 order by weight, str";
    return dbQuery($this->db,$sql); 
  }

  public function rxIngredientList($dfgcui = null)
  {
    $args = array(); 
    $sql = "select rxcui, str , tty, str as display from rxnconso where tty in ('IN', 'BN')  order by str"; 
    return dbQuery($this->db,$sql, $args); 
  }

  function rxNames()
  {
    // We run two queries -- one to get the single ingredient ones and another to get the multiples 
    // ingredients separated by slash 
    $lang = $this->lang;
    $columns = array( 
      'en' => ',str',
      'es' => ',sp_str'
    );
    $lang_cols = $columns[$lang];
    $sql = "select * from rxmeds where str not like '%/%'  order by sortstr, weight;";
    $results =  dbQuery($this->db,$sql); 
    $sql = "select * from rxmeds where str like '%/%'  order by sortstr, weight;";
    $results = array_merge($results, dbQuery($this->db,$sql)); 


    // Set spanish string if we have one and lang is spanish 
    if ($lang == 'es') {
      for ($i = 0; $i < count($results) ; $i++)
      {
        if ($results[$i]['sp_str']) {
          $results[$i]['str'] = $results[$i]['sp_str'];
        }
      }
    }
    
    return $results;
  }

  function rxNamesNih()
  {
    $url = 'https://rxnav.nlm.nih.gov/REST/displaynames.json'; 
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
  }
   
  function rxCuiFromName($term) 
  {
    $url = 'https://rxnav.nlm.nih.gov/REST/Prescribe/rxcui.json?name='.urlencode($term); 
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    curl_close($ch);
    $data_obj = json_decode($data);
    $rxcui = 0;
    if (isset($data_obj->idGroup->rxnormId[0])) {
      $rxcui = $data_obj->idGroup->rxnormId[0]; 
    }
    return $rxcui;
  }

  function rxDosesFromCui($rxcui) 
  {
    global $diseaseFunc;
    $strengths = rxStrengths($rxcui); 
    $diseases = $diseaseFunc($rxcui);
    return ['rxdoses'=>$strengths, 'diseases' => $diseases]; 
  }
  // Ingredient cui 
  function rxDosesFromCuiIn($rxcui, $dfgcui) 
  {
    // nexium
    $sql = "select c.rxcui, c.str , c.tty, s.atv as dose from rxnrel r1 join rxnconso c on r1.rxcui2 = c.rxcui 
    join rxnrel r2 on c.rxcui = r2.rxcui1    
    join rxnsat s on c.rxcui = s.rxcui 
    where r1.rxcui1 = ? and   r2.rxcui2 = ? and r1.rela = 'has_ingredient'  and r2.rela='doseformgroup_of' and s.atn = 'RXN_AVAILABLE_STRENGTH'" ;

    //rx_error_log("Ing $rxcui, $dfgcui");
    $results = dbQuery($this->db,$sql, array($rxcui, $dfgcui)) ; 
    $sorted = array();
    // Sort results by qty 
    for ($i = 0 ; $i < count($results) ; $i++)   
    {
      $results[$i]['display'] = preg_replace('/\(expressed as .*\)/', '', $results[$i]['dose']); 
      $qty = $results[$i]['dose'] + 0;

      $matches = array();
      preg_match('/^(\d+)\s*([\w\/]+)/', $results[$i]['dose'], $matches);
      $results[$i]['qty'] = $qty ; //matches[1];
      #$unit = $matches[2] ? $matches[2]: '?'; // Somereason unit isn't set sometimes 
      #$results[$i]['unit'] = $unit;
      
      // Key by quantity so we can sort 
      $sorted["$qty"] = $results[$i];
    }
    ksort($sorted, SORT_NUMERIC);
    $sorted_results = array();
    foreach ($sorted as $s )
    {
      $sorted_results[] = $s;
    }
    
    //rx_error_log($results); 
    //rx_error_log($sorted_results); 
    return ['rxdoses'=>$sorted_results]; 
  }

  function rxDataFromCui($rxcui) 
  {
    $url = "https://rxnav.nlm.nih.gov/REST/Prescribe/rxcui/$rxcui/allrelated.json"; 
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    curl_close($ch);
    #return $data ;   
    $data_obj = json_decode($data);
    $concept_groups = $data_obj->allRelatedGroup->conceptGroup;
    # Dosages are in certain term types (tty) . 
    # SCD - semantic clinical drug 
    # SBD - semantic branded drug 
  # See https://www.nlm.nih.gov/research/umls/rxnorm/docs/2015/appendix5.html
    $wanted = ['SCD', 'SBD', 'GPCK', 'BPCK', 'PSN', 'SCDC', 'SBDC'];
    $rxnorm = [];
    foreach ($concept_groups as $g) { 
      if ( isset($g->conceptProperties )) { 
        $rxnorm[] = $g->conceptProperties[0];
      }
    }
    //$diseases = rxDiseases($rxcui);
    $diseases = rxJoshDiseases($rxcui);
    return ['rxnorm'=>$rxnorm, 'diseases'=>$diseases]; 
  }

  // Get medi phecode disease list 
  function rxMediPhecodeDiseases($rxcui, $other_lang = null, $prevalence = .2) {
    /*
    if (is_array($rxcui)) {
      $rxcui = join(',',$rxcui);
    }
    */
    $table = 'medi_top_rx_phecode';
    $columns = array( 
      'en' => ', phestr as str',
      'es' => ', sp_phestr as str');
    $lang = 'en';
    if (isset($_REQUEST['lang'])) { 
      $lang = $_REQUEST['lang']; 
    }
    $lang_cols = $columns[$lang];
    $diseases = [];

    $ing = $this->rxIngredients($rxcui);
    
    $ingredients_str = "$rxcui";

    foreach ($ing as $i) {
      $ingredients_str .= ", " . $i['rxcui'];
    }
    // Query josh's med indication table 
    $sql = "SELECT rxcui, '' as sui, phecode as chv_code, prec_high as prevalence, 'medi-phe' as src $lang_cols
  FROM $table  where rxcui in ($ingredients_str)  
  order by prec_high desc " ;
    $results = dbQuery($this->db,$sql);
    
    return $results;
  }


  // Get josh smith's notes disease list 
  function rxJoshDiseases($rxcui, $prevalence = .2) {
    
    $columns = array( 
      'en' => ', mp_str as chv_str',
      'es' => ', sp_str as chv_str');
    $lang = 'en';
    if (isset($_REQUEST['lang'])) { 
      $lang = $_REQUEST['lang']; 
    }
    $lang_cols = $columns[$lang];
    $diseases = [];

    $ing = $this->rxIngredients($rxcui);
    $ingredients_str = "$rxcui";

    foreach ($ing as $i) {
      $ingredients_str .= ", " . $i['rxcui'];
    }
    // Query josh's med indication table 
    $sql = "SELECT rxcui, '' as sui, '' as chv_code, cmname as str, per_drug_cm as prevalence , 'joshhp' as src $lang_cols
  FROM deb_drug_indications where rxcui in ($ingredients_str)  order by per_drug_cm desc" ;
    $results = dbQuery($this->db,$sql);
    return $results;
  }

  /* Josh's with the missing ones from medi */
  function rxJoshMediDiseases($rxcui, $prevalence = .2) {
    
    $columns = array( 
      'en' => ', mp_str as chv_str',
      'es' => ', sp_str as chv_str');
    $lang = 'en';
    if (isset($_REQUEST['lang'])) { 
      $lang = $_REQUEST['lang']; 
    }
    $lang_cols = $columns[$lang];
    $diseases = [];

    $ing = $this->rxIngredients($rxcui);
    $ingredients_str = "$rxcui";

    foreach ($ing as $i) {
      $ingredients_str .= ", " . $i['rxcui'];
    }
    // Query josh's med indication table 
    $sql = "SELECT m.rxcui, m.cmname as str, m.per_drug_cm as prevalence,'joshhp' as src, c.chv_str as chv_str 
  FROM deb_drug_indications m left join icd9_to_chv_str c on m.cui = c.cui and c.tty = 'PT'
  where m.rxcui in ($ingredients_str)  
  union 
  select m.rxcui , m.str as str, m.ppv_max as prevalence , 'medi' as src , c.chv_str as chv_str 
  from `medi_missing_from_josh` m  left join icd9_to_chv_str c on m.cui = c.cui and c.tty = 'PT'
  where m.rxcui in ($ingredients_str) 
  order by prevalence desc, chv_str";

    
    $results = dbQuery($this->db,$sql);
    return $results;
  }

  // Get medi disease list joined to chv 
  function rxDiseases($rxcui, $prevalence = 0) {
    
    $columns = array( 
      'en' => ', c.chv_str',
      'es' => ', c.sp_chv_str as chv_str');
    $lang = 'en';
    if (isset($_REQUEST['lang'])) { 
      $lang = $_REQUEST['lang']; 
    }
    $lang_cols = $columns[$lang];
    $diseases = [];

    // Medi mainly has diseases for the main ingredients 
    // Just in case a ingredient is passed to us, we add the cui to the ing list : 
    $ing = $this->rxIngredients($rxcui);
    $ingredients_str = "$rxcui";
    foreach ($ing as $i) {
      $ingredients_str .= ", " . $i['rxcui'];
    }
     
    $sql = "SELECT m.rxcui, c.SUI, c.chv_code " . $lang_cols . 
      " FROM medi_pub m JOIN icd9_to_chv_str c USING (icd9) 
      WHERE m.rxcui in ($ingredients_str)  AND m.hsp = 1 AND c.tty = 'PT' and prevalence >= ?
      group by c.SUI order by prevalence desc, chv_str" ;
    $results = dbQuery($this->db,$sql, array($prevalence)); 
    
    return $results;

  }

  function rxMediDiseases($rxcui, $other_lang = null, $prevalence = .2) {
    
    $columns = array( 
      'en' => ', c.chv_str',
      'es' => ', c.sp_chv_str as chv_str');
    $lang = 'en';
    if (isset($_REQUEST['lang'])) { 
      $lang = $_REQUEST['lang']; 
    }
    $lang_cols = $columns[$lang];
    $diseases = [];

    // Medi mainly has diseases for the main ingredients 
    // Just in case a ingredient is passed to us, we add the cui to the ing list : 
    $ing = $this->rxIngredients($rxcui);
    $ingredients_str = "$rxcui";
    foreach ($ing as $i) {
      $ingredients_str .= ", " . $i['rxcui'];
    }
    
    //rx_error_log($ing);
    //rx_error_log("ingredients");
    $sql = "SELECT m.rxcui,m.prevalence, m.icd9_str as str, c.SUI, c.chv_code, 'medi' as src " . $lang_cols . 
    " FROM medi_pub m left JOIN icd9_to_chv_str c on m.icd9 = c.icd9 and c.tty = 'PT' 
    WHERE m.rxcui in ($ingredients_str)  AND m.hsp = 1 
     order by m.prevalence desc, c.chv_str" ;

    $results = dbQuery($this->db,$sql, array($prevalence));
    return $results;

  }
  function rxMediJoshDiseases($rxcui, $other_lang = null, $prevalence = .2) {
    
    $columns = array( 
      'en' => ', c.chv_str',
      'es' => ', c.sp_chv_str as chv_str');
    $lang = 'en';
    if (isset($_REQUEST['lang'])) { 
      $lang = $_REQUEST['lang']; 
    }
    $lang_cols = $columns[$lang];
    $diseases = [];

    // Medi mainly has diseases for the main ingredients 
    // Just in case a ingredient is passed to us, we add the cui to the ing list : 
    $ing = $this->rxIngredients($rxcui);
    $ingredients_str = "$rxcui";
    foreach ($ing as $i) {
      $ingredients_str .= ", " . $i['rxcui'];
    }
    
    //rx_error_log($ing);
    //rx_error_log("ingredients");
    $sql = "SELECT m.rxcui,m.ppv_max as prevalence, m.str as str, c.chv_str as chv_str, m.cui, 'medi' as src
     FROM medi_pub_cui m left join icd9_to_chv_str c on m.cui = c.cui and c.tty = 'PT'
       WHERE m.rxcui in ($ingredients_str) 
     union 
  select m.rxcui , m.per_drug_cm as prevalence, m.cmname as str, c.chv_str as chv_str, m.cui, 'deb2' as src
  from `josh_missing_from_medi` m left join icd9_to_chv_str c on m.cui = c.cui and c.tty = 'PT'
     where rxcui in ($ingredients_str)  
     order by prevalence desc, chv_str";


    $results = dbQuery($this->db,$sql, array($prevalence));
    return $results;

  }

  /* Get list of dose strengths */
  function rxStrengths($rxcui ) {
    $sql = "Select rxcui, atv as dose from dg_strength where rxcui = ? order by atv";
    $results = dbQuery($this->db,$sql, array($rxcui));  
    $sorted = array();
    // Sort results by qty 
    for ($i = 0 ; $i < count($results) ; $i++)   
    {
        $results[$i]['display'] = preg_replace('/\(expressed as .*\)/', '', $results[$i]['dose']); 
        $qty = $results[$i]['dose'] + 0;

        $matches = array();
        preg_match('/^(\d+)\s*([\w\/]+)/', $results[$i]['dose'], $matches);
        $results[$i]['qty'] = $qty ; //matches[1];
        #$unit = $matches[2] ? $matches[2]: '?'; // Somereason unit isn't set sometimes 
        #$results[$i]['unit'] = $unit;
        
        // Key by quantity so we can sort 
        $sorted["$qty"] = $results[$i];
    }
    ksort($sorted, SORT_NUMERIC);
    $sorted_results = array();
    foreach ($sorted as $s )
    {
      $sorted_results[] = $s;
    }
    
    //rx_error_log($results); 
    //rx_error_log($sorted_results); 
    return $sorted_results;
  }
  /* Given the semantic dose form group and the dose attribute value selected 
   * This will give semantic clinical doses (or branded ) 
   */
  function rxScds($rxcui_sdfg, $dose_atv ) {
    $sql = "Select c.rxcui, c.str, c.tty  from RXNCONSO c join RXNSAT s on c.rxcui = s.rxcui join RXNREL r on r.rxcui2 = c.rxcui 
    where r.rxcui1 = ? and  r.rela in ('ingredient_of','isa') and c.tty in ('IN','BN','SCD','SBD') "; 
    if ($dose_atv)  { 
      $sql .= " and s.atn = 'RXN_AVAILABLE_STRENGTH' and s.atv= ? ";
    }
    $sql .= " order by str; ";
    $results =  dbQuery($this->db,$sql, array($rxcui_sdfg, $dose_atv));  
    
    return ['scds' => $results]; 
  }

  // Get the ingredients for a drug . This works for rxcui of tty sbdf or scdf
  // for branded dose forms the relationship  will give us a brand name for the ingredient which 
  // we then have to look up again to get with the has_tradename relationship 
  public function rxIngredients($rxcui) {
    if (!$rxcui ) {
      rx_error_log("No rxcui passed to $action"); 
      sendResponse(['error'=> 'No rxcui'],500);
    }
    $sql = "Select c.rxcui, c.str, c.tty  from RXNCONSO c join RXNREL r on r.rxcui2 = c.rxcui where r.rxcui1 = ? and  r.rela in ('ingredient_of') and c.tty in('IN', 'BN');";
    $rows =  dbQuery($this->db,$sql, array($rxcui));
    $results = [];
    foreach ($rows as $r) {
      if ($r['tty'] == 'IN') {
        $results[] = $r;
      }
      else { 
      # Brand name BN
        $sql = "Select c.rxcui, c.str, c.tty  from RXNCONSO c join RXNREL r on r.rxcui2 = c.rxcui where r.rxcui1 = ? and  r.rela in ('has_tradename') and c.tty = 'IN';";
        $bn_rows  =  dbQuery($this->db,$sql, array($r['rxcui']));
        $results = array_merge($results, $bn_rows);
      }
    }
    return $results;
  }

}


?>

