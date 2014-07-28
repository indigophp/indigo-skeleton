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

use Codeception\TestCase\Test;

/**
 * Tests for Skeleton Model
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 *
 * @coversDefaultClass Indigo\Skeleton\Model
 * @group              Skeleton
 * @group              Model
 */
class ModelTest extends Test
{
	public function _before()
	{
		\DummyModel::clear_cache();
	}

	/**
	 * @covers ::list_properties
	 * @covers ::skeleton_properties
	 * @covers ::compile_properties
	 */
	public function testListProperties()
	{
		$properties = \DummyModel::list_properties();
		$expected = [
			'id' => [
				'label' => 'ID',
				'type'  => 'text',
			],
		];

		$this->assertEquals($expected, $properties);
		$this->assertEquals($properties, \DummyModel::list_properties());
	}

	/**
	 * @covers ::form_properties
	 * @covers ::skeleton_properties
	 * @covers ::compile_properties
	 */
	public function testFormProperties()
	{
		$properties = \DummyModel::form_properties();
		$expected = [
			'name' => [
				'label' => 'Name',
				'type'  => 'text',
			],
			'description' => [
				'label'    => 'Description',
				'fieldset' => 'dummy',
				'type'     => 'textarea',
			],
		];

		$this->assertEquals($expected, $properties);
	}

	/**
	 * @covers ::view_properties
	 * @covers ::skeleton_properties
	 * @covers ::compile_properties
	 */
	public function testViewProperties()
	{
		$properties = \DummyModel::view_properties();
		$expected = [
			'id' => [
				'label' => 'ID',
			],
			'name' => [
				'label' => 'Name',
				'type'  => 'text',
			],
		];

		$this->assertEquals($expected, $properties);
	}

	/**
	 * @covers ::fieldsets
	 */
	public function testFieldsets()
	{
		$fieldsets = \DummyModel::fieldsets();

		$expected = [
			'dummy' => [
				'legend' => 'dummy',
			],
			'fake' => [
				'legend' => 'legend',
			],
		];

		$this->assertEquals($expected, $fieldsets);
		$this->assertEquals($fieldsets, \DummyModel::fieldsets());
	}

	/**
	 * @covers ::setProvider
	 */
	public function testValidator()
	{
		\DummyModel::forgeValidator();
	}

	/**
	 * @covers ::populateForm
	 * @covers ::generateInput
	 */
	public function testForm()
	{
		\DummyModel::add_select_field();

		\DummyModel::forgeForm();
	}

	/**
	 * @covers            ::populateForm
	 * @covers            ::generateInput
	 * @expectedException RuntimeException
	 */
	public function testFormFailure()
	{
		\DummyModel::add_fake_field();

		\DummyModel::forgeForm();
	}

	/**
	 * @covers ::generate_filters
	 */
	public function testFilters()
	{
		\DummyModel::unset_builder();
		\DummyModel::generate_filters();
	}
}
