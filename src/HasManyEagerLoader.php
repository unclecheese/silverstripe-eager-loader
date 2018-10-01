<?php

namespace App\ORM;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;

class HasManyEagerLoader extends EagerLoader
{

    /**
     * @return DataList
     */
    protected function createList()
    {
        $joinField = $this->getJoinField();
        return $this->filter([
            $joinField => $this->parentList->column('ID')
        ]);
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function getJoinField()
    {
        return $this->schema->getRemoteJoinField(
            $this->parentDataObjectClass,
            $this->relationName,
            'has_many'
        );
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function createMap()
    {
        foreach ($this->eager as $loader) {
            $loader->createMap();
        }
        $joinField = $this->getJoinField();
        foreach ($this->getList() as $item) {
            $parentID = $item->$joinField;
            if (!isset($this->map[$parentID])) {
                $this->map[$parentID] = [];
            }
            $this->map[$parentID][] = $item;
        }
        return $this;
    }

    /**
     * @return null|string
     */
    public function getRelationClass()
    {
        return $this->schema->hasManyComponent($this->parentDataObjectClass, $this->relationName);
    }

    /**
     * @param DataObject $dataObject
     * @return null|DataObject
     */
    public function extract(DataObject $dataObject)
    {
        return isset($this->map[$dataObject->ID]) ? $this->map[$dataObject->ID] : null;
    }

    /**
     * @param DataObject $dataObject
     */
    public function applyToDataObject(DataObject $dataObject)
    {
        $list = $this->extract($dataObject);
        if ($list) {
            // Todo: custom implmentations of mapping. Callback, maybe?
            $method = 'set' . $this->relationName;
            $dataObject->$method($list);
            foreach ($this->eager as $loader) {
                foreach ($list as $item) {
                    $loader->applyToDataObject($item);
                }
            }
        }
    }
}