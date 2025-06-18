<?php

namespace App\Controller;

use App\Repository\MouvementStockRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MouvementStockController extends AbstractController
{
    #[Route('/mouvement/stock', name: 'app_mouvement_stock')]
    public function index(): Response
    {
        return $this->render('mouvement_stock/index.html.twig', [
            'controller_name' => 'MouvementStockController',
        ]);
    }

    #[Route('/api/mouvements', name: 'api_mouvements_list', methods: ['GET'])]
    public function liste(Request $request, MouvementStockRepository $repo): JsonResponse
    {
        $filters = [
            'produit' => $request->query->get('produit'),
            'type' => $request->query->get('type'),
            'date_start' => $request->query->get('date_start'),
            'date_end' => $request->query->get('date_end'),
        ];

        $resultats = $repo->findFiltered($filters);

        $data = array_map(function ($mvt) {
            return [
                'id' => $mvt->getId(),
                'date' => $mvt->getDate()->format('Y-m-d'),
                'produit' => $mvt->getProduit()->getNom(),
                'type' => $mvt->getType(),
                'quantite' => $mvt->getQuantite(),
                'source' => $mvt->getSource(),
                'commentaire' => $mvt->getCommentaire(),
            ];
        }, $resultats);

        return $this->json($data);
    }

    #[Route('/api/mouvements/stats', name: 'api_mouvements_stats', methods: ['GET'])]
    public function stats(Request $request, MouvementStockRepository $repo): JsonResponse
    {
        $filters = [
            'produit' => $request->query->get('produit'),
            'type' => $request->query->get('type'),
            'date_start' => $request->query->get('date_start'),
            'date_end' => $request->query->get('date_end'),
        ];

        $entrees = $repo->countByType('entrée', $filters);
        $sorties = $repo->countByType('sortie', $filters);

        return $this->json([
            'entrees' => $entrees,
            'sorties' => $sorties,
            'total' => $entrees + $sorties,
        ]);
    }
}
