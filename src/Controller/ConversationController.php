<?php

namespace App\Controller;

use Exception;
use App\Entity\Participant;
use App\Entity\Conversation;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ConversationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/conversations', name: 'conversations.')]
class ConversationController extends AbstractController
{
    public function __construct(private UserRepository $userRepository, private EntityManagerInterface $entityManager, private ConversationRepository $conversationRepository)
    {
    }

    #[Route('/', name: 'newConversations', methods:'POST')]
    public function index(Request $request)
    {
        $otherUser = $request->get('otherUser', 0);
        $otherUser = $this->userRepository->find($otherUser);

        if(is_null($otherUser)) {
            dd('The user wasnt found');
        }

        if($otherUser->getId() === $this->getUser()->getId()) {
            dd('Cant create conversation with yourself');
        }

        $conversation = $this->conversationRepository->findConversationByParticipants($otherUser->getId(), $this->getUser()->getId());
        
        if(count($conversation)) {
            dd('Conversation already exist');
        }

        $conversation = new Conversation();

        $participant = new Participant();
        $participant->setUser($this->getUser());
        $participant->setConversation($conversation);
        
        $otherParticipant = new Participant();
        $otherParticipant->setUser($otherUser);
        $otherParticipant->setConversation($conversation);

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $this->entityManager->persist($conversation);
            $this->entityManager->persist($participant);
            $this->entityManager->persist($otherParticipant);

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch(\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
        
        return $this->json(['id' => $conversation->getId()], Response::HTTP_CREATED, [], []);
    }

    #[Route('/', name: 'getConversations', methods: 'GET')]
    public function getConvs(Request $request) {
        $conversations = $this->conversationRepository->findConversationsByUser($this->getUser()->getId());
        
        /* $hubUrl = $this->getParameter('mercure.default_hub');

        $this->addLink($request, new Link('mercure', $hubUrl));
        return $this->json($conversations); */
        dd($conversations);
    }
}
