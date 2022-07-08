<?php

namespace ADT\QueryObjectDataSource;

interface IQueryObjectDataSourceFactory
{
	/**
	 * @param \ADT\DoctrineComponents\QueryObject $queryObject
	 * @return QueryObjectDataSource
	 */
	function create(\ADT\DoctrineComponents\QueryObject $queryObject);
}
