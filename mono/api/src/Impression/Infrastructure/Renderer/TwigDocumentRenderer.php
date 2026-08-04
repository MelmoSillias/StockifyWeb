<?php

namespace App\Impression\Infrastructure\Renderer;

use App\Impression\Application\Dto\DocumentViewModel;
use App\Impression\Application\Renderer\DocumentRendererInterface;
use Twig\Environment;

final class TwigDocumentRenderer implements DocumentRendererInterface
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    public function render(DocumentViewModel $viewModel): string
    {
        $template = sprintf('impression/%s.html.twig', $viewModel->documentType);

        return $this->twig->render($template, [
            'vm' => $viewModel->toArray(),
            'page' => $viewModel->page,
            'profile' => $viewModel->profile,
            'data' => $viewModel->data,
            'auto_print' => $viewModel->autoPrint,
        ]);
    }
}
