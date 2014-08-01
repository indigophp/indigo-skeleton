<?php

/*
 * This file is part of the Indigo Skeleton package.
 *
 * (c) Indigo Development Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Indigo\Skeleton;

use Fuel\Fieldset\Form;
use Fuel\Fieldset\Builder\Basic;
use Fuel\Validation\ValidationAwareInterface;

/**
 * Model
 *
 * Skeleton Model component
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
trait Model
{
	use \Fuel\Fieldset\Builder\ModelBuilder;
	use \Fuel\Validation\RuleProvider\ModelProvider;

	/**
	 * Cached skeleton properties
	 *
	 * @var []
	 */
	protected static $_skeleton_cached = [];

	/**
	 * Cached fieldsets
	 *
	 * @var []
	 */
	protected static $_fieldsets_cached = [];

	/**
	 * Compiles skeleton properties
	 *
	 * @param []      $properties
	 * @param boolean $defaults
	 *
	 * @return []
	 */
	protected static function compile_properties(array $properties, $defaults = true)
	{
		$p = $properties;
		$properties = [];

		foreach ($p as $key => $value)
		{
			if (is_int($key))
			{
				$key = $value;
				$value = [];
			}

			// Get property defaults
			if ($defaults and $property = static::property($key, false))
			{
				$value = array_replace_recursive($property, $value);
			}

			$properties[$key] = $value;
		}

		return $properties;
	}

	/**
	 * Handles property caching
	 *
	 * @param string $type
	 *
	 * @return []
	 */
	public static function skeleton_properties($type)
	{
		$class = get_called_class();

		if (isset(static::$_skeleton_cached[$class][$type]))
		{
			return static::$_skeleton_cached[$class][$type];
		}

		$var = '_' . $type . '_properties';
		$properties = [];

		if (property_exists($class, $var))
		{
			$properties = static::$$var;
		}

		return static::$_skeleton_cached[$class][$type] = static::compile_properties($properties);
	}

	/**
	 * List skeleton properties
	 *
	 * @return []
	 */
	public static function list_properties()
	{
		return static::skeleton_properties('list');
	}

	/**
	 * Form skeleton properties
	 *
	 * @return []
	 */
	public static function form_properties()
	{
		return static::skeleton_properties('form');
	}

	/**
	 * View skeleton properties
	 *
	 * @return []
	 */
	public static function view_properties()
	{
		return static::skeleton_properties('view');
	}

	/**
	 * Returns the model fieldsets
	 *
	 * @return []
	 */
	public static function fieldsets()
	{
		$class = get_called_class();

		if (array_key_exists($class, static::$_fieldsets_cached))
		{
			return static::$_fieldsets_cached[$class];
		}

		$fieldsets = [];

		if (property_exists($class, '_fieldsets'))
		{
			$fieldsets = [];

			foreach (static::$_fieldsets as $fieldset => $config)
			{
				if (is_int($fieldset))
				{
					$fieldset = $config;
					$config = null;
				}

				if (empty($config))
				{
					$config = [
						'legend' => $fieldset,
					];
				}
				elseif (is_string($config))
				{
					$config = [
						'legend' => $config,
					];
				}

				$fieldsets[$fieldset] = $config;
			}
		}

		return static::$_fieldsets_cached[$class] = $fieldsets;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function setProvider(ValidationAwareInterface $provider)
	{
		static::$provider = $provider;

		static::$provider->setData(static::form_properties());
	}

	/**
	 * {@inheritdoc}
	 */
	public static function populateForm(Form $form)
	{
		if (static::$builder === null)
		{
			static::setBuilder(new Basic);
		}

		// Loop through and add all fieldsets
		foreach (static::fieldsets() as $fieldset => $config)
		{
			$form[$fieldset] = static::$builder->generateFieldset($config);
		}

		// Loop through and add all fields
		foreach (static::form_properties() as $field => $config)
		{
			$instance = static::generateInput($field, $config);

			if ($fieldset = \Arr::get($config, 'fieldset', false) and isset($form[$fieldset]))
			{
				$form[$fieldset][$field] = $instance;
			}
			elseif(isset($form[$field]))
			{
				throw new \RuntimeException('Another field or fieldset already exists with this identifier.');
			}
			else
			{
				$form[$field] = $instance;
			}
		}

		return $form;
	}

	/**
	 * Processes the given field and returns an element
	 *
	 * @param string $field          Name of the field to add
	 * @param []     $propertyConfig Array of any config to be added to the field
	 *
	 * @return Input Form input
	 */
	protected static function generateInput($field, array $propertyConfig)
	{
		$type = \Arr::get($propertyConfig, 'type', 'text');

		// Build up a config array to pass to the parent
		$config = [
			'name'       => $field,
			'label'      => \Arr::get($propertyConfig, 'label', $field),
			'attributes' => \Arr::get($propertyConfig, 'attributes', []),
		];

		$content = \Arr::get($propertyConfig, 'options', false);

		if ($content !== false)
		{
			foreach ($content as $value => $contentName)
			{
				if (is_array($contentName))
				{
					$group = [
						'type'  => 'optgroup',
						'label' => $value,
					];

					foreach ($contentName as $optValue => $optName)
					{
						$group['content'][] = [
							'type'    => 'option',
							'value'   => $optValue,
							'content' => $optName,
						];
					}

					$config['content'][] = $group;
				}
				else
				{
					$config['content'][] = [
						'type'    => 'option',
						'value'   => $value,
						'content' => $contentName, // FIXME
						'label'   => $contentName,
					];
				}
			}
		}

		$irrelevantKeys = ['attributes', 'label', 'options', 'type'];

		$meta = \Arr::filter_keys($propertyConfig, $irrelevantKeys, true);

		$instance = static::$builder->generateInput($type, $config)
			->setMeta($meta);

		return $instance;
	}

	/**
	 * Generates filters used in list
	 *
	 * @return [] Array of form elements
	 */
	public static function generate_filters()
	{
		if (static::$builder === null)
		{
			static::setBuilder(new Basic);
		}

		$form = [];

		// Loop through and add all fields
		foreach (static::list_properties() as $field => $config)
		{
			$form[] = static::generateInput($field, $config);
		}

		return $form;
	}
}
