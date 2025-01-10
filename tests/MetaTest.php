<?php

declare(strict_types=1);

namespace BEAR\AppMeta;

use FakeVendor\HelloWorld\Resource\App\One;
use FakeVendor\HelloWorld\Resource\App\Sub\Sub\Four;
use FakeVendor\HelloWorld\Resource\App\Sub\Three;
use FakeVendor\HelloWorld\Resource\App\Two;
use FakeVendor\HelloWorld\Resource\App\User;
use FakeVendor\HelloWorld\Resource\Page\Index;
use PHPUnit\Framework\TestCase;

use function array_map;
use function dirname;
use function file_put_contents;
use function sort;
use function str_replace;

use const DIRECTORY_SEPARATOR;

class MetaTest extends TestCase
{
    /** @var Meta */
    protected $meta;

    protected function setUp(): void
    {
        parent::setUp();

        $app = $this->normalizePath(dirname(__DIR__) . '/tests/Fake/fake-app/var/tmp');
        file_put_contents(
            $app . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'cache',
            '1',
        );
        $this->meta = new Meta('FakeVendor\HelloWorld', 'prod-app');
    }

    public function testAppReflectorResourceList(): void
    {
        $meta = new Meta('FakeVendor\HelloWorld');
        $classes = $files = [];
        foreach ($meta->getResourceListGenerator() as [$class, $file]) {
            $classes[] = $class;
            $files[] = $file;
        }

        $expect = [
            One::class,
            Two::class,
            User::class,
            Index::class,
            Three::class,
            Four::class,
        ];
        $this->assertSame($expect, $classes);

        // ファイルパスの比較は相対パスで行う
        $expectFiles = [
            'src/Resource/App/One.php',
            'src/Resource/App/Two.php',
            'src/Resource/App/User.php',
            'src/Resource/Page/Index.php',
            'src/Resource/App/Sub/Three.php',
            'src/Resource/App/Sub/Sub/Four.php',
        ];
        sort($expectFiles);
        $actualFiles = array_map(
            fn ($file) => str_replace($meta->appDir . DIRECTORY_SEPARATOR, '', $this->normalizePath($file)),
            $files,
        );
        sort($actualFiles);
        $this->assertSame($expectFiles, $actualFiles);
    }

    public function testVarTmpFolderCreation(): void
    {
        new Meta('FakeVendor\HelloWorld', 'stage-app');
        $this->assertFileExists($this->normalizePath(__DIR__ . '/Fake/fake-app/var/log/stage-app'));
        $this->assertFileExists($this->normalizePath(__DIR__ . '/Fake/fake-app/var/tmp/stage-app'));
    }

    public function testDoNotClear(): void
    {
        new Meta('FakeVendor\HelloWorld', 'test-app');
        $this->assertFileExists($this->normalizePath(__DIR__ . '/Fake/fake-app/var/tmp/test-app/not-cleared.txt'));
    }

    private function normalizePath(string $path): string
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }
}
