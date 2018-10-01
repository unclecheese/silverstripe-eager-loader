<?php

namespace App\ORM;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLSelect;

class HasOneEagerLoader extends EagerLoader
{

    protected $foreignKeys = [];

    protected function createList()
    {
        $joinField = $this->getJoinField();

        return $this->byIDs($this->parentList->column($joinField));
    }

    protected function getJoinField()
    {
        return $this->relationName . 'ID';
    }
    public function createMap()
    {
        foreach ($this->eager as $loader) {
            $loader->createMap();
        }
        foreach ($this->getList() as $item) {
            $this->map[$item->ID] = $item;
        }
        $ids = $this->parentList->column('ID');
        $placeholders = DB::placeholders($ids);
        $table = $this->schema->tableForField($this->parentDataObjectClass, $this->getJoinField());
        $query = SQLSelect::create(
            ['ID', $this->getJoinField()],
            $table,
            ['ID IN (' . $placeholders . ')' => $ids]
        );
        foreach ($query->execute() as $record) {
            $this->foreignKeys[$record['ID']] = $record[$this->getJoinField()];
        }
    }

    public function getRelationClass()
    {
        return $this->schema->hasOneComponent($this->parentDataObjectClass, $this->relationName);
    }

    public function extract(DataObject $dataObject)
    {
        $joinField = $this->getJoinField() . '__Cached';
        return isset($this->map[$dataObject->$joinField]) ? $this->map[$dataObject->$joinField] : null;
    }

    public function applyToDataObject(DataObject $dataObject)
    {
        $fk = isset($this->foreignKeys[$dataObject->ID]) ? $this->foreignKeys[$dataObject->ID] : null;
        if ($fk) {
            $dataObject->setField($this->getJoinField() . '__Cached', $fk);
        }

        $component = $this->extract($dataObject);
        if ($component) {
            $dataObject->setComponent($this->relationName, $component);
            foreach ($this->eager as $loader) {
                $loader->applyToDataObject($component);
            }
        }
    }
}