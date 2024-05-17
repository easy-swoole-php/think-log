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

namespace EasySwoole\ThinkLog;

use EasySwoole\Component\Singleton;
use EasySwoole\ThinkLog\Exception\ClassNotFoundException;

/**
 * Class Log
 *
 * @package EasySwoole\ThinkLog
 *
 * @method void log($msg) static 记录一般日志
 * @method void error($msg) static 记录错误日志
 * @method void info($msg) static 记录一般信息日志
 * @method void sql($msg) static 记录 SQL 查询日志
 * @method void notice($msg) static 记录提示日志
 * @method void alert($msg) static 记录报警日志
 */
class Log
{
    use Singleton;

    const LOG = 'log';
    const ERROR = 'error';
    const INFO = 'info';
    const SQL = 'sql';
    const NOTICE = 'notice';
    const ALERT = 'alert';
    const DEBUG = 'debug';

    /**
     * @var array 日志信息
     */
    protected $log = [];

    /**
     * @var array 配置参数
     */
    protected $config = [];

    /**
     * @var array 日志类型
     */
    protected $type = ['log', 'error', 'info', 'sql', 'notice', 'alert', 'debug'];
    protected static $types = ['log', 'error', 'info', 'sql', 'notice', 'alert', 'debug'];

    /**
     * @var Driver\File 日志写入驱动
     */
    protected $driver;

    /**
     * @var string 当前日志授权 key
     */
    protected $key;

    /**
     * 日志初始化
     *
     * @param array $config 配置参数
     *
     * @return void
     */
    public function init(array $config = [])
    {
        $type = isset($config['type']) ? $config['type'] : 'File';
        $class = false !== strpos($type, '\\') ? $type : '\\EasySwoole\\ThinkLog\\Driver\\' . ucwords($type);

        $this->config = $config;
        unset($config['type']);

        if (class_exists($class)) {
            $this->driver = new $class($config);
        } else {
            throw new ClassNotFoundException('class not exists:' . $class, $class);
        }

        // 记录初始化信息
        $this->record('[ LOG ] INIT ' . $type, 'info');
    }

    /**
     * 获取日志信息
     *
     * @param string $type 信息类型
     *
     * @return array|string
     */
    public function getLog(string $type = '')
    {
        return $type ? $this->log[$type] : $this->log;
    }

    public function isCli()
    {
        return PHP_SAPI == 'cli';
    }

    /**
     * 记录调试信息
     *
     * @param mixed  $msg  调试信息
     * @param string $type 信息类型
     *
     * @return void
     */
    public function record($msg, string $type = 'log')
    {
        $this->log[$type][] = $msg;

        // 命令行下面日志写入改进
        if ($this->isCli()) {
            $this->save();
        }
    }

    /**
     * 清空日志信息
     *
     * @return void
     */
    public function clear()
    {
        $this->log = [];
    }

    /**
     * 设置当前日志记录的授权 key
     *
     * @param string $key 授权 key
     *
     * @return void
     */
    public function key(string $key)
    {
        $this->key = $key;
    }

    /**
     * 检查日志写入权限
     *
     * @param array $config 当前日志配置参数
     *
     * @return bool
     */
    public function check(array $config)
    {
        return !$this->key || empty($config['allow_key']) || in_array($this->key, $config['allow_key']);
    }

    /**
     * 保存调试信息
     *
     * @return bool
     */
    public function save()
    {
        // 没有需要保存的记录则直接返回
        if (empty($this->log)) {
            return true;
        }

        is_null($this->driver) && $this->init($this->config);

        // 检测日志写入权限
        if (!$this->check($this->config)) {
            return false;
        }

        if (empty($this->config['level'])) {
            // 获取全部日志
            $log = $this->log;
        } else {
            // 记录允许级别
            $log = [];
            foreach ($this->config['level'] as $level) {
                if (isset($this->log[$level])) {
                    $log[$level] = $this->log[$level];
                }
            }
        }

        if ($result = $this->driver->save($log, true)) {
            $this->log = [];
        }

        return $result;
    }

    /**
     * 实时写入日志信息 并支持行为
     *
     * @param mixed  $msg   调试信息
     * @param string $type  信息类型
     * @param bool   $force 是否强制写入
     *
     * @return bool
     */
    public function write($msg, string $type = 'log', bool $force = false)
    {
        $log = $this->log;

        // 如果不是强制写入，而且信息类型不在可记录的类别中则直接返回 false 不做记录
        if (true !== $force && !empty($this->config['level']) && !in_array($type, $this->config['level'])) {
            return false;
        }

        // 封装日志信息
        $log[$type][] = $msg;

        is_null($this->driver) && self::init($this->config);

        // 写入日志
        if ($result = $this->driver->save($log, false)) {
            $this->log = [];
        }

        return $result;
    }

    /**
     * 静态方法调用
     *
     * @param string $method 调用方法
     * @param mixed  $args   参数
     *
     * @return void
     */
    public static function __callStatic(string $method, $args)
    {
        if (in_array($method, self::$types)) {
            array_push($args, $method);
            $log = Log::getInstance();
            call_user_func_array([$log, 'record'], $args);
        }
    }
}
