<?php

namespace App\Impression\Presentation\Api\Controller;

use App\Impression\Application\Dto\DocumentResponse;
use App\Impression\Application\Factory\DocumentRequestFactory;
use App\Impression\Application\Service\DocumentPipeline;
use App\Impression\Domain\Repository\PrintSettingsRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
final class ImpressionTableController extends AbstractController
{
    public function __construct(
        private readonly DocumentPipeline $documentPipeline,
        private readonly DocumentRequestFactory $documentRequestFactory,
        private readonly PrintSettingsRepositoryInterface $printSettingsRepository,
    ) {
    }

    #[Route('/impressions/tables/{tableType}/export', name: 'api_impressions_tables_export', methods: ['POST'])]
    #[IsGranted('impression.tables.export')]
    public function export(string $tableType, Request $request): Response
    {
        try {
            $payload = $request->toArray();
            $settings = $this->printSettingsRepository->getOrCreateDefault();
            $documentRequest = $this->documentRequestFactory->createTableExport(
                tableType: $tableType,
                format: (string) ($payload['format'] ?? 'pdf'),
                page: isset($payload['page']) ? (string) $payload['page'] : null,
                payload: $payload,
                settings: $settings,
            );
            $response = $this->documentPipeline->execute($documentRequest);
        } catch (\InvalidArgumentException | \ValueError $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->toHttpResponse($response);
    }

    #[Route('/impressions/tables/{tableType}/print', name: 'api_impressions_tables_print', methods: ['POST'])]
    #[IsGranted('impression.documents.print')]
    public function print(string $tableType, Request $request): Response
    {
        try {
            $payload = $request->toArray();
            $settings = $this->printSettingsRepository->getOrCreateDefault();
            $documentRequest = $this->documentRequestFactory->createTablePrint(
                tableType: $tableType,
                page: isset($payload['page']) ? (string) $payload['page'] : null,
                payload: $payload,
                settings: $settings,
            );
            $response = $this->documentPipeline->execute($documentRequest);
        } catch (\InvalidArgumentException | \ValueError $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->toHttpResponse($response);
    }

    private function toHttpResponse(DocumentResponse $document): Response
    {
        $disposition = $document->inline ? 'inline' : 'attachment';

        return new Response(
            $document->content,
            Response::HTTP_OK,
            [
                'Content-Type' => $document->format->mimeType(),
                'Content-Disposition' => sprintf('%s; filename="%s"', $disposition, $document->filename),
            ],
        );
    }
}
