<?php

declare(strict_types=1);

namespace BEAR\AppMeta;

use BEAR\AppMeta\Exception\AppNameException;
use BEAR\AppMeta\Exception\NotWritableException;

/**
 * Application Meta-Data
 */
class Meta extends AbstractAppMeta
{
    /**
     * @param string $name    application name      (Vendor\Project)
     * @param string $context application context   (prod-hal-app)
     * @param string $appDir  application directory
     */
    public function __construct(string $name, string $context = 'app', string $appDir = '')
    {
        $this->name = $name;
        try {
            $this->appDir = $appDir ?: dirname((string) (new \ReflectionClass($name . '\Module\AppModule'))->getFileName(), 3);
        } catch (\ReflectionException $e) {
            throw new AppNameException($name);
        }
        $this->tmpDir = $this->appDir . '/var/tmp/' . $context;
        if (! file_exists($this->tmpDir) && ! @mkdir($this->tmpDir, 0777, true) && ! is_dir($this->tmpDir)) {
            throw new NotWritableException($this->tmpDir);
        }
        $this->logDir = $this->appDir . '/var/log/' . $context;
        if (! file_exists($this->logDir) && ! @mkdir($this->logDir, 0777, true) && ! is_dir($this->logDir)) {
            throw new NotWritableException($this->logDir);
        }
    }
}
