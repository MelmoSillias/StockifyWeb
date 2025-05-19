<?php

namespace App\Controller;

use App\Entity\CreditClient;
use App\Repository\CreditClientRepository;
use App\Repository\PaiementCreditClientRepository;
use App\Repository\TransactionCaisseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\PaiementCreditClient;
use App\Entity\TransactionCaisse;

final class CreditClientController extends AbstractController
{
    #[Route('/credit/client', name: 'app_credit_client')]
    public function index(): Response
    {
        return $this->render('credit_client/index.html.twig', [
            'controller_name' => 'CreditClientController',
        ]);
    }

    #[Route('/api/credits', name: 'api_credit_list', methods: ['GET'])]
    public function list(Request $request, CreditClientRepository $repo): JsonResponse
    {
        $periode = $request->query->get('periode');
        $client = $request->query->get('client');
        $statut = $request->query->get('statut');

        $qb = $repo->createQueryBuilder('c')
            ->join('c.vente', 'v');

        if ($client) {
            $qb->andWhere('c.client_nom LIKE :client')
               ->setParameter('client', '%' . $client . '%');
        }

        if ($statut) {
            $qb->andWhere('c.statut = :statut')
               ->setParameter('statut', $statut);
        }

        if ($periode) {
            [$start, $end] = explode(' - ', $periode);
            $qb->andWhere('v.date BETWEEN :start AND :end')
               ->setParameter('start', new \DateTime($start))
               ->setParameter('end', (new \DateTime($end))->setTime(23, 59, 59));
        }

        $qb->orderBy('c.id', 'DESC');
        $credits = $qb->getQuery()->getResult();

        $data = array_map(function (CreditClient $c) {
            return [
                'id' => $c->getId(),
                'vente_id' => $c->getVente()->getId(),
                'client_nom' => $c->getClientNom(),
                'montant_total' => $c->getMontantTotal(),
                'montant_restant' => $c->getMontantRestant(),
                'statut' => $c->getStatut()
            ];
        }, $credits);

        return $this->json(['data' => $data]);
    }

    #[Route('/api/credits/{id}/payer', name: 'api_credit_payer', methods: ['POST'])]
    public function payer(Request $request, CreditClient $credit, EntityManagerInterface $em): JsonResponse
    {
        $montant = (float)$request->request->get('montant');

        if ($montant <= 0 || $montant > $credit->getMontantRestant()) {
            return $this->json(['error' => 'Montant invalide ou trop élevé'], 400);
        }

        $reste = $credit->getMontantRestant() - $montant;
        $credit->setMontantRestant($reste);

        if ($reste <= 0) {
            $credit->setStatut('payé');
        } elseif ($reste < $credit->getMontantTotal()) {
            $credit->setStatut('partiel');
        }

        // Enregistrer le paiement
        $paiement = new PaiementCreditClient();
        $paiement->setCredit($credit);
        $paiement->setMontant($montant);
        $paiement->setDate(new \DateTime());
        $em->persist($paiement);

        // Créer la transaction caisse
        $transaction = new TransactionCaisse();
        $transaction->setType('entrée');
        $transaction->setMontant($montant);

        $transaction->setDate(new \DateTime());
        $transaction->setPaiementCredit($paiement);

        // Si aucune méthode n'existe, commentez ou supprimez la ligne ci-dessus
        $transaction->setDescription('Paiement crédit client #' . $credit->getId());
        $transaction->setMotif("Paiement de credit");
        $em->persist($transaction);

        $em->flush();

        return $this->json(['message' => 'Paiement enregistré avec succès']);
    }

