<?php

namespace mheinzerling\commons;

class Process
{
    private $command;

    private $currentWorkingDir;
    private $out;
    private $err;
    private $returnValue;


    function __construct($command, $currentWorkingDir = null)
    {
        $this->command = $command;
        $this->currentWorkingDir = $currentWorkingDir;
    }

    function run($dieOnError = false)
    {
        $descriptorSpec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w")
        );

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


    /**
     * @return String
     */
    public function getErr()
    {
        return $this->err;
    }

    /**
     * @return String
     */
    public function getOut()
    {
        return $this->out;
    }


    /**
     * @return String
     */
    public function getReturnValue()
    {
        return $this->returnValue;
    }


}