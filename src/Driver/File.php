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

namespace EasySwoole\ThinkLog\Driver;

use EasySwoole\ThinkLog\DriverInterface;
use Exception;

/**
 * Class File
 *
 * 本地化调试输出到文件
 *
 * @package EasySwoole\ThinkLog\Driver
 */
class File implements DriverInterface
{
    protected $config = [
        'time_format' => ' c ',
        'single'      => false,
        'file_size'   => 2097152,
        'path'        => '/tmp',
        'apart_level' => [],
        'max_files'   => 0,
        'json'        => false,
    ];

    // 实例化并传入参数
    public function __construct(array $config = [])
    {
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }
    }

    /**
     * 日志写入接口
     *
     * @param array $log    日志信息
     * @param bool  $append 是否追加请求信息
     *
     * @return bool
     */
    public function save(array $log = [], bool $append = false)
    {
        $destination = $this->getMasterLogFile();

        $path = dirname($destination);
        !is_dir($path) && mkdir($path, 0755, true);

        $info = [];

        foreach ($log as $type => $val) {

            foreach ($val as $msg) {
                if (!is_string($msg)) {
                    $msg = var_export($msg, true);
                }

                $info[$type][] = $this->config['json'] ? $msg : '[ ' . $type . ' ] ' . $msg;
            }

            if (!$this->config['json'] && (true === $this->config['apart_level'] || in_array($type, $this->config['apart_level']))) {
                // 独立记录的日志级别
                $filename = $this->getApartLevelFile($path, $type);

                $this->write($info[$type], $filename, true, $append);
                unset($info[$type]);
            }
        }

        if ($info) {
            return $this->write($info, $destination, false, $append);
        }

        return true;
    }

    /**
     * 获取主日志文件名
     *
     * @return string
     */
    protected function getMasterLogFile()
    {
        $cli = PHP_SAPI == 'cli' ? '_cli' : '';

        if ($this->config['single']) {

            $name = is_string($this->config['single']) ? $this->config['single'] : 'single';
            $destination = $this->config['path'] . $name . $cli . '.log';

        } else {

            if ($this->config['max_files']) {
                $filename = date('Ymd') . $cli . '.log';
                $files = glob($this->config['path'] . '*.log');

                try {
                    if (count($files) > $this->config['max_files']) {
                        unlink($files[0]);
                    }
                } catch (Exception $e) {
                }
            } else {
                $filename = date('Ym') . DIRECTORY_SEPARATOR . date('d') . $cli . '.log';
            }

            $destination = $this->config['path'] . $filename;
        }

        return $destination;
    }

    /**
     * 获取独立日志文件名
     *
     * @param string $path 日志目录
     * @param string $type 日志类型
     *
     * @return string
     */
    protected function getApartLevelFile(string $path, string $type)
    {
        $cli = PHP_SAPI == 'cli' ? '_cli' : '';

        if ($this->config['single']) {
            $name = is_string($this->config['single']) ? $this->config['single'] : 'single';
        } elseif ($this->config['max_files']) {
            $name = date('Ymd');
        } else {
            $name = date('d');
        }

        return $path . DIRECTORY_SEPARATOR . $name . '_' . $type . $cli . '.log';
    }

    /**
     * 日志写入
     *
     * @param array  $message     日志信息
     * @param string $destination 日志文件
     * @param bool   $apart       是否独立文件写入
     * @param bool   $append      是否追加请求信息
     *
     * @return bool
     */
    protected function write(array $message, string $destination, $apart = false, $append = false)
    {
        // 检测日志文件大小，超过配置大小则备份日志文件重新生成
        $this->checkLogSize($destination);

        // 日志信息封装
        $info['timestamp'] = date($this->config['time_format']);

        foreach ($message as $type => $msg) {
            $msg = is_array($msg) ? implode("\r\n", $msg) : $msg;
            if (PHP_SAPI == 'cli') {
                $info['msg'] = $msg;
            } else {
                $info[$type] = $msg;
            }
        }

        if (PHP_SAPI == 'cli') {
            $message = $this->parseCliLog($info);
        } else {
            // 添加调试日志
            $message = $this->parseLog($info);
        }

        return error_log($message, 3, $destination);
    }

    /**
     * 检查日志文件大小并自动生成备份文件
     *
     * @param string $destination 日志文件
     *
     * @return void
     */
    protected function checkLogSize(string $destination)
    {
        if (is_file($destination) && floor($this->config['file_size']) <= filesize($destination)) {
            try {
                rename($destination, dirname($destination) . DIRECTORY_SEPARATOR . time() . '-' . basename($destination));
            } catch (Exception $e) {
            }
        }
    }

    /**
     * CLI日志解析
     *
     * @param array $info 日志信息
     *
     * @return string
     */
    protected function parseCliLog(array $info)
    {
        if ($this->config['json']) {
            $message = json_encode($info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\r\n";
        } else {
            $now = $info['timestamp'];
            unset($info['timestamp']);
            $message = implode("\r\n", $info);
            $message = "[{$now}]" . $message . "\r\n";
        }

        return $message;
    }

    /**
     * 解析日志
     *
     * @param array $info 日志信息
     *
     * @return string
     */
    protected function parseLog(array $info)
    {
        if ($this->config['json']) {
            return json_encode($info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\r\n";
        }

        array_unshift($info, "---------------------------------------------------------------\r\n[{$info['timestamp']}]");
        unset($info['timestamp']);

        return implode("\r\n", $info) . "\r\n";
    }
}
