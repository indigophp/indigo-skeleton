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

use League\Fractal\TransformerAbstract;
use Fuel\Common\Arr;
use Uri;

/**
 * Transformer
 *
 * Skeleton Transformer component
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
class Transformer extends TransformerAbstract
{
	/**
	 * Controller object
	 *
	 * @var Controller
	 */
	protected $controller;

	/**
	 * Whether to attach actions
	 *
	 * @var boolean
	 */
	protected $actions = true;

	public function __construct($controller, $actions = true)
	{
		$this->controller = $controller;
		$this->actions = $actions;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param Orm\Model $model
	 */
	public function transform(\Orm\Model $model)
	{
		$properties = $model->list_properties();

		$data = $model->to_array(false, false, true);
		$data = Arr::subset($data, array_keys($properties));
		$data = Arr::flattenAssoc($data, '.');

		// Check for options and set value
		foreach ($properties as $key => $value)
		{
			if ( ! empty($data) and $options = Arr::get($value, 'options', false))
			{
				$data[$key] = $options[$data[$key]];
			}
		}

		if ($this->actions)
		{
			$data['action'] = $this->actions($model);
		}

		return array_values($data);
	}

	/**
	 * Return actions
	 *
	 * @return string Rendered View
	 */
	protected function actions(\Orm\Model $model)
	{
		$actions = array();

		if ($this->controller->has_access('view'))
		{
			array_push($actions, array(
				'url' => Uri::create($this->controller->get_url(). '/view/' . $model->id),
				'icon' => 'glyphicon glyphicon-eye-open',
			));
		}

		if ($this->controller->has_access('edit'))
		{
			array_push($actions, array(
				'url' => Uri::create($this->controller->get_url(). '/edit/' . $model->id),
				'icon' => 'glyphicon glyphicon-edit',
			));
		}

		if ($this->controller->has_access('delete'))
		{
			array_push($actions, array(
				'url' => Uri::create($this->controller->get_url(). '/delete/' . $model->id),
				'icon' => 'glyphicon glyphicon-remove text-danger',
			));
		}

		return $this->controller->view('admin/skeleton/list/action')
				->set('actions', $actions, false)
				->render();
	}
}
