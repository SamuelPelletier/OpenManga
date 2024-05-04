<?php
namespace App\Service;

class WorkerService
{
    #private $workerCommand;
    private $workerPidFile;

    public function __construct()
    {
        $this->workerCommand = $_ENV['WORKER_COMMAND'] ?? '';
        $this->workerPidFile = sys_get_temp_dir() . '/worker.pid';
    }

    public function isWorkerRunning(): bool
    {
        if (!file_exists($this->workerPidFile)) {
            return false;
        }

        $workerPid = (int) file_get_contents($this->workerPidFile);
        if (!$workerPid) {
            return false;
        }

        // Check if a process with this PID is running
        $status = shell_exec('ps -p ' . $workerPid);
        return (bool) preg_match('/^ *\d+ +' . preg_quote($workerPid, '/') . ' /m', $status);
    }

    public function startWorkerIfNeeded(): void
    {
        /*

        if ($_ENV['TRANSLATION_ENABLED'] !== 'true') {
            return;
        }
        if ($this->isWorkerRunning()) {
            return;
        }
        */

        // Start the worker in the background and write its PID to a file
        //$command = $this->workerCommand . ' > /dev/null 2>&1 & echo $!';
        $logFile = sys_get_temp_dir() . '/worker_output.log';
        $command = $_ENV['WORKER_COMMAND'] ?? ''. ' > ' . $logFile . ' 2>&1 & echo $!';
        exec($command, $output);
        file_put_contents($this->workerPidFile, trim($output[0]));
    }
}
