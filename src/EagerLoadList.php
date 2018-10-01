<?php

namespace App\ORM;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use InvalidArgumentException;
use BadMethodCallException;

class EagerLoadList extends DataList
{

    /**
     * @var EagerLoader[]
     */
    protected $eager = [];

    /**
     * @return DataList
     */
    public function getList()
    {
        return $this;
    }

    /**
     * @param $relation
     * @param null $class
     * @return null|static
     */
    public function createEagerLoader($relation, $class = null)
    {
        /* @var DataObject $dataObject */
        if (!$class) {
            $class = $this->dataClass;
        }
        $dataObject = DataObject::singleton($class);
        $type = $dataObject->getRelationType($relation);
        $loader = null;
        if (!$type) {
            throw new InvalidArgumentException(sprintf(
                '%s is not a valid relation on %s',
                $relation,
                $class
            ));
        }
        switch ($type) {
            case 'has_one':
                $loader = HasOneEagerLoader::create($this->getList(), $class, $relation);
                break;
            case 'belongs_to':
                $loader = BelongsToEagerLoader::create($this->getList(), $class, $relation);
                break;
            case 'many_many':
            case 'belongs_many_many':
                $loader = ManyManyEagerLoader::create($this->getList(), $class, $relation);
                break;
            case 'has_many':
                $loader = HasManyEagerLoader::create($this->getList(), $class, $relation);
                break;
        }

        return $loader;
    }

    /**
     * @param EagerLoader $loader
     * @return $this
     */
    public function applyEagerLoader(EagerLoader $loader)
    {
        $this->eager[$loader->getRelationName()] = $loader;

        return $this;
    }

    /**
     * @param $relation
     * @param null $class
     * @return EagerLoadList|null
     */
    public function eagerLoad($relation, $class = null)
    {
        $this->applyEagerLoader(
            $loader = $this->createEagerLoader($relation, $class)
        );

        return $loader;
    }

    /**
     * @param array $row
     * @return DataObject
     */
    public function createDataObject($row)
    {
        $dataObject = parent::createDataObject($row);
        foreach ($this->eager as $relation => $loader) {
            $loader->applyToDataObject($dataObject);
        }

        return $dataObject;

    }

    /**
     * @return $this
     */
    public function load()
    {
        foreach($this->eager as $loader) {
            $loader->createMap();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function clearLoaders()
    {
        $this->eager = [];

        return $this;
    }
}