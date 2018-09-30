<?php

namespace Workarea\Controller;

use Drone\Mvc\AbstractionController;

class Start extends AbstractionController
{
   /**
     * Shows the dashboard
     *
     * @return array
     */
    public function index()
    {
        # data to send
        $data = [];

        # SUCCESS-MESSAGE
        $data["process"] = "success";

        return $data;
    }
}