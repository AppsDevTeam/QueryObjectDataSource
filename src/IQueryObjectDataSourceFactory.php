<?php

namespace ADT\QueryObjectDataSource;

interface IQueryObjectDataSourceFactory {

	/**
	 * @param \Kdyby\Doctrine\QueryObject $queryObject
	 * @param \Kdyby\Doctrine\EntityRepository $repo
	 * @return QueryObjectDataSource
	 */
	function create(\Kdyby\Doctrine\QueryObject $queryObject, \Kdyby\Doctrine\EntityRepository $repo);

}
