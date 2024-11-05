<?php

namespace App\Controller;

use DateTime;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    public $em;
    public $passwordHasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher) {
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
    }
    
    #[Route('/user/signup', name: 'userSignup', methods: ['POST'])]
    public function signup(Request $request){
        $user = new User();

        $email = $request->get('email');
        if (is_null($email) || empty($email)) {
            return new JsonResponse('Email cannot be blank', Response::HTTP_BAD_REQUEST);
        }

        $exists = $this->em->getRepository(User::class)->findOneBy([
            'email' => $email
        ]);
        if($exists){
            return new JsonResponse('Email already used !', Response::HTTP_BAD_REQUEST);
        }
        $user->setEmail($email);

        $password = $request->get('password');
        if (is_null($password) || empty($password)) {
            return new JsonResponse('Password cannot be blank', Response::HTTP_BAD_REQUEST);
        }
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $firstName = $request->get('firstName');
        if (is_null($firstName) || empty($firstName)) {
            return new JsonResponse('FirstName cannot be blank', Response::HTTP_BAD_REQUEST);
        }
        $user->setFirstName($firstName);

        $lastName = $request->get('lastName');
        if (is_null($lastName) || empty($lastName)) {
            return new JsonResponse('LastName cannot be blank', Response::HTTP_BAD_REQUEST);
        }
        $user->setLastName($lastName);

        $phone = $request->get('phone');
        if (is_null($phone) || empty($phone)) {
            return new JsonResponse('Phone cannot be blank', Response::HTTP_BAD_REQUEST);
        }
        $user->setPhone($phone);

        $user->setRoles(['USER']);
        $user->setCreatedAt(new DateTimeImmutable());
        $user->setUpdatedAt(new DateTimeImmutable());

        $this->em->persist($user);
        $this->em->flush();
        
        return new JsonResponse(['code' => 200, 'message' => "User with email ".$request->get('email')." was created successfully!"], Response::HTTP_OK);
    }

    #[Route('/user/update', name: 'userUpdate', methods: ['PATCH'])]
    public function update(Request $request){
        $requestData = json_decode($request->getContent(), true);

        $user = $this->em->getRepository(User::class)->find($this->getUser()->getId());
        
        if (isset($requestData['firstName']) && !is_null($requestData['firstName']) && !empty($requestData['firstName'])) {
            $user->setFirstName($requestData['firstName']);
        }

        if (isset($requestData['lastName']) && !is_null($requestData['lastName']) && !empty($requestData['lastName'])) {
            $user->setLastName($requestData['lastName']);
        }

        if (isset($requestData['phone']) && !is_null($requestData['phone']) && !empty($requestData['phone'])) {
            $user->setPhone($requestData['phone']);
        }

        $user->setUpdatedAt(new DateTimeImmutable());

        $this->em->persist($user);
        $this->em->flush();

        return new JsonResponse(['message' => 'User updated successfully!'], Response::HTTP_OK);
    }
}