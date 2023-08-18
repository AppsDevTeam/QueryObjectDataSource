<?php

namespace ADT\QueryObjectDataSource;

use Ublaboo\DataGrid\Filter\Filter;
use Ublaboo\DataGrid\Filter\FilterDate;
use Ublaboo\DataGrid\Filter\FilterDateRange;
use Ublaboo\DataGrid\Filter\FilterText;
use Ublaboo\DataGrid\Filter\OneColumnFilter;
use Ublaboo\DataGrid\Utils\DateTimeHelper;
use Ublaboo\DataGrid\DataSource\IDataSource;

class QueryObjectDataSource implements IDataSource
{
	/** @var \ADT\DoctrineComponents\ResultSet */
	protected $resultSet;

	/** @var \ADT\DoctrineComponents\QueryObject */
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
	 * @throws \Exception
	 */
	public function __construct(\ADT\DoctrineComponents\QueryObject $queryObject)
	{
		$this->queryObject = $queryObject;
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

	protected function getResultSet(int $page = 1, ?int $itemsPerPage = null) {
		if (!$this->resultSet) {
			$this->resultSet = $itemsPerPage
				? iterator_to_array($this->queryObject->getResultSet($page, $itemsPerPage)->getIterator())
				: $this->queryObject->getQuery()->getResult();
		}

		return $this->resultSet;
	}

	/**
	 * Get count of data
	 * @return int
	 */
	public function getCount(): int {
		return $this->queryObject->count();
	}

	/**
	 * Get the data
	 * @return array
	 */
	public function getData(): array {
		if ($this->data) {
			return $this->data;
		}

		return $this->getResultSet();
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
			($this->filterCallback)($this->queryObject, $filters);
		}

		/** @var Filter $filter */
		foreach ($filters as $filter) {
			if ($filter->isValueSet()) {
				if ($filter->getConditionCallback()) {
					call_user_func($filter->getConditionCallback(), $this->queryObject, $filter->getValue());
				}
				elseif ($filter instanceof FilterText) {
					$by = array_keys($filter->getCondition());
					$value = $filter->getCondition()[$by[0]];
					$this->queryObject->by(
						$by,
						$value,
						false);
				}
				elseif (!$filter instanceof FilterDateRange) {
					$this->queryObject->by(
						$filter instanceof OneColumnFilter ? $filter->getColumn() : $filter->getKey(),
						$filter->getValue(),
						true
					);
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

		if (is_callable($this->limitCallback)) {
			call_user_func_array($this->limitCallback, [$offset, $limit, $defaultCallback]);

		} else {
			$this->getResultSet($offset / $limit + 1, $limit);
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
			if (!empty($sorting->getSort())) {
				$this->queryObject->orderBy($sorting->getSort());
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
