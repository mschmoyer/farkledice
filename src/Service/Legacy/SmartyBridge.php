<?php

namespace App\Service\Legacy;

use Smarty;

/**
 * Bridge service for rendering legacy Smarty templates.
 * Allows Twig templates to include Smarty partials during migration.
 */
class SmartyBridge
{
    private BaseUtilBridge $baseUtil;
    private ?Smarty $smarty = null;

    public function __construct(BaseUtilBridge $baseUtil)
    {
        $this->baseUtil = $baseUtil;
    }

    /**
     * Get the Smarty instance.
     * Initializes baseutil.php which creates the global $smarty.
     *
     * @return Smarty The Smarty instance
     */
    public function getSmarty(): Smarty
    {
        if ($this->smarty === null) {
            // Initialize legacy baseutil which creates $smarty global
            $this->baseUtil->initialize();

            global $smarty;
            if ($smarty instanceof Smarty) {
                $this->smarty = $smarty;
            } else {
                throw new \RuntimeException('Smarty instance not available after baseutil initialization');
            }
        }

        return $this->smarty;
    }

    /**
     * Render a Smarty template and return the HTML.
     * Useful for embedding legacy templates in Twig.
     *
     * @param string $templateName The template file name (e.g., 'farkle_div_lobby.tpl')
     * @param array $variables Variables to assign to the template
     * @return string The rendered HTML
     */
    public function render(string $templateName, array $variables = []): string
    {
        $smarty = $this->getSmarty();

        foreach ($variables as $key => $value) {
            $smarty->assign($key, $value);
        }

        return $smarty->fetch($templateName);
    }

    /**
     * Assign a variable to Smarty.
     *
     * @param string $name The variable name
     * @param mixed $value The value
     */
    public function assign(string $name, mixed $value): void
    {
        $this->getSmarty()->assign($name, $value);
    }

    /**
     * Assign multiple variables to Smarty.
     *
     * @param array $variables Array of name => value pairs
     */
    public function assignMultiple(array $variables): void
    {
        $smarty = $this->getSmarty();
        foreach ($variables as $name => $value) {
            $smarty->assign($name, $value);
        }
    }

    /**
     * Display a Smarty template (outputs directly).
     *
     * @param string $templateName The template file name
     */
    public function display(string $templateName): void
    {
        $this->getSmarty()->display($templateName);
    }

    /**
     * Check if a template exists.
     *
     * @param string $templateName The template file name
     * @return bool True if template exists
     */
    public function templateExists(string $templateName): bool
    {
        return $this->getSmarty()->templateExists($templateName);
    }

    /**
     * Clear all assigned variables.
     */
    public function clearAllAssign(): void
    {
        $this->getSmarty()->clearAllAssign();
    }

    /**
     * Clear compiled templates.
     */
    public function clearCompiledTemplate(): void
    {
        $this->getSmarty()->clearCompiledTemplate();
    }
}
