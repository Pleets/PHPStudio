<?php

namespace Workarea\Model;

use Drone\Db\TableGateway\TableGateway;

class ProjectsTable extends TableGateway
{
    /**
     * Returns the max primary key to add
     *
     * @return string
     */
    public function getNextId()
    {
        $table = $this->getEntity()->getTableName();

        $sql = "SELECT MAX(PROJECT_ID) PROJECT_ID FROM $table";

        $this->getDriver()->getDb()->execute($sql);
        $rowset = $this->getDriver()->getDb()->getArrayResult();
        $row = array_shift($rowset);

        return is_null($row["PROJECT_ID"]) ? 1 : (integer) $row["PROJECT_ID"] + 1;
    }
}