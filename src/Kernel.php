<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function getProjectDir(): string
    {
        return dirname(__DIR__);
    }

    public function getCacheDir(): string
    {
        // Use /tmp on Heroku (ephemeral filesystem)
        if ($this->isHeroku()) {
            return '/tmp/symfony/cache/' . $this->environment;
        }
        return $this->getProjectDir() . '/var/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        // Use /tmp on Heroku (ephemeral filesystem)
        if ($this->isHeroku()) {
            return '/tmp/symfony/logs';
        }
        return $this->getProjectDir() . '/var/log';
    }

    private function isHeroku(): bool
    {
        return getenv('DYNO') !== false ||
               (getenv('DATABASE_URL') !== false && str_contains(getenv('DATABASE_URL') ?: '', 'heroku'));
    }
}
