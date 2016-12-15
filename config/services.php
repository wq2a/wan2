<?php 
// api/config/services.php
use Symfony\Component\DependencyInjection\Definition;


$container->setDefinition(
    'app.cpm_user_provider',
    new Definition('Cpm\Security\UserProvider')
);

$container->setDefinition(
	'app.cpm_token_authenticator', 
	new Definition('Cpm\Security\TokenAuthenticator'));
?>