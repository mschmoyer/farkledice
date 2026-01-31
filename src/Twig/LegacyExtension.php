<?php

namespace App\Twig;

use App\Service\Legacy\SmartyBridge;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension for including legacy Smarty templates.
 * Enables gradual migration from Smarty to Twig.
 */
class LegacyExtension extends AbstractExtension
{
    private SmartyBridge $smarty;

    public function __construct(SmartyBridge $smarty)
    {
        $this->smarty = $smarty;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('legacy_include', [$this, 'includeLegacyTemplate'], [
                'is_safe' => ['html']
            ]),
            new TwigFunction('legacy_exists', [$this, 'templateExists']),
        ];
    }

    /**
     * Include a Smarty template from within Twig.
     *
     * Usage in Twig:
     *   {{ legacy_include('farkle_div_lobby.tpl', {lobbyinfo: lobbyData}) }}
     *
     * @param string $template The Smarty template name
     * @param array $vars Variables to pass to the template
     * @return string The rendered HTML
     */
    public function includeLegacyTemplate(string $template, array $vars = []): string
    {
        return $this->smarty->render($template, $vars);
    }

    /**
     * Check if a legacy Smarty template exists.
     *
     * Usage in Twig:
     *   {% if legacy_exists('farkle_div_custom.tpl') %}
     *       {{ legacy_include('farkle_div_custom.tpl') }}
     *   {% endif %}
     *
     * @param string $template The Smarty template name
     * @return bool True if template exists
     */
    public function templateExists(string $template): bool
    {
        return $this->smarty->templateExists($template);
    }
}
