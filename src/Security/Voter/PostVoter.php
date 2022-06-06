<?php

namespace App\Security\Voter;

use App\Entity\Post;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class PostVoter extends Voter
{

    // Construct the Security to get the current user
    private $security;
    public function __construct(Security $security){
        $this->security = $security;
    }

    // Declare the constant of the methods we want vote to
    public const EDIT = 'edit';
    public const DELETE = 'delete';

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE])
            && $subject instanceof \App\Entity\Post;
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

        /**@var Post $post */
        $post = $subject;

        // check if the post has a creator
        if(null === $post->getAuthor()) return false;

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case self::EDIT:
                return $this->canEdit($post, $user);
                break;
            case self::DELETE:
                return $this->canDelete($post, $user);
                break;
        }

        return false;
    }

    // Check if we are the owner of the announce
    // If true, we can edit or delete it

    private function canEdit(Post $post, $user)
    {
        return $user === $post->getAuthor();
    }
    private function canDelete(Post $post, $user)
    {
        return $user === $post->getAuthor();
    }
}
