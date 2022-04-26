<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\Conversation;
use App\Repository\UserRepository;
use App\Repository\MessageRepository;
use Symfony\Component\Mercure\Update;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ParticipantRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route(path:"/messages", name:"messages.")]
class MessageController extends AbstractController
{
    const ATTRIBUTES_TO_SERIALIZE = ['id', 'content', 'createdAt', 'mine'];

    public function __construct(private EntityManagerInterface $entityManager,
                                private MessageRepository $messageRepository,
                                private UserRepository $userRepository,
                                private ParticipantRepository $participantRepository,
                                /* private PublisherInterface $publisher */)
    {
    }

    #[Route(path:"/{id}", name:"getMessages", methods:"GET")]
    public function index(Request $request, Conversation $conversation)
    {
        // can i view the conversation

        $this->denyAccessUnlessGranted('view', $conversation);

        $messages = $this->messageRepository->findMessageByConversationId(
            $conversation->getId()
        );

        /**
         * @var $message Message
         */
        array_map(function ($message) {
            $message->setMine(
                $message->getUser()->getId() === $this->getUser()->getId()
                    ? true : false
            );
        }, $messages);


        return $this->json($messages, Response::HTTP_OK, [], [
            'attributes' => self::ATTRIBUTES_TO_SERIALIZE
        ]);
    }

    #[Route(path:"/{id}", name:"newMessage", methods:"POST")]
    public function newMessage(Request $request, Conversation $conversation, SerializerInterface $serializer)
    {
        $user = $this->getUser();

        $recipient = $this->participantRepository->findParticipantByConverstionIdAndUserId(
            $conversation->getId(),
            $user->getId()
        );

        $content = $request->get('content', null);
        $message = new Message();
        $message->setContent($content);
        $message->setUser($user);

        $conversation->addMessage($message);
        $conversation->setLastMessage($message);

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $this->entityManager->persist($message);
            $this->entityManager->persist($conversation);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
        $message->setMine(false);
        $messageSerialized = $serializer->serialize($message, 'json', [
            'attributes' => ['id', 'content', 'createdAt', 'mine', 'conversation' => ['id']]
        ]);
        $update = new Update(
            [
                sprintf("/conversations/%s", $conversation->getId()),
                sprintf("/conversations/%s", $recipient->getUser()->getUsername()),
            ],
            $messageSerialized,
            [
                sprintf("/%s", $recipient->getUser()->getUsername())
            ]
        );

        $this->publisher->__invoke($update);

        $message->setMine(true);
        return $this->json($message, Response::HTTP_CREATED, [], [
            'attributes' => self::ATTRIBUTES_TO_SERIALIZE
        ]);
    }
}
