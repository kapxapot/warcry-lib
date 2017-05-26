<?php

namespace Warcry\Validation\Rules;

abstract class TableFieldAvailable extends ContainerRule {
	private $table;
	private $field;
	private $id;

	public function __construct($table, $field, $id = null) {
		$this->table = $table;
		$this->field = $field;
		$this->id = $id;
	}
	
	public function validate($input) {
		$query = $this->container->db->forTable($this->table)
			->where($this->field, $input);
		
		if ($this->id) {
			$query = $query->where_not_equal('id', $this->id);
		}

		return $query->count() == 0;
	}
}
