<?php

namespace SockServer;

class TCPServer extends BaseServer
{
	protected $addressFamily = AF_INET;
	protected $socketType = SOCK_STREAM;

	protected $requestQueueSize = 5;
	protected $allowReuseAddress = false;

	public function __construct($serverAddress, $equestHandlerClass, $bindAndActivate=true)
	{
		parent::__construct($serverAddress, $equestHandlerClass);
		$this->socket = socket_create($this->addressFamily, $this->socketType, SOL_TCP);
		if ($bindAndActivate) {
			try {
				$this->serverBind();
				$this->serverActivate();
			} catch(Exception $e) {
				$this->serverClose();
				throw $e;
			}
		}
	}

	/**
	 *
	 */
	protected function serverBind()
	{
		if ($this->allowReuseAddress) {
			if (!socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1)) {
				throw new \RuntimeException(socket_strerror(socket_last_error()));
			}
		}
        list($addr, $port) = explode(':', $this->serverAddress);
        if (!socket_bind($this->socket, $addr, $port)) {
            throw new \RuntimeException(socket_strerror(socket_last_error()));
        }
        socket_getsockname($this->socket, $ip, $port);
        $this->serverAddress = $ip;
        if ($port) {
            $this->serverAddress .= ':'.$port;
        }
	}

    /**
     *
     */
    protected function serverActivate()
    {
        socket_listen($this->socket, $this->requestQueueSize);
    }

    /**
     *
     */
    protected function getRequest()
    {
        $con = socket_accept($this->socket);
        if (false === $con) {
            throw new \RuntimeException('Error accepting new connection');
        }
        return $con;
    }

    /**
     *
     */
    protected function shutdownRequest($request)
    {
        // 0 shutdown read
        // 1 shutdown write
        // 2 shutdown read & write
        if (socket_shutdown($request, 1)) {
            $this->closeRequest($request);
        }
    }

    /**
     *
     */
    protected function closeRequest($request)
    {
        socket_close($request);
    }
}