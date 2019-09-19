<?php

namespace App\Security;


use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    public const CREATE = "user-create";
    public const READ = "user-read";
    public const LIST = "user-list";
    public const UPDATE = "user-update";
    public const DELETE = "user-delete";

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

        if (!$subject instanceof User && !is_null($subject)) {
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

        if (in_array($attribute, [self::CREATE, self::LIST])) {
            // The current user must be admin
            return in_array(User::ROLE_ADMIN, $currentUser->getRoles());
        }

        if (in_array($attribute, [self::READ, self::UPDATE, self::DELETE])) {
            // The current user must be the same as the requested user or must be an admin
            return $currentUser->getId() === $requestedUser->getId() || in_array(User::ROLE_ADMIN, $currentUser->getRoles());
        }

        throw new \LogicException('This code should not be reached!');
    }
}
