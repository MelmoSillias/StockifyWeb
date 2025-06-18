<?php

namespace App\Controller;

use App\Entity\CreanceFournisseur;
use App\Entity\PaiementCreanceFournisseur;
use App\Entity\PaiementCreditClient;
use App\Entity\TransactionCaisse;
use App\Repository\CreanceFournisseurRepository;
use App\Repository\PaiementCreanceFournisseurRepository;
use App\Repository\TransactionCaisseRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PhpParser\Node\Expr\New_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CreanceFournisseurController extends AbstractController
{
    #[Route('/creance/fournisseur', name: 'app_creance_fournisseur')]
    public function index(): Response
    {
        return $this->render('creance_fournisseur/index.html.twig', [
            'controller_name' => 'CreanceFournisseurController',
        ]);
    }

    #[Route('/api/creances')]
    public function list(Request $request, CreanceFournisseurRepository $repo): JsonResponse
    {
        $periode = $request->query->get('periode');
        $fournisseur = $request->query->get('fournisseur');
        $statut = $request->query->get('statut');

        $qb = $repo->createQueryBuilder('c');

        if ($fournisseur) {
            $qb->andWhere('c.fournisseur LIKE :fournisseur')
                ->setParameter('fournisseur', '%' . $fournisseur . '%');
        }

        if ($statut) {
            $qb->andWhere('c.statut = :statut')
                ->setParameter('statut', $statut);
        }

        if ($periode) {
            [$start, $end] = explode(' - ', $periode);
            $qb->andWhere('c.date BETWEEN :start AND :end')
                ->setParameter('start', new DateTime($start))
                ->setParameter('end', (new DateTime($end))->setTime(23, 59, 59));
        }

        $creances = $qb->orderBy('c.date', 'DESC')->getQuery()->getResult();

        $data = array_map(fn(CreanceFournisseur $c) => [
            'id' => $c->getId(),
            'fournisseur' => $c->getFournisseurNom(),
            'date' => $c->getdate()->format('Y-m-d'),
            'devise' => $c->getDevise(),
            'montant_total' => $c->getMontantTotal(),
            'montant_restant' => $c->getMontantRestant(),
            'statut' => $c->getStatut()
        ], $creances);

        return $this->json(['data' => $data]);
    }

    #[Route('/api/creances/create', name: 'api_creance_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = $request->request->all();

        $creance = new CreanceFournisseur();
        $creance->setFournisseurNom($data['fournisseur']);
        $creance->setMontantTotal(floatval($data['montant_devise']));
        $creance->setMontantRestant(floatval($data['montant_devise']));
        $creance->setDevise($data['devise']);
        $creance->setDate(new DateTime($data['date']));
        $creance->setStatut('impayé');
        $creance->setTauxChange(0);

        $em->persist($creance);
        $em->flush();

        return $this->json(['message' => 'Créance enregistrée']);
    }
    #[Route('/api/creances/{id}/payer', name: 'api_creance_payer', methods: ['POST'])]
    public function payer(
        Request $request,
        CreanceFournisseur $creance,
        EntityManagerInterface $em
    ): JsonResponse {
        $montantFCFA = floatval($request->request->get('montant'));
        $taux = floatval($request->request->get('taux'));

        if ($montantFCFA <= 0 || $taux <= 0) {
            return $this->json(['error' => 'Montant ou taux invalide'], 400);
        }

        $montantDevise = $montantFCFA / $taux;
        $paiement = new PaiementCreanceFournisseur();
        $paiement->setCreance($creance);
        $paiement->setMontantPayeDevise($montantDevise);
        $paiement->setTauxApplique($taux);
        $paiement->setMontantEnCaisse($montantFCFA);
        $paiement->setDate(new \DateTime());

        $creance->setMontantRestant($creance->getMontantRestant() - $montantDevise);

        if ($creance->getMontantRestant() <= 0) {
            $creance->setStatut('payé');
        } elseif ($creance->getMontantRestant() < $creance->getMontantTotal()) {
            $creance->setStatut('partiel');
        }

        $em->persist($paiement);

        // Créer transaction caisse
        $transaction = new TransactionCaisse();
        $transaction->setType('sortie');
        $transaction->setMontant($montantFCFA);
        $transaction->setDate(new \DateTime());
        $transaction->setMotif('paiement fournisseur');
        $transaction->setDescription('Paiement fournisseur : ' . $creance->getFournisseurNom());
        $transaction->setPaiementFournisseur($paiement);
        $em->persist($transaction);

        $em->flush();

        return $this->json(['message' => 'Paiement enregistré']);
    }

    #[Route('/api/creances/{id}/details', name: 'api_creance_details', methods: ['GET'])]
    public function details(CreanceFournisseur $creance): JsonResponse
    {
        $paiements = [];

        /** @var PaiementCreanceFournisseur $p */
        foreach ($creance->getPaiements() as $p) {
            $paiements[] = [
            'id' => $p->getId(),
            'date' => $p->getDate()->format('Y-m-d'),
            'montant_devise' => $p->getMontantPayeDevise(),
            'taux' => $p->getTauxApplique(),
            'montant_fcfa' => $p->getMontantEnCaisse()
            ];
        }

        return $this->json(['paiements' => $paiements]);
    }
    #[Route('/api/paiements-fournisseur/{id}', name: 'api_paiement_fournisseur_delete', methods: ['DELETE'])]
    public function deletePaiement(
        int $id,
        PaiementCreanceFournisseurRepository $repo,
        TransactionCaisseRepository $trxRepo,
        EntityManagerInterface $em
    ): JsonResponse {

        /** @var PaiementCreanceFournisseur|null $paiement */
        $paiement = $repo->find($id);

        if (!$paiement) return $this->json(['error' => 'Introuvable'], 404);

        $creance = $paiement->getCreance();
        $creance->setMontantRestant($creance->getMontantRestant() + $paiement->getMontantPayeDevise());

        // MAJ statut
        if ($creance->getMontantRestant() >= $creance->getMontantTotal()) {
            $creance->setStatut('impayé');
        } elseif ($creance->getMontantRestant() > 0) {
            $creance->setStatut('partiel');
        } else {
            $creance->setStatut('payé');
        }

        // Supprimer transaction liée
        $trx = $trxRepo->findOneBy(['paiementCreanceFournisseur' => $paiement]);
        if ($trx) $em->remove($trx);

        $em->remove($paiement);
        $em->flush();

        return $this->json(['message' => 'Paiement annulé']);
    }

    #[Route('/api/creances/stats', name: 'api_creance_stats', methods: ['GET'])]
public function stats(Request $request, CreanceFournisseurRepository $repo): JsonResponse
{
    $periode = $request->query->get('periode');
    $fournisseur = $request->query->get('fournisseur');

    $qb = $repo->createQueryBuilder('c');

    if ($fournisseur) {
        $qb->andWhere('c.fournisseur LIKE :fournisseur')
           ->setParameter('fournisseur', '%' . $fournisseur . '%');
    }

    if ($periode && str_contains($periode, ' - ')) {
        [$start, $end] = explode(' - ', $periode);
        $qb->andWhere('c.date BETWEEN :start AND :end')
           ->setParameter('start', new \DateTime($start))
           ->setParameter('end', (new \DateTime($end))->setTime(23, 59, 59));
    }

    $creances = $qb->getQuery()->getResult();

    $total = count($creances);
    $payees = 0;
    $restantes = 0;

    foreach ($creances as $c) {
        if ($c->getStatut() === 'payé') $payees++;
        if ($c->getMontantRestant() > 0) $restantes++;
    }

    return $this->json([
        'total' => $total,
        'payees' => $payees,
        'restantes' => $restantes
    ]);
}

#[Route('/api/creances/{id}', name: 'api_creance_delete', methods: ['DELETE'])]
public function delete(
    CreanceFournisseur $creance,
    PaiementCreanceFournisseurRepository $repo,
    TransactionCaisseRepository $trxRepo,
    EntityManagerInterface $em
): JsonResponse {
    foreach ($creance->getPaiements() as $paiement) {
        $trx = $trxRepo->findOneBy(['paiement_fournisseur' => $paiement]);
        if ($trx) $em->remove($trx);
        $em->remove($paiement);
    }

    $em->remove($creance);
    $em->flush();

    return $this->json(['message' => 'Créance supprimée']);
}


}
