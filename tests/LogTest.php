<?php
/**
 * This file is part of EasySwoole.
 *
 * @link     https://www.easyswoole.com
 * @document https://www.easyswoole.com
 * @contact  https://www.easyswoole.com/Preface/contact.html
 * @license  https://github.com/easy-swoole/easyswoole/blob/3.x/LICENSE
 */
declare(strict_types=1);

namespace EasySwoole\ThinkLog\Tests;

use EasySwoole\ThinkLog\Log;
use PHPUnit\Framework\TestCase;

final class LogTest extends TestCase
{
    public function testLogWithTestDriver()
    {
        $config = [
            // 日志记录方式，内置 file 支持扩展
            'type'  => 'test',
            // 日志保存目录
            'path'  => __DIR__ . DIRECTORY_SEPARATOR,
            // 日志记录级别
            'level' => [],
        ];

        $filename = date('d') . '_cli.log';
        $logFilePath = __DIR__ . DIRECTORY_SEPARATOR . date('Ym') . DIRECTORY_SEPARATOR;
        $logFile = $logFilePath . $filename;
        $this->removeDir($logFilePath);

        $log = new Log();
        $log->init($config);
        $log->record('log1', 'log');

        $this->assertFileDoesNotExist($logFile);
    }

    private function getFilesByDir(string $dir)
    {
        $dir = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!file_exists($dir)) {
            return [];
        }

        $files = scandir($dir);
        $lastFiles = [];

        foreach ($files as $file) {

            if ($file === '.' || $file === '..') {
                continue;
            }

            $fileRealPath = $dir . $file;

            if (is_dir($fileRealPath)) {
                $childFiles = $this->getFilesByDir($fileRealPath);
                $lastFiles = array_merge($lastFiles, $childFiles);
            } else {
                $lastFiles[] = $fileRealPath;
            }
        }

