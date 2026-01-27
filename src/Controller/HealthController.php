<?php

namespace App\Controller;

use App\Service\Legacy\LegacyDatabaseBridge;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Health check controller for monitoring and load balancer health checks.
 * Only accessible in dev environment.
 */
class HealthController extends AbstractController
{
    #[Route('/health', name: 'health_check', methods: ['GET'])]
    public function healthCheck(LegacyDatabaseBridge $db): JsonResponse
    {
        // Only accessible in dev environment
        if ($this->getParameter('kernel.environment') !== 'dev') {
            throw new NotFoundHttpException();
        }
        $status = 'healthy';
        $checks = [];

        // Check database connection
        try {
            $connection = $db->getConnection();
            $result = $connection->query('SELECT 1')->fetch();
            $checks['database'] = 'ok';
        } catch (\Exception $e) {
            $checks['database'] = 'error: ' . $e->getMessage();
            $status = 'unhealthy';
        }

        // Check if legacy files are accessible
        $legacyFile = $this->getParameter('kernel.project_dir') . '/wwwroot/farkle.php';
        $checks['legacy_files'] = is_file($legacyFile) ? 'ok' : 'missing';
        if ($checks['legacy_files'] !== 'ok') {
            $status = 'degraded';
        }

        // Get app version
        $version = $_ENV['APP_VERSION'] ?? (defined('APP_VERSION') ? APP_VERSION : 'unknown');

        return $this->json([
            'status' => $status,
            'version' => $version,
            'checks' => $checks,
            'timestamp' => date('c'),
            'environment' => $this->getParameter('kernel.environment'),
        ], $status === 'healthy' ? 200 : 503);
    }

    #[Route('/health/simple', name: 'health_simple', methods: ['GET'])]
    public function simpleHealthCheck(): JsonResponse
    {
        // Only accessible in dev environment
        if ($this->getParameter('kernel.environment') !== 'dev') {
            throw new NotFoundHttpException();
        }

        // Simple health check for basic load balancer checks
        return $this->json(['status' => 'ok']);
    }
}
