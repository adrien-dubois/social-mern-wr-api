<?php

namespace App\Controller\Api\V1;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

/**
 * @Route("/api/v1/user", name="app_api_v1_user_", requirements={"id":"\d+"})
 */
class UserController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     *
     * @Route("/datas", name="currentUser", methods={"GET"})
     *
     * @param UserRepository $repository
     * @return JsonResponse
     */
    public function getCurrentUser( UserRepository $repository): JsonResponse
    {
        $currentUser = $this->security->getUser()->getUserIdentifier();
        $user = $repository->findOneBy(array('email' => $currentUser));

        return $this->json($user, 200, [], [
            "groups" => "user"
        ]);
    }

    /**
     *
     * @Route("/", name="all", methods={"GET"})
     *
     * @param UserRepository $repository
     * @return JsonResponse
     */
    public function getAll(UserRepository $repository): JsonResponse
    {
        $users = $repository->findAll();

        return $this->json($users, 200, [], [
        "groups" => "user"
        ]);
    }


    /**
     * Add a follower
     *
     * @Route("/follow/{id}", name="follow", methods={"PATCH"})
     *
     * @param int $id
     * @param UserRepository $repository
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function follow(int $id, UserRepository $repository, EntityManagerInterface $em): JsonResponse
    {
        $currentUser = $this->security->getUser();
        $follow = $repository->find($id);
        $currentUser->addFollowing($follow);

        $em->flush();

        return $this->json(['message' => "Follow new user"], 200, [], [
            "groups" => "follow"
        ]);
    }

    /**
     * Follow list of the current user
     *
     * @Route("/follow/", name="follow", methods={"GET"})
     *
     * @param UserRepository $repository
     * @return JsonResponse
     */
    public function followList(UserRepository $repository): JsonResponse
    {
        $currentUser = $this->security->getUser();
        $profile = $repository->findOneBy(['email' => $currentUser->getUserIdentifier()]);

        return $this->json($profile, 200, [],[
            "groups" => "follow"
        ]);
    }

}
