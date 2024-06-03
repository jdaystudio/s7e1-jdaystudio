<?php
// src/EventSubscriber/LogoutSubscriber.php
/**
 * Record the logout time.
 * Required for single session and auto delete code.
 *
 * @author John Day jdayworkplace@gmail.com
 */
namespace App\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use App\Entity\User;

class LogoutSubscriber implements EventSubscriberInterface
{

    public function __construct(private EntityManagerInterface $em){}

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        /* @var User $user */
        $user = $event->getToken()->getUser();
        $user->setSid(null);
        $this->em->persist($user);
        $this->em->flush();
    }

}