    #[Route('/api/credits/{id}/details', name: 'api_credit_details', methods: ['GET'])]
public function details(CreditClient $credit): JsonResponse
{ 
        


    $vente = $credit->getVente();
    $venteDetails = [];

    foreach ($vente->getDetailsVente() as $detail) {
        $venteDetails[] = [
            'produit' => $detail->getProduit()?->getNom() ?? 'Produit inconnu',
            'quantite' => $detail->getQuantite(),
            'pu' => $detail->getPrixUnitaireVente()
        ];
    }

    $paiements = [];
    foreach ($credit->getPaiementCreditClients() as $paiement) {
        $paiements[] = [
            'id' => $paiement->getId(),
            'date' => $paiement->getDate()->format('Y-m-d'),
            'montant' => $paiement->getMontant()
        ];
    }

    return $this->json([
        'vente' => $venteDetails,
        'paiements' => $paiements,
        'id' => $credit->getId(),
        'client_nom' => $credit->getClientNom(),
        'montant_total' => $credit->getMontantTotal(),
        'montant_restant' => $credit->getMontantRestant(),
        'statut' => $credit->getStatut(),
        'date' => $credit->getVente()?->getDate()?->format('Y-m-d') ?? null
    ]);
}

#[Route('/api/paiements/{id}', name: 'api_paiement_delete', methods: ['DELETE'])]
public function deletePaiement(
    int $id,
    PaiementCreditClientRepository $repo,
    TransactionCaisseRepository $transactionRepo,
    EntityManagerInterface $em
): JsonResponse {
    $paiement = $repo->find($id);

    if (!$paiement) {
        return $this->json(['error' => 'Paiement introuvable'], 404);
    }

    $credit = $paiement->getCredit();

    // Rétablir le montant restant
    $credit->setMontantRestant($credit->getMontantRestant() + $paiement->getMontant());

    // Recalculer le statut
    if ($credit->getMontantRestant() >= $credit->getMontantTotal()) {
        $credit->setStatut('impayé');
    } elseif ($credit->getMontantRestant() > 0) {
        $credit->setStatut('partiel');
    } else {
        $credit->setStatut('payé');
    }

    // Supprimer la transaction liée au paiement
    $transaction = $transactionRepo->findOneBy(['paiement_credit' => $paiement]);
    if ($transaction) {
        $em->remove($transaction);
    }

    $em->remove($paiement);
    $em->flush();

    return $this->json(['message' => 'Paiement et transaction annulés avec succès']);
}

#[Route('/api/credits/stats', name: 'api_credits_stats', methods: ['GET'])]
public function stats(Request $request, CreditClientRepository $repo): JsonResponse
{
    $periode = $request->query->get('periode');
    $client = $request->query->get('client');

    $qb = $repo->createQueryBuilder('c')
        ->join('c.vente', 'v');

    if ($periode && str_contains($periode, ' - ')) {
        [$start, $end] = explode(' - ', $periode);
        $qb->andWhere('v.date BETWEEN :start AND :end')
           ->setParameter('start', new \DateTime($start))
           ->setParameter('end', (new \DateTime($end))->setTime(23, 59, 59));
    }

    if ($client) {
        $qb->andWhere('c.client_nom LIKE :client')
           ->setParameter('client', '%' . $client . '%');
    }

    $credits = $qb->getQuery()->getResult();

    $nbTotal = 0;
    $montantTotal = 0;
    $nbPayes = 0;
    $montantPayes = 0;
    $nbPartiels = 0;
    $montantPartiels = 0;
    $nbImpayes = 0;
    $montantImpayes = 0;
    $recette = 0;

    foreach ($credits as $c) {
        $nbTotal++;
        $montantTotal += $c->getMontantTotal();
        $recette += $c->getMontantTotal() - $c->getMontantRestant();

        match ($c->getStatut()) {
            'payé' => [$nbPayes++, $montantPayes += $c->getMontantTotal()],
            'partiel' => [$nbPartiels++, $montantPartiels += $c->getMontantRestant()],
            'impayé' => [$nbImpayes++, $montantImpayes += $c->getMontantRestant()],
            default => null
        };
    }

    return $this->json([
        'nb_total' => $nbTotal,
        'montant_total' => $montantTotal,
        'nb_payes' => $nbPayes,
        'montant_payes' => $montantPayes,
        'nb_partiels' => $nbPartiels,
        'montant_partiels' => $montantPartiels,
        'nb_impayes' => $nbImpayes,
        'montant_impayes' => $montantImpayes,
        'recette' => $recette
    ]);
}


}
