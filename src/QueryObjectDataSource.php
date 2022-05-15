<?php

namespace ADT\QueryObjectDataSource;

use Ublaboo\DataGrid\Filter\Filter;
use Ublaboo\DataGrid\Filter\FilterDate;
use Ublaboo\DataGrid\Filter\FilterDateRange;
use Ublaboo\DataGrid\Filter\FilterText;
use Ublaboo\DataGrid\Utils\DateTimeHelper;
use Ublaboo\DataGrid\DataSource\IDataSource;

class QueryObjectDataSource implements IDataSource
{
	/** @var \ADT\DoctrineComponents\ResultSet */
	protected $resultSet;

	/** @var \Doctrine\ORM\EntityRepository */
	protected $repo;

	/** @var \ADT\DoctrineComponents\QueryObject|IQueryObject */
	protected $queryObject;

	/** @var callable */
	public $filterCallback;

	/** @var callable */
	public $filterOneCallback;

	/** @var callable */
	public $sortCallback;

	/** @var callable */
	public $limitCallback;

	/** @var array */
	private $data;

	/**
	 * QueryObjectDataSource constructor.
	 * @param \ADT\DoctrineComponents\QueryObject $queryObject
	 * @param \Doctrine\ORM\EntityRepository|null $repo
	 * @throws \Exception
	 */
	public function __construct(\ADT\DoctrineComponents\QueryObject $queryObject, \Doctrine\ORM\EntityRepository $repo = null)
	{
		if (!$repo && (!$queryObject instanceof IQueryObject)) {
			throw new \Exception('"repo" must be set or "queryObject" has to implement IQueryObject interface.');
		}

		$this->queryObject = $queryObject;
		$this->repo = $repo ?: $queryObject->getEntityManager();
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

	/**
	 * Pro nastavení limitu lze specifikovat callback $callback. Pokud není zadán, je použit defaultní callback $defaultCallback.
	 * Callback $callback dostane mezi parametry i $defaultCallback, aby ho mohl případně zavolat.
	 *
	 * @param callable $callback Dostane parametry $offset, $limit, $defaultCallback
	 * @return self
	 */
	public function setLimitCallback($callback) {
		$this->limitCallback = $callback;
		return $this;
	}

	protected function getResultSet() {
		if (!$this->resultSet) {
			$this->resultSet = $this->queryObject->fetch();
		}

		return $this->resultSet;
	}

	/**
	 * Get count of data
	 * @return int
	 */
	public function getCount(): int {
		return $this->queryObject->fetch()->getTotalCount();
	}

	/**
	 * Get the data
	 * @return array
	 */
	public function getData(): array {
		if ($this->data) {
			return $this->data;
		}

		return $this->getResultSet()->toArray();
	}

	/**
	 * Set the data
	 * @return $this
	 */
	public function setData($data) {
		$this->data = $data;
		return $this;
	}

	/**
	 * Filter data
	 * @param array $filters
	 * @return static
	 */
	public function filter(array $filters): void
	{
		if (is_callable($this->filterCallback)) {
			call_user_func($this->filterCallback, $this->queryObject, $filters);
		}

		/** @var Filter $filter */
		foreach ($filters as $filter) {
			if ($filter->isValueSet()) {
				if ($filter->getConditionCallback()) {
					call_user_func($filter->getConditionCallback(), $this->queryObject, $filter->getValue());
				}
				elseif (!$filter instanceof FilterDateRange && $this->queryObject instanceof IQueryObject) {
					$this->queryObject->searchIn($filter->getKey(), $filter->getValue(), !$filter instanceof FilterText);
				}
			}
		}
	}

	/**
	 * Filter data - get one row
	 * @param array $filter
	 * @return static
	 */
	public function filterOne(array $filter): IDataSource {

		if (is_callable($this->filterOneCallback)) {
			call_user_func_array($this->filterOneCallback, [$this->queryObject, $filter]);
		}

		return $this;
	}

	/**
	 * Apply limit and offset on data
	 * @param int $offset
	 * @param int $limit
	 * @return static
	 */
	public function limit(int $offset, int $limit): IDataSource {
		$defaultCallback = function () use ($offset, $limit) {
			$this->getResultSet()->applyPaging($offset, $limit);
		};

		if (is_callable($this->limitCallback)) {
			call_user_func_array($this->limitCallback, [$offset, $limit, $defaultCallback]);

		} else {
			$defaultCallback();
		}

		return $this;
	}

	/**
	 * Sort data
	 * @param \Ublaboo\DataGrid\Utils\Sorting $sorting
	 * @return static
	 */
	public function sort(\Ublaboo\DataGrid\Utils\Sorting $sorting): IDataSource {

		if (is_callable($sorting->getSortCallback())) {
			call_user_func(
				$sorting->getSortCallback(),
				$this->queryObject,
				array_values($sorting->getSort())[0]
			);
			
		} else {
			
			$sort = $sorting->getSort();

			if (!empty($sort) && ($this->queryObject instanceof IQueryObject)) {
				$isFirst = true;
				foreach ($sort as $column => $order) {
					if ($isFirst) {
						$this->queryObject->orderBy($column, $order);
					}
					else {
						$this->queryObject->addOrderBy($column, $order);
					}
					$isFirst = false;
				}
			}
		} 
		
		if (is_callable($this->sortCallback)) {
			call_user_func_array($this->sortCallback, [$this->queryObject, $sorting]);
		}

		return $this;
	}

	/**
	 * @param FilterDateRange $filter
	 * @return array
	 */
	public static function parseFilterDateRange(FilterDateRange $filter) {

		$conditions = $filter->getCondition();

		$value_from = $conditions[$filter->getColumn()]['from'];
		$value_to = $conditions[$filter->getColumn()]['to'];

		if ($value_from) {
			$date_from = DateTimeHelper::tryConvertToDate($value_from, [$filter->getPhpFormat()]);
			$date_from->setTime(0, 0, 0);
		} else {
			$date_from = NULL;
		}

		if ($value_to) {
			$date_to = DateTimeHelper::tryConvertToDate($value_to, [$filter->getPhpFormat()]);
			$date_to->setTime(23, 59, 59);
		} else {
			$date_to = NULL;
		}

		return [
			'from' => $date_from,
			'to' => $date_to,
		];
	}

	/**
	 * @param FilterDate $filter
	 * @return \DateTime|null
	 */
	public static function parseFilterDate(FilterDate $filter) {
		foreach ($filter->getCondition() as $column => $value) {
			$date = DateTimeHelper::tryConvertToDateTime($value, [$filter->getPhpFormat()]);
			$date->setTime(0, 0, 0);
			return $date;
		}
	}

	public function getQueryObject() {
		return $this->queryObject;
	}

}
