<?php

namespace SockServer;

trait ForkingServer
{
    protected $activeChildren;

    protected $maxChildren = 40;

    /**
     *
     */
    protected function handleTimeout()
    {
        $this->collectChildren();
    }

    /**
     *
     */
    protected function serviceAction()
    {
        $this->collectChildren();
    }

    /**
     *
     */
    protected function processRequest($request)
    {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new \RuntimeException('Cant process request');
        } elseif ($pid) {
            //parent process
            if ($this->activeChildren === null) {
                $this->activeChildren = [];
            }
            // set the pid on array key
            $this->activeChildren[$pid] = true;
            $this->closeRequest($request);
        } else {
            try {
                $this->finishRequest($request);
                $this->shutdownRequest($request);
                exit(0);
            } catch (\Exception $e) {
                try {
                    $this->handleError($request);
                    $this->shutdownRequest($request);
                } finally {
                    exit(1);
                }
            }
        }
    }

    /**
     *
     */
    protected function collectChildren()
    {
        if ($this->activeChildren === null) {
            return;
        }

        while (count($this->activeChildren) >= $this->maxChildren) {
            $pid = pcntl_waitpid(-1, $status, 0);
            if (isset($this->activeChildren[$pid])) {
                unset($this->activeChildren[$pid]);
            }
            sleep(1);
        }

        // hmm, no we should reap all defunct children.
        foreach ($this->activeChildren as $pid => $_) {
            $res = pcntl_waitpid(-1, $status, WNOHANG);
            // If the process has already exited
            if ($res === -1 || $res > 0) {
                unset($this->activeChildren[$pid]);
            }
        }
    }
}
