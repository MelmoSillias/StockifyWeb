<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response; 
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TransactionCaisseRepository; 
use DateTime;
use App\Entity\TransactionCaisse;
use App\Entity\CreanceFournisseur;
use App\Entity\CreanceClient;

final class FinanceController extends AbstractController
{
    #[Route('/finance', name: 'app_finance')]
    public function index(): Response
    {
        return $this->render('finance/index.html.twig', [
            'controller_name' => 'FinanceController',
        ]);
    }

    #[Route('/api/transactions/stats', name: 'api_transactions_stats', methods: ['GET'])]
public function stats(Request $request, TransactionCaisseRepository $repo): JsonResponse
{
    $periode = $request->query->get('periode');
    $type = $request->query->get('type');

    $qb = $repo->createQueryBuilder('t');

    if ($periode) {
        [$start, $end] = explode(' - ', $periode);
        $qb->andWhere('t.date BETWEEN :start AND :end')
           ->setParameter('start', new \DateTime($start))
           ->setParameter('end', (new \DateTime($end))->setTime(23, 59, 59));
    }

    if ($type) {
        $qb->andWhere('t.type = :type')->setParameter('type', $type);
    }

    $transactions = $qb->getQuery()->getResult();

    $entrees = 0;
    $sorties = 0;

    foreach ($transactions as $t) {
        if ($t->getType() === 'entrée') {
            $entrees += $t->getMontant();
        } else {
            $sorties += $t->getMontant();
        }
    }

    return $this->json([
        'total_entrees' => $entrees,
        'total_sorties' => $sorties,
        'solde_net' => $entrees - $sorties
    ]);
}

#[Route('/api/transactions/{id}', name: 'api_transaction_details', methods: ['GET'])]
public function show(TransactionCaisse $transaction): JsonResponse
{
    return $this->json([
        'id' => $transaction->getId(),
        'type' => $transaction->getType(),
        'montant' => $transaction->getMontant(),
        'libelle' => $transaction->getLibelle(),
        'motif' => $transaction->getMotif(),
        'description' => $transaction->getDescription(),
        'date' => $transaction->getDate()->format('Y-m-d H:i'),
        'is_Erasable' => ($transaction->getVente() || $transaction->getPaiementCredit() || $transaction->getPaiementFournisseur())
    ]);
}

#[Route('/api/transactions/{id}', name: 'api_transaction_delete', methods: ['DELETE'])]
public function delete(
    TransactionCaisse $transaction,
    EntityManagerInterface $em
): JsonResponse {
    if ($transaction->getVente() || $transaction->getPaiementCredit() || $transaction->getPaiementFournisseur()) {
        return $this->json(['error' => 'Impossible de supprimer une transaction liée à une opération.'], 400);
    }

    $em->remove($transaction);
    $em->flush();

    return $this->json(['message' => 'Transaction supprimée avec succès']);
}


    #[Route('/api/transactions', name: 'api_transactions_list', methods: ['GET'])]
    public function list(Request $request, TransactionCaisseRepository $repo): JsonResponse
    {
        $periode = $request->query->get('periode');
        $type = $request->query->get('type');
        $motif = $request->query->get('motif');
        $libelle = $request->query->get('libelle');

        $qb = $repo->createQueryBuilder('t');

        if ($type) {
            $qb->andWhere('t.type = :type')->setParameter('type', $type);
        }

        if ($motif) {
            $qb->andWhere('t.motif LIKE :motif')->setParameter('motif', '%' . $motif . '%');
        }

        if ($libelle) {
            $qb->andWhere('t.libelle LIKE :libelle')->setParameter('libelle', '%' . $libelle . '%');
        }

        if ($periode) {
            [$start, $end] = explode(' - ', $periode);
            $qb->andWhere('t.date BETWEEN :start AND :end')
               ->setParameter('start', new DateTime($start))
               ->setParameter('end', (new DateTime($end))->setTime(23, 59, 59));
        }

        $transactions = $qb->orderBy('t.date', 'DESC')->getQuery()->getResult();

        $data = array_map(fn(TransactionCaisse $t) => [
            'id' => $t->getId(),
            'type' => $t->getType(),
            'montant' => $t->getMontant(),
            'libelle' => $t->getLibelle(),
            'motif' => $t->getMotif(),
            'description' => $t->getDescription(),
            'date' => $t->getDate()->format('Y-m-d H:i'),
            'is_Erasable' => !($t->getVente() || $t->getPaiementCredit() || $t->getPaiementFournisseur())
        ], $transactions);

        return $this->json(['data' => $data]);
    }

    #[Route('/api/transactions', name: 'api_transaction_add', methods: ['POST'])]
    public function add(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (
            empty($data['type']) ||
            empty($data['montant']) ||
            empty($data['motif'])
        ) {
            return $this->json(['error' => 'Champs requis manquants'], 400);
        }

        $transaction = new TransactionCaisse();
        $transaction->setType($data['type']);
        $transaction->setMontant((float)$data['montant']);
        $transaction->setLibelle($data['libelle'] ?? null);
        $transaction->setMotif($data['motif']);
        $transaction->setDescription($data['description'] ?? null);
        $transaction->setDate(new \DateTime());

        $em->persist($transaction);
        $em->flush();

        return $this->json(['message' => 'Transaction enregistrée']);
    }
}
