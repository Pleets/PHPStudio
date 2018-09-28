<?php

namespace NotFound\Controller;

use Drone\Mvc\AbstractionController;
use Drone\Network\Http;

class Error extends AbstractionController
{
	public function notFound()
	{
		$this->setShowView(false);
		$this->setLayout('HTTP404');

        $http = new Http();
        $http->writeStatus($http::HTTP_NOT_FOUND);

		return [];
	}

	public function notFoundView()
	{
		$this->setLayout('blank');

        $http = new Http();
        $http->writeStatus($http::HTTP_NOT_FOUND);

		return [];
	}
}