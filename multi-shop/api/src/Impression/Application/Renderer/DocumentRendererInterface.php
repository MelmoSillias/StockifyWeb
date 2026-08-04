<?php

namespace App\Impression\Application\Renderer;

use App\Impression\Application\Dto\DocumentViewModel;

interface DocumentRendererInterface
{
    public function render(DocumentViewModel $viewModel): string;
}
