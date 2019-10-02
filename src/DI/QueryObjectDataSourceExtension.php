<?php

namespace ADT\QueryObjectDataSource\DI;

class QueryObjectDataSourceExtension extends \Nette\DI\CompilerExtension {

	public function loadConfiguration() {

		$builder = $this->getContainerBuilder();
		if (method_exists($builder, 'addFactoryDefinition')) {
			$definition = $builder->addFactoryDefinition($this->prefix('factory'));
		} else {
			$definition = $builder->addDefinition($this->prefix('factory'));
		}
		$definition->setImplement(\ADT\QueryObjectDataSource\IQueryObjectDataSourceFactory::class);
	}

}