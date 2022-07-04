<?php

namespace App\Service;

use App\AppException\AppException;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService{


    private RequestBodyValidatorService $_requestBodyValidatorService;
    private UserPasswordHasherInterface $_userPasswordHasher;
    private EntityManagerInterface $_entityManager;
    private UserRepository $_userRepository;

    public function __construct(RequestBodyValidatorService $requestBodyValidatorService, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, UserRepository $userRepository)
    {
        $this->_requestBodyValidatorService = $requestBodyValidatorService;
        $this->_userPasswordHasher = $userPasswordHasher;
        $this->_entityManager = $entityManager;
        $this->_userRepository = $userRepository;
    }

    public function createUser(array $userData){
        $userRegistrationRequiredAttributes = ['email', 'password', 'password_confirm'];
        
        //Validate user data
        
        $bodyValidationResult = $this->_requestBodyValidatorService->bodyIsValid($userRegistrationRequiredAttributes, $userData);
        
        //If user data is invalid, we raise an exception that will be caught by our controller
        
        if(count($bodyValidationResult) > 0)
        {
            throw new AppException($bodyValidationResult, Response::HTTP_BAD_REQUEST);
        }

         //We make sure that user isn't already registered
         if (count($this->_userRepository->findBy(['email' => $userData['email']])) > 0) {
            throw new AppException(['Email address already exists'], Response::HTTP_CONFLICT);
        }

        //We also check if the two passwords matches, it's a bit odd because that supposed to be the work of
        //our request body validator

        if(strcmp($userData['password'], $userData['password_confirm']) != 0 ) throw new AppException(['password'=> 'Password and confirm must match'], Response::HTTP_BAD_REQUEST);

        // If everything is ok we persist user in our database

        $userEmail = $userData['email'];
        $userPlainTextPassword = $userData['password'];

        $user = new User();

        $uuid = substr(bin2hex(openssl_random_pseudo_bytes(16)), 0, 16);
        //We hash our user password
        $userHashedPassword = $this->_userPasswordHasher->hashPassword($user, $userPlainTextPassword);

        $user->setEmail($userEmail)
            ->setPassword($userHashedPassword)
            ->setUuid($uuid);
        
        $this->_entityManager->persist($user);
        $this->_entityManager->flush();
        return $user; //We return our new user to our controller;
    }

    public function authenticateUser(array $userData){
        $userLoginRequiredAttributes = ['email', 'password'];
        
        //Validate user data
        
        $bodyValidationResult = $this->_requestBodyValidatorService->bodyIsValid($userLoginRequiredAttributes, $userData);
        
        //If user data is invalid, we raise an exception that will be caught by our controller
        
        if(count($bodyValidationResult) > 0)
        {
            throw new AppException($bodyValidationResult, Response::HTTP_BAD_REQUEST);
        }

        //We search for user in database
        $user = $this->_userRepository->findOneBy(['email'=> $userData['email']]);

        //If user is not found or password does not match
        if (!$user || !$this->_userPasswordHasher->isPasswordValid($user, $userData['password'])) {
            throw new AppException(['Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }
        //If everything is fine we pass the user to our controller

        return $user;
    }
}