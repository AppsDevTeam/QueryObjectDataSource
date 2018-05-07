<?php

namespace ADT\QueryObjectDataSource;

interface IQueryObject
{

	function orderBy($column, $value);
	
	function searchIn($column, $value);
	
	function equalIn($column, $value);
	
}
