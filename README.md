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
			$queryObject->{'by' . $field}($value);
		}
	});

$grid->setDataSource($queryObjectDataSource);
```
