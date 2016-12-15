<?php 
// rxnorm controllers

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Cpm\RxNorm;

$rxnorm = $app['controllers_factory'];
$rxnorm->get('/{action}', function (Silex\Application $app, Request $request, $action) {
  $rxnorm = new RxNorm;
  
  $mydata = $rxnorm->rxAction($action, $request->query->all());
  return $app->json($mydata);
});
return $rxnorm;

?>