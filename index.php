<?php
/**
 * 腾讯云函数入口文件
 *
 * @author mybsdc <mybsdc@gmail.com>
 * @date 2019/3/2
 * @time 11:05
 * @link https://github.com/luolongfei/freenom
 */

error_reporting(E_ERROR);
ini_set('display_errors', 1);
set_time_limit(0);

define('IS_SCF', true); // 是否腾讯云函数环境
define('IS_CLI', PHP_SAPI === 'cli');
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', realpath(__DIR__));
define('VENDOR_PATH', realpath(ROOT_PATH . '/vendor'));
define('APP_PATH', realpath(ROOT_PATH . '/app'));
define('DATA_PATH', IS_SCF ? '/tmp' : realpath(ROOT_PATH . '/app/Data')); // 腾讯云函数只有 /tmp 目录的读写权限
define('RESOURCES_PATH', realpath(ROOT_PATH . '/resources'));

date_default_timezone_set('Asia/Shanghai');

/**
 * 注册错误处理
 */
register_shutdown_function('customize_error_handler');

/**
 * 注册异常处理
 */
set_exception_handler('exception_handler');

require VENDOR_PATH . '/autoload.php';

use Luolongfei\Libs\Log;
use Luolongfei\Libs\Message;

/**
 * @throws Exception
 */
function customize_error_handler()
{
    if (!is_null($error = error_get_last())) {
        Log::error('程序意外终止', $error);
        Message::send('具体情况我也不清楚，请查看服务器日志定位问题。', '主人，程序意外终止');
    }
}

/**
 * @param \Exception $e
 *
 * @throws \Exception
 */
function exception_handler($e)
{
    Log::error('未捕获的异常：' . $e->getMessage());
    Message::send("具体的异常内容是：\n" . $e->getMessage(), '主人，未捕获的异常');
}

function main_handler($event, $context)
{
    try {
        system_check(true);

        $class = sprintf('Luolongfei\App\Console\%s', get_argv('c', 'FreeNom'));
        $fn = get_argv('m', 'handle');

        $class::getInstance()->$fn();

        return '云函数执行成功。';
    } catch (\Exception $e) {
        system_log(sprintf('执行出错：<red>%s</red>', $e->getMessage()), $e->getTrace());
        Message::send("执行出错：\n" . $e->getMessage(), '主人，捕获异常');
    }

    return '云函数执行失败。';
}
