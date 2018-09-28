<?php

namespace Catcher;

use Drone\Dom\Element\Form;
Use Drone\Mvc\AbstractionModule;
use Drone\Mvc\AbstractionController;
use Drone\Mvc\Layout;
use Drone\Validator\FormValidator;
use Drone\Util\ArrayDimension;

class Module extends AbstractionModule
{
	public function init(AbstractionController $c)
	{
        $config = $this->getUserConfig();

        $_config = ArrayDimension::toUnidimensional($config, "_");

        $this->setTranslator($c);

        # config constraints

        $components = [
            "attributes" => [
                "project_name" => [
                    "required"  => true,
                    "type"      => "text",
                    "minlength" => 2,
                    "maxlength" => 60
                ],
            ],
        ];

        $options = [
            "project" => [
                "label"      => "project -> name"
            ],
        ];

        $form = new Form($components);
        $form->fill($_config);

        $validator = new FormValidator($form, $options);
        $validator->validate();

        $data["validator"] = $validator;

        try
        {
            if (!$validator->isValid())
            {
                $data["messages"] = $validator->getMessages();
                throw new \Exception("Module config errros in user.config!", 300);
            }
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
                //$this->handleErrors($errors, __METHOD__);
            }

            $data["code"]    = $errorCode;
            $data["message"] = $e->getMessage();

            $config = include 'config/application.config.php';
            $data["dev_mode"] = $config["environment"]["dev_mode"];

            # stops current controller execution
            $c->stopExecution(false);

            # loads error view
            $layoutManager = new Layout();
            $layoutManager->setBasePath($c->getBasePath());

            $layoutManager->setView($this, "validation");
            $layoutManager->setParams($data);

            # for AJAX requests!
            if ($c->isXmlHttpRequest())
                $layoutManager->content();
            else
                $layoutManager->fromTemplate($this, 'blank');
        }
	}

    private function setTranslator(AbstractionController $c)
    {
        $config = include('config/application.config.php');
        $locale = $config["environment"]["locale"];

        $i18nTranslator = \Zend\I18n\Translator\Translator::factory(
            [
                'locale'  => "$locale",
                'translation_files' => [
                    [
                        "type" => 'phparray',
                        "filename" => __DIR__ . "/lang/$locale.php"
                    ]
                ]
            ]
        );

        $c->translator = new \Zend\Mvc\I18n\Translator($i18nTranslator);
    }

	public function getUserConfig()
	{
		return include __DIR__ . "/config/user.config.php";
	}
}