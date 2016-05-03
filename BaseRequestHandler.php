<?php

namespace SockServer;

abstract class BaseRequestHandler
{
	protected $request;

	protected $server;

	/**
	 *
	 */
	public function __construct($request, $server)
	{
		$this->request = $request;
		$this->server = $server;
		$this->setup();
		try {
			$this->handle();
		} finally {
			$this->finish();
		}
	}

	protected function setup()
	{
	}

	protected function handle()
	{
	}

	protected function finish()
	{
	}
}