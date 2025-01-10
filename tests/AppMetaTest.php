<?php

declare(strict_types=1);

namespace BEAR\AppMeta;

use BEAR\AppMeta\Exception\AppNameException;
use BEAR\AppMeta\Exception\NotWritableException;
use FakeVendor\HelloWorld\Resource\App\One;
use FakeVendor\HelloWorld\Resource\App\Sub\Sub\Four;
use FakeVendor\HelloWorld\Resource\App\Sub\Three;
use FakeVendor\HelloWorld\Resource\App\Two;
use FakeVendor\HelloWorld\Resource\App\User;
use FakeVendor\HelloWorld\Resource\Page\Index;
use PHPUnit\Framework\TestCase;

use function array_map;
use function chmod;
use function dirname;
use function file_put_contents;
use function sort;
use function str_replace;

use const DIRECTORY_SEPARATOR;
use const PHP_OS_FAMILY;

class AppMetaTest extends TestCase
{
    /** @var Meta */
    protected $appMeta;

    protected function setUp(): void
    {
        parent::setUp();

        $app = dirname(__DIR__) . '/tests/Fake/fake-app/var/tmp';
        file_put_contents($app . '/app/cache', '1');
        // ディレクトリの権限を元に戻しておく
        chmod(__DIR__ . '/Fake/fake-not-writable/var', 0644);
        $this->appMeta = new Meta('FakeVendor\HelloWorld', 'prod-app');
    }

    protected function tearDown(): void
    {
        $notWritableDir = __DIR__ . '/Fake/fake-not-writable/var';
        if (PHP_OS_FAMILY === 'Windows') {
            @chmod($notWritableDir, 0777);
        } else {
            chmod($notWritableDir, 0777);
        }
    }

    public function testNew(): void
    {
        $actual = $this->appMeta;
        $this->assertInstanceOf(Meta::class, $actual);
        $this->assertFileExists($this->appMeta->tmpDir);
    }

    public function testAppReflectorResourceList(): void
    {
        $appMeta = new Meta('FakeVendor\HelloWorld');
        $classes = $files = [];
        foreach ($appMeta->getResourceListGenerator() as [$class, $file]) {
            $classes[] = $class;
            $files[] = $file;
        }

        $expectClasses = [
            One::class,
            Two::class,
            User::class,
            Index::class,
            Three::class,
            Four::class,
        ];
        sort($expectClasses);
        sort($classes);
        $this->assertSame($expectClasses, $classes);

        $expectFiles = [
            'src/Resource/App/One.php',
            'src/Resource/App/Two.php',
            'src/Resource/App/User.php',
            'src/Resource/Page/Index.php',
            'src/Resource/App/Sub/Three.php',
            'src/Resource/App/Sub/Sub/Four.php',
        ];
        sort($expectFiles);

        // 実際のフルパスから $appMeta->appDir までを削除し、相対パスにして比較する
        $actualFiles = array_map(static fn ($file) => str_replace($appMeta->appDir . '/', '', $file), $files);
        sort($actualFiles);
        $this->assertSame($expectFiles, $actualFiles);
    }

    public function testInvalidName(): void
    {
        $this->expectException(AppNameException::class);
        new Meta('Invalid\Invalid');
    }

    public function testNotWritable(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Skipping write-protected test on Windows.');
        }

        $this->expectException(NotWritableException::class);
        new Meta('FakeVendor\NotWritable');
    }

    public function testVarTmpFolderCreation(): void
    {
        new Meta('FakeVendor\HelloWorld', 'stage-app');
        $this->assertFileExists(__DIR__ . $this->normalizePath('/Fake/fake-app/var/log/stage-app'));
        $this->assertFileExists(__DIR__ . $this->normalizePath('/Fake/fake-app/var/tmp/stage-app'));
    }

    public function testDoNotClear(): void
    {
        new Meta('FakeVendor\HelloWorld', 'test-app');
        $this->assertFileExists(__DIR__ . '/Fake/fake-app/var/tmp/test-app/not-cleared.txt');
    }

    public function testGetGeneratorApp(): void
    {
        $appMeta = new Meta('FakeVendor\HelloWorld');
        $uris = [];
        $paths = [];
        foreach ($appMeta->getGenerator('app') as $uri) {
            $uris[] = $uri;
            $paths[] = $uri->uriPath;
        }

        $this->assertCount(5, $uris);
        $this->assertSame('/one', $uris[0]->uriPath);
        $this->assertSame(One::class, $uris[0]->class);
        // パス比較が Windows/Mac/Linux でずれないように部分一致テスト
        $this->assertStringContainsString('src/Resource/App/One.php', $uris[0]->filePath);
        $this->assertSame('/one', $paths[0]);
    }

    public function testGetGeneratorAll(): void
    {
        $appMeta = new Meta('FakeVendor\HelloWorld');
        $uris = [];
        foreach ($appMeta->getGenerator('*') as $uri) {
            $uris[] = $uri;
        }

        $this->assertCount(6, $uris);
        $this->assertSame('app://self/one', $uris[0]->uriPath);
        $this->assertSame(One::class, $uris[0]->class);
        $this->assertStringContainsString('src/Resource/App/One.php', $uris[0]->filePath);
    }

    public function normalizePath(string $path): string
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }
}
