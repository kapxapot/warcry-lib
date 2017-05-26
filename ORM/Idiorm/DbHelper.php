<?php

namespace Warcry\ORM\Idiorm;

use Warcry\Util\Util;

use Warcry\Exceptions\IApiException;
use Warcry\Exceptions\NotFoundException;
use Warcry\Exceptions\ValidationException;
use Warcry\Exceptions\AuthorizationException;

class DbHelper extends Helper {
	protected $tables;

	public function __construct($c) {
		parent::__construct($c);
		
		$this->tables = $this->getSettings('tables');
	}

	protected function can($table, $rights, $item = null) {
		return true;
	}
	
	public function forTable($table) {
		return \ORM::forTable($this->tables[$table]['table']);
	}
	
	public function fields($table) {
		return isset($this->tables[$table]['fields'])
			? $this->tables[$table]['fields']
			: null;
	}
	
	public function hasField($table, $field) {
		$fields = $this->fields($table);
		return $fields && in_array($field, $fields);
	}

	public function selectMany($table, $exclude = null) {
		$t = $this->forTable($table);
		$fields = $this->fields($table);
		
		if ($fields !== null && is_array($exclude)) {
			$fields = array_diff($fields, $exclude);
		}
		
		return ($fields !== null)
			? $t->selectMany($fields)
			: $t->selectMany();
	}

	public function json($response, $e, $options = []) {
		$result = $this->toArray($e);
		
		if (isset($options['params']['format'])) {
			$format = $options['params']['format'];
			// datatables
			if ($format == 'dt') {
				$wrapper = new \stdClass;
				$wrapper->data = $result;
				
				$result = $wrapper;
			}
		}

		return $response->withJson($result);
	}
	
	public function jsonOne($response, $e) {
		try {
			if ($e) {
				$response = $this->json($response, $e);
			}
			else {
	            throw new NotFoundException;
			}
		}
		catch (\Exception $ex) {
			$response = $this->error($response, $ex);
		}
		
		return $response;
	}

	public function error($response, $ex) {
		$status = 500;

		if ($ex instanceof IApiException) {
			$status = $ex->GetErrorCode();
		}
		
		$msg = null;
		$errors = [];

		if ($ex instanceof ValidationException) {
			foreach ($ex->errors as $field => $error) {
				$errors[$field] = $error;
				
				if (!$msg) {
					$msg = $error[0];
				}
			}
		}
		else {
			$msg = $ex->getMessage();
		}
	
		if ($this->getSettings()['log_errors']) {
			$this->logger->info("Error: {$msg}");
		}
	
		$error = [ 'error' => true, 'message' => $msg, 'format' => 'plain' ];
		
		if ($errors) {
			$error['errors'] = $errors;
		}
		
		return $this->json($response, $error)->withStatus($status);
	}

	public function getMany($table, $options = []) {
		$exclude = isset($options['exclude'])
			? $options['exclude']
			: null;

		$items = $this->selectMany($table, $exclude);
		
		if (isset($options['filter'])) {
			$items = $options['filter']($items, $options['args']);
		}

		$settings = $this->tables[$table];
		
		if (isset($settings['sort'])) {
			$sortBy = $settings['sort'];
			$reverse = isset($settings['reverse']);
			$items = $reverse
				? $items->orderByDesc($sortBy)
				: $items->orderByAsc($sortBy);
		}
		
		$array = $items->findArray();

		if (isset($options['mutator'])) {
			$array = array_map($options['mutator'], $array);
		}

		return array_values($array);
	}

	public function jsonMany($response, $table, $options = []) {
		$items = $this->getMany($table, $options);
		return $this->json($response, $items, $options);
	}

	public function get($response, $table, $id) {
		$e = $this->selectMany($table)
			->findOne($id);

		if (!$this->can($table, 'read', $e)) {
			$this->logger->info("Unauthorized read attempt on {$table}: {$e->id}");

			throw new AuthorizationException;
		}
			
		return $this->jsonOne($response, $e);
	}

	protected function beforeSave($request, $table, $data, $id = null) {
		return $data;
	}
	
