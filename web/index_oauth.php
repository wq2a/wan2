<?php
require_once __DIR__ . '/../vendor/autoload.php';
// This is client id for oath and the secret 
define ('GOOGLE_API_KEY', '67953830588-tursupli3tcvcmj0jipaqlha4lcogt9c.apps.googleusercontent.com');
//this is google api key 'AIzaSyADt7UuV1IPMUi99yLtVMNss3WFXfoRR-c');
define('GOOGLE_API_SECRET',   'rB0GY2S9tui0FBKTmPU2NwrP');

$app = new Silex\Application();
error_reporting(E_ALL);
ini_set('display_errors', 1);
$app['debug'] = true;
$app->register(new Gigablah\Silex\OAuth\OAuthServiceProvider(), array(
    'oauth.services' => array(
        
       
        'Google' => array(
            'key' => GOOGLE_API_KEY,
            'secret' => GOOGLE_API_SECRET,
            'scope' => array(
                'https://www.googleapis.com/auth/userinfo.email',
                'https://www.googleapis.com/auth/userinfo.profile'
            ),
            'user_endpoint' => 'https://www.googleapis.com/oauth2/v1/userinfo'
        )
    )
));
// Provides URL generation
// not in silex 2.0 
//$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
// Provides CSRF token generation
$app->register(new Silex\Provider\FormServiceProvider());
// Provides session storage
$app->register(new Silex\Provider\SessionServiceProvider(), array(
    'session.storage.save_path' => __DIR__ . '/../cache'
));
// Provides Twig template engine
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__
));
$app->register(new Silex\Provider\SecurityServiceProvider(), array(
    'security.firewalls' => array(
        'default' => array(
            'pattern' => '^/',
            'anonymous' => true,
            'oauth' => array(
                //'login_path' => '/auth/{service}',
                //'callback_path' => '/auth/{service}/callback',
                //'check_path' => '/auth/{service}/check',
                'failure_path' => '/',
                'with_csrf' => true
            ),
            'logout' => array(
                'logout_path' => '/logout',
                'with_csrf' => true
            ),
            'users' => new Gigablah\Silex\OAuth\Security\User\Provider\OAuthInMemoryUserProvider()
        )
    ),
    'security.access_rules' => array(
        array('^/auth', 'ROLE_USER')
    )
));
$app->before(function (Symfony\Component\HttpFoundation\Request $request) use ($app) {
    $token = $app['security']->getToken();
    $app['user'] = null;
    if ($token && !$app['security.trust_resolver']->isAnonymous($token)) {
        $app['user'] = $token->getUser();
    }
});
$app->get('/', function () use ($app) {
    return $app['twig']->render('index.twig', array(
        'login_paths' => $app['oauth.login_paths'],
        'logout_path' => $app['url_generator']->generate('logout', array(
            '_csrf_token' => $app['oauth.csrf_token']('logout')
        ))
    ));
});
$app->match('/logout', function () {
})->bind('logout');


$app->run();


