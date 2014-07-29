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

use Orm\Model;
use Orm\Query;
use League\Fractal;

/**
 * Controller
 *
 * Skeleton Controller component
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
trait Controller
{
	/**
	 * Returns the module name
	 *
	 * @return string
	 */
	abstract public function get_module();

	/**
	 * Returns a translated name based on count
	 *
	 * @param integer $count
	 *
	 * @return string
	 */
	abstract public function get_name($count = 1);

	/**
	 * Returns the model name
	 *
	 * @return string
	 */
	abstract public function get_model();

	/**
	 * Returns the URL
	 *
	 * @return string
	 */
	abstract public function get_url();

	/**
	 * {@inheritdoc}
	 */
	public function before()
	{
		parent::before();

		$this->init();
	}

	/**
	 * Loads skeleton
	 */
	protected function init()
	{
		$this->check_access();

		$this->template->set_global('module', $this->get_module());
		$this->template->set_global('item', $this->get_name());
		$this->template->set_global('items', $this->get_name(999));
		$this->template->set_global('url', $this->get_url());
	}

	/**
	 * Checks whether user has access to an action
	 */
	protected function check_access($action = null)
	{
		is_null($action) and $action = $this->request->action;

		if ($this->has_access($action) === false)
		{
			$context = array(
				'form' => array(
					'%action%' => $action,
					'%items%'  => $this->get_name(999),
				)
			);

			\Logger::instance('alert')->error(gettext('You are not authorized to %action% %items%.'), $context);

			return \Response::redirect_back(\Uri::admin(false));
		}
	}

	/**
	 * Check whether user has access to something
	 *
	 * @param string $access
	 *
	 * @return boolean
	 */
	public function has_access($access)
	{
		return \Auth::has_access($this->get_module() . '.' . $access);
	}

	/**
	 * Sets the title
	 *
	 * @param string $title
	 *
	 * @return this
	 */
	public function set_title($title)
	{
		$this->template->set_global('title', $title);

		return $this;
	}

	/**
	 * Creates a new query
	 *
	 * @param [] $options
	 *
	 * @return Query
	 */
	protected function query($options = [])
	{
		return call_user_func([$this->get_model(), 'query'], $options);
	}

	/**
	 * Finds an entity of model
	 *
	 * @param integer $id
	 *
	 * @return Model
	 */
	protected function find($id = null)
	{
		$query = $this->query();
		$query->where('id', $id);

		if (is_null($id) or is_null($model = $query->get_one()))
		{
			throw new \HttpNotFoundException();
		}

		return $model;
	}

	/**
	 * Forge a new Model
	 *
	 * @see Model::forge
	 *
	 * @return Model
	 */
	protected function forge($data = [], $new = true, $view = null, $cache = true)
	{
		return call_user_func([$this->get_model(), 'forge'], $data, $new, $view, $cache);
	}

	/**
	 * Create new Form instance
	 *
	 * @return Form
	 */
	public function form()
	{
		return call_user_func([$this->get_model(), 'forgeForm']);
	}

	/**
	 * Create new Validator instance
	 *
	 * @return Validator
	 */
	public function validation()
	{
		return call_user_func([$this->get_model(), 'forgeValidator']);
	}

	/**
	 * Create new Transformer instance
	 *
	 * @param boolean $actions Use actions
	 *
	 * @return Transformer
	 */
	protected function transformer($actions = true)
	{
		return new Transformer($this, $actions);
	}

	/**
	 * Get filters for list
	 *
	 * @return array
	 */
	public function filters()
	{
		return call_user_func([$this->get_model(), 'generate_filters']);
	}

	/**
	 * Redirects a page
	 *
	 * @param string  $url
	 * @param string  $method
	 * @param integer $code
	 */
	protected function redirect($url = '', $method = 'location', $code = 302)
	{
		return \Response::redirect($url, $method, $code);
	}

	/**
	 * Decide whether the call is ajax or not
	 * Helps in development
	 *
	 * @return boolean
	 */
	protected function is_ajax()
	{
		if (\Fuel::$env == \Fuel::DEVELOPMENT)
		{
			return \Input::extension();
		}

		return \Input::is_ajax();
	}

	/**
	 * Process query for ajax request
	 *
	 * @param Query $query    Query object
	 * @param array $columns  Column definitions
	 * @param array $defaults Default column values
	 *
	 * @return integer Items count
	 */
	protected function process_query(Query $query, array $columns = [], array $defaults = [])
	{
		// Count all items
		$all_items_count = $query->count();

		// Process incoming sorting values
		$sort = [];
		for ($i = 0; $i < \Input::param('iSortingCols'); $i++)
		{
			$sort[\Input::param('iSortCol_'.$i)] = \Input::param('sSortDir_'.$i);
		}

		$i = 0;
		$order_by = [];
		$where = [];
		$global_filter = \Input::param('sSearch');

		foreach ($columns as $key => $value)
		{
			$rels = explode('.', $key);

			$rel = '';

			for ($j=0; $j < count($rels) - 1 and count($rels) > 1; $j++)
			{
				$rel .= '.' . $rels[$j];
				$query->related($rel);
			}

			$value = \Arr::merge($defaults, $value);

			if ($eav = \Arr::get($value, 'eav', false))
			{
				$query->related($rel . '.' . $eav);
			}

			// Order by statement
			if (\Input::param('bSortable_'.$i, true) and \Arr::get($value, 'sort', true) and array_key_exists($i,  $sort))
			{
				$order_by[$key] = $sort[$i];
			}

			$filter = \Input::param('sSearch_'.$i);

			$filter = json_decode($filter);

			if ( ! in_array($filter, array(null, '', 'null')) and \Input::param('bSearchable_'.$i, true) and \Arr::get($value, 'search', true))
			{
				switch (\Arr::get($value, 'type', 'text'))
				{
					case 'select-multiple':
					case 'select':
					case 'enum':
						$query->where($key, 'IN', $filter);
						break;
					case 'select-single':
					case 'number':
						$query->where($key, $filter);
						break;
					case 'text':
						$query->where($key, 'LIKE', '%' . $filter . '%');
						break;
					case 'range':
						$query->where($key, 'BETWEEN', $filter);
						break;
					default:
						break;
				}
			}

			if ( ! empty($global_filter))
			{
				if (\Arr::get($value, 'search', true) === true and \Arr::get($value, 'global', true) === true)
				{
					$where[] = array($key, 'LIKE', '%' . $global_filter . '%');
				}
			}

			$i++;
		}

		if ( ! empty($where))
		{
			$query->where_open();
			foreach ($where as $where)
			{
				$query->or_where($where[0], $where[1], $where[2]);
			}
			$query->where_close();
		}

		// Order query
		$query->order_by($order_by);

		$partial_items_count = $query->count();

		// Limit query
		$query
			->limit(\Input::param('iDisplayLength', 10))
			->offset(\Input::param('iDisplayStart', 0));

		return array($all_items_count, $partial_items_count);
	}

	public function action_index()
	{
		if ($ext = $this->is_ajax())
		{
			$properties = call_user_func([$this->get_model(), 'list_properties']);

			$query = $this->query();

			$count = $this->process_query($query, $properties);

			$models = $query->get();

			$resource = new Fractal\Resource\Collection($models, $this->transformer());
			$manager = new Fractal\Manager;

			$models = $manager->createData($resource)->toArray();

			$data = array(
				'sEcho' => \Input::param('sEcho'),
				'iTotalRecords' => $count[0],
				'iTotalDisplayRecords' => $count[1],
				'aaData' => $models['data']
			);

			in_array($ext, array('xml', 'json')) or $ext = 'json';

			$data = \Format::forge($data)->{'to_' . $ext}();

			return \Response::forge($data, 200, array('Content-type' => 'application/' . $ext));
		}
		else
		{
			$this->set_title(ucfirst($this->get_name(999)));

			$this->template->content = $this->view('admin/skeleton/list');
			$this->template->content->set('filters', $this->filters(), false);
		}
	}

	public function action_create()
	{
		$form = $this->form();

		if (\Input::method() == 'POST')
		{
			$post = \Input::post();

			$validator = $this->validation();
			$result = $validator->run($post);

			if ($result->isValid())
			{
				$model = $this->forge();
				$data = \Arr::filter_keys($post, $result->getValidated());
				$model->set($data)->save();

				$context = array(
					'template' => 'success',
					'from'     => '%item%',
					'to'       => $this->get_name(),
				);

				\Logger::instance('alert')->info(gettext('%item% successfully created.'), $context);

				return $this->redirect($this->url . '/view/' . $model->id);
			}
			else
			{
				$form->repopulate();

				$context = array(
					'errors' => $result->getErrors(),
				);

				\Logger::instance('alert')->error(gettext('There were some errors.'), $context);
			}
		}

		$this->set_title(ucfirst(strtr(gettext('New %item%'), ['%item%' => $this->get_name()])));

		$this->template->content = $this->view('admin/skeleton/create');
		$this->template->content->set('form', $form, false);
		isset($errors) and $this->template->content->set('errors', $errors, false);
	}

	public function action_view($id = null)
	{
		$model = $this->find($id);

		$this->set_title(strtr(gettext('View %item%'), ['%item%' => $this->get_name()]));
		$this->template->content = $this->view('admin/skeleton/view');
		$this->template->content->set('model', $model, false);
	}

	public function action_edit($id = null)
	{
		$model = $this->find($id);
		$form = $this->form();

		if (\Input::method() == 'POST')
		{
			$post = \Input::post();

			$validator = $this->validation();
			$result = $validator->run($post);

			if ($result->isValid())
			{
				$data = \Arr::filter_keys($post, $result->getValidated());
				$model->set($data)->save();

				$context = array(
					'template' => 'success',
					'from'     => '%item%',
					'to'       => $this->get_name(),
				);

				\Logger::instance('alert')->info(gettext('%item% successfully created.'), $context);

				return $this->redirect($this->url);
			}
			else
			{
				$form->repopulate();

				$context = array(
					'errors' => $result->getErrors(),
				);

				\Logger::instance('alert')->error(gettext('There were some errors.'), $context);
			}
		}
		else
		{
			$form->populate($model);
		}

		$this->set_title(strtr(gettext('Edit %item%'), ['%item%' => $this->get_name()]));

		$this->template->content = $this->view('admin/skeleton/edit');
		$this->template->content->set('model', $model, false);
		$this->template->content->set('form', $form, false);
		isset($errors) and $this->template->content->set('errors', $errors, false);
	}

	public function action_delete($id = null)
	{
		$model = $this->find($id);

		$context = array(
			'from' => '%item%',
			'to'   => $this->get_name(),
		);

		if ($model->delete())
		{
			$context['template'] = 'success';
			\Logger::instance('alert')->info(gettext('%item% successfully deleted.'), $context);
		}
		else
		{
			\Logger::instance('alert')->error(gettext('%item% cannot be deleted.'), $context);
		}

		return \Response::redirect_back();
	}
}
