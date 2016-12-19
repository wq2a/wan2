<?php
namespace Cpm\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class UserToken
{
    private $user;
    private $secret = '1S.TBRDHv2uiMEFEGrVeZ6';

    public function getToken(UserInterface $user)
    {
        $header = '{"alg":"HS256","typ":"JWT"}';
        $payload = array(
            'iss'      => 'phewas.tech',
            // token expire a week from now
            'exp'      => time() + (7 * 24 * 60 * 60),
            'id'       => $user->getId(),
            'username' => $user->getUsername(),
            'roles'    => $user->getRoles(),
            'projects' => $user->getProjects(),
        );

        $payload = json_encode($payload);
        $encodedContent = base64_encode($header) . '.' . base64_encode($payload);
        $signature = $this->sign($encodedContent);
        return $encodedContent . '.' . $signature;
    }

    public function parseToken($token)
    {
        $token = explode('.', $token);
        $encodedContent = $token[0] . '.' . $token[1];
        $result = array(
            'header'    => base64_decode($token[0]),
            'payload'   => base64_decode($token[1]),
            'signature' => $token[2],
            'valid'     => $token[2]==$this->sign($encodedContent)
        );

        return $result;
    }

    private function sign($content)
    {
        return hash_hmac('sha256',$content,$this->secret);
    }
}
