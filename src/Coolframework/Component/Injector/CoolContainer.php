<?php

namespace Coolframework\Component\Injector;

use Coolframework\Component\Injector\Exception\ServiceNotFoundException;
use ReflectionClass;

class CoolContainer
{
	private $container;
	private $service_store;

	public function __construct(
		$yml_parser,
		$path_to_services_config_file
	)
	{
		$this->container     = [];
		$this->service_store = [];

		$service_definitions = $yml_parser->parse(file_get_contents($path_to_services_config_file));
		foreach ($service_definitions as $service => $content)
		{
			if (array_key_exists($service, $this->container))
			{
				continue;
			}
			$this->container[ $service ] = $content;
		}
	}

	public function getService($a_name_of_the_service)
	{
		if (!array_key_exists($a_name_of_the_service, $this->container))
		{
			throw new ServiceNotFoundException('Service not found: ' . $a_name_of_the_service);
		}

		$this->service_store[ $a_name_of_the_service ] = $this->createService($this->container[ $a_name_of_the_service ]
		);

		return $this->service_store[ $a_name_of_the_service ];
	}

	private function createService($service_schema)
	{
		if (isset( $service_schema['arguments'] ))
		{
			$services_arguments = [];
			foreach ($service_schema['arguments'] as $argument)
			{
				$first_character = substr($argument, 0, 1);

				if ('@' === $first_character)
				{
					$service_to_ask       = str_replace("@", "", $argument);
					$services_arguments[] = $this->createService($this->container[ $service_to_ask ]);
				}
				else
				{
					$services_arguments[] = $argument;
				}

			}

			$reflector = new ReflectionClass($service_schema['class']);

			return $reflector->newInstanceArgs($services_arguments);
		}

		return new $service_schema['class']();
	}
}