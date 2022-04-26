<?php

namespace App\Repository;

use App\Entity\Conversation;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Conversation>
 *
 * @method Conversation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Conversation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Conversation[]    findAll()
 * @method Conversation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Conversation $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Conversation $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return Conversation[] Returns an array of Conversation objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Conversation
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function findConversationByParticipants(int $otherUserId, int $myId)
    {

        $qb = $this->createQueryBuilder('c');
        return $qb->select($qb->expr()->count('p.conversation'))
            ->innerJoin('c.participants','p')
            ->where($qb->expr()->orX($qb->expr()->eq('p.user', ':me'), $qb->expr()->eq('p.user', ':otherUser')))
            ->groupBy('p.conversation')
            ->having($qb->expr()->eq($qb->expr()->count('p.conversation'),2))
            ->setParameters(['me' => $myId, 'otherUser' => $otherUserId])
            ->getQuery()
            ->getResult()
        ;
    }

    public function findConversationsByUser(int $userId)
    {
        $qb = $this->createQueryBuilder('c');
        return 
        $qb
            ->select('otherUser.username', 'c.id as conversationId', 'lm.content', 'lm.createdAt')
            ->innerJoin('c.participants', 'p', Join::WITH, $qb->expr()->neq('p.user', ':user'))
            ->innerJoin('c.participants', 'me', Join::WITH, $qb->expr()->eq('me.user', ':user'))
            ->leftJoin('c.lastMessage', 'lm')
            ->innerJoin('me.user', 'meUser')
            ->innerJoin('p.user', 'otherUser')
            ->where('meUser.id = :user')
            ->setParameter('user', $userId)
            ->orderBy('lm.createdAt', 'DESC')
            ->getQuery()->getResult();
    }

    public function checkIfUserisParticipant(int $conversationId, int $userId)
    {
        $qb = $this->createQueryBuilder('c');
        return 
        $qb
            ->innerJoin('c.participants', 'p')
            ->where('c.id = :conversationId')
            ->andWhere(
                $qb->expr()->eq('p.user', ':userId')
            )
            ->setParameters([
                'conversationId' => $conversationId,
                'userId' => $userId
            ])
        ->getQuery()->getOneOrNullResult();
    }
}
