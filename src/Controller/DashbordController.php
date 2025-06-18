<?php

namespace App\Controller;

use App\Repository\CreanceFournisseurRepository;
use App\Repository\CreditClientRepository;
use App\Repository\PaiementCreditClientRepository;
use App\Repository\ProduitRepository;
use App\Repository\TransactionCaisseRepository;
use App\Repository\UserRepository;
use App\Repository\VenteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashbordController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
     public function index(
        ProduitRepository $produitRepo,
        VenteRepository   $venteRepo,
        CreditClientRepository $creditRepo,
        PaiementCreditClientRepository $paiementRepo,
        CreanceFournisseurRepository $creanceRepo,
        TransactionCaisseRepository $caisseRepo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ) {
        // Statistiques générales
        $totalProduits = $produitRepo->count(['actif' => true]);
        $totalStock = (int) $em->createQuery("SELECT SUM(p.stock_actuel) FROM App\Entity\Produit p")->getSingleScalarResult();
        $ventesDuJour = $venteRepo->countVentesDuJour(); // méthode personnalisée à créer
        $creditsEnCours = $creditRepo->count(['statut' => 'en cours']);
        $creancesEnCours = $creanceRepo->countEnCours(); // méthode personnalisée à créer
        $soldeCaisse = $caisseRepo->calculerSolde(); // méthode personnalisée
        $usersActifs = $userRepo->count(['actif' => true]);
        $produitsStockFaible = $produitRepo->findProduitsStockFaible(); // méthode personnalisée

        // Graphique ventes des 12 derniers mois
        $ventesParMois = $venteRepo->getStatsMensuelles(); // méthode personnalisée
        $moisLabels = array_map(fn($m) => $m['mois'], $ventesParMois);
        $ventesData = array_map(fn($m) => $m['total'], $ventesParMois);

        // Graphique catégorie
        $repartitionCategories = $produitRepo->getRepartitionParCategorie(); // méthode personnalisée

        // Tableaux récents
        $dernieresVentes = $venteRepo->findBy([], ['date' => 'DESC'], 5);
        $derniersPaiements = $paiementRepo->findBy([], ['date' => 'DESC'], 5);

        return $this->render('dashbord/index.html.twig', [
            'controller_name' => "DashboardController",
            'total_produits' => $totalProduits,
            'total_stock' => $totalStock,
            'ventes_du_jour' => $ventesDuJour,
            'credits_en_cours' => $creditsEnCours,
            'creances_en_cours' => $creancesEnCours,
            'solde_caisse' => $soldeCaisse,
            'users_actifs' => $usersActifs,
            'produits_stock_faible' => $produitsStockFaible,
            'ventes_par_mois' => $ventesData,
            'mois_labels' => $moisLabels,
            'repartition_categories' => $repartitionCategories,
            'dernieres_ventes' => $dernieresVentes,
            'derniers_paiements' => $derniersPaiements,
        ]);
    }

    #[Route('/api/DataTableFRJson', name: 'api_data_table_fr_json', methods: ['GET'])]
    public function dataTableFRJson(): Response
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/public/utils/dataTables_fr-FR.json';

        if (!file_exists($filePath)) {
            return $this->json(['error' => 'File not found'], Response::HTTP_NOT_FOUND);
        }

        $data = file_get_contents($filePath);
        $jsonData = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON format'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($jsonData);
    }
}
