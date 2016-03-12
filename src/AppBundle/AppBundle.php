<?php

namespace AppBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use AppBundle\DependencyInjection\Compiler\ViewTypeCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

class AppBundle extends Bundle
{
	public function build(ContainerBuilder $container)
	{
		$container->addCompilerPass(new ViewTypeCompilerPass(), PassConfig::TYPE_OPTIMIZE);
	}
}
