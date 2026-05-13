<?php

namespace App\Twig;

use App\Controller\SiteSectionController;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class SiteNavExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('site_header_nav', $this->headerNav(...)),
            new TwigFunction('site_full_nav', $this->fullNav(...)),
        ];
    }

    /**
     * @return array<string, array{label: string, items: list<array{path: string, label: string}>}>
     */
    public function headerNav(): array
    {
        return SiteSectionController::headerNavTree();
    }

    /**
     * @return array<string, array{label: string, items: list<array{path: string, label: string}>}>
     */
    public function fullNav(): array
    {
        return SiteSectionController::navTree();
    }
}
