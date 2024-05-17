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

use EasySwoole\ThinkLog\Log;

if (!function_exists('trace')) {
    /**
     * 记录日志信息
     *
     * @param mixed    $log   log信息 支持字符串和数组
     * @param string   $level 日志级别
     * @param Log|null $logObj
     */
    function trace($log, string $level = 'log', Log $logObj = null)
    {
        if (is_null($logObj)) {
            $logObj = Log::getInstance();
        }

        $logObj->record($log, $level);
    }
}
