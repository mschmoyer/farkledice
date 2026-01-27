<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Fallback controller that routes unmatched requests to the legacy application.
 * This enables the strangler fig pattern - Symfony handles new routes,
 * legacy code handles everything else.
 */
class LegacyFallbackController extends AbstractController
{
    private string $projectDir;
    private string $wwwrootDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
        $this->wwwrootDir = $projectDir . '/wwwroot';
    }

    /**
     * Catch-all route with lowest priority.
     * Handles requests that don't match any Symfony routes.
     */
    #[Route('/{path}', name: 'legacy_fallback', requirements: ['path' => '.*'], priority: -1000)]
    public function fallback(Request $request, string $path = ''): Response
    {
        // Handle root URL - redirect to legacy entry point
        if ($path === '' || $path === '/') {
            return $this->executeLegacyScript('farkle.php', $request);
        }

        // Check for static files in wwwroot (CSS, JS, images)
        $staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'ico', 'svg', 'woff', 'woff2', 'ttf', 'eot'];
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if (in_array(strtolower($extension), $staticExtensions)) {
            return $this->serveStaticFile($path);
        }

        // Handle direct PHP file requests (e.g., wwwroot/farkle.php)
        if (str_ends_with($path, '.php')) {
            $phpFile = $this->resolvePhpFile($path);
            if ($phpFile) {
                return $this->executeLegacyScript($phpFile, $request);
            }
        }

        // Handle paths that map to legacy URLs
        // e.g., /farkle_fetch.php -> wwwroot/farkle_fetch.php
        $legacyMappings = [
            'farkle.php' => 'farkle.php',
            'farkle_fetch.php' => 'farkle_fetch.php',
            'wwwroot/farkle.php' => 'farkle.php',
            'wwwroot/farkle_fetch.php' => 'farkle_fetch.php',
        ];

        if (isset($legacyMappings[$path])) {
            return $this->executeLegacyScript($legacyMappings[$path], $request);
        }

        // Check if it's a path to wwwroot
        if (str_starts_with($path, 'wwwroot/')) {
            $wwwrootPath = substr($path, 8); // Remove 'wwwroot/' prefix
            $fullPath = $this->wwwrootDir . '/' . $wwwrootPath;

            if (is_file($fullPath)) {
                if (str_ends_with($fullPath, '.php')) {
                    return $this->executeLegacyScript($wwwrootPath, $request);
                }
                return new BinaryFileResponse($fullPath);
            }
        }

        // Default: try to serve from wwwroot
        $wwwrootFile = $this->wwwrootDir . '/' . $path;
        if (is_file($wwwrootFile)) {
            if (str_ends_with($wwwrootFile, '.php')) {
                return $this->executeLegacyScript($path, $request);
            }
            return new BinaryFileResponse($wwwrootFile);
        }

        // Nothing found - 404
        throw new NotFoundHttpException("Page not found: {$path}");
    }

    /**
     * Serve a static file from wwwroot or other directories
     */
    private function serveStaticFile(string $path): Response
    {
        // Try wwwroot first
        $file = $this->wwwrootDir . '/' . $path;
        if (is_file($file)) {
            return new BinaryFileResponse($file);
        }

        // Try project root (for css/, js/ directories)
        $file = $this->projectDir . '/' . $path;
        if (is_file($file)) {
            return new BinaryFileResponse($file);
        }

        throw new NotFoundHttpException("Static file not found: {$path}");
    }

    /**
     * Resolve a PHP path to the actual file in wwwroot
     */
    private function resolvePhpFile(string $path): ?string
    {
        // Remove leading slashes and 'wwwroot/' prefix
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'wwwroot/')) {
            $path = substr($path, 8);
        }

        $fullPath = $this->wwwrootDir . '/' . $path;
        if (is_file($fullPath)) {
            return $path;
        }

        return null;
    }

    /**
     * Execute a legacy PHP script and capture its output
     */
    private function executeLegacyScript(string $script, Request $request): Response
    {
        $scriptPath = $this->wwwrootDir . '/' . $script;

        if (!is_file($scriptPath)) {
            throw new NotFoundHttpException("Legacy script not found: {$script}");
        }

        // Save current state
        $originalCwd = getcwd();
        $originalGet = $_GET;
        $originalPost = $_POST;
        $originalRequest = $_REQUEST;
        $originalServer = $_SERVER;

        try {
            // Set up the environment for legacy code
            $_GET = $request->query->all();
            $_POST = $request->request->all();
            $_REQUEST = array_merge($_GET, $_POST);

            // Update $_SERVER with request info
            $_SERVER['REQUEST_URI'] = $request->getRequestUri();
            $_SERVER['REQUEST_METHOD'] = $request->getMethod();
            $_SERVER['QUERY_STRING'] = $request->getQueryString() ?? '';
            $_SERVER['SCRIPT_FILENAME'] = $scriptPath;
            $_SERVER['SCRIPT_NAME'] = '/wwwroot/' . $script;
            $_SERVER['PHP_SELF'] = '/wwwroot/' . $script;
            $_SERVER['DOCUMENT_ROOT'] = $this->projectDir;

            // Change to wwwroot directory - legacy code expects to run from there
            // and uses relative paths like ../includes/baseutil.php
            chdir($this->wwwrootDir);

            // Capture output
            ob_start();

            // Include the legacy script directly
            include $scriptPath;

            $output = ob_get_clean();

            // Detect content type from output
            $contentType = 'text/html';
            if ($this->isJsonResponse($output)) {
                $contentType = 'application/json';
            }

            // Get any headers that were set
            $headers = [];
            foreach (headers_list() as $header) {
                if (str_contains($header, ':')) {
                    [$name, $value] = explode(':', $header, 2);
                    $headers[trim($name)] = trim($value);
                }
            }

            // Clear headers (they've been captured)
            if (!headers_sent()) {
                header_remove();
            }

            return new Response($output, Response::HTTP_OK, array_merge(
                ['Content-Type' => $contentType],
                $headers
            ));
        } finally {
            // Restore original state
            chdir($originalCwd);
            $_GET = $originalGet;
            $_POST = $originalPost;
            $_REQUEST = $originalRequest;
            $_SERVER = $originalServer;
        }
    }

    /**
     * Check if output looks like JSON
     */
    private function isJsonResponse(string $output): bool
    {
        $trimmed = trim($output);
        return (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')) ||
               (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']'));
    }
}
