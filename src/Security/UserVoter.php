<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    public const CREATE = "create";
    public const READ = "read";
    public const LIST = "list";
    public const UPDATE = "update";
    public const DELETE = "delete";

    public const ACTIONS = [
        self::CREATE,
        self::READ,
        self::LIST,
        self::UPDATE,
        self::DELETE
    ];

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * @param string $attribute An attribute
     * @param mixed $subject The subject to secure, e.g. an object the user wants to access or any other PHP type
     *
     * @return bool True if the attribute and subject are supported, false otherwise
     */
    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, self::ACTIONS)) {
            return false;
        }

        return true;
    }

    /**
     * Perform a single access check operation on a given attribute, subject and token.
     * It is safe to assume that $attribute and $subject already passed the "supports()" method check.
     *
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $currentUser = $token->getUser();
        $requestedUser = $subject;

        // the current user must be logged in; if not, deny access
        if (!$currentUser instanceof User) {

            return false;
        }

        // The subject must be a User
        if (!$requestedUser instanceof User) {

            return false;
        }

        if (in_array($attribute, [self::READ, self::UPDATE, self::DELETE])) {
            // The current user must be the same as the requested user or must be an admin
            if ($currentUser->getId() === $requestedUser->getId() || in_array(User::ROLE_ADMIN, $currentUser->getRoles())) {
                return true;
            }

            return false;
        }

        if (in_array($attribute, [self::CREATE, self::LIST])) {
            // The current user must be admin
            if (in_array(User::ROLE_ADMIN, $currentUser->getRoles())) {
                return true;
            }

            return false;
        }

        throw new \LogicException('This code should not be reached!');
    }
}
