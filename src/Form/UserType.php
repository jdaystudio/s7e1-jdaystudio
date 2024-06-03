<?php
// src/Form/UserType.php
/**
 * Simple user details data entry, however, with multiple validation rules depending on context.
 * More details available below.
 *
 * @author John Day jdayworkplace@gmail.com
 */
namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bundle\SecurityBundle\Security;

class UserType extends AbstractType
{

    public function __construct(private Security $security, private RequestStack $requestStack){}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /**
         *  want to control the password field requirement depending on route (profile)
         *  This can't rely solely on the validation rules as they are only enforced on form submission
         *  - therefore we'll modify the requirement at this point, as well as in validation
         * - otherwise password is always marked as required
         */
        $route = $this->requestStack->getCurrentRequest()->attributes->get('_route');

        $builder
            ->add('name', TextType::class, ['attr'=>['placeholder'=>'Between 3 and 180 characters']])
            ->add('plainpassword', PasswordType::class,[
                'required' => $route != 'profile'
            ])
            ->add('save', SubmitType::class)
        ;

    }

    /**
     * In this case the form validation is a using the asserts defined in the Entity
     * the data_class maps this FORM to USER ENTITY (and therefore the asserts within the user class)
     *
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'roles' => ['ROLE_USER'],
            'validation_groups' => function(FormInterface $form):array{
                $validationGroups = ['Default'];

                $route = $this->requestStack->getCurrentRequest()->attributes->get('_route');

                /* @var User $data */
                $data = $form->getData();

                /**
                 * a contrived setup for example/research purposes
                 * attempting to stay within the validation system, rather than adding logic elsewhere
                 *
                 * Username has to be unique on creation
                 * and can stay the same on a profile update
                 *
                 * Password validation depends on route and auth of session user
                 * - when the admin is creating a user then can set a simple password
                 * - when user updating profile - password can be left blank to indicate no change
                 */

                if ($route == 'profile') {
                    $validationGroups = array_merge($validationGroups, ['profileName']);

                    if ($data->getPlainpassword()) {
                        $validationGroups = array_merge($validationGroups, ['password', 'strict']);
                    }

                }else{

                    $validationGroups = array_merge($validationGroups, ['freshName']);

                    if ($this->security->isGranted('ROLE_ADMIN')) {
                        $validationGroups = array_merge($validationGroups, ['password']);
                    } else {
                        $validationGroups = array_merge($validationGroups, ['password', 'strict']);
                    }

                }

                return $validationGroups;
            }
        ]);
    }
}
