<?php 
// rxnorm controllers

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Cpm\Tools;

$ctrls = $app['controllers_factory'];
$ctrls->get('/list', function (Silex\Application $app, Request $request) {
  $tools = new Tools;
  $mydata = $tools->getToolList();
  return $app->json($mydata);
});

return $ctrls;

?>