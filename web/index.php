<?php
require_once __DIR__.'/../vendor/autoload.php';
include('../config/database.php');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Definition;

use Cpm\RxNorm;

$app = new Silex\Application();
// Register the Doctrine ORM DB 
$app->register(new Silex\Provider\DoctrineServiceProvider(),$db);


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
    // ...
        'security.encoders' => array(
            'AppBundle\Entity\User' => array(
            'algorithm' => 'bcrypt',
            ),
        ),

    // ...

    /*'providers' => array(
        'cpm_users' => array(
            'entity' => array(
                'class'    => 'Cpm:Security:User',
                'property' => 'username',
            ),
        ),
    ),*/
    
    'security.firewalls' => array(
        'main' => array(
            'pattern'        => '^/*',
            
            'guard'          => array(
                'authenticators'  => array(
                    'app.cpm_token_authenticator'
                )
            ),
            //'provider' => 'cpm_users',
            'users' => function () use ($app) {
                return new Cpm\Security\UserProvider($app['db']);
            },
            
            // ...
        )
     )
));



            
$app->get('/', function() use ($app) {
    $app['monolog']->debug('/ page viewed');
	return 'Root page . Baby ';
});
$app->get('/test', function() use ($app) {
    return 'test  page .  ';
});

$app->post('/add_user', function(Request $request) use($app) {
    // See if user checked out 
    // Get user 

    $token = $app['security.token_storage']->getToken();
    if (null !== $token) {
        $user = $token->getUser();
        return "Got Username: " . $user->getUsername() . " pass : " . $user->getPassword();
    }

    return "No User :( user:  " . $request->get('username');
});


$app->post('/login_check', function(Request $request) use($app) {
    // See if user checked out 
    // Get user 

    $token = $app['security.token_storage']->getToken();
    if (null !== $token) {
        $user = $token->getUser();
        $app['monolog']->debug('/login_check : user logged in successfully');
        return "Got Username: " . $user->getUsername() . " pass : " . $user->getPassword();
    }
    $app['monolog']->debug('/login_check : login failed');
    return "No User :( user:  " . $request->get('username');
});


$app->get('/hello/{name}', function ($name) use ($app) {

      return 'Hello '.$app->escape($name);
});

// Mount rxnorm controllers
$app->mount('/rxnorm', include 'rxnorm_controllers.php');

// Mount tools 
$app->mount('/tools', include 'tool_controllers.php');


// Allow cors from anywhere : TODO limit when pushed live 
//$app->after(function (Request $request, Response $response) {
 //   $response->headers->set('Access-Control-Allow-Origin', '*');
//});

$app->run();
