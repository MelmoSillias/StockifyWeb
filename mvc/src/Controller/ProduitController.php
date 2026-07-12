<?php

namespace App\Controller;

use App\Entity\LotProduit;
use App\Entity\MouvementStock;
use App\Entity\Produit;
use App\Repository\LotProduitRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProduitController extends AbstractController
{
    #[Route('/produit', name: 'app_produit')]
    public function index(): Response
    {
        return $this->render('produit/index.html.twig', [
            'controller_name' => 'ProduitController',
        ]);
    }

    #[Route('/api/produits', name: 'api_produits_list', methods: ['GET'])]
    public function list(ProduitRepository $produitRepository): JsonResponse
    {
        $produits = $produitRepository->findAll();
        $data = [];

        foreach ($produits as $produit) {
            $data[] = [
                'id' => $produit->getId(),
                'nom' => $produit->getNom(),
                'reference' => $produit->getReference(),
                'categorie' => $produit->getCategorie(),
                'description' => $produit->getDescription(),
                'stock_actuel' => $produit->getStockActuel(),
                'prix_de_vente' => $produit->getPrixDeVente(),
                'pme' => $produit->getPme(),
                'seuil_alerte' => $produit->getSeuilAlerte(),
                'actif' => $produit->isActif()
            ];
        }

        return $this->json($data);
    }

    #[Route('api/produits/stats', name: 'api_produits_stats', methods: ['GET'])]
    public function stats(ProduitRepository $produitRepository): JsonResponse
    {
        $produits = $produitRepository->findAll();

        $totalProduits = count($produits);
        $stockTotal = 0;
        $valeurTotale = 0;

        foreach ($produits as $produit) {
            $stockTotal += $produit->getStockActuel();
            $valeurTotale += $produit->getStockActuel() * $produit->getPme();
        }

        return $this->json([
            'totalProduits' => $totalProduits,
            'stockTotal' => $stockTotal,
            'valeurTotale' => $valeurTotale
        ]);
    }

    #[Route('/api/produits/create', name: 'api_produits_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ProduitRepository $repo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $produit = null;
        if (!empty($data['id'])) {
            $produit = $repo->find($data['id']);
        }

        if (!$produit) {
            $produit = new Produit();
            $produit->setStockActuel(0);
            $produit->setPme(0);
        }

        $produit->setNom($data['nom'] ?? '');
        $produit->setReference($data['reference'] ?? null);
        $produit->setCategorie($data['categorie'] ?? '');
        $produit->setDescription($data['description'] ?? '');
        $produit->setPrixDeVente($data['prix_de_vente'] ?? 0);
        $produit->setSeuilAlerte($data['seuil_alerte'] ?? null);
        $produit->setActif(!empty($data['actif']));

        $em->persist($produit);
        $em->flush();

        return $this->json(['status' => 'ok']);
    }

    #[Route('/api/produits/{id}', name: 'api_produits_get', methods: ['GET'])]
    public function getProduit(Produit $produit): JsonResponse
    {
        return $this->json([
            'id' => $produit->getId(),
            'nom' => $produit->getNom(),
            'reference' => $produit->getReference(),
            'categorie' => $produit->getCategorie(),
            'description' => $produit->getDescription(),
            'seuil_alerte' => $produit->getSeuilAlerte(),
            'prix_de_vente' => $produit->getPrixDeVente(),
            'stock_actuel' => $produit->getStockActuel(),
            'pme' => $produit->getPme(),
            'actif' => $produit->isActif()
        ]);
    }

    #[Route('/api/produits/{id}', name: 'api_produits_update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request, ProduitRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $produit = $repo->find($id);
        if (!$produit) {
            return $this->json(['error' => 'Produit introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nom'])) {
            $produit->setNom($data['nom']);
        }
        if (array_key_exists('reference', $data)) {
            $produit->setReference($data['reference']);
        }
        if (isset($data['categorie'])) {
            $produit->setCategorie($data['categorie']);
        }
        if (isset($data['description'])) {
            $produit->setDescription($data['description']);
        }
        if (isset($data['prix_de_vente'])) {
            $produit->setPrixDeVente($data['prix_de_vente']);
        }
        if (array_key_exists('seuil_alerte', $data)) {
            $produit->setSeuilAlerte($data['seuil_alerte']);
        }

        $em->flush();

        return $this->json(['status' => 'updated']);
    }

    #[Route('/api/produits/{id}', name: 'api_produits_delete', methods: ['DELETE'])]
    public function delete(Produit $produit, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($produit);
        $em->flush();
        return $this->json(['status' => 'deleted']);
    }

    #[Route('/api/produits/lots', name: 'api_lot_ajout', methods: ['POST'])]
    public function ajouterLot(Request $request, ProduitRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $produit = $repo->find($data['produit_id'] ?? 0);
        if (!$produit) {
            return $this->json(['error' => 'Produit introuvable'], 404);
        }

        $lot = new LotProduit();
        $lot->setProduit($produit);
        $lot->setQuantite((int)$data['quantite']);
        $lot->setPrixUnitaireAchat((float)$data['prix_unitaire_achat']);
        $lot->setDateAchat(new \DateTime($data['date_achat']));
        $lot->setFournisseur($data['fournisseur'] ?? null);
        $lot->setDevise($data['devise'] ?? null);

        $em->persist($lot);

        // Mise à jour du stock
        $ancienStock = $produit->getStockActuel();
        $ancienPme = $produit->getPme();
        $nouveauStock = $ancienStock + $lot->getQuantite();

        // Nouveau PME
        $nouveauPme = $nouveauStock > 0
            ? round((($ancienStock * $ancienPme) + ($lot->getQuantite() * $lot->getPrixUnitaireAchat())) / $nouveauStock, 2)
            : $lot->getPrixUnitaireAchat();

        $produit->setStockActuel($nouveauStock);
        $produit->setPme($nouveauPme);

        $mvt = new MouvementStock();
        $mvt->setProduit($produit);
        $mvt->setType('entrée');
        $mvt->setQuantite($lot->getQuantite());
        $mvt->setDate($lot->getDateAchat());
        $mvt->setSource('Lot');
        $mvt->setCommentaire('Ajout lot ' . $lot->getQuantite() . ' x ' . $lot->getPrixUnitaireAchat() . ' FCFA' . ($lot->getFournisseur() ? ' / ' . $lot->getFournisseur() : ''));
    
        $em->persist($mvt);
    
        $em->flush();

        return $this->json(['status' => 'lot ajouté', 'pme' => $nouveauPme]);
    } 

    #[Route('/api/produits/{id}/lots', name: 'api_produit_lots', methods: ['GET'])]
    public function getLotsByProduit(int $id, LotProduitRepository $lotRepo): JsonResponse
    {
        $lots = $lotRepo->findBy(['produit' => $id]);

        $data = array_map(function (LotProduit $lot) {
            return [
                'id' => $lot->getId(),
                'quantite' => $lot->getQuantite(),
                'prix_unitaire_achat' => $lot->getPrixUnitaireAchat(),
                'date_achat' => $lot->getDateAchat()->format('Y-m-d'),
                'fournisseur' => $lot->getFournisseur(),
                'devise' => $lot->getDevise()
            ];
        }, $lots);

        return $this->json($data);
    }

    #[Route('/api/produits/lots/{id}', name: 'api_produit_lot_delete', methods: ['DELETE'])]
    public function deleteLot(int $id, LotProduitRepository $lotRepo, EntityManagerInterface $em): JsonResponse
    {
        $lot = $lotRepo->find($id);

        if (!$lot) {
            return $this->json(['message' => 'Lot introuvable'], 404);
        }

        $em->remove($lot);
        // Mise à jour du stock du produit
        $produit = $lot->getProduit();
        $ancienStock = $produit->getStockActuel();
        $nouveauStock = $ancienStock - $lot->getQuantite();

        if ($nouveauStock < 0) {
            return $this->json(['message' => 'Stock insuffisant pour supprimer ce lot'], 400);
        }

        $produit->setStockActuel($nouveauStock);

        // Enregistrement du mouvement de stock
        $mvt = new MouvementStock();
        $mvt->setProduit($produit);
        $mvt->setType('sortie');
        $mvt->setQuantite($lot->getQuantite());
        $mvt->setDate(new \DateTime());
        $mvt->setSource('Suppression Lot');
        $mvt->setCommentaire('Suppression du lot ID ' . $lot->getId());

        $em->persist($mvt);
        $em->flush();

        return $this->json(['message' => 'Lot supprimé avec succès']);
    }


}
