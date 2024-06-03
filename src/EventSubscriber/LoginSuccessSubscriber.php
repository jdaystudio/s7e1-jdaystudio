<?php
// src/EventSubscriber/LoginSuccessSubscriber.php
/**
 * Records the sessionID and time when a user logins successfully.
 * These are required for our single session and auto eject code.
 *
 * Using a subscriber (rather than a Listener) as in the Docs it says ...
 * ... because the knowledge of the events is kept in the class rather than in the service definition...
 * ... This is the reason why Symfony uses subscribers internally;
 *
 * @author John Day jdayworkplace@gmail.com
 */

namespace App\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use App\Entity\User;

class LoginSuccessSubscriber implements EventSubscriberInterface
{

    public function __construct(private EntityManagerInterface $em){}

    /**
     * I believe this means that on a LoginSuccessEvent object creation the method declared is called
     * I located this event by looking at the vendor/symfony code
     *
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        /* @var User $user */
        $user = $event->getUser();
        $user
            ->setSid($event->getRequest()->getSession()->getId())
            ->setLastLoginAt(new \DateTimeImmutable());
        $this->em->persist($user);
        $this->em->flush();
    }

}