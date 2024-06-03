<?php
// src/Security/SameSessionVoter.php
/**
 * Used to create an access denied event if the user has been auto ejected or logged in elsewhere.
 *
 * @author John Day jdayworkplace@gmail.com
 */
namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SameSessionVoter extends Voter
{
    const SAME_SESSION = 'SAME_SESSION';
    
    protected function supports(string $attribute, mixed $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::SAME_SESSION])) {
            return false;
        }
        // only vote on `Request` routes
        if (!$subject instanceof Request) {
            return false;
        }

        return true;
    }

    /**
     * We are only letting the session continue in one browser, so if the user has logged in elsewhere their
     * session may not match the request, this then throws a AccessDenied which we catch with our listener
     *
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        return $user->getSid() == $subject->getSession()->getId();
    }

}