	public function create($request, $response, $table, $options) {
		try {
			if (!$this->can($table, 'create')) {
				$this->logger->info("Unauthorized create attempt on {$table}");

				throw new AuthorizationException;
			}

			$original = $request->getParsedBody();
			$data = $this->beforeSave($request, $table, $original);
			
			if (isset($options['before_save'])) {
				$data = $options['before_save']($this->container, $data, null);
			}
			
			$e = $this->forTable($table)->create();
			$e->set($data);
			$e->save();
			
			if (isset($options['after_save'])) {
				$options['after_save']($this->container, $e, $original);
			}
			
			$this->logger->info("Created {$table}: {$e->id}");
			
			$response = $this->get($response, $table, $e->id)->withStatus(201);
		}
		catch (\Exception $ex) {
			$response = $this->error($response, $ex);
		}
		
		return $response;
	}
	
	public function update($request, $response, $table, $id, $options) {
		try {
			$original = $request->getParsedBody();
			$data = $this->beforeSave($request, $table, $original, $id);

			if (isset($options['before_save'])) {
				$data = $options['before_save']($this->container, $data, $id);
			}
	
			$e = $this->forTable($table)->findOne($id);

			if ($e) {
				if (!$this->can($table, 'edit', $e)) {
					$this->logger->info("Unauthorized edit attempt on {$table}: {$e->id}");

					throw new AuthorizationException;
				}
				else {
					$e->set($data);
					$e->save();
					
					if (isset($options['after_save'])) {
						$options['after_save']($this->container, $e, $original);
					}
					
					$this->logger->info("Updated {$table}: {$e->id}");
					
					$response = $this->get($response, $table, $e->id);
				}
			}
			else {
	            throw new NotFoundException;
			}
		}
		catch (\Exception $ex) {
			$response = $this->error($response, $ex);
		}
		
		return $response;
	}
	
	public function delete($response, $table, $id, $options) {
		try {
			$e = $this->forTable($table)->findOne($id);
			
			if ($e) {
				if (!$this->can($table, 'delete', $e)) {
					$this->logger->info("Unauthorized delete attempt on {$table}: {$e->id}");

					throw new AuthorizationException;
				}
				else {
					$e->delete();
					
					if (isset($options['after_delete'])) {
						$options['after_delete']($this->container, $e);
					}
					
					$this->logger->info("Deleted {$table}: {$e->id}");
					
					$response = $response->withStatus(204);
				}
			}
			else {
	            throw new NotFoundException;
			}
		}
		catch (\Exception $ex) {
			$response = $this->error($response, $ex);
		}
		
		return $response;
	}
	
	public function crud($app, $alias, $access = null, $options = []) {
		$table = $alias;
		
		$get = $app->get('/'.$alias.'/{id:\d+}', function ($request, $response, $args) use ($table) {
			return $this->db->get($response, $table, $args['id']);
		});
		
		$post = $app->post('/'.$alias, function ($request, $response, $args) use ($table, $options) {
			return $this->db->create($request, $response, $table, $options);
		});
		
		$put = $app->put('/'.$alias.'/{id:\d+}', function ($request, $response, $args) use ($table, $options) {
			return $this->db->update($request, $response, $table, $args['id'], $options);
		});
		
		$delete = $app->delete('/'.$alias.'/{id:\d+}', function ($request, $response, $args) use ($table, $options) {
			return $this->db->delete($response, $table, $args['id'], $options);
		});
		
		if ($access) {
			$get->add($access($table, 'api_read'));
			$post->add($access($table, 'api_create'));
			$put->add($access($table, 'api_edit'));
			$delete->add($access($table, 'api_delete'));
		}
	}
	
	public function getEntityById($table, $id) {
		$path = "data.{$table}.{$id}";
		$value = $this->cache->get($path);

		if ($value === null) {
			$entities = $this->forTable($table)
				->findArray();
			
			foreach ($entities as $entity) {
				$this->cache->set("data.{$table}.{$entity['id']}", $entity);
			}
		}

		return $this->cache->get($path);
	}
}
