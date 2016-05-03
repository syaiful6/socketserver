<?php

namespace SockServer;

class BaseServer
{
    protected $timeout;

    protected $serverAddress;

    private $_shutdownRequest = false;

    public $socket;

    const MICROSECONDS_PER_SECOND = 1000000;

    /**
     *
     */
    public function __construct($serverAddress, $requestHandlerClass)
    {
        $this->serverAddress = $serverAddress;
        $this->requestHandlerClass = $requestHandlerClass;
    }

    /**
     * Called by constructor to activate the server.
     */
    protected function serverActivate()
    {
    }

    /**
     *
     */
    public function serveForever($interval=0.5)
    {
        $timeout = $interval * static::MICROSECONDS_PER_SECOND;
        // trap the signal
        pcntl_signal(SIGTERM, [$this, 'signalHandler']);
        pcntl_signal(SIGHUP,  [$this, 'signalHandler']);
        pcntl_signal(SIGKILL,  [$this, 'signalHandler']);
        pcntl_signal(SIGINT, [$this, 'signalHandler']);

        try {
            while (!$this->_shutdownRequest) {
                $write = $except = null;
                $read = [$this->socket];
                if (! is_resource($read[0])) {
                    throw new \RuntimeException(
                        'connection');
                }
                $ss = socket_select($read, $write, $except, $timeout === null ? null : 0, $timeout);
                if ($ss) {
                    foreach ($read as $r) {
                        $this->_handleRequestNoblock();
                    }
                }
                $this->serviceActions();
                pcntl_signal_dispatch(); // grr, this maybe not here
            }
        } finally {
            $this->_shutdownRequest = true;
        }
    }

    /**
     *
     */
    public function signalHandler($signal)
    {
        switch ($signal) {
            case SIGTERM:
            case SIGKILL:
            case SIGINT:
                $this->shutdown();
                $this->serverClose(); // maybe just shutdown?
                break;
            case SIGHUP:
                //
                break;
            default:
                //
        }
    }

    /**
     *
     */
    public function shutdown()
    {
        $this->_shutdownRequest = true;
        fclose($this->socket);
    }

    /**
     *
     */
    public function serviceActions()
    {
    }

    /**
     *
     */
    public function handleRequest()
    {
        $write = $except = [];
        $read = [$this->socket];

        $info = socket_get_option($this->socket, SOL_SOCKET, SO_RCVTIMEO);
        $timeout = null;
        if ($info['usec']) {
            $timeout = $info['usec'];
        } elseif ($this->timeout !== null) {
            $timeout = min($timeout, $this->timeout);
        }

        $ss = socket_select($read, $write, $except, $timeout === null ? null : 0, $timeout);
        if (!$ss) {
            $this->handleTimeout();
        } else {
            $this->_handleRequestNoblock();
        }
    }

    /**
     *
     */
    protected function _handleRequestNoblock()
    {
        if (false !== ($res = $this->getRequest())) {
            $request = $res;

            if ($this->verifyRequest($request)) {
                try {
                    $this->processRequest($request);
                } catch(Exception $e) {
                    $this->handleError($request);
                    $this->shutdownRequest($request);
                }
            }
        }
    }

    /**
     *
     */
    protected function verifyRequest($request)
    {
        return true;
    }

    /**
     *
     */
    protected function processRequest($request)
    {
        $this->finishRequest($request);
        $this->shutdownRequest($request);
    }

    protected function serverClose()
    {
    }

    protected function finishRequest($request)
    {
        $req = $this->requestHandlerClass;
        return new $req($request, $this);
    }

    /**
     *
     */
    protected function handleTimeout()
    {
    }

    /**
     *
     */
    protected function shutdownRequest($request)
    {
        $this->closeRequest($request);
    }

    /**
     *
     */
    protected function closeRequest($request)
    {
    }

    /**
     *
     */
    protected function handleError($request)
    {
        print(str_repeat('-', 40));
        print('Exception happened during processing of request from');
        print($address);
        print(str_repeat('-', 40));
    }
}