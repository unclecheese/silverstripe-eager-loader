<?php

namespace App\ORM;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;

abstract class EagerLoader extends EagerLoadList
{
    /**
     * @var string
     */
    protected $relationName;

    /**
     * @var DataList
     */
    protected $parentList;

    /**
     * @var string
     */
    protected $parentDataObjectClass;

    /**
     * @var DataObjectSchema
     */
    protected $schema;

    /**
     * @var DataList
     */
    protected $list;

    /**
     * @var array
     */
    protected $map = [];

    /**
     * EagerLoader constructor.
     * @param DataList $parentList
     * @param string $class
     * @param string $relation
     */
    public function __construct(DataList $parentList, $class, $relation)
    {
        $this->relationName = $relation;
        $this->parentList = $parentList;
        $this->schema = DataObject::getSchema();
        $this->parentDataObjectClass = $class;
        parent::__construct($this->getRelationClass());
    }

    /**
     * @return string
     */
    public function getRelationName()
    {
        return $this->relationName;
    }

    /**
     * @return DataList
     */
    public function getList()
    {
        if (!$this->list) {
            $this->list = $this->createList();
        }
        return $this->list;
    }

    /**
     * @return DataList
     */
    public function end()
    {
        return $this->parentList;
    }
    /**
     * @return string
     */
    abstract public function getRelationClass();

    /**
     * @param DataObject $dataObject
     * @return DataObject
     */
    abstract public function extract(DataObject $dataObject);

    /**
     * @return $this
     */
    abstract public function createMap();

    /**
     * @return DataList
     */
    abstract protected function createList();

    /**
     * @param DataObject $dataObject
     */
    public function applyToDataObject(DataObject $dataObject)
    {
        /* @var EagerLoader $loader */
        foreach ($this->eager as $loader) {
            $loader->applyToDataObject($dataObject);
        }
    }

}