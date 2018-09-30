<?php

namespace Workarea\Controller;

use Auth\Model\User as UserModel;
use Drone\Mvc\AbstractionController;
use Drone\Dom\Element\Form;
use Drone\Validator\FormValidator;
use Drone\Db\TableGateway\EntityAdapter;
use Drone\Db\TableGateway\TableGateway;
use Drone\Network\Http;
use Workarea\Model\Project;
use Workarea\Model\ProjectsTable;

class Projects extends AbstractionController
{
    /**
     * @var integer
     */
    private $identity;

    /**
     * @var EntityAdapter
     */
    private $userEntity;

    /**
     * @var EntityAdapter
     */
    private $projectEntity;

    /**
     * @return integer
     */
    private function getIdentity()
    {
        $config = include 'module/Auth/config/user.config.php';
        $method = $config["authentication"]["method"];
        $key    = $config["authentication"]["key"];

        switch ($method)
        {
            case '_COOKIE':

                $user = $this->getUserEntity()->select([
                    "USERNAME" => $_COOKIE[$key]
                ]);

                break;

            case '_SESSION':

                $user = $this->getUserEntity()->select([
                    "USERNAME" => $_SESSION[$key]
                ]);

                break;
        }

        $user = array_shift($user);

        return $user->USER_ID;
    }

