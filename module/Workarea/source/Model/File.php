<?php

namespace Workarea\Model;

use Drone\Db\Entity;

class File extends Entity
{
	/**
	* @var integer
	*/
	public $FILE_ID;

	/**
	* @var string
	*/
	public $FILE_NAME;

	/**
	* @var integer
	*/
	public $USER_ID;

	/**
	* @var string
	*/
	public $STATE;

	/**
	* @var date
	*/
	public $RECORD_DATE;

    public function __construct($data = [])
    {
    	parent::__construct($data);
        $this->setTableName("PHS_FILE");
    }
}