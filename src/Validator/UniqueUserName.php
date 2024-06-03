<?php
// src/Validator/UniqueUserName.php
/**
 * Register our custom user name validator.
 *
 * @author John Day jdayworkplace@gmail.com
 */
namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class UniqueUserName extends Constraint
{
    public string $message = 'Name already in use, please use another name';

    // optional parameter for this validator
    public bool $profileMode = false;

    // all configurable options must be passed to the constructor
    public function __construct(?bool $profileMode = null, array $groups = null, $payload = null)
    {
        // no mandatory parameters
        parent::__construct([], $groups, $payload);

        $this->profileMode = $profileMode ?? $this->profileMode;
    }

}