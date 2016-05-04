<?php

namespace SockServer;

class UnixStreamServer extends TCPServer
{
	protected $addressFamily = AF_UNIX;
}