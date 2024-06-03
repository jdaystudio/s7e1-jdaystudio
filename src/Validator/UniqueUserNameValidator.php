<?php
// src/Validator/UniqueUserNameValidator.php
/**
 * Our custom validator.
 * Enforce unique names (unless updating your profile, in which can use your current name again)
 *
 * @author John Day jdayworkplace@gmail.com
 */
namespace App\Validator;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class UniqueUserNameValidator extends ConstraintValidator
{

    public function __construct(private EntityManagerInterface $em, private Security $security){}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueUserName) {
            throw new UnexpectedTypeException($constraint, UniqueUserName::class);
        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) to take care of that
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            // throw this exception if your validator cannot handle the passed type so that it can be marked as invalid
            throw new UnexpectedValueException($value, 'string');
        }

        $userRepo = $this->em->getRepository(User::class);
        $found_user = $userRepo->findOneBy(['name' => $value]);

        // check username is unique, unless for profile in which case allow through if same user returned
        $error = false;

        if ($found_user) {

            if ($constraint->profileMode) {
                /* @var User $sessionUser */
                $sessionUser = $this->security->getUser();
                if ($found_user->getId() != $sessionUser->getId()) {
                    $error = true;
                }
            }else{
                $error = true;
            }

        }

        if ($error){
            $this->context->buildViolation($constraint->message)
                ->atPath('name')
                ->addViolation()
            ;
        }

    }
}