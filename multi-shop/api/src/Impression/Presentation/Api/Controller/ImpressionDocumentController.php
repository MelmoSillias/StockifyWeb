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
final class ImpressionDocumentController extends AbstractController
{
    public function __construct(
        private readonly DocumentPipeline $documentPipeline,
        private readonly DocumentRequestFactory $documentRequestFactory,
        private readonly PrintSettingsRepositoryInterface $printSettingsRepository,
    ) {
    }

    #[Route('/impressions/documents/{type}/{id}', name: 'api_impressions_documents_show', methods: ['GET'])]
    #[IsGranted('impression.documents.print')]
    public function show(string $type, string $id, Request $request): Response
    {
        try {
            $settings = $this->printSettingsRepository->getOrCreateDefault();
            $disposition = (string) $request->query->get('disposition', 'inline');
            $documentRequest = $this->documentRequestFactory->createDocument(
                type: $type,
                id: $id,
                format: $request->query->get('format'),
                page: $request->query->get('page'),
                inline: 'attachment' !== $disposition,
                settings: $settings,
            );
            $response = $this->documentPipeline->execute($documentRequest);
        } catch (\InvalidArgumentException | \ValueError $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
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
