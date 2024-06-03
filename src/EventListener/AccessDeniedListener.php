<?php
// src/EventListener/AccessDeniedListener.php
/**
 * Return 403 for XHR requests,
 * allowing the possibility to redirect in our JS code when the user becomes logged out
 * we have to catch these here, before the firewall performs a 302
 * (this was the only method I could get to work at the time of writing)
 *
 * and a 403 with a redirect for all other AccessDenied instances
 * (eg when our Voter detects user has logged in elsewhere)
 *
 * @author John Day jdayworkplace@gmail.com
 */

namespace App\EventListener;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AccessDeniedListener implements EventSubscriberInterface
{
    public function __construct(
        private RequestStack $request,
        private Security $security
    ){}

    public static function getSubscribedEvents(): array
    {
        return [
            // the priority must be greater than the Security HTTP ExceptionListener, to make sure it's called before
            // the default exception listener
            KernelEvents::EXCEPTION => ['onKernelException', 2],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (!$exception instanceof AccessDeniedException) {
            return;
        }

        // if request was for an API route, report a 403, then we handle it in javascript
        if (preg_match("/^(\/api\/).*/",$event->getRequest()->getRequestUri())){
            // this also stops propagation
            $event->setResponse(new Response(null, 403));
            return;
        }

        $user = $this->security->getUser();
        if ($user
            && null != $user->getSid()
            && $user->getSid() == $this->request->getSession()->getId()
        ){
            // if user is still logged in here then just take them home
            $event->setResponse(new RedirectResponse("/", 302));
        }else {
            // otherwise they have been auto logged out
            $event->setResponse(new RedirectResponse("/login", 302));
        }
    }
}