<?php
// src/Service/UserProcess.php
/**
 * As a public facing demo, I want to auto eject and auto delete users
 * The admin user is recreated with defaults after deletion.
 *
 * This is the state machine for managing that, and also reporting to the live status feeds.
 *
 * @author John Day jdayworkplace@gmail.com
 */

namespace App\Service;

use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class UserProcess
{
    // valid state values
    const DELETED = 'DELETED';
    const PENDING_LOG_OUT_REMOTE = 'PENDING_LOG_OUT_REMOTE';
    const PENDING_LOG_OUT_LOCAL = 'PENDING_LOG_OUT_LOCAL';
    const PENDING_DELETE = 'PENDING_DELETE';
    const LOGGED_OUT = 'LOGGED_OUT';

    private int $auto_logout_seconds;
    private int $auto_delete_seconds;

    private bool $check_remote_logout = true;

    public function __construct(
        private RequestStack $request,
        private EntityManagerInterface $em,
        private ContainerBagInterface $params,
        private Kernel $kernel,
    ){
        // cache these, and allow exception if parameter is missing
        $this->auto_logout_seconds = $this->params->get('app.auto_logout_seconds');
        $this->auto_delete_seconds = $this->params->get('app.auto_delete_seconds');
    }

    /**
     * Returns
     * [
     *  id: user_id
     *  state : PENDING_LOG_OUT_LOCAL | PENDING_LOG_OUT_REMOTE | LOGGED_OUT | PENDING_DELETE | DELETED
     *  seconds : time left in pending state | 0
     * ]
     *
     * @param int $id
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function process(int $id):array{
        $result = [
            'id' => $id,
            'state' => self::DELETED,
            'seconds' => 0,
        ];

        /* @var User $user */
        if ($user = $this->em->getRepository(User::class)->find($id)) {

            $result['seconds'] = $this->autoLogout($user);

            // null falls through to deletion logic
            if ($this->check_remote_logout
                && null != $user->getSid()
                && $user->getSid() !== $this->request->getSession()->getId())
            {

                // We are not able to push to client, but can report that user has logged in elsewhere
                $result['state'] = self::PENDING_LOG_OUT_REMOTE;

            }else {

                if ($result['seconds'] > 0) {

                    $result['state'] = self::PENDING_LOG_OUT_LOCAL;

                } else {

                    if ($this->params->get('app.auto_delete_enabled')) {

                        // special case for admin, in this public example we allow deletion but recreate admin
                        $wasAdmin = $user->hasRole('ROLE_ADMIN');

                        $result['seconds'] = $this->autoDelete($user);
                        if ($result['seconds'] > 0) {
                            $result['state'] = self::PENDING_DELETE;
                        } else {

                            // follow up the special case, but is still reported that they were deleted in this call
                            if ($wasAdmin) {
                                $newUser = $this->em->getRepository(User::class)->createPublicAdmin();
                                $result['id'] = $newUser->getId();
                            }
                            $result['state'] = self::DELETED;

                        }

                    } else {

                        // only happens if we have turned off auto delete
                        $result['state'] = self::LOGGED_OUT;
                    }

                }
            }
        }

        return $result;
    }

    /**
     * All users are auto logged out after a set time
     * Reason: the demo is publicly available and I want to garbage collect users
     *
     * @param User $user
     * @return int number of seconds left before logout | 0
     */
    private function autoLogout(User $user):int{
        $seconds_left = 0;

        // is currently logged in
        if ($user->getSid()) {
            $seconds_since = $this->secondsSinceLastLogin($user);
            $seconds_left = max($this->auto_logout_seconds - $seconds_since, 0);

            if ($seconds_left == 0) {
                // clear sid so the user will be auto logged out (performed via User::isEqualTo)
                $user->setSid(null);
                $this->em->flush();
            }
        }

        return $seconds_left;
    }

    /**
     *  Returns seconds since last login time
     *
     * @param User $user
     * @return int
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function secondsSinceLastLogin(User $user):int{
        $interval = 0;

        if ($lastLoggedInAt = $user->getLastLoginAt()) {
            $now = new \DateTimeImmutable();
            $interval = $now->getTimestamp() - $lastLoggedInAt->getTimestamp();
        }

        return $interval;
    }

    /**
     * This is called when a user is not logged in
     * Auto delete is triggered when
     *  - time since last_login 'is greater than'  auto_logout_period + auto_delete_period
     *
     * @param User $user
     * @return int seconds left until delete | 0
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function autoDelete(User $user):int{
        $seconds = $this->secondsSinceLastLogin($user);

        $total_lifetime_allowed = $this->auto_logout_seconds + $this->auto_delete_seconds;

        // if they never log in, lets time it from creation
        if (!$user->getLastLoginAt()){
            $now = new \DateTimeImmutable();
            $seconds = $now->getTimestamp() - $user->getCreated()->getTimestamp();
        }

        $seconds = max($total_lifetime_allowed - $seconds , 0);

        if ($seconds == 0 ) {
            $this->em->remove($user);
            $this->em->flush();
        }

        return $seconds;
    }

    /**
     * During some Integration tests we need to turn this off because we don't have any remote session states
     *
     * @param bool $enabled
     * @return void
     */
    public function setCheckRemoteLogout(bool $enabled):void{
        $this->check_remote_logout = $enabled;
    }

}