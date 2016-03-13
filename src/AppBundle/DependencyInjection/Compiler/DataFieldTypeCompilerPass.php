<?php

namespace AppBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use AppBundle\Form\Form\ViewType;
use Symfony\Component\DependencyInjection\Definition;

class DataFieldTypeCompilerPass implements CompilerPassInterface
{
	public function process(ContainerBuilder $container)
	{
		if (!$container->hasDefinition('ems.form.field.datafieldtypepickertype')) {
			return;
		}
		
		/** @var Definition $definition */
		$definition = $container->findDefinition(
			'ems.form.field.datafieldtypepickertype'
		);
		
		$taggedServices = $container->findTaggedServiceIds(
			'ems.form.datafieldtype'
		);
		
		foreach ($taggedServices as $id => $tags) {
			foreach ($tags as $attributes) {
				$definition->addMethodCall(
					'addDataFieldType',
					array(new Reference($id), $id)
					
				);
			}
		}
	}
}