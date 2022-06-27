<?php

namespace ADT\QueryObjectDataSource;

interface IQueryObject
{
	function orderBy(string $column, string $order = 'ASC');
	
	function addOrderBy(string $column, string $order = 'ASC');

	function searchIn(array|string $column, mixed $value, bool $strict = false, ?string $joinType = 'innerJoin');
	
	function getEntityManager();
}