        return $lastFiles;
    }

    private function removeDir(string $dir)
    {
        $dir = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!file_exists($dir)) {
            return;
        }

        $files = scandir($dir);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $fileRealPath = $dir . $file;

            if (is_dir($fileRealPath)) {
                $this->removeDir($fileRealPath);
            } else {
                if (file_exists($fileRealPath)) {
                    @unlink($fileRealPath);
                }
            }
        }

        @rmdir($dir);
    }

    private function removeFile(string $file)
    {
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    public function testLogOverMaxFileSize()
    {
        $config = [
            // 日志记录方式，支持 file socket
            'type'        => 'File',
            // 日志保存目录
            'path'        => __DIR__ . DIRECTORY_SEPARATOR,
            // 单个日志文件的大小限制，超过后会自动记录到第二个文件
            'file_size'   => 2097152,
            // 日志的时间格式，默认是` c `
            'time_format' => 'c'
        ];

        $filename = date('d') . '_cli.log';
        $logFilePath = __DIR__ . DIRECTORY_SEPARATOR . date('Ym') . DIRECTORY_SEPARATOR;
        $this->removeDir($logFilePath);

        $log = new Log();
        $log->init($config);
        foreach (range(1, 2097) as $i) {
            $log->record(str_repeat('x', 965));
        }

        $files = $this->getFilesByDir($logFilePath);
        $cliFiles = [];
        foreach ($files as $file) {
            if (strpos($file, $filename) !== false) {
                $cliFiles[] = $file;
            }
        }

        $this->assertTrue(\count($cliFiles) > 1);

        $this->removeDir($logFilePath);
    }

    public function testLogRecordFunc()
    {
        $config = [
            // 日志记录方式，内置 file 支持扩展
            'type'  => 'File',
            // 日志保存目录
            'path'  => __DIR__ . DIRECTORY_SEPARATOR,
            // 日志记录级别
            'level' => [],
        ];

        $filename = date('d') . '_cli.log';
        $logFilePath = __DIR__ . DIRECTORY_SEPARATOR . date('Ym') . DIRECTORY_SEPARATOR;
        $logFile = $logFilePath . $filename;
        $this->removeDir($logFilePath);

        $log = new Log();
        $log->init($config);
        $log->record('测试日志信息');
        $log->record('测试日志信息，这是警告级别', 'notice');
        $log->write('测试日志信息，这是警告级别，并且实时写入', 'notice');

        $logContent = file_get_contents($logFile);

        $this->assertStringContainsString('[ log ] 测试日志信息', $logContent);
        $this->assertStringContainsString('[ notice ] 测试日志信息，这是警告级别', $logContent);
        $this->assertStringContainsString('[ notice ] 测试日志信息，这是警告级别，并且实时写入', $logContent);

        $this->removeDir($logFilePath);
    }

    public function testLogLevel()
    {
        $config = [
            // 日志记录方式，内置 file 支持扩展
            'type'  => 'File',
            // 日志保存目录
            'path'  => __DIR__ . DIRECTORY_SEPARATOR,
            // 日志记录级别
            'level' => [],
        ];

        $filename = date('d') . '_cli.log';
        $logFilePath = __DIR__ . DIRECTORY_SEPARATOR . date('Ym') . DIRECTORY_SEPARATOR;
        $logFile = $logFilePath . $filename;
        $this->removeDir($logFilePath);

        $log = new Log();
        $log->init($config);
        $log->record('log1', 'log');
        $log->record('错误信息', 'error');
        $log->record('notice1', 'notice');
        $log->record('日志信息', 'info');
        $log->record('debug1', 'debug');
        $log->record('sql1', 'sql');

        $logContent = file_get_contents($logFile);

        $this->assertStringContainsString('[ log ] log1', $logContent);
        $this->assertStringContainsString('[ error ] 错误信息', $logContent);
        $this->assertStringContainsString('[ notice ] notice1', $logContent);
        $this->assertStringContainsString('[ info ] 日志信息', $logContent);
        $this->assertStringContainsString('[ debug ] debug1', $logContent);
        $this->assertStringContainsString('[ sql ] sql1', $logContent);

        $this->removeDir($logFilePath);
    }

    public function testLogHelperFunc()
    {
        $config = [
            // 日志记录方式，内置 file 支持扩展
            'type'  => 'File',
            // 日志保存目录
            'path'  => __DIR__ . DIRECTORY_SEPARATOR,
            // 日志记录级别
            'level' => [],
        ];

        $filename = date('d') . '_cli.log';
        $logFilePath = __DIR__ . DIRECTORY_SEPARATOR . date('Ym') . DIRECTORY_SEPARATOR;
        $logFile = $logFilePath . $filename;
        $this->removeDir($logFilePath);

        $log = new Log();
        $log->init($config);
        trace('错误信息', 'error', $log);
        trace('日志信息', 'info', $log);

        $logContent = file_get_contents($logFile);

        $this->assertStringContainsString('[ error ] 错误信息', $logContent);
        $this->assertStringContainsString('[ info ] 日志信息', $logContent);

        $this->removeDir($logFilePath);
    }

    public function testLogLevelFilterLevel()
    {
        $config = [
            // 日志记录方式，内置 file 支持扩展
            'type'  => 'File',
            // 日志保存目录
            'path'  => __DIR__ . DIRECTORY_SEPARATOR,
            // 日志记录级别，使用数组表示
            'level' => ['error'],
        ];

        $filename = date('d') . '_cli.log';
        $logFilePath = __DIR__ . DIRECTORY_SEPARATOR . date('Ym') . DIRECTORY_SEPARATOR;
        $logFile = $logFilePath . $filename;
        $this->removeDir($logFilePath);

        $log = new Log();
        $log->init($config);
        $log->record('log1', 'log');
        $log->record('错误信息', 'error');
        $log->record('notice1', 'notice');
        $log->record('日志信息', 'info');
        $log->record('debug1', 'debug');
        $log->record('sql1', 'sql');

        $logContent = file_get_contents($logFile);

        $this->assertStringNotContainsString('[ log ] log1', $logContent);
        $this->assertStringContainsString('[ error ] 错误信息', $logContent);
        $this->assertStringNotContainsString('[ notice ] notice1', $logContent);
        $this->assertStringNotContainsString('[ info ] 日志信息', $logContent);
        $this->assertStringNotContainsString('[ debug ] debug1', $logContent);
        $this->assertStringNotContainsString('[ sql ] sql1', $logContent);

        $this->removeDir($logFilePath);
    }

    public function testLogWithSingleFile()
    {
        $config = [
            // 日志记录方式，内置 file 支持扩展
            'type'   => 'File',
            // 日志保存目录
            'path'   => __DIR__ . DIRECTORY_SEPARATOR,
            // 日志记录级别，使用数组表示
            'level'  => [],
            // 开启单文件日志写入
            'single' => true,
        ];

        $filename = 'single_cli.log';
        $logFilePath = __DIR__ . DIRECTORY_SEPARATOR;
        $logFile = $logFilePath . $filename;
        $this->removeFile($logFile);

        $log = new Log();
        $log->init($config);
        $log->record('log1', 'log');
        $log->record('错误信息', 'error');
        $log->record('notice1', 'notice');
        $log->record('日志信息', 'info');
        $log->record('debug1', 'debug');
        $log->record('sql1', 'sql');

        $logContent = file_get_contents($logFile);

        $this->assertStringContainsString('[ log ] log1', $logContent);
        $this->assertStringContainsString('[ error ] 错误信息', $logContent);
        $this->assertStringContainsString('[ notice ] notice1', $logContent);
        $this->assertStringContainsString('[ info ] 日志信息', $logContent);
        $this->assertStringContainsString('[ debug ] debug1', $logContent);
        $this->assertStringContainsString('[ sql ] sql1', $logContent);

        $this->removeFile($logFile);
    }

    public function testLogMaxFiles()
    {
        $config = [
            // 日志记录方式，内置 file 支持扩展
            'type'      => 'File',
            // 日志保存目录
            'path'      => __DIR__ . DIRECTORY_SEPARATOR,
            // 单个日志文件的大小限制，超过后会自动记录到第二个文件
            'file_size' => 1,
            // 日志记录级别，使用数组表示
            'level'     => [],
            // 日志文件最多只会保留30个，超过会自动清理较早的日志文件，避免日志文件长期写入占满磁盘空间
            // 文件日志的自动清理功能
            // 开启自动清理功能后，不会生成日期子目录。
            'max_files' => 2
        ];

        $filename = date('Ymd') . '_cli.log';
        $logFilePath = __DIR__ . DIRECTORY_SEPARATOR;
        $logFile = $logFilePath . $filename;
        $files = $this->getFilesByDir(__DIR__);
        foreach ($files as $file) {
            if (strpos($file, $filename) !== false) {
                $this->removeFile($file);
            }
        }

        $log = new Log();
        $log->init($config);
        $log->record('foo', 'log');
        $log->record('bar', 'info');
        $log->record('bar1', 'info');

        $logContent = file_get_contents($logFile);
        $this->assertStringContainsString('[ info ] bar', $logContent);

        $fileCount = 1;
        $files = $this->getFilesByDir(__DIR__);
        foreach ($files as $file) {
            if (strpos($file, $logFile) !== false) {
                $fileCount += 1;
            }
        }

        $this->assertTrue($fileCount <= $config['max_files']);

        $files = $this->getFilesByDir(__DIR__);
        foreach ($files as $file) {
            if (strpos($file, $filename) !== false) {
                $this->removeFile($file);
            }
        }
    }

    public function testLogSpecLevelSingleFile()
    {
        $config = [
            // 日志记录方式，内置 file 支持扩展
            'type'        => 'File',
            // 日志保存目录
            'path'        => __DIR__ . DIRECTORY_SEPARATOR,
            // 日志记录级别，使用数组表示
            'level'       => [],
            // error和sql日志单独记录
            'apart_level' => ['error', 'sql'],
        ];

        $logFilePath = __DIR__ . DIRECTORY_SEPARATOR . date('Ym') . DIRECTORY_SEPARATOR;
        $this->removeDir($logFilePath);

        $log = new Log();
        $log->init($config);
        $log->record('foo', 'error');
        $log->record('bar', 'sql');

        $filename = date('d') . '_error_cli.log';
        $logFile = $logFilePath . $filename;
        $logContent = file_get_contents($logFile);
        $this->assertStringContainsString('[ error ] foo', $logContent);

        $filename = date('d') . '_sql_cli.log';
        $logFile = $logFilePath . $filename;
        $logContent = file_get_contents($logFile);
        $this->assertStringContainsString('[ sql ] bar', $logContent);

        $this->removeDir($logFilePath);
    }

    public function testLogFilterKey()
    {
        $config = [
            // 日志记录方式，内置 file 支持扩展
            'type'      => 'File',
            // 日志保存目录
            'path'      => __DIR__ . DIRECTORY_SEPARATOR,
            // 日志记录级别，使用数组表示
            'level'     => [],
            // 授权只有202.12.36.89 才能记录日志
            'allow_key' => ['202.12.36.89'],
        ];

        $filename = date('d') . '_cli.log';
        $logFilePath = __DIR__ . DIRECTORY_SEPARATOR . date('Ym') . DIRECTORY_SEPARATOR;
        $logFile = $logFilePath . $filename;
        $this->removeDir($logFilePath);

        $log = new Log();
        $log->init($config);
        $log->key('xxx');
        $log->record('foo', 'error');

        $logContent = file_get_contents($logFile);
        $this->assertStringNotContainsString('[ error ] foo', $logContent);

        $this->removeDir($logFilePath);

        $log = new Log();
        $log->init($config);
        $log->key('202.12.36.89');
        $log->record('foo', 'error');

        $logContent = file_get_contents($logFile);
        $this->assertStringContainsString('[ error ] foo', $logContent);

        $this->removeDir($logFilePath);
    }
}
