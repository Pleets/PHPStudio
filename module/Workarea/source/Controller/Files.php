<?php

namespace Workarea\Controller;

use Auth\Model\User as UserModel;
use Drone\Mvc\AbstractionController;
use Drone\Dom\Element\Form;
use Drone\Validator\FormValidator;
use Drone\Db\TableGateway\EntityAdapter;
use Drone\Db\TableGateway\TableGateway;
use Drone\Network\Http;
use Workarea\Model\File;
use Workarea\Model\FilesTable;

class Files extends AbstractionController
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
    private $fileEntity;

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
    private function getFileEntity()
    {
        if (!is_null($this->fileEntity))
            return $this->fileEntity;

        $this->fileEntity = new EntityAdapter(new FilesTable(new File()));

        return $this->fileEntity;
    }

    /**
     * Lists all files
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

            $data["files"] = $this->getFileEntity()->select([
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
     * Deletes a file
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

            $connection = $this->getFileEntity()->select([
                "FILE_ID" => $post["id"]
            ]);

            if (!count($connection))
                throw new \Exception("The file does not exists");

            $connection = array_shift($connection);

            if ($connection->STATE == 'I')
                throw new \Drone\Exception\Exception("This file was deleted");

            $connection->exchangeArray([
                "STATE" =>  'I'
            ]);

            $this->getFileEntity()->update($connection, [
                "FILE_ID" => $post["id"]
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
     * Adds a file
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
                $needles = ['filename'];

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
                        "filename" => [
                            "required"  => true,
                            "type"      => "text",
                            "minlength" => 2,
                            "maxlength" => 100
                        ],
                    ],
                ];

                $options = [
                    "filename" => [
                        "label"      => "File name",
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

                $this->getFileEntity()->getTableGateway()->getDriver()->getDb()->beginTransaction();

                $file = new File();
                $FILE_ID = $this->getFileEntity()->getTableGateway()->getNextId();

                $file->exchangeArray([
                    "FILE_ID"   => $FILE_ID,
                    "USER_ID"      => $this->getIdentity(),
                    "FILE_NAME" => $post["filename"],
                    "STATE"        =>  'A'
                ]);

                $this->getFileEntity()->insert($file);

                $this->getFileEntity()->getTableGateway()->getDriver()->getDb()->endTransaction();

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
     * Updates a file
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

                $file = $this->getFileEntity()->select([
                    "FILE_ID" => $get["id"]
                ]);

                if (!count($file))
                    throw new \Exception("The file does not exists");

                $file = array_shift($file);

                if ($file->STATE == 'I')
                    throw new \Drone\Exception\Exception("This file was deleted");

                $data["file"] = $file;

                # SUCCESS-MESSAGE
                $data["process"] = "update-form";
            }
            else if ($this->isPost())
            {
                # STANDARD VALIDATIONS [check needed arguments]
                $needles = ['_file_id', 'filename'];

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
                        "filename" => [
                            "required"  => true,
                            "type"      => "text",
                            "minlength" => 2,
                            "maxlength" => 100
                        ],
                    ],
                ];

                $options = [
                    "filename" => [
                        "label"      => "File name",
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

                $this->getFileEntity()->getTableGateway()->getDriver()->getDb()->beginTransaction();

                $file = $this->getFileEntity()->select([
                    "FILE_ID"    => $post["_file_id"],
                ]);

                if (!count($file))
                    throw new \Exception("The file does not exists");

                $file = array_shift($file);

                if ($file->STATE == 'I')
                    throw new \Drone\Exception\Exception("This file was deleted");

                $this->getFileEntity()->update($file, [
                    "FILE_ID"    => $post["_file_id"],
                ]);

                $this->getFileEntity()->getTableGateway()->getDriver()->getDb()->endTransaction();

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