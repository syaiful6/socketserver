<?php

namespace SockServer;

class StreamRequestHandler extends BaseRequestHandler
{
	protected $disableNagleAlgorithm = false;

	/**
	 *
	 */
	protected function setup()
	{

		if ($this->disableNagleAlgorithm) {
			socket_set_option($this->request, 0, TCP_NODELAY, true);
		}
	}
}