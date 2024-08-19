<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TemplateExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            // new TwigFunction('setts', [$this, 'generateSetting'], ['is_safe' => ['html']]),
            new TwigFunction('icon', [$this, 'generateIcon'], ['is_safe' => ['html']]),
        ];
    }
    // public function generateSetting(string $value, SiteSettingsFinder $setts)
    // {
    //     return $setts->get($value);
    // }

    public function generateIcon(string $value)
    {
        return '<svg role="img" width="20" height="20"><use xlink:href="/img/feather-icons.svg#' . $value . '"></use></svg>';
    }

}
