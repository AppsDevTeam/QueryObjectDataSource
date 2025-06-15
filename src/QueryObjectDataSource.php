<?php

namespace ADT\QueryObjectDataSource;

use ADT\DoctrineComponents\QueryObject\QueryObject;
use ADT\DoctrineComponents\QueryObject\QueryObjectByMode;
use Contributte\Datagrid\DataSource\IDataSource;
use Contributte\Datagrid\Exception\DatagridDateTimeHelperException;
use DateTime;
use Exception;
use ReflectionException;
use Contributte\Datagrid\Filter\Filter;
use Contributte\DataGrid\Filter\FilterDate;
use Contributte\DataGrid\Filter\FilterDateRange;
use Contributte\DataGrid\Filter\FilterText;
use Contributte\Datagrid\Filter\OneColumnFilter;
use Contributte\Datagrid\Utils\DateTimeHelper;
use Contributte\Datagrid\Utils\Sorting;

class QueryObjectDataSource implements IDataSource
{
	protected ?array $resultSet = null;

	/** @var QueryObject */
	protected QueryObject $queryObject;

	/** @var callable */
	public $filterCallback;

	/** @var callable */
	public $filterOneCallback;

	/** @var callable */
	public $sortCallback;

	/** @var callable */
	public $limitCallback;

	private array $data = [];

	/**
	 * @throws Exception
	 */
	public function __construct(QueryObject $queryObject)
	{
		$this->queryObject = $queryObject;
	}

	public function setFilterCallback(callable $callback): static
	{
		$this->filterCallback = $callback;
		return $this;
	}

	public function setFilterOneCallback(callable $callback): static
	{
		$this->filterOneCallback = $callback;
		return $this;
	}

	public function setSortCallback(callable $callback): static
	{
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
	public function setLimitCallback(callable $callback): static
	{
		$this->limitCallback = $callback;
		return $this;
	}

	/**
	 * @throws ReflectionException
	 * @throws Exception
	 */
	protected function getResultSet(int $page = 1, ?int $itemsPerPage = null): array
	{
		if ($this->resultSet === null) {
			$this->resultSet = $itemsPerPage
				? iterator_to_array($this->queryObject->getResultSet($page, $itemsPerPage)->getIterator())
				: $this->queryObject->fetch();
		}

		return $this->resultSet;
	}

	/**
	 * Get count of data
	 * @return int
	 * @throws Exception
	 */
	public function getCount(): int
	{
		return $this->queryObject->count();
	}

	/**
	 * Get the data
	 * @throws ReflectionException
	 */
	public function getData(): array
	{
		if ($this->data) {
			return $this->data;
		}

		return $this->getResultSet();
	}

	/**
	 * Set the data
	 * @return $this
	 */
	public function setData($data): static
	{
		$this->data = $data;
		return $this;
	}

	/**
	 * Filter data
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
						QueryObjectByMode::CONTAINS);
				}
				elseif (!$filter instanceof FilterDateRange) {
					$this->queryObject->by(
						$filter instanceof OneColumnFilter ? $filter->getColumn() : $filter->getKey(),
						$filter->getValue()
					);
				}
			}
		}
	}

	/**
	 * Filter data - get one row
	 */
	public function filterOne(array $condition): static
	{
		if (is_callable($this->filterOneCallback)) {
			call_user_func_array($this->filterOneCallback, [$this->queryObject, $condition]);
		}

		return $this;
	}

	/**
	 * Apply limit and offset on data
	 * @param int $offset
	 * @param int $limit
	 * @return static
	 * @throws ReflectionException
	 */
	public function limit(int $offset, int $limit): static
	{
		if (is_callable($this->limitCallback)) {
			call_user_func_array($this->limitCallback, [$offset, $limit]);

		} else {
			$this->getResultSet($offset / $limit + 1, $limit);
		}

		return $this;
	}

	/**
	 * Sort data
	 */
	public function sort(Sorting $sorting): static
	{
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
	 * @throws DatagridDateTimeHelperException
	 */
	public static function parseFilterDateRange(FilterDateRange $filter): array
	{
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
	 * @throws DatagridDateTimeHelperException
	 */
	public static function parseFilterDate(FilterDate $filter): ?DateTime
	{
		foreach ($filter->getCondition() as $value) {
			$date = DateTimeHelper::tryConvertToDateTime($value, [$filter->getPhpFormat()]);
			$date->setTime(0, 0, 0);
			return $date;
		}

		return null;
	}

	public function getQueryObject(): QueryObject
	{
		return $this->queryObject;
	}
}
