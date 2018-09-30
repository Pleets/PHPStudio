<?php

namespace Workarea\Model;

use Drone\Db\TableGateway\TableGateway;

class FilesTable extends TableGateway
{
    /**
     * Returns the max primary key to add
     *
     * @return string
     */
    public function getNextId()
    {
        $table = $this->getEntity()->getTableName();

        $sql = "SELECT MAX(FILE_ID) FILE_ID FROM $table";

        $this->getDriver()->getDb()->execute($sql);
        $rowset = $this->getDriver()->getDb()->getArrayResult();
        $row = array_shift($rowset);

        return is_null($row["FILE_ID"]) ? 1 : (integer) $row["FILE_ID"] + 1;
    }
}