<?php
// src/Entity/User.php
/**
 * Minimal User Object
 *
 * with
 * - custom UniqueUserName validator rule
 * - optional password strength validation rule
 * - enforced default and unique roles
 * - Auto encrypted passwords (via listener)
 *
 * @author John Day jdayworkplace@gmail.com
 */

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Validator as NameAssert;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\EquatableInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_NAME', fields: ['name'])]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface, EquatableInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var string in this case our identifier
     */
    #[ORM\Column(length: 60)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 3,
        max: 60,
        minMessage: 'User name must be at least {{ limit }} characters long',
        maxMessage: 'User name cannot be longer than {{ limit }} characters'
    )]
    #[NameAssert\UniqueUserName(
        groups:['freshName']
    )]
    #[NameAssert\UniqueUserName(
        profileMode: true,
        groups:['profileName'],
    )]
    private string $name = '';

    /**
     * @var list<string> The user roles
     *
     * NOTE: automatic migration declares as CLOB
     */
    #[ORM\Column(
        type: Types::JSON
    )]
    private array $roles = ['ROLE_USER'];

    /**
     * @var string|null The hashed password
     */
    #[ORM\Column(
        nullable: true
    )]
    private ?string $password = null;

    /**
     * @var string|null session id
     */
    #[ORM\Column(
        length: 128,
        nullable: true,
    )]
    private ?string $sid = null;

    /**
     * @var \DateTimeImmutable|null
     */
    #[ORM\Column(
        type: Types::DATETIME_IMMUTABLE,
        nullable: true,
     )]
     private ?\DateTimeImmutable $lastLoginAt = null;

    /**
     * @var \DateTimeImmutable|null
     */
    #[ORM\Column(
        type: Types::DATETIME_IMMUTABLE,
        nullable: true,
    )]
    private ?\DateTimeImmutable $created = null;

    /**
     * Using a separate plain password field for forms
     * this gets auto hashed to the password field when persisted via a listener
     *
     * NOTE: this is NOT a database column
     *
     * @var string|null
     */
    #[Assert\Length(
        max: 60,
        maxMessage: 'Password cannot be longer than {{ limit }} characters'
    )]
    #[Assert\NotBlank(
        allowNull: true,
        groups : ['password','strict'],
    )]
    #[Assert\PasswordStrength(
        groups : ['strict'],
        message : 'The password strength is too low: Increase its entropy using its length and/or number of unique characters.',
    )]
    private ?string $plainpassword = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->name;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $this->guaranteeDefaultAndUniqueRoles();
        return $this->roles;
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        $this->guaranteeDefaultAndUniqueRoles();
        return $this;
    }

    /**
     * @param string $role full role name ie ROLE_ADMIN
     * @return bool
     */
    public function hasRole(string $role):bool{
        return in_array($role,$this->getRoles());
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }


    public function getSid(): ?string
    {
        return $this->sid;
    }

    public function setSid(?string $sid): static
    {
        $this->sid = $sid;
        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $datetime): static
    {
        $this->lastLoginAt = $datetime;
        return $this;
    }

    /**
     * An in memory only property to temporarily hold the plain text password before it gets hashed
     *
     * @return string|null
     */
    public function setPlainPassword(?string $plainpassword): static
    {
        $this->plainpassword = $plainpassword ;
        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainpassword ;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // clear temporary, sensitive data
        $this->setPlainPassword(null);
    }

    public function guaranteeDefaultAndUniqueRoles(){
        if (!in_array('ROLE_USER',$this->roles)){
            $this->roles[]='ROLE_USER';
        }
        $this->roles = array_unique($this->roles);
    }

    /**
     * Logging out another user seems quite difficult to get to grips with (well for me in the first couple of weeks).
     *
     * However since I monitor the sessionID and clear it
     * - on logout (via logout event listener)
     * - on timeout (via the UserProcess service)
     * I can detect the change here, and return false to cause a login request.
     *
     * This is called on all requests if the user has not been logged out.
     * (via Symfony/security-http/Firewall/ContextListener)
     *
     * @param UserInterface $user
     * @return bool
     */
    public function isEqualTo(UserInterface $user): bool{
        $result = true;

        // would have liked to also compare passwords and sids
        if ($this->getUserIdentifier() !== $user->getUserIdentifier()
        || $this->getRoles() !== $user->getRoles()){
            $result = false;
        }

        if ($result){
            if (is_null($this->sid)) $result = false;
        }
        return $result;
    }

    /**
     * Wasn't going to bother with this field, however required for calculation if using auto delete and no login has happened
     * You could use the StofDoctrineExtensionsBundle, but I didn't really want another bundle for this example,
     * and I already have a Listener setup
     *
     * @param \DateTimeImmutable|null $datetime
     * @return $this
     */
    public function setCreated(?\DateTimeImmutable $datetime): static
    {
        $this->created = $datetime;
        return $this;
    }
    public function getCreated(): ?\DateTimeImmutable
    {
        return $this->created;
    }


}
