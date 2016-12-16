<?php
// src/AppBundle/Security/TokenAuthenticator.php
namespace Cpm\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Cpm\Db;
class TokenAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * Get the authentication credentials from the request and return them
     * as any type (e.g. an associate array). If you return null, authentication
     * will be skipped.
     *
     * Whatever value you return here will be passed to getUser() and checkCredentials()
    */
    public function getCredentials(Request $request)
    {
        $token = $request->headers->get('X-AUTH-TOKEN');
        if ($token) {
            // no token? Return null and no other methods will be called
            return array(
                'token' => $token,
            );
        }
        // What you return here will be passed to getUser() as $credentials
         
        // Handle json post data 
        if ($request->getContentType() == 'json'){
            $data = json_decode($request->getContent(), true);
            $db = new Db();
            $db->rx_error_log($data);
            $db->rx_error_log($request->getContent());
            $db->rx_error_log("content type: " . $request->getContentType());
            //$request->request->replace(is_array($data) ? $data : array());
            return $data;
        }
        
        // Otherwise regular form data 
        $username = $request->get('username');
        $password = $request->get('password');
       
        return array(
            'username' => $username,
            'password' => $password,
        );
    }

    /**
     * Return a UserInterface object based on the credentials.
     *
     * You may throw an AuthenticationException if you wish. If you return
     * null, then a UsernameNotFoundException is thrown for you.
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        //$apiKey = $credentials['token'];
        $apiKey = $credentials['username'];

        // if null, authentication will fail
        // if a User object, checkCredentials() is called
        return $userProvider->loadUserByUsername($apiKey);
    }

    /**
     * Returns true if the credentials are valid.
     *
     * If any value other than true is returned, authentication will
     * fail. You may also throw an AuthenticationException if you wish
     * to cause authentication to fail.
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        // check credentials - e.g. make sure the password is valid
        // no credential check is needed in this case
        

        // return true to cause authentication success
        if($user->isEqualTo(new User($credentials['username'],
            $credentials['password'],'abc123',array('Authenticated','Admin')))) {
            return true;
        }else{
            return false;
        }
        
    }

    /**
     * Called when authentication executed and was successful!
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
       
        //return $token;
    }

    /**
     * Called when authentication executed, but failed (e.g. wrong username password).
    */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = array(
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
            // or to translate this message
            // $this->translator->trans($exception->getMessageKey(), $exception->getMessageData())
        );

        return new JsonResponse($data, 403);
    }

    /**
     * Called when authentication is needed, but it's not sent
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = array(
            // you might translate this message
            'message' => 'Authentication Required'
        );

        return new JsonResponse($data, 401);
    }

    public function supportsRememberMe()
    {
        return false;
    }
}
