<?php

namespace App\EventListener;


use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthenticationSuccessListener{

    private $repository;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UserRepository $repository, UrlGeneratorInterface $urlGenerator)
    {
        $this->repository = $repository;
        $this->urlGenerator = $urlGenerator;
    }

    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event){

        $data = $event->getData();
        $user = $event->getUser();

        if(!$user instanceof UserInterface){
            return;
        }

        $users = $user->getUserIdentifier();

        $find = $this->repository->findOneBy(['email' => $users]);

        $token = $find->getActivationToken();

        if($token){
            new JsonResponse('Ce compte n\'a pas encore été activé.', JsonResponse::HTTP_UNAUTHORIZED);
        }

        $name = $find->getPseudo();
        $id = $find->getId();
        $role = $find->getRoles();
        $mail = $find->getEmail();
        $picture = $find->getPicture();

        $data['data'] = array(
            'id' => $id,
            'email' => $mail,
            'name' => $name,
            'picture' => $picture,
            'roles' => $role
        );

        $event->setData($data);

    }
}