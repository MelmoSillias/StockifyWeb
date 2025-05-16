<?php

namespace App\Controller;

use App\Entity\Vente;
use App\Entity\DetailVente;
use App\Entity\CreditClient;
use App\Entity\MouvementStock;
use App\Entity\PaiementCreditClient;
use App\Entity\Produit;
use App\Entity\TransactionCaisse;
use App\Repository\VenteRepository;
use App\Repository\ProduitRepository;
use App\Repository\DetailVenteRepository;
use App\Repository\CreditClientRepository;
use App\Repository\LotProduitRepository;
use App\Repository\MouvementStockRepository;
use App\Repository\PaiementCreditClientRepository;
use App\Repository\TransactionCaisseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; 
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class VenteController extends AbstractController
{
    #[Route('/vente', name: 'app_vente')]
    public function index(): Response
    {
        return $this->render('vente/index.html.twig', [
            'controller_name' => 'VenteController',
        ]);
    }

    #[Route('/api/ventes/create', name: 'api_vente_create', methods: ['POST'])]
public function create(
    Request $request,
    EntityManagerInterface $em,
    ProduitRepository $produitRepo,
    LotProduitRepository $lotRepo,
    MouvementStockRepository $mouvementRepo
): JsonResponse {
    $data = json_decode($request->getContent(), true);

    $vente = new Vente();
    $vente->setDate(new \DateTime());
    $vente->setNomClient($data['client'] ?? null);
    $vente->setType($data['type_paiement']);
    $vente->setMontantPaye($data['montant_paye']);
    
    $total = 0;

    foreach ($data['lignes'] as $ligne) {
        $produit = $produitRepo->find($ligne['produit_id']);
        if (!$produit) continue;

        $qte = (int)$ligne['quantite'];
        $pu = (float)$ligne['prix_unitaire'];

        // Détail vente
        $detail = new DetailVente();
        $detail->setVente($vente);
        $detail->setProduit($produit);
        $detail->setQuantite($qte);
        $detail->setPrixUnitaireVente($pu);
        $em->persist($detail);

        $total += $qte * $pu;

        // Mouvement de stock
        $mouvement = new MouvementStock();
        $mouvement->setProduit($produit);
        $mouvement->setQuantite($qte);
        $mouvement->setType('sortie');
        $mouvement->setDate(new \DateTime());
        $mouvement->setSource('vente');
        $mouvement->setCommentaire("Sortie automatique - Vente");
        $em->persist($mouvement);

        // Diminuer le stock actuel
        $produit->setStockActuel($produit->getStockActuel() - $qte);

        // Décrément des lots FIFO
        $quantiteRestante = $qte;
        $lots = $lotRepo->createQueryBuilder('l')
            ->where('l.produit = :produit')
            ->andWhere('l.quantite > 0')
            ->setParameter('produit', $produit)
            ->orderBy('l.date_achat', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($lots as $lot) {
            if ($quantiteRestante <= 0) break;

            $qteLot = $lot->getQuantite();
            if ($qteLot >= $quantiteRestante) {
                $lot->setQuantite($qteLot - $quantiteRestante);
                $quantiteRestante = 0;
            } else {
                $lot->setQuantite(0);
                $quantiteRestante -= $qteLot;
            }

            $em->persist($lot);
        }
    }

    $vente->setTotal($total);
    $reste = max(0, $total - $vente->getMontantPaye());
    $vente->setReste($reste);
    $em->persist($vente);

    // Paiement espèces
    if ($vente->getType() === 'especes') {
        $transaction = new TransactionCaisse();
        $transaction->setType('entrée');
        $transaction->setMontant($vente->getMontantPaye());
        $transaction->setDate(new \DateTime());
        $transaction->setMotif("Vente");
        $transaction->setDescription("Paiement vente #" . $vente->getId());
        $transaction->setVente($vente);
        $em->persist($transaction);
    }

    // Paiement à crédit
    if ($vente->getType() === 'credit') {
        $creance = new CreditClient();
        $creance->setVente($vente);
        $creance->setClientNom($vente->getNomClient());
        $creance->setMontantTotal($total);
        $creance->setMontantRestant($reste);
        $creance->setStatut($reste > 0 ? 'impayé' : 'réglé');
        $em->persist($creance);

        if ($vente->getMontantPaye() > 0) {
            $paiement = new PaiementCreditClient();
            $paiement->setCredit($creance);
            $paiement->setMontant($vente->getMontantPaye());
            $paiement->setDate(new \DateTime());
            $em->persist($paiement);

            $transaction = new TransactionCaisse();
            $transaction->setType('entrée');
            $transaction->setMontant($vente->getMontantPaye());
            $transaction->setDate(new \DateTime());
            $transaction->setMotif("Acompte crédit");
            $transaction->setDescription("Acompte sur crédit vente #" . $vente->getId());
            $transaction->setVente($vente);
            $em->persist($transaction);
        }
    }

    $em->flush();

    return $this->json([
        'status' => 'vente enregistrée',
        'vente_id' => $vente->getId()
    ]);
}


    #[Route('/api/ventes', name: 'api_vente_list', methods: ['GET'])]
    public function list(Request $request, VenteRepository $repo): JsonResponse
    {
        $periode = $request->query->get('periode');
        $client = $request->query->get('client');

        $query = $repo->createQueryBuilder('v');

        if ($client) {
            $query->andWhere('v.nomClient LIKE :client')
                ->setParameter('client', '%' . $client . '%');
        }

        if ($periode) {
            [$start, $end] = explode(' - ', $periode);
            $query->andWhere('v.date BETWEEN :start AND :end')
                ->setParameter('start', new \DateTime($start))
                ->setParameter('end', (new \DateTime($end))->setTime(23, 59, 59));
        }

        $ventes = $query->orderBy('v.id', 'DESC')->getQuery()->getResult();

        $data = array_map(function (Vente $v) {
            return [
                'id' => $v->getId(),
                'date' => $v->getDate()->format('Y-m-d'),
                'client' => $v->getNomClient(),
                'total' => $v->getTotal(),
                'montant_paye' => $v->getMontantPaye(),
            ];
        }, $ventes);

        return $this->json(['data' => $data]);
    }

    #[Route('/api/ventes/{id}/details', name: 'api_ventes_details', methods: ['GET'])]
    public function venteDetails(int $id, VenteRepository $venteRepo): JsonResponse
    {
        $vente = $venteRepo->find($id);

        if (!$vente) {
            throw new NotFoundHttpException('Vente introuvable');
        }

        $lignes = [];
        foreach ($vente->getDetailsVente() as $ligne) {
            $produit = $ligne->getProduit();
            $lignes[] = [
                'produit' => $produit ? $produit->getNom() : 'Inconnu',
                'quantite' => $ligne->getQuantite(),
                'prix_unitaire' => $ligne->getPrixUnitaireVente(),
            ];
        }

        return $this->json([
            'id' => $vente->getId(),
            'date' => $vente->getDate()->format('Y-m-d H:i'),
            'client' => $vente->getNomClient(),
            'type' => ucfirst($vente->getType()), // "Espèces" ou "Crédit"
            'montant_paye' => $vente->getMontantPaye(),
            'lignes' => $lignes,
        ]);
    }

    #[Route('/api/ventes/stats', name: 'api_ventes_stats', methods: ['GET'])]
    public function stats(
        Request $request,
        VenteRepository $venteRepo,
        DetailVenteRepository $detailRepo
    ): JsonResponse {
        $periode = $request->query->get('periode');
        $client = $request->query->get('client');

        $start = null;
        $end = null;

        if ($periode && str_contains($periode, ' - ')) {
            [$startStr, $endStr] = explode(' - ', $periode);
            $start = \DateTime::createFromFormat('Y-m-d', trim($startStr));
            $end = \DateTime::createFromFormat('Y-m-d', trim($endStr));
            if ($end) {
                $end->setTime(23, 59, 59);
            }
        }

        $ventes = $venteRepo->findStatsFiltered($start, $end, $client);

        // Stats basiques
        $nbTotal = count($ventes);
        $montantTotal = 0;
        $nbEspeces = 0;
        $montantEspeces = 0;
        $nbCredit = 0;
        $montantCredit = 0;
        $recette = 0;
        $benefice = 0;

        foreach ($ventes as $vente) {
            $montant = $vente->getTotal();
            $montantPaye = $vente->getMontantPaye();
            $type = $vente->getType(); // 'especes' ou 'credit'

            $montantTotal += $montant;
            $recette += $montantPaye;

            if ($type === 'especes') {
                $nbEspeces++;
                $montantEspeces += $montant;
            } else {
                $nbCredit++;
                $montantCredit += $montant;
            }

            // Bénéfice via lignes de vente
            foreach ($vente->getDetailsVente() as $ligne) {
                $pme = $ligne->getProduit()?->getPme() ?? 0;
                $benefice += ($ligne->getPrixUnitaireVente() - $pme) * $ligne->getQuantite();
            }
        }

        return $this->json([
            'nb_total' => $nbTotal,
            'montant_total' => $montantTotal,
            'nb_especes' => $nbEspeces,
            'montant_especes' => $montantEspeces,
            'nb_credit' => $nbCredit,
            'montant_credit' => $montantCredit,
            'recette' => $recette,
            'benefice' => $benefice
        ]);
    }

    #[Route('/api/ventes/{id}/annuler', name: 'api_vente_annuler', methods: ['DELETE'])]
public function annulerVente(
    int $id,
    EntityManagerInterface $em,
    VenteRepository $venteRepo,
    LotProduitRepository $lotRepo,
    MouvementStockRepository $mouvementRepo,
    CreditClientRepository $creditRepo,
    TransactionCaisseRepository $caisseRepo,
    PaiementCreditClientRepository $paiementRepo
): JsonResponse {
    $vente = $venteRepo->find($id);
    if (!$vente) {
        return $this->json(['error' => 'Vente introuvable'], 404);
    }

    // Restaurer stock produit et lots
    foreach ($vente->getDetailsVente() as $detail) {
        $produit = $detail->getProduit();
        $qte = $detail->getQuantite();

        // 1. Restaurer stock général
        $produit->setStockActuel($produit->getStockActuel() + $qte);
        $em->persist($produit);

        // 2. Restaurer stock dans les lots (FIFO inversé)
        $lots = $lotRepo->createQueryBuilder('l')
            ->where('l.produit = :produit')
            ->orderBy('l.date_achat', 'DESC')
            ->setParameter('produit', $produit)
            ->getQuery()
            ->getResult();

        $reste = $qte;
        foreach ($lots as $lot) {
            if ($reste <= 0) break;

            $lot->setQuantite($lot->getQuantite() + $reste);
            $em->persist($lot);
            $reste = 0; // tout mis dans le dernier lot
        }

        // Supprimer le détail
        $em->remove($detail);
    }

    // Supprimer les mouvements de stock liés
    $mouvements = $mouvementRepo->findBy(['source' => 'vente', 'produit' => null]); // optionnel
    foreach ($mouvements as $mv) {
        if ($mv->getDate() == $vente->getDate()) {
            $em->remove($mv);
        }
    }

    // Si espèces → supprimer transaction caisse
    if ($vente->getType() === 'especes') {
        $transaction = $caisseRepo->findOneBy(['vente' => $vente]);
        if ($transaction) $em->remove($transaction);
    }

    // Si crédit → supprimer créance + paiements
    if ($vente->getType() === 'credit') {
        $creance = $creditRepo->findOneBy(['vente' => $vente]);
        if ($creance) {
            $paiements = $paiementRepo->findBy(['credit' => $creance]);
            foreach ($paiements as $p) $em->remove($p);
            $em->remove($creance);

            $transactions = $caisseRepo->findBy(['vente' => $vente]);
            foreach ($transactions as $transaction) {
                $em->remove($transaction);
            }
        }
    }

    // Supprimer la vente
    $em->remove($vente);
    $em->flush();

    return $this->json(['status' => 'vente annulée']);
}

}
