<?php

namespace App\Controller\Api\V1;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api/v1/comment", name="app_api_v1_comment_", requirements={"id":"\d+"})
 */
class CommentController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * Get all the comments
     *
     * @Route("/", name="read_all", methods={"GET"})
     *
     * @param CommentRepository $repository
     * @return JsonResponse
     */
    public function getAllComments(CommentRepository $repository): JsonResponse
    {
        $comments = $repository->findAll();

        return $this->json($comments, 200, [], [
            'groups' => 'comment'
        ]);
    }

    /**
     * Method to get one single comment by its Id
     *
     * @Route("/{id}", name="read_one", methods={"GET"})
     *
     * @param int $id
     * @param CommentRepository $repository
     * @return JsonResponse
     */
    public function getComment(int $id, CommentRepository $repository): JsonResponse
    {
        $comment = $repository->find($id);

        if(!$comment){
            return $this->json(['error' => 'Commentaire indisponible'], 404);
        }

        return $this->json($comment, 200, [], [
            'groups' => 'comment'
        ]);
    }

    /**
     * Endpoint for creating a new comment
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
        $jsonData = $request->getContent();

        /** @var Comment $comment */
        $comment = $serializer->deserialize($jsonData, Comment::class, 'json', [AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER]);

        $errors = $validator->validate($comment);

        if(count($errors) > 0){
            return $this->json($errors, 400);
        }

        $user = $this->security->getUser();
        $comment->setCommenter($user);

        $em->persist($comment);
        $em->flush();

        return $this->json($comment, 201, [], [
            'groups' =>'comment'
        ]);
    }

    /**
     * Update a comment by its ID only if it's the creator
     *
     * @Route("/{id}", name="update", methods={"PUT","PATCH"})
     *
     * @param Comment $comment
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function update(
        Comment $comment,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em
    ): JsonResponse
    {
        // This method will allows to access to the update method, with the voter logic
        $this->denyAccessUnlessGranted('edit', $comment, "Seul l'auteur de ce commentaire peut le modifier.");

        $jsonData = $request->getContent();

        if(!$comment){
            return $this->json([
                'errors' => ['message'=>'Ce commentaire n\'existe pas']
            ], 404
            );
        }

        $serializer->deserialize($jsonData, Comment::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE=>$comment]);


        $em->flush();

        return $this->json($comment, 200, [], [
            'groups' => 'comment'
        ]);
    }

    /**
     * Remove a comment by its ID only by its author
     *
     * @Route("/{id}", name="delete", methods={"DELETE"})
     *
     * @param Comment $comment
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function delete(Comment $comment, EntityManagerInterface $em): JsonResponse
    {
        // This protection will check by the voter if we are allowed to delete this article
        $this->denyAccessUnlessGranted('delete', $comment, "Seul l'auteur de ce commentaire peut le supprimer.");

        if(!$comment){
            return $this->json([
                'error' => 'Ce commentaire n\'existe pas.'
            ], 404);
        }

        $em->remove($comment);
        $em->flush();

        return $this->json([
            'message' => 'Le commentaire a bien été supprimé'
        ], 200);
    }
}
