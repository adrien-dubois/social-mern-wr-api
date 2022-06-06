<?php

namespace App\Controller\Api\V1;

use App\Entity\Post;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api/v1/post", name="app_api_v1_post_", requirements={"id": "\d+"})
 */
class PostController extends AbstractController
{

    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * Method to get all the posts
     *
     * @Route("/", name="read_all", methods={"GET"})
     * @param PostRepository $repository
     * @return JsonResponse
     */
    public function readAll(PostRepository $repository): JsonResponse
    {
        // We get all the posts in DB and return it in Json
        $posts = $repository->findAll();

        // This entry to the serializer will transform objects into Json, by searching only properties tagged by the "groups" name
        return $this->json($posts, 200, [], [
            'groups' => 'post'
        ]);
    }

    /**
     * Method to get one single post by its Id
     *
     * @Route("/{id}", name="read_one", methods={"GET"})
     *
     * @param int $id
     * @param PostRepository $repository
     * @return JsonResponse
     */
    public function readSingle(int $id, PostRepository $repository): JsonResponse
    {
        // We get an post by its ID
        $post = $repository->find($id);


        // If the post does not exists, we display 404
        if(!$post){
            return $this->json([
                'error' => 'Le post numéro ' . $id . ' n\'existe pas'
            ], 404
            );
        }

        // We return the result with Json format
        return $this->json($post, 200, [], [
            'groups' => 'post'
        ]);
    }

    /**
     * Endpoint for creating a new post
     *
     * @Route("/", name="add", methods={"POST"})
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function create(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $em
    ): JsonResponse
    {
        // we take back the JSON
        $jsonData = $request->getContent();

        // We transform the json in object
        // First argument : datas to deserialize
        // Second : The type of object we want
        // Last : Start type

        /** @var Post $post */
        $post = $serializer->deserialize($jsonData, Post::class, 'json');

        // We validate the datas stucked in $post on criterias of annotations' Entity @assert
        $errors = $validator->validate($post);

        // If the errors array is not empty, we return an error code 400 that is a Bad Request
        if (count($errors) > 0) {
            return $this->json($errors, 400);
        }
        // get the user which is posting an article for the voter, to check if we are the creator of it
        $user = $this->security->getUser();
        $post->setAuthor($user);

        $dataPicture = json_decode($request->getContent(), true);
        if(isset($dataPicture['image']['base64'])){
            $imageFile = $dataPicture['image']['base64'];
            $post->setPicture($imageFile);
        }

        $em->persist($post);
        $em->flush();

        return $this->json($post, 201, [], [
            'groups' => 'post'
        ]);
    }
}
