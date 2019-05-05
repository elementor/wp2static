<?php declare(strict_types = 1);

namespace PHPStan\WordPress;

use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\Dummy\DummyPropertyReflection;

class WpThemeMagicPropertiesClassReflectionExtension implements PropertiesClassReflectionExtension
{
	/** @var array */
	private $properties = array(
		'name', 'title', 'version', 'parent_theme', 'template_dir', 'stylesheet_dir', 'template', 'stylesheet',
		'screenshot', 'description', 'author', 'tags', 'theme_root', 'theme_root_uri',
	);

	public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
	{
		if ($classReflection->getName() !== 'WP_Theme') {
			return false;
		}
		return in_array($propertyName, $this->properties);
	}

	public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection {
		return new DummyPropertyReflection();
	}
}
