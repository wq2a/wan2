<?php
/* survey web service, retrieves and saves survey data  */
namespace Cpm;
include "db.php";
class Survey {

  public function surveyAction($action='', $uid = 1) {
    $action='';
    $uid = 1; 


    if (isset($_REQUEST['action'])) { 
      $action = $_REQUEST['action']; 
    }
    if (isset($_REQUEST['uid'])) { 
      $uid = $_REQUEST['uid']; 
    }

    $lang = 'en';
      
    if (isset($_REQUEST['lang'])) { 
      $lang = $_REQUEST['lang']; 
    }

    rx_error_log("Request made. action: $action, ");
    rx_error_log($_REQUEST);

    if ($action == "list" ) {
      $response = getSurveyList($lang);
      sendResponse($response);
    }

    if ($action == "survey" ) {
      $name = '';
      

      if (isset($_REQUEST['name'])) { 
        $name = $_REQUEST['name']; 
      }
      if (isset($_REQUEST['page'])) { 
        $page = intval($_REQUEST['page']); 
      }
      else {
        $page = 0;
      }
      if (!$name ) {
        $msg = "Survey name is required"; 
        rx_error_log($msg);
        sendResponse(['error'=> $msg], 500); 

      }
      if ($page) {
        $response = getSurveyPage($name, $page, $lang); 
      }
      else
      {
        $response = getSurvey($name, $uid, $lang);
      }
      
      sendResponse($response);
    }
    else if ($action == 'save_survey') {
      $uid = $_REQUEST['uid'];
      $survey = $_REQUEST['answers'];
      if (!$uid)  {
        rx_error_log("No uid sent: $uid "); 
        sendResponse(['error'=> 'User uid is required to save survey. Got: '.$uid], 500); 
      }
      if (!$answers) {
        rx_error_log("No answers argument :  $answers"); 
        sendResponse(['error'=> 'Answers are required to save survey. Got: '.$answers], 500); 
      }
      rx_error_log(array($uid, $survey));
      $response = "Survey saved";
      sendResponse($response);
    }
    else {
      rx_error_log("No action specified that we understand: $action"); 
      sendResponse(['error'=> 'No action specified that we understand'], 500); 

    }
  }

  /* Get list of available surveys */
  function getSurveyList($lang = 'en')
  {
    return getSurveyMeta(null, $lang);
  }

  /* Get survey page with questions */
  function getSurveyPage($name, $page, $lang = 'en')
  {
    $sql = "select * from view_survey_page where name = ? and page = ?";
    $result = dbQuery($sql, array($name, $page));
    
    $page = $result[0];
    $qids = explode(',', $page['qids']);
    $questions = [];
    foreach($qids as $qid)
    {
      $questions[] = getSurveyQuestion($qid);
    }
    $page['questions'] = $questions;
    return $page;
  }

  /* Gets meta data for a survey or all surveys if name isn't passed */
  function getSurveyMeta($name = null, $lang = 'en') {
    $columns = array( 'en' => ',title, header, `desc`', 
                      'es' => ',sp_title as title, sp_header as header, sp_desc as `desc`'
                    );
    $lang_cols = $columns[$lang];
    $sql = "select sid, name, logo, url, next, review " . $lang_cols  . " from survey ";
    if ($name) { 
      $sql .= " where name = ?";
      $result = dbQuery($sql, array($name));
      if (!$result) { return null; }
      $survey = $result[0];
      $survey['lang'] = $lang;
      return $survey;
    }
    else { 
      $sql .= " order by sid";
      rx_error_log($sql);
      $result = dbQuery($sql);
      return $result;
    }
    
  }

  /* Get survey with all questions */
  function getSurvey($name, $uid = 0, $lang = 'en')
  {
    // Todo -- get for user 
    $survey = getSurveyMeta($name, $lang);
    if (!$survey) { return null; }

    $survey['curpage'] = 1; 
    $survey['uid'] = $uid;
    $columns = array( 'en' => ', p.title as page_title, p.label as page_label, p.step as page_step ',
                      'es' => ', p.sp_title as page_title, p.sp_label as page_label, p.sp_step as page_step '
                    );
    $lang_cols = $columns[$lang];
    $sql = "SELECT s.sid , s.name, p.page, p.qids " . $lang_cols . 
           " FROM survey s join survey_page p on s.sid  = p.sid ";

    $result = dbQuery($sql, array($name));
    $survey['pages'] = [];
    if ($survey['next']) { 
      $survey['next'] = getSurveyMeta($survey['next'], $lang);
    }
    
    foreach ($result as $page) { 
      $qids = explode(',', $page['qids']);
      $questions = [];
      foreach($qids as $qid)
      {
        $questions[] = getSurveyQuestion($qid, $lang);
      }
      $page['questions'] = $questions;
      // Additional page comes after survey for any final questions 
      if ($page['page_label'] == 'Additional') {
        $survey['additional'] = $page;
      }
      else {
        $survey['pages'][] =  $page;
      }

    }
    #rx_error_log($survey);
    return $survey;
  }

  /* Recursively gets question and children  */
  function getSurveyQuestion($qid, $lang = 'en')
  {
    //rx_error_log("getting question $qid");

    $columns = array( 'en' => ',`qtext`, `help`, `children_prompt`' , 
                      'es' => ',`sp_qtext` as qtext, `sp_help` as help, `sp_children_prompt` as children_prompt' 
                    );
    $lang_cols = $columns[$lang];
    $sql = 'select `qid`, `qtype`, `children`, `phecode` ' . $lang_cols . 
      ' from survey_question where qid = ?  ';
    $result = dbQuery($sql, array($qid));
    if (!$result) { return null;}
    $qs = $result[0];
    
    if (isset($qs['children']) && $qs['children']) { 
      $children = explode(',', $qs['children']);
      $qs['children'] = [];
      foreach ($children as $child) {
        $qs['children'][] = getSurveyQuestion($child, $lang);
      }
    }
    else {
      $qs['children'] = [];
    }
    return $qs;
  }

}
?>

