<?php

namespace Warcry\ORM;

use Warcry\Exceptions\IApiException;
use Warcry\Exceptions\NotFoundException;
use Warcry\Exceptions\ValidationException;
use Warcry\Exceptions\AuthorizationException;

use Respect\Validation\Validator as v;

class IdiormHelper extends Helper {
	private $tables;
	private $validation;

	public function __construct($c) {
		parent::__construct($c);
		
		$this->tables = $this->getSettings('tables');
		$this->validation = $this->getSettings('validation');
	}
	
	private function getTableHelper($table) {
		return new TableHelper($this->container, $table);
	}
	
	private function can($table, $rights, $item = null) {
		$tableHelper = $this->getTableHelper($table);
		
		$access = $item
			? $tableHelper->getRights($item)
			: $tableHelper->getTableRights();

		return $access[$rights];
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

	public function json($response, $e) {
		return $response->withJson($this->toArray($e));
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

	private function addUserNames($item) {
		if (isset($item['created_by'])) {
			$created = $this->getUser($item['created_by']);
			if ($created !== null) {
				$item['created_by_name'] = $created['login'];
			}
		}

		if (isset($item['updated_by'])) {
			$updated = $this->getUser($item['updated_by']);
			if ($updated !== null) {
				$item['updated_by_name'] = $updated['login'];
			}
		}
		
		return $item;
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
		
		$tableHelper = $this->getTableHelper($table);

		$array = array_filter($array, array($tableHelper, 'canRead'));

		if (isset($options['mutator'])) {
			$array = array_map($options['mutator'], $array);
		}
		
		$array = array_map(array($this, 'addUserNames'), $array);
		$array = array_map(array($tableHelper, 'addRights'), $array);

		return array_values($array);
	}

	public function jsonMany($response, $table, $options = []) {
		$items = $this->getMany($table, $options);
		return $this->json($response, $items);
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

	private function validation($table, $data, $id = null) {
		$rules = [];
		
		$name = function() { return v::notBlank()->alnum(); };
		$alias = function() { return v::noWhitespace()->notEmpty()->alnum(); };
		$text = function() { return v::notBlank(); };
		$url = function() { return v::noWhitespace()->notEmpty(); };
		$posInt = function() { return v::numeric()->positive(); };
		
		$settings = $this->getSettings();
		
		$lat = function($add = '') { return "/^[\w {$add}]+$/"; };
		$cyr = function($add = '') { return "/^[\w\p{Cyrillic} {$add}]+$/u"; };

		switch ($table) {
			case 'articles':
				$rules = [
					'name_ru' => $text(),//->regex($cyr("'\(\):\-\.\|,\?!—«»")),
				];
				
				if (array_key_exists('name_en', $data) && array_key_exists('cat', $data)) {
					$rules['name_en'] = $text()->regex($lat("':\-"))->articleNameCatAvailable($data['cat'], $id);
				}

				break;
		
			case 'gallery_authors':
				$rules = [
					'name' => $text()->galleryAuthorNameAvailable($id),
					'alias' => $alias()->galleryAuthorAliasAvailable($id),
				];
				break;

			case 'gallery_pictures':
				$rules = [
					'comment' => $text(),
				];
				break;

			case 'games':
				$rules = [
					'icon' => $url(),
					'name' => $text()->gameNameAvailable($id),
					'alias' => $alias()->gameAliasAvailable($id),
					'news_forum_id' => v::optional($posInt()),
					'main_forum_id' => v::optional($posInt()),
					'position' => $posInt(),
				];
				break;
		
			case 'menus':
			case 'menu_items':
				$rules = [
					'link' => $url(),
					'text' => $text(),
					'position' => $posInt(),
				];
				break;

			case 'streams':
				$rules = [
					'title' => $text()->streamTitleAvailable($id),
					'stream_id' => $alias()->streamIdAvailable($id),
					'comments' => $text(),
				];
				break;
		
			case 'users':
				$rules = [
					'login' => $alias()->length($settings['login_min'], $settings['login_max'])->loginAvailable($id),
					'email' => $url()->email()->emailAvailable($id),
					'password' => v::optional(v::noWhitespace()->length($settings['password_min'])),
				];
				break;
		}
		
		return $rules;
	}

	private function beforeSave($request, $table, $data, $id = null) {
		// unset
		$canPublish = $this->can($table, 'publish');
		
		if (isset($data['published']) && !$canPublish) {
			unset($data['published']);
		}

		if (isset($data['password'])) {
			$password = $data['password'];
			if (strlen($password) > 0) {
				$data['password'] = $this->auth->encodePassword($password);
			}
			else {
				unset($data['password']);
			}
		}
		
		// validation
		$rules = $this->validation($table, $data, $id);
		$validation = $this->validator->validate($request, $rules);
		
		if ($validation->failed()) {
			throw new ValidationException($validation->errors);
		}

		// dirty
		/*if ($this->hasField($table, 'created_at') && !$id) {
			$data['created_at'] = $this->now();
		}*/

		if ($this->hasField($table, 'updated_at')) {
			$data['updated_at'] = $this->now();
		}

		$user = $this->auth->getUser();
		if ($this->hasField($table, 'created_by') && !$id) {
			$data['created_by'] = $user->id;
		}
		
		if ($this->hasField($table, 'updated_by')) {
			$data['updated_by'] = $user->id;
		}

		if ($this->hasField($table, 'cache')) {
			$data['cache'] = null;
		}
		
		if ($this->hasField($table, 'contents_cache')) {
			$data['contents_cache'] = null;
		}

		return $data;
	}
	
	public function create($request, $response, $table, $onWrite = null) {
		try {
			$data = $request->getParsedBody();
			$data = $this->beforeSave($request, $table, $data);
			
			if ($onWrite != null) {
				$data = $onWrite($this->container, $data);
			}
			
			$e = $this->forTable($table)->create();
			$e->set($data);
			$e->save();
			
			$this->logger->info("Created {$table}: {$e->id}");
			
			$response = $this->get($response, $table, $e->id)->withStatus(201);
		}
		catch (\Exception $ex) {
			$response = $this->error($response, $ex);
		}
		
		return $response;
	}
	
	public function update($request, $response, $table, $id, $onWrite = null) {
		try {
			$data = $request->getParsedBody();
			$data = $this->beforeSave($request, $table, $data, $id);

			if ($onWrite != null) {
				$data = $onWrite($this->container, $data, $id);
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
	
	public function delete($response, $table, $id) {
		try {
			$e = $this->forTable($table)->findOne($id);
			
			if ($e) {
				if (!$this->can($table, 'delete', $e)) {
					$this->logger->info("Unauthorized delete attempt on {$table}: {$e->id}");

					throw new AuthorizationException;
				}
				else {
					$e->delete();
					
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
	
	public function crud($app, $uriChunk, $access = null, $onWrite = null, $table = null) {
		if ($table == null) {
			$table = $uriChunk;
		}
	
		$get = $app->get('/'.$uriChunk.'/{id:\d+}', function ($request, $response, $args) use ($table) {
			return $this->db->get($response, $table, $args['id']);
		});
		
		$post = $app->post('/'.$uriChunk, function ($request, $response, $args) use ($table, $onWrite) {
			return $this->db->create($request, $response, $table, $onWrite);
		});
		
		$put = $app->put('/'.$uriChunk.'/{id:\d+}', function ($request, $response, $args) use ($table, $onWrite) {
			return $this->db->update($request, $response, $table, $args['id'], $onWrite);
		});
		
		$delete = $app->delete('/'.$uriChunk.'/{id:\d+}', function ($request, $response, $args) use ($table) {
			return $this->db->delete($response, $table, $args['id']);
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
	
	public function getUser($id) {
		return $this->getEntityById('users', $id);
	}
}
