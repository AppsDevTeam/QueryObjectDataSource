<?php

namespace ADT\QueryObjectDataSource;

use ADT\DoctrineComponents\QueryObject\QueryObject;

interface IQueryObjectDataSourceFactory
{
	public function create(QueryObject $queryObject): QueryObjectDataSource;
}
