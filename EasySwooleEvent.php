<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/28
 * Time: 下午6:33
 */

namespace EasySwoole\EasySwoole;


use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\Component\Di;
use EasySwoole\Socket\Client\Tcp;
use EasySwoole\Socket\Client\Udp;
use EasySwoole\Socket\Dispatcher;
use Esw\Exception\InvalidDataException\InvalidDataException;
use Esw\Parser\WebSocketParser;
use Esw\Process\HotReload;


class EasySwooleEvent implements Event
{

    public static function initialize()
    {
        // TODO: Implement initialize() method.
        date_default_timezone_set('Asia/Shanghai');
        // App目录切换
        $namespace = 'Esw\Controller\Http\\';
        Di::getInstance()->set(SysConst::HTTP_CONTROLLER_NAMESPACE, $namespace);
    }

    /**
     * @param EventRegister $register
     * @throws \EasySwoole\Socket\Exception\Exception
     */
    public static function mainServerCreate(EventRegister $register)
    {
        // TODO: Implement mainServerCreate() method.

        self::runHotReload($register);
        self::runWebSocket($register);
    }

    public static function onRequest(Request $request, Response $response): bool
    {
        // TODO: Implement onRequest() method.
        return true;
    }

    public static function afterRequest(Request $request, Response $response): void
    {
        // TODO: Implement afterAction() method.
    }

    /**
     * 热重载
     * @param EventRegister $register
     */
    private static function runHotReload(EventRegister $register): void
    {
        ServerManager::getInstance()
            ->getSwooleServer()
            ->addProcess((new HotReload(...Config::getInstance()->getConf('HOT_RELOAD')))
                ->getProcess());
    }

    /**
     * web socket 控制器
     * @param EventRegister $register
     * @throws \EasySwoole\Socket\Exception\Exception
     */
    private static function runWebSocket(EventRegister $register): void
    {
        $conf = new \EasySwoole\Socket\Config();
        $conf->setType(\EasySwoole\Socket\Config::WEB_SOCKET);
        $conf->setParser(new WebSocketParser());
        $conf->setOnExceptionHandler(function (
            \Swoole\Server $server,
            $throwable,
            $raw,
            $client,
            \EasySwoole\Socket\Bean\Response $response
        ) use (
            $conf
        ) {
            if ($throwable instanceof InvalidDataException && ($client instanceof Tcp || $client instanceof Udp)) {
                // 数据解析错误
                $response->setMessage(createReturn(WARN_CODE, 'invalid data'));
                $data = $conf->getParser()->encode($response, $client);
                if ($server->exist($client->getFd())) {
                    $server->send($client->getFd(), $data);
                }
            } else {
                if ($client instanceof Tcp && $server->exist($client->getFd())) {
                    $server->close($client->getFd());
                }
                throw $throwable;
            }
        });
        $dispatch = new Dispatcher($conf);
        $register->set(EventRegister::onMessage, function (\swoole_websocket_server $server, \swoole_websocket_frame $frame) use ($dispatch) {
            $dispatch->dispatch($server, $frame->data, $frame);
        });
    }
}