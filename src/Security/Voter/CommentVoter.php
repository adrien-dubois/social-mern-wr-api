<?php

namespace App\Security\Voter;

use App\Entity\Comment;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class CommentVoter extends Voter
{

    // Construct the Security to get the current user
    private $security;
    public function __construct(Security $security){
        $this->security = $security;
    }

    public const EDIT = 'edit';
    public const DELETE = 'delete';

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE])
            && $subject instanceof \App\Entity\Comment;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        // If user is an Admin alway allow access
        if ($this->security->isGranted('ROLE_ADMIN')){
            return true;
        }

        /** @var Comment $comment */
        $comment = $subject;

        if(null === $comment->getCommenter()) return false;

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case self::EDIT:
                return $this->canEdit($comment, $user);
                break;
            case self::DELETE:
                return $this->canDelete($comment, $user);
                break;
        }

        return false;
    }

    private function canEdit(Comment $comment,$user)
    {
        return $user === $comment->getCommenter();
    }
    private function canDelete(Comment $comment,$user)
    {
        return $user === $comment->getCommenter();
    }
}
