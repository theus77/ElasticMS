<?php

namespace AppBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use AppBundle\DependencyInjection\Compiler\ViewTypeCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use AppBundle\DependencyInjection\Compiler\DataFieldTypeCompilerPass;

class AppBundle extends Bundle
{
	public function build(ContainerBuilder $container)
	{
		$container->addCompilerPass(new ViewTypeCompilerPass(), PassConfig::TYPE_OPTIMIZE);
		$container->addCompilerPass(new DataFieldTypeCompilerPass(), PassConfig::TYPE_OPTIMIZE);
	}
}
