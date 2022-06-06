<?php

namespace App\Security\Authentication;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler as BaseAuthenticationSuccessHandler;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private AuthenticationSuccessHandlerInterface $baseHandler;

    public function __construct(BaseAuthenticationSuccessHandler $baseHandler, UserRepository $repository)
    {
        $this->baseHandler = $baseHandler;
        $this->repository = $repository;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $user = $token->getUser();
        $id = $user->getUserIdentifier();
        $find = $this->repository->findOneBy(['email' => $id]);

        $activate = $find->getIsActive();

        if ($activate === false) {

            return new JsonResponse('E-Mail non vérifié', Response::HTTP_FORBIDDEN);
        }

        return $this->baseHandler->onAuthenticationSuccess($request, $token);
    }
}