# Ublaboo Datagrid data source bindings for Kdyby Doctrine query objects

## Installation

via composer:

```sh
composer require adt/query-object-data-source
```

and in config.neon:

```neon
extensions:
	- ADT\QueryObjectDataSource\DI\QueryObjectDataSourceExtension
```

## Usage

Inject or autowire QueryObjectDataSourceFactory:
```php
/** @var \ADT\QueryObjectDataSource\IQueryObjectDataSourceFactory @autowire */
protected $queryObjectDataSourceFactory;
```

Create query object and wrap it as data source:
```php
$qo = /* create query object */;

$dataSource = $this->queryObjectDataSourceFactory->create($qo, "id")
	->setSortCallback(function($queryObject, \Ublaboo\DataGrid\Utils\Sorting $sorting) {
		$sort = $sorting->getSort();

		if (!empty($sort)) {
			foreach ($sort as $order => $by) {
				$queryObject->order("e.$order", $by);
			}
		}
	})
	->setFilterCallback(function ($queryObject, array $filter) {
		foreach ($filter as $field => $value) {			
			switch ($column) {
				case 'dateRange':
					$queryObject->byDateRange(QueryObjectDataSource::parseFilterDateRange($fieldSet));
					break;
				case 'date':
					$queryObject->byDate(QueryObjectDataSource::parseFilterDate($fieldSet));
					break;
				default:
					$queryObject->{'by' . $field}($value);
			}
		}
	});

$grid->setDataSource($queryObjectDataSource);
```

You can use per column condition and sortable callbacks as well:
```php
$datagrid->addColumnText('email', 'entity.user.email')
	->setSortable()
	->setSortableCallback(function (UserQueryObject $userQuery, $email) {
		$userQuery->orderByEmail($email);
	})
	->setFilterText()
	->setCondition(function (UserQueryObject $userQuery, $email) {
		$userQuery->searchInEmail($email);
	});
```

If you implement \ADT\QueryObjectDataSource\IQueryObject on your QueryObject,
those methods will be called when there is no per column callbacks provided.
Function `searchIn($column, $value)` will be called on text fields and 
`equalIn($column, $value)` on other column types.
