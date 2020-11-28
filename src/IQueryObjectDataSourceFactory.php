<?php

namespace ADT\QueryObjectDataSource;

interface IQueryObjectDataSourceFactory {

	/**
	 * @param \Kdyby\Doctrine\QueryObject $queryObject
	 * @param \Kdyby\Doctrine\EntityRepository|null $repo
	 * @return QueryObjectDataSource
	 */
	function create(\Kdyby\Doctrine\QueryObject $queryObject, \Kdyby\Doctrine\EntityRepository $repo = null);

}
