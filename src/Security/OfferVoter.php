<?php

namespace App\Security;

use App\Entity\User;
use App\Entity\Project;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class OfferVoter extends Voter
{

    private $requestStack;
    
    function __construct(RequestStack $requestStack) {
        $this->requestStack= $requestStack;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, ["view", "edit"])) {
            return false;
        }
        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $offer, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if(!$user instanceof User) return false;
        if(in_array("ROLE_MANAGER", $user->getRoles()) ) return true;

        $result = true;

        switch ($attribute) {
            case 'view':
                $result = $this->canView($user, $offer);
                break;

            case 'edit':
                $result = $this->canEdit($user, $offer);
                break;
        }
        return $result;
    }

    protected function canView($user, $offer){
        if($offer->getUser()->getId() === $user->getId()) return true;
        return false;
    }

    protected function canEdit($user, $offer){
        if( $offer->getUser()->getId() === $user->getId() ) return true;
        return false;
    }
}