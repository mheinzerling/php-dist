<?php
declare(strict_types = 1);

namespace mheinzerling\commons;

class Process
{
    /**
     * @var string
     */
    private $command;

    /**
     * @var string|null
     */
    private $currentWorkingDir;
    /**
     * @var string|null
     */
    private $out;
    /**
     * @var string|null
     */
    private $err;
    /**
     * @var int|null
     */
    private $returnValue;


    function __construct(string $command, $currentWorkingDir = null)
    {
        $this->command = $command;
        $this->currentWorkingDir = $currentWorkingDir;
    }

    function run($dieOnError = false)
    {
        $descriptorSpec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        $process = proc_open($this->command, $descriptorSpec, $pipes, $this->currentWorkingDir);

        if (is_resource($process)) {

//           fwrite($pipes[0], '<?php print_r($_ENV); ? >');
            fclose($pipes[0]);

            $this->out = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $this->err = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            $this->returnValue = proc_close($process);

            if ($this->err != '' && $dieOnError) {
                var_dump($this);
                die();
            }
        }
    }

    public function getErr():?string
    {
        return $this->err;
    }

    public function getOut():?string
    {
        return $this->out;
    }

    public function getReturnValue():?int
    {
        return $this->returnValue;
    }
}