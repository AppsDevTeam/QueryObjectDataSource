<?php

namespace ADT\QueryObjectDataSource;

class QueryObjectDataSource extends \Nette\Object implements \Ublaboo\DataGrid\DataSource\IDataSource {

	/** @var \Kdyby\Doctrine\ResultSet */
	protected $resultSet;

	/** @var \Kdyby\Doctrine\EntityRepository */
	protected $repo;

	/** @var \Kdyby\Doctrine\QueryObject */
	protected $queryObject;

	/** @var callable */
	public $filterCallback;

	/** @var callable */
	public $filterOneCallback;

	/** @var callable */
	public $sortCallback;

	/**
	 * @param \Kdyby\Doctrine\QueryObject $queryObject
	 * @param \Kdyby\Doctrine\EntityRepository $repo
	 */
	public function __construct(\Kdyby\Doctrine\QueryObject $queryObject, \Kdyby\Doctrine\EntityRepository $repo) {
		$this->queryObject = $queryObject;
		$this->repo = $repo;
	}

	/**
	 * @param callable $callback
	 * @return self
	 */
	public function setFilterCallback($callback) {
		$this->filterCallback = $callback;
		return $this;
	}

	/**
	 * @param callable $callback
	 * @return self
	 */
	public function setFilterOneCallback($callback) {
		$this->filterOneCallback = $callback;
		return $this;
	}

	/**
	 * @param callable $callback
	 * @return self
	 */
	public function setSortCallback($callback) {
		$this->sortCallback = $callback;
		return $this;
	}

	protected function getResultSet() {
		if (!$this->resultSet) {
			$this->resultSet = $this->repo
				->fetch($this->queryObject);
		}

		return $this->resultSet;
	}

	/**
	 * Get count of data
	 * @return int
	 */
	public function getCount() {
		return $this->repo
			->fetch($this->queryObject)
			->getTotalCount();
	}

	/**
	 * Get the data
	 * @return array
	 */
	public function getData() {
		return $this->getResultSet()->toArray();
	}

	/**
	 * Filter data
	 * @param array $filters
	 * @return static
	 */
	public function filter(array $filters) {
		foreach ($filters as $filter) {
			if ($filter->isValueSet()) {
				if ($filter->hasConditionCallback()) {
					\Nette\Utils\Callback::invokeArgs(
						$filter->getConditionCallback(), [ $this->queryObject, $filter->getValue() ]
					);
				}
			}
		}

		if (is_callable($this->filterCallback)) {
			$this->filterCallback($this->queryObject, $filters);
		}

		return $this;
	}

	/**
	 * Filter data - get one row
	 * @param array $filter
	 * @return static
	 */
	public function filterOne(array $filter) {

		if (is_callable($this->filterOneCallback)) {
			$this->filterOneCallback($this->queryObject, $filter);
		}

		return $this;
	}

	/**
	 * Apply limit and offset on data
	 * @param int $offset
	 * @param int $limit
	 * @return static
	 */
	public function limit($offset, $limit) {
		$this->getResultSet()->applyPaging($offset, $limit);

		return $this;
	}

	/**
	 * Sort data
	 * @param \Ublaboo\DataGrid\Utils\Sorting $sorting
	 * @return static
	 */
	public function sort(\Ublaboo\DataGrid\Utils\Sorting $sorting) {

		if (is_callable($this->sortCallback)) {
			$this->sortCallback($this->queryObject, $sorting);
		}

		return $this;
	}

	public function getQueryObject() {
		return $this->queryObject;
	}

}
