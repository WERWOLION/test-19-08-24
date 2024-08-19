<?php

namespace App\Security;

use App\Entity\User;
use App\Entity\Project;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ActivateUserVoter extends Voter
{

    private $requestStack;

    function __construct(RequestStack $requestStack) {
        $this->requestStack= $requestStack;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, ["isUserEmailConfirmed", "isUserApproved", "isPartnerActive"])) {
            return false;
        }
        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            // пользователь должен быть в системе; если нет - отказать в доступе
            return false;
        }

        if(in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_MANAGER', $user->getRoles())){
            return true;
        }

        switch ($attribute) {
            case 'isUserEmailConfirmed':
                return $user->getIsEmailConfirm();
            case 'isPartnerActive':
                if(!$user->getPartner() || !$user->getPartner()->getType()) return false;
                if(!$user->getPartner()->getBitrixContactID()) return false;
                return true;
        }
    }
}
