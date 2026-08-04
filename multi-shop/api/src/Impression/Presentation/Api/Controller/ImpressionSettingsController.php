<?php

namespace App\Impression\Presentation\Api\Controller;

use App\Impression\Application\Command\UpdatePrintSettings\UpdatePrintSettingsCommand;
use App\Impression\Application\Command\UpdatePrintSettings\UpdatePrintSettingsHandler;
use App\Impression\Application\Query\GetPrintSettings\GetPrintSettingsHandler;
use App\Impression\Application\Query\GetPrintSettings\GetPrintSettingsQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
final class ImpressionSettingsController extends AbstractController
{
    public function __construct(
        private readonly GetPrintSettingsHandler $getPrintSettingsHandler,
        private readonly UpdatePrintSettingsHandler $updatePrintSettingsHandler,
    ) {
    }

    #[Route('/impressions/settings', name: 'api_impressions_settings_show', methods: ['GET'])]
    #[IsGranted('impression.settings.view')]
    public function show(): JsonResponse
    {
        $dto = $this->getPrintSettingsHandler->handle(new GetPrintSettingsQuery());

        return $this->json($dto->toArray());
    }

    #[Route('/impressions/settings', name: 'api_impressions_settings_update', methods: ['PUT'])]
    #[IsGranted('impression.settings.manage')]
    public function update(Request $request): JsonResponse
    {
        try {
            $dto = $this->updatePrintSettingsHandler->handle(new UpdatePrintSettingsCommand($request->toArray()));
        } catch (\ValueError $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($dto->toArray());
    }
}
