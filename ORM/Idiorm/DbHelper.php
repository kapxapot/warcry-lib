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
	
	protected function getTableName($table) {
		return $this->tables[$table]['table'];
	}
	
	public function forTable($table) {
		$tableName = $this->getTableName($table);
		
		return \ORM::forTable($tableName);
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

	public function error($response, $ex) {
		$status = 400;

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
	
		$error = [ 'error' => true, 'message' => $msg ];
		
		if ($errors) {
			$error['errors'] = $errors;
		}
		
		return $this->json($response, $error)->withStatus($status);
	}
	
	protected function filterBy($items, $field, $args) {
		return $items->where($field, $args['id']);
	}

	public function apiGetMany($table, $provider, $options = []) {
		$exclude = $options['exclude'] ?? null;

		$items = $this->selectMany($table, $exclude);
		
		if (isset($options['filter'])) {
			$items = $this->filterBy($items, $options['filter'], $options['args']);
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
		
		$array = array_map(array($provider, 'afterLoad'), $array);

		return array_values($array);
	}

	public function jsonMany($response, $table, $provider, $options = []) {
		if (!$this->can($table, 'api_read')) {
			$this->logger->info("Unauthorized read attempt on {$table}");

			throw new AuthorizationException;
		}
		
		$items = $this->apiGetMany($table, $provider, $options);
		$response = $this->json($response, $items, $options);

		return $response;
	}

	public function apiGet($response, $table, $id, $provider) {
		$e = $this->selectMany($table)->findOne($id);

		if (!$e) {
            throw new NotFoundException;
		}

		if (!$this->can($table, 'api_read', $e)) {
			$this->logger->info("Unauthorized read attempt on {$table}: {$e->id}");

			throw new AuthorizationException;
		}
		
		$e = $provider->afterLoad($e);

		return $this->json($response, $e);
	}

	protected function beforeValidate($request, $table, $data, $id = null) {
		return $data;
	}

	public function apiCreate($request, $response, $table, $provider) {
		if (!$this->can($table, 'create')) {
			$this->logger->info("Unauthorized create attempt on {$table}");

			throw new AuthorizationException;
		}

		$original = $request->getParsedBody();
		$data = $this->beforeValidate($request, $table, $original);
		
		$provider->validate($request, $data);
		
		$data = $provider->beforeSave($data);

		$e = $this->forTable($table)->create();
		
		$e->set($data);
		$e->save();
		
		$provider->afterSave($e, $original);

		$this->logger->info("Created {$table}: {$e->id}");
		
		return $this->apiGet($response, $table, $e->id, $provider)->withStatus(201);
	}
	
	public function apiUpdate($request, $response, $table, $id, $provider) {
		$e = $this->forTable($table)->findOne($id);

		if (!$e) {
            throw new NotFoundException;
		}

		if (!$this->can($table, 'edit', $e)) {
			$this->logger->info("Unauthorized edit attempt on {$table}: {$e->id}");

			throw new AuthorizationException;
		}

		$original = $request->getParsedBody();
		$data = $this->beforeValidate($request, $table, $original, $id);

		$provider->validate($request, $data, $id);
		
		$data = $provider->beforeSave($data, $id);

		$e->set($data);
		$e->save();
		
		$provider->afterSave($e, $original);
		
		$this->logger->info("Updated {$table}: {$e->id}");
		
		$response = $this->apiGet($response, $table, $e->id, $provider);

		return $response;
	}
	
	public function apiDelete($response, $table, $id, $provider) {
		$e = $this->forTable($table)->findOne($id);
		
		if (!$e) {
            throw new NotFoundException;
		}

		if (!$this->can($table, 'delete', $e)) {
			$this->logger->info("Unauthorized delete attempt on {$table}: {$e->id}");

			throw new AuthorizationException;
		}

		$e->delete();
		
		$provider->afterDelete($e);

		$this->logger->info("Deleted {$table}: {$e->id}");
		
		$response = $response->withStatus(204);

		return $response;
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
