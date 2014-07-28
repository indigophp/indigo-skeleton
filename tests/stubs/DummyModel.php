<?php

class DummyModel extends \Orm\Model
{
	use \Indigo\Skeleton\Model;

	protected static $_properties = [
		'id' => [
			'label' => 'ID',
		],
		'name' => [
			'label' => 'Name',
			'type'  => 'text',
		],
		'slug' => [
			'label' => 'Slug',
		],
		'description' => [
			'label' => 'Description',
		],
	];

	protected static $_list_properties = [
		'id' => [
			'type' => 'text',
		],
	];

	protected static $_form_properties = [
		'name',
		'description' => [
			'fieldset' => 'dummy',
			'type'     => 'textarea',
		],
	];

	protected static $_view_properties = [
		'id',
		'name',
		'slug',
		'description',
	];

	protected static $_dummy_properties = [
		'id',
		'name',
		'dummy',
	];

	protected static $_fieldsets = [
		'dummy',
		'fake' => 'legend',
	];

	public static function add_fake_field()
	{
		static::$_form_properties['dummy'] = ['type' => 'text'];
	}

	public static function add_select_field()
	{
		static::$_form_properties['sel'] = [
			'type' => 'select',
			'options' => [
				'Group' => [
					'Value'
				],
				'Value 2',
			],
		];
	}

	public static function clear_cache()
	{
		static::$_skeleton_cached = [];
		static::$_properties_cached = [];
	}

	public static function unset_builder()
	{
		static::$builder = null;
	}
}
