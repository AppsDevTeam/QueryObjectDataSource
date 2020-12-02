<?php

namespace ADT\QueryObjectDataSource;

interface IQueryObject
{
	function orderBy(string $column, string $order = 'ASC');
	
	function addOrderBy(string $column, string $order = 'ASC');

	function searchIn($column, $value, bool $strict = false);
	
	function getEntityManager();
}
