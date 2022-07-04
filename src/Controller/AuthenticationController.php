<?php

namespace App\Controller;

use App\AppException\AppException;
use App\Service\UserService;
use Firebase\JWT\JWT;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[Route('/api/v1')]
class AuthenticationController extends AbstractController
{


    private UserService $_userService;
    private NormalizerInterface $_normalizer;

    public function __construct(UserService $userService, NormalizerInterface $normalizer)
    {
        $this->_userService = $userService;
        $this->_normalizer = $normalizer;
    }

    #[Route('/auth/register', name: 'app_user_registration', methods: ['POST'])]
    public function registerUser(Request $request): Response
    {
        try {
            $userData = $request->toArray();
            $user = $this->_userService->createUser($userData);
            return $this->json(['user' => $user]); //TODO: serialize data before returning it to client
        } catch (AppException $exception) {
            return $this->json(['error' => $exception->getErrorMessage()], $exception->getStatusCode());
        }
    }

    #[Route('/auth/login', name: 'app_user_authentication', methods: ['POST'])]
    public function authenticateUser(Request $request): Response
    {
        try {
            $userData = $request->toArray();
            $user = $this->_userService->authenticateUser($userData);
            $normalizedData = $this->_normalizer->normalize($user, null, [
                AbstractNormalizer::ATTRIBUTES => ['id','email', 'roles']
            ]);
            //JSON web token
            $payload = [
                'iat' => (new \DateTime())->getTimestamp(),
                'exp' => (new \DateTime())->modify('1 day')->getTimestamp(),
                'user' => $normalizedData
            ];
            $secret = $this->getParameter('app.jwt_secret');
            $algo = $this->getParameter('app.jwt_algo');

            $token = JWT::encode($payload, $secret, $algo);

            return $this->json(['token' => $token]); //We return a jwt token as response to our client
        } catch (AppException $exception) {
            return $this->json(['error' => $exception->getErrorMessage()], $exception->getStatusCode());
        }
    }
}