    /**
     * @return EntityAdapter
     */
    private function getUserEntity()
    {
        if (!is_null($this->userEntity))
            return $this->userEntity;

        $this->userEntity = new EntityAdapter(new TableGateway(new UserModel()));

        return $this->userEntity;
    }

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
     * Lists all projects
     *
     * @return array
     */
    public function list()
    {
        # data to send
        $data = array();

        # environment settings
        $this->setTerminal(true);           # set terminal

        # TRY-CATCH-BLOCK
        try {

            # STANDARD VALIDATIONS [check method]
            if (!$this->isGet())
            {
                $http = new Http();
                $http->writeStatus($http::HTTP_METHOD_NOT_ALLOWED);

                die('Error ' . $http::HTTP_METHOD_NOT_ALLOWED .' (' . $http->getStatusText($http::HTTP_METHOD_NOT_ALLOWED) . ')!!');
            }

            $data["projects"] = $this->getProjectEntity()->select([
                "USER_ID" => $this->getIdentity(),
                "STATE"   => "A"
            ]);

            # SUCCESS-MESSAGE
            $data["process"] = "success";
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

    /**
     * Deletes a project
     *
     * @return array
     */
    public function delete()
    {
        clearstatcache();
        session_write_close();

        # data to send
        $data = array();

        # environment settings
        $post = $this->getPost();           # catch $_POST
        $this->setTerminal(true);           # set terminal

        # TRY-CATCH-BLOCK
        try {

            # STANDARD VALIDATIONS [check method]
            if (!$this->isPost())
            {
                $http = new Http();
                $http->writeStatus($http::HTTP_METHOD_NOT_ALLOWED);

                die('Error ' . $http::HTTP_METHOD_NOT_ALLOWED .' (' . $http->getStatusText($http::HTTP_METHOD_NOT_ALLOWED) . ')!!');
            }

            # STANDARD VALIDATIONS [check needed arguments]
            $needles = ['id'];

            array_walk($needles, function(&$item) use ($post) {
                if (!array_key_exists($item, $post))
                {
                    $http = new Http();
                    $http->writeStatus($http::HTTP_BAD_REQUEST);

                    die('Error ' . $http::HTTP_BAD_REQUEST .' (' . $http->getStatusText($http::HTTP_BAD_REQUEST) . ')!!');
                }
            });

            $components = [
                "attributes" => [
                    "id" => [
                        "required"  => true,
                        "type"      => "number"
                    ]
                ],
            ];

            $options = [
                "id" => [
                    "label" => "Id"
                ]
            ];

            $form = new Form($components);
            $form->fill($post);

            $validator = new FormValidator($form, $options);
            $validator->validate();

            $data["validator"] = $validator;

            # form validation
            if (!$validator->isValid())
            {
                $data["messages"] = $validator->getMessages();
                throw new \Drone\Exception\Exception("Form validation errors");
            }

            $connection = $this->getProjectEntity()->select([
                "PROJECT_ID" => $post["id"]
            ]);

            if (!count($connection))
                throw new \Exception("The project does not exists");

            $connection = array_shift($connection);

            if ($connection->STATE == 'I')
                throw new \Drone\Exception\Exception("This project was deleted");

            $connection->exchangeArray([
                "STATE" =>  'I'
            ]);

            $this->getProjectEntity()->update($connection, [
                "PROJECT_ID" => $post["id"]
            ]);

            $data["id"] = $post["id"];

            # SUCCESS-MESSAGE
            $data["process"] = "success";
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

    /**
     * Adds a project
     *
     * @return array
     */
    public function add()
    {
        clearstatcache();
        session_write_close();

        # data to send
        $data = array();

        # environment settings
        $post = $this->getPost();           # catch $_POST
        $this->setTerminal(true);           # set terminal

        # TRY-CATCH-BLOCK
        try {

            if ($this->isGet())
            {
                # SUCCESS-MESSAGE
                $data["process"] = "register-form";
            }
            else if ($this->isPost())
            {
                # STANDARD VALIDATIONS [check needed arguments]
                $needles = ['projectname'];

                array_walk($needles, function(&$item) use ($post) {
                    if (!array_key_exists($item, $post))
                    {
                        $http = new Http();
                        $http->writeStatus($http::HTTP_BAD_REQUEST);

                        die('Error ' . $http::HTTP_BAD_REQUEST .' (' . $http->getStatusText($http::HTTP_BAD_REQUEST) . ')!!');
                    }
                });

                $components = [
                    "attributes" => [
                        "projectname" => [
                            "required"  => true,
                            "type"      => "text",
                            "minlength" => 2,
                            "maxlength" => 100
                        ],
                    ],
                ];

                $options = [
                    "projectname" => [
                        "label"      => "Project name",
                        "validators" => [
                            "Alnum"  => ["allowWhiteSpace" => true]
                        ],
                    ],
                ];

                $form = new Form($components);
                $form->fill($post);

                $validator = new FormValidator($form, $options);
                $validator->validate();

                $data["validator"] = $validator;

                # form validation
                if (!$validator->isValid())
                {
                    $data["messages"] = $validator->getMessages();
                    throw new \Drone\Exception\Exception("Form validation errors", 300);
                }

                $this->getProjectEntity()->getTableGateway()->getDriver()->getDb()->beginTransaction();

                $project = new Project();
                $PROJECT_ID = $this->getProjectEntity()->getTableGateway()->getNextId();

                $project->exchangeArray([
                    "PROJECT_ID"   => $PROJECT_ID,
                    "USER_ID"      => $this->getIdentity(),
                    "PROJECT_NAME" => $post["projectname"],
                    "STATE"        =>  'A'
                ]);

                $this->getProjectEntity()->insert($project);

                $this->getProjectEntity()->getTableGateway()->getDriver()->getDb()->endTransaction();

                # SUCCESS-MESSAGE
                $data["process"] = "process-response";
            }
            else
            {
                $http = new Http();
                $http->writeStatus($http::HTTP_METHOD_NOT_ALLOWED);

                die('Error ' . $http::HTTP_METHOD_NOT_ALLOWED .' (' . $http->getStatusText($http::HTTP_METHOD_NOT_ALLOWED) . ')!!');
            }
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

    /**
     * Updates a project
     *
     * @return array
     */
    public function edit()
    {
        clearstatcache();
        session_write_close();

        # data to send
        $data = array();

        # environment settings
        $post = $this->getPost();           # catch $_POST
        $get = $_GET;                       # catch $_GET
        $this->setTerminal(true);           # set terminal

        # TRY-CATCH-BLOCK
        try {

            if ($this->isGet())
            {
                # STANDARD VALIDATIONS [check needed arguments]
                $needles = ['id'];

                array_walk($needles, function(&$item) use ($get) {
                    if (!array_key_exists($item, $get))
                    {
                        $http = new Http();
                        $http->writeStatus($http::HTTP_BAD_REQUEST);

                        die('Error ' . $http::HTTP_BAD_REQUEST .' (' . $http->getStatusText($http::HTTP_BAD_REQUEST) . ')!!');
                    }
                });

                $project = $this->getProjectEntity()->select([
                    "PROJECT_ID" => $get["id"]
                ]);

                if (!count($project))
                    throw new \Exception("The project does not exists");

                $project = array_shift($project);

                if ($project->STATE == 'I')
                    throw new \Drone\Exception\Exception("This project was deleted");

                $data["project"] = $project;

                # SUCCESS-MESSAGE
                $data["process"] = "update-form";
            }
            else if ($this->isPost())
            {
                # STANDARD VALIDATIONS [check needed arguments]
                $needles = ['_project_id', 'projectname'];

                array_walk($needles, function(&$item) use ($post) {
                    if (!array_key_exists($item, $post))
                    {
                        $http = new Http();
                        $http->writeStatus($http::HTTP_BAD_REQUEST);

                        die('Error ' . $http::HTTP_BAD_REQUEST .' (' . $http->getStatusText($http::HTTP_BAD_REQUEST) . ')!!');
                    }
                });

                $components = [
                    "attributes" => [
                        "projectname" => [
                            "required"  => true,
                            "type"      => "text",
                            "minlength" => 2,
                            "maxlength" => 100
                        ],
                    ],
                ];

                $options = [
                    "projectname" => [
                        "label"      => "Project name",
                        "validators" => [
                            "Alnum"  => ["allowWhiteSpace" => true]
                        ],
                    ],
                ];

                $form = new Form($components);
                $form->fill($post);

                $validator = new FormValidator($form, $options);
                $validator->validate();

                $data["validator"] = $validator;

                # form validation
                if (!$validator->isValid())
                {
                    $data["messages"] = $validator->getMessages();
                    throw new \Drone\Exception\Exception("Form validation errors");
                }

                $this->getProjectEntity()->getTableGateway()->getDriver()->getDb()->beginTransaction();

                $project = $this->getProjectEntity()->select([
                    "PROJECT_ID"    => $post["_project_id"],
                ]);

                if (!count($project))
                    throw new \Exception("The project does not exists");

                $project = array_shift($project);

                if ($project->STATE == 'I')
                    throw new \Drone\Exception\Exception("This project was deleted");

                $this->getProjectEntity()->update($project, [
                    "PROJECT_ID"    => $post["_project_id"],
                ]);

                $this->getProjectEntity()->getTableGateway()->getDriver()->getDb()->endTransaction();

                # SUCCESS-MESSAGE
                $data["process"] = "process-response";
            }
            else
            {
                $http = new Http();
                $http->writeStatus($http::HTTP_METHOD_NOT_ALLOWED);

                die('Error ' . $http::HTTP_METHOD_NOT_ALLOWED .' (' . $http->getStatusText($http::HTTP_METHOD_NOT_ALLOWED) . ')!!');
            }
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