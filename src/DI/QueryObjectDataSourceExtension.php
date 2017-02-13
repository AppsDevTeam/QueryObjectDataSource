<?php

namespace ADT\QueryObjectDataSource\DI;

class QueryObjectDataSourceExtension extends \Nette\DI\CompilerExtension {

	public function loadConfiguration() {

		$this->getContainerBuilder()
			->addDefinition($this->prefix('factory'))
			->setImplement(\ADT\QueryObjectDataSource\IQueryObjectDataSourceFactory::class);
	}

}