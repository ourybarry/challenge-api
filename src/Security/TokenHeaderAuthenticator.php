<?php

namespace App\Security;

use App\Repository\UserRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class TokenHeaderAuthenticator extends AbstractAuthenticator{
    static public $TOKEN_HEADER_KEY = 'x-auth-token';
    private $parametersBag;
    public function __construct(UserRepository $userRepository, ParameterBagInterface $parametersBag)
    {
        $this->userRepository = $userRepository;
        $this->parametersBag = $parametersBag;
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has(TokenHeaderAuthenticator::$TOKEN_HEADER_KEY);
    }
    public function authenticate(Request $request): Passport
    {
        $token = $request->headers->get(TokenHeaderAuthenticator::$TOKEN_HEADER_KEY);
        if(null === $token){
            return new JsonResponse(['message'=> 'Missing token'], Response::HTTP_UNAUTHORIZED);
        }
        
        $user = $this->retrieveUserFromToken($token);

        return new SelfValidatingPassport(new UserBadge($user['email']));
    }
    public function onAuthenticationSuccess(Request $request,TokenInterface $token, string $firewallName): ?Response
    {
        $token = $request->headers->get(TokenHeaderAuthenticator::$TOKEN_HEADER_KEY);
        $user = $this->retrieveUserFromToken($token);
        $request->headers->set('user', $user['id']); //Save user id in request header for future use
        return null; // The request continue
    }
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['message'=>'Authentication Failure'], Response::HTTP_UNAUTHORIZED);
    }

    public function retrieveUserFromToken($token){
        $secret = $this->parametersBag->get('app.jwt_secret');
        $algo = $this->parametersBag->get('app.jwt_algo');
        
        $decodedToken = (array) JWT::decode($token, new Key($secret, $algo));

        $userIdentifier = (array) $decodedToken['user'];
        return $userIdentifier;
    }
}
