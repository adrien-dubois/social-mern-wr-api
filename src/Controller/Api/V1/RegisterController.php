<?php

namespace App\Controller\Api\V1;

use App\Entity\User;
use App\Form\OtpType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegisterController extends AbstractController
{

    /**
     * Method to register a new user
     *
     * @Route("/api/v1/register", name="register_user", methods={"POST"})
     *
     * @param Request $request
     * @param UserPasswordHasherInterface $hasher
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @param MailerInterface $mailer
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function register(
        Request $request,
        UserPasswordHasherInterface $hasher,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        MailerInterface $mailer,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $jsonData = $request->getContent();

        $user = $serializer->deserialize($jsonData, User::class, 'json');

        $errors = $validator->validate($user);

        $password = $user->getPassword();
        $confirmPassword = $user->getConfirmPassword();

        if($password != $confirmPassword){
            return $this->json([
                'message' => "Les mots de passe de sont pas identiques"
            ], 400);
        }

        $otp = rand(100000, 999999);

        $user->setPassword($hasher->hashPassword($user, $password));
        $user->setActivationToken(md5(uniqid()));
        $user->setOtp($otp);

        $dataPicture = json_decode($request->getContent(), true);
        if(isset($dataPicture['image']['base64'])){
            $imageFile = $dataPicture['image']['base64'];
            $user->setPicture($imageFile);
        }

        if(count($errors) > 0){
            return $this->json($errors, 400);
        }

        $em->persist($user);
        $em->flush();

        $email = (new TemplatedEmail())
                ->to($user->getEmail())
                ->subject("Validation de votre inscription sur White Rabbit")
                ->htmlTemplate("emails/confirmation.html.twig")
                ->context(compact('user'));

        $mailer->send($email);

        return $this->json($user, 201);
    }

    /**
     *
     * Methode to activate a user after registration by mail
     *
     * @Route("/activation/{activationToken}", name="register_activation")
     *
     * @param $activationToken
     * @param UserRepository $repository
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function activation(
        $activationToken,
        UserRepository $repository,
        Request $request,
        EntityManagerInterface $em
    ){

        $form = $this->createForm(OtpType::class);
        $content = $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

            $otp = $content->get('otp')->getData();

            /** @var User $user */
            $user = $repository->findOtp($otp, $activationToken);

            if(!$user){
                throw $this->createNotFoundException('Le numéro d\'activation est invalide');
            }

            $user->setIsActive(true);
            $em->flush();

            return $this->redirectToRoute('app_login');
            // return $this->redirect('', 301);
        }

        return $this->render('security/activation.html.twig',[
            'formView' => $form->createView()
        ]);
    }
}
