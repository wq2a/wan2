<?php 
// config/security.php -- configure security for app 
use Symfony\Component\DependencyInjection\Definition;
$container->loadFromExtension('security', array(
    'encoders' => array(
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
    ),
    */
    'firewalls' => array(
        'main' => array(
            'pattern'        => '^/*',
            /*'http' => true,*/
            'guard'          => array(
                'authenticators'  => array(
                    'app.cpm_token_authenticator'
                )
            ),
            //'provider' => 'cpm_users'
            'users' => function () use ($app) {
                return new Cpm\Security\UserProvider($app['db']);
            },
    	),
    )
    ));
?>