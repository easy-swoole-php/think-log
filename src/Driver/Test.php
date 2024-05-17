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

/**
 * 模拟测试输出
 */
class Test implements DriverInterface
{
    /**
     * 日志写入接口
     *
     * @param array $log 日志信息
     *
     * @return bool
     */
    public function save(array $log = [])
    {
        return true;
    }
}
