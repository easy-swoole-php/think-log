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

namespace EasySwoole\ThinkLog\Exception;

class ClassNotFoundException extends \RuntimeException
{
    protected $class;

    public function __construct($message, $class = '')
    {
        $this->message = $message;
        $this->class = $class;
    }

    /**
     * 获取类名
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }
}
