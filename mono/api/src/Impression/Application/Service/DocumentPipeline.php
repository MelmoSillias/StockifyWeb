<?php

namespace App\Impression\Application\Service;

use App\Impression\Application\Builder\DocumentViewModelBuilder;
use App\Impression\Application\Dto\DocumentResponse;
use App\Impression\Application\Renderer\DocumentRendererInterface;
use App\Impression\Application\Resolver\DocumentDataResolverRegistry;
use App\Impression\Domain\Repository\PrintSettingsRepositoryInterface;
use App\Impression\Domain\ValueObject\DocumentRequest;
use App\Impression\Infrastructure\Exporter\DocumentExporterRegistry;

final class DocumentPipeline
{
    public function __construct(
        private readonly PrintSettingsRepositoryInterface $printSettingsRepository,
        private readonly DocumentDataResolverRegistry $resolverRegistry,
        private readonly DocumentViewModelBuilder $viewModelBuilder,
        private readonly DocumentRendererInterface $renderer,
        private readonly DocumentExporterRegistry $exporterRegistry,
    ) {
    }

    public function execute(DocumentRequest $request): DocumentResponse
    {
        $settings = $this->printSettingsRepository->getOrCreateDefault();
        $data = $this->resolverRegistry->resolve($request);
        $viewModel = $this->viewModelBuilder->build($request, $settings, $data);
        $html = $this->renderer->render($viewModel);
        $content = $this->exporterRegistry->export($viewModel, $html, $request->format);

        $baseName = $data['filename'] ?? $request->documentType->value;
        $filename = sprintf('%s.%s', $baseName, $request->format->extension());

        return new DocumentResponse(
            content: $content,
            format: $request->format,
            filename: $filename,
            inline: $request->inline,
        );
    }
}
