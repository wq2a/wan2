<?php
require_once __DIR__.'/../vendor/autoload.php';
include('../config/database.php');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpFoundation\ParameterBag;

use Cpm\RxNorm;
use Cpm\UserDb;
use Cpm\Security\User;
use Cpm\Security\UserToken;

$app = new Silex\Application();
// Register the Doctrine DBAL  DB 
$app->register(new Silex\Provider\DoctrineServiceProvider(), $DBConfig);

$app['debug'] = true;
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/development.log',
));

$app['app.cpm_user_provider'] = function(){
    return new Cpm\Security\UserProvider;
};

$app['app.cpm_token_authenticator'] = function ($app) {
    return new Cpm\Security\TokenAuthenticator($app['security.encoder_factory']);
    //return new Cpm\Security\TokenAuthenticator;
};

$app->register(new Silex\Provider\SecurityServiceProvider(), array(
    'security.encoders' => array(
        'AppBundle\Entity\User' => array(
            'algorithm' => 'bcrypt',
        ),
    ),
    'security.firewalls' => array(
        'register' => array(
            'pattern'        => '^/add_user$',
        ),
        'login' => array(
            'pattern'        => '^/login_check$',
            'guard'          => array(
                'authenticators'  => array(
                    'app.cpm_token_authenticator'
                )
            ),
            //'provider' => 'cpm_users',
            'users' => function () use ($app) {
                return new Cpm\Security\UserProvider($app['db']);
            }
        ),
        'api' => array(
            'pattern'        => '^.*$',
        )
     )
));

$app->get('/', function() use ($app) {
    $app['monolog']->debug('/ page viewed');
    return 'Root page . Baby ';
});

$tokenCheck = function (Request $request, Silex\Application $app){
    $token = $request->get('token');
    $userToken = new UserToken();
    $result = $userToken->parseToken($token);
    if(!$result['valid']) {
        // return new Response(implode(',',$user->parseToken($token)),200);
        $data = array('message'=>'Invalid token');
        return new JsonResponse($data, 403);
    }
};

$app->post('/api/{rest}', function(Request $request) use ($app) {
    return 'ok';
})
->assert('rest','.*')
->before($tokenCheck);

$app->post('/add_user', function(Request $request) use($app) {
    // See if user checked out 
    // Get user 
    $userData = array();
    $userData['username'] = $request->get('username');
    $userData['password'] = $request->get('password');
    $user = new User($userData);
    $userdb = new UserDb($app['db']);
    $userdb->addUser($user);
    return $app->json($user->toArray()); 
});

$app->post('/login_check', function(Request $request) use($app) {
    // See if user checked out 
    // Get user 
});

$app->get('/hello/{name}', function ($name) use ($app) {
    return 'Hello '.$app->escape($name);
});

// Mount rxnorm controllers
$app->mount('/rxnorm', include 'rxnorm_controllers.php');

// Mount tools 
$app->mount('/tools', include 'tool_controllers.php');

// Allow cors from anywhere : TODO limit when pushed live 
$app->after(function (Request $request, Response $response) {
    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->headers->set('Access-Control-Allow-Headers', 'Authorization');
});

$app->match("{url}", function($url) use ($app) {
    return "OK";
})
->assert('url', '.*')
->method("OPTIONS");

$app->options("{anything}", function () {
    return new \Symfony\Component\HttpFoundation\JsonResponse(null, 204);
})
->assert("anything", ".*");

$app->run();

