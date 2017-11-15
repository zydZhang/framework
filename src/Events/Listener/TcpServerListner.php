<?php
/**
 * Created by PhpStorm.
 * User: heui
 * Date: 2017/11/14
 * Time: 17:24
 */

namespace Eelly\Events\Listener;


use Swoole\Server;

class TcpServerListner
{
    public function onStart(Server $server)
    {
    }

    public function onShutdown()
    {
    }

    public function onWorkerStart()
    {
    }

    public function onWorkerStop()
    {
    }

    public function onConnect()
    {
    }

    public function onReceive()
    {
    }

    public function onPacket()
    {
    }

    public function onClose()
    {
    }

    public function onBufferFull()
    {
    }

    public function onBufferEmpty()
    {
    }

    public function onTask()
    {
    }

    public function onFinish()
    {
    }

    public function onPipeMessage()
    {
    }

    public function onWorkerError()
    {
    }

    public function onManagerStart()
    {
    }
    
    public function onManagerStop()
    {
    }
}