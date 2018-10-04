<?php

namespace FileManager\Controller;

use Drone\Mvc\AbstractionController;
use Drone\Validator\FormValidator;
use Drone\Db\TableGateway\EntityAdapter;
use Drone\Db\TableGateway\TableGateway;
use Drone\Network\Http;
use Workarea\Model\Project;
use Workarea\Model\ProjectsTable;

class File extends AbstractionController
{
    /**
     * @var EntityAdapter
     */
    private $projectEntity;

    /**
     * @return EntityAdapter
     */
    private function getProjectEntity()
    {
        if (!is_null($this->projectEntity))
            return $this->projectEntity;

        $this->projectEntity = new EntityAdapter(new ProjectsTable(new Project()));

        return $this->projectEntity;
    }

   /**
     * Reads a file
     *
     * @return array
     */
    public function read()
    {
        # data to send
        $data = [];

        # environment settings
        $p = $this->getPost();           # catch $_POST
        $this->setTerminal(true);           # set terminal

        # TRY-CATCH-BLOCK
        try {

            # STANDARD VALIDATIONS [check needed arguments]
            $needles = ['project', 'id', 'file'];

            array_walk($needles, function(&$item) use ($p) {
                if (!array_key_exists($item, $p))
                {
                    $http = new Http();
                    $http->writeStatus($http::HTTP_BAD_REQUEST);

                    die('Error ' . $http::HTTP_BAD_REQUEST .' (' . $http->getStatusText($http::HTTP_BAD_REQUEST) . ')!!');
                }
            });

            $project = $this->getProjectEntity()->select([
                "PROJECT_ID" => $p["project"]
            ]);

            $project = array_shift($project);

            $directory = dirname($project->CONFIG_FILE);

            $file = $directory . "/" . $p["file"];

            $data["id"] = $p["id"];
            $data["file"] = $file;
            $data["file_contents"] = file_get_contents($file);

            return $data;
        }
        catch (\Drone\Exception\Exception $e)
        {
            # ERROR-MESSAGE
            $data["process"] = "warning";
            $data["message"] = $e->getMessage();
        }
        catch (\Exception $e)
        {
            $file = str_replace('\\', '', __CLASS__);
            $storage = new \Drone\Exception\Storage("cache/$file.json");

            # stores the error code
            if (($errorCode = $storage->store($e)) === false)
            {
                $errors = $storage->getErrors();

                # if error storing is not possible, handle it (internal app error)
                $this->handleErrors($errors, __METHOD__);
            }

            $data["code"]    = $errorCode;
            $data["message"] = $e->getMessage();

            $config = include 'config/application.config.php';
            $data["dev_mode"] = $config["environment"]["dev_mode"];

            # redirect view
            $this->setMethod('error');

            return $data;
        }

        return $data;
    }

    private function handleErrors(Array $errors, $method)
    {
        if (count($errors))
        {
            $errorInformation = "";

            foreach ($errors as $errno => $error)
            {
                $errorInformation .=
                    "<strong style='color: #a94442'>".
                        $method
                            . "</strong>: <span style='color: #e24f4c'>{$error}</span> \n<br />";
            }

            $hd = @fopen('cache/errors.txt', "a");

            if (!$hd || !@fwrite($hd, $errorInformation))
            {
                # error storing are not mandatory!
            }
            else
                @fclose($hd);

            $config = include 'config/application.config.php';
            $dev = $config["environment"]["dev_mode"];

            if ($dev)
                echo $errorInformation;
        }
    }
}