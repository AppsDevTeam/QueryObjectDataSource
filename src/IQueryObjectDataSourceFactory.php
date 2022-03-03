<?php

namespace ADT\QueryObjectDataSource;

interface IQueryObjectDataSourceFactory {

	/**
	 * @param \ADT\BaseQuery\QueryObject $queryObject
	 * @param \Doctrine\ORM\EntityRepository|null $repo
	 * @return QueryObjectDataSource
	 */
	function create(\ADT\BaseQuery\QueryObject $queryObject, \Doctrine\ORM\EntityRepository $repo = null);

}
