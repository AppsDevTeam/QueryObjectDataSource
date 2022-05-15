<?php

namespace ADT\QueryObjectDataSource;

interface IQueryObjectDataSourceFactory {

	/**
	 * @param \ADT\DoctrineComponents\QueryObject $queryObject
	 * @param \Doctrine\ORM\EntityRepository|null $repo
	 * @return QueryObjectDataSource
	 */
	function create(\ADT\DoctrineComponents\QueryObject $queryObject, \Doctrine\ORM\EntityRepository $repo = null);

}
