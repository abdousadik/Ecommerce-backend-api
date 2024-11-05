<?php

namespace App\EventListener;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class UserLoginListener
{
    private $entityManager;
    private $requestStack;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();

        // Ensure the user is an instance of your User entity
        if ($user instanceof \App\Entity\User) {
            $request = $this->requestStack->getCurrentRequest();

            // Update last login IP and date
            $user->setLastIp($request->getClientIp());
            $user->setLastLogin(new DateTime());

            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }
}