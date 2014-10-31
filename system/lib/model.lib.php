<?php
class model {

	/**
	 * Array with data of model instance (field values of table row)
	 * @var array
	*/
	protected $_row = array();

	/**
	 * Class name of model
	 * @var string
	*/
	protected static $_modelName = '';

	/**
	 * Table name in db for this model
	 * @var string
	*/
	protected static $_tableName = '';

	/**
	 * Array with table structure.
	 * @var array
	*/
	protected static $_tableStructure = array();

	/**
	 * Field name of identity model
	 * @var string
	*/
	protected static $_rowIdentity = '';

	/**
	 * Identity of current model
	 * @var int
	*/
	protected $_id = 0;

	/**
	 * Turn on or off APC caching for current model
	 * @var bool
	*/
	protected static $_caching = true;

	/**
	 * Array fields of type date, datetime or time
	 * @var array
	*/
	protected static $_dateFields = array();

	/**
	 * Init model vars for static functions like get(), find()
	 * @return void
	*/
	public static function __init() {

			// get current model class name
		static::$_modelName = get_called_class();
		static::$_tableStructure = array();
		static::$_rowIdentity = '';

		if ( static::$_tableName!=='' ) {

			if ( false === ($fields_info = cache::get(static::$_tableName, 'db_schema')) ) {
				$fields_info = db::table_structure(static::$_tableName);
				if ( empty($fields_info['primary']['name']) ) {
					print('Фатальная ошибка: Не определен первичный ключ для таблицы "'.static::$_tableName.'" в модели "'.get_called_class().'"'); exit();
				} elseif ( empty($fields_info['fields']) ) {
					print('Фатальная ошибка: Не удалось определить структуру таблицы "'.static::$_tableName.'" в модели "'.get_called_class().'". Проверьте существует ли такая таблица, и есть ли у нее поля.'); exit();
				} else {
					cache::set(static::$_tableName, 'db_schema', $fields_info);
				}
			}

			static::$_tableStructure = $fields_info['fields'];
			static::$_rowIdentity = $fields_info['primary'];

			foreach (static::$_tableStructure as $field_name=>$field_type) {
				if ( $field_type!=='datetime' && $field_type!=='date' && $field_type!=='time' && $field_type!=='timestamp' ) continue;
				static::$_dateFields[] = $field_name;
			}
		}
	}

	/**
	 * Get single model by identity or by $fieldname
	 *
	 * @param  integer
	 * @return mixed Model object or false if not founded
	*/
	public static function get($identity, $fieldname='') {
		$fieldname = ( !empty($fieldname) ) ? $fieldname : static::getIdentityName();

		$query = 'SELECT * FROM '.static::getTableName().' WHERE '.$fieldname."='".db::real_escape($identity)."'";

		$model_list = static::getModelList($query);
		$model = ( $model_list ) ? array_pop($model_list) : false;

		return $model;
	}


	/**
	 * Fetches model objects from table by given params
	 * @return array
	*/
	public static function find() {
		$args = static::prepare_args(func_get_args());
		$query = static::query($args);

		return static::getModelList($query);
	}


	public static function query($args) {
		$offset = $args['offset'];
		$limit = $args['limit'];
		$distinct = $args['distinct'];
		$select = $args['select'];
		$join = $args['join'];
		$where = $args['where'];
		$order = $args['order'];
		$group = $args['group'];

			// mysql
		if ( config::get('dbTYPE')=='mysql' ) {
			return ( !(is_numeric($offset) && is_numeric($limit)) )
				? 'SELECT ' . $distinct . $select . ' FROM ' . static::getTableName() . implode(',', $join) . $where . $group . ' ORDER BY ' . $order
				: 'SELECT ' . $distinct . $select . ' FROM ' . static::getTableName() . implode(',', $join) . $where . $group . ' ORDER BY ' . $order . ' LIMIT ' . $offset . ', ' . $limit
				;

		} else {
			// mssql
			return ( !(is_numeric($offset) && is_numeric($limit)) )
				? 'SELECT ' . $distinct . $select . ' FROM ' . static::getTableName() . implode(',', $join) . $where . $group . ' ORDER BY ' . $order

				: 'SELECT * FROM
					(
						SELECT ' . $distinct . $select . ', ROW_NUMBER() OVER (ORDER BY ' . $order . ') AS RowNum
						FROM ' . static::getTableName() . implode(',', $join) . $where . $group . '
					) AS [table]
					WHERE [table].RowNum BETWEEN ' . ($offset + 1) . ' AND ' . ($offset + $limit) . ' ORDER BY RowNum ASC'
				;
		}
	}

	public static function prepare_args($args = array()) {
		$aditional_args = array();
		$placeholder_args = array();
		$join = array();
		foreach ($args as $key=>$value) {
			if ( is_array($value) ) {
				$aditional_args = $value;
			} else {
				$placeholder_args[] = $value;
			}
		}
		$where = db::placeholder($placeholder_args);
		if ( !empty($aditional_args['join']) ) {
			if ( is_array($aditional_args['join']) ) {

				foreach ($aditional_args['join'] as $table_name => $condition) {
					$join[] = ' LEFT JOIN ' . $table_name . ' ON (' . $condition . ')';
				}

			} else {
				$join[] = ' LEFT JOIN ' . $aditional_args['join'];
			}
		}

		$aditional_args['group'] = ( !empty($aditional_args['group']) ) ? ' GROUP BY ' . $aditional_args['group'] : NULL ;

		return array(
			'where'=> !empty($where) ? ' WHERE ' . $where : '',
			'order'=> !empty($aditional_args['order']) ? $aditional_args['order'] : self::getIdentityName(),
			'group'=> $aditional_args['group'],
			'offset'=> isset($aditional_args['offset']) ? intval($aditional_args['offset']) : NULL,
			'limit'=> isset($aditional_args['limit'] ) ? intval($aditional_args['limit']) : NULL,
			'join'=>$join,
			'distinct'=> isset($aditional_args['distinct']) ? ' DISTINCT ' : '',
			'select'=> !empty($aditional_args['select']) ? $aditional_args['select'] : self::getTableName() . '.*',
		);
	}


	/**
	 * Insert new row in table with current model info
	 * @return bool
	*/
	public function create() {
		$data = $this->toArray();

		if ( isset($data[$this->getIdentityName()]) && static::isIdentityAutoincrement() ) {
			unset($data[$this->getIdentityName()]);
		}

		$result = db::insert(static::getTableName(), $data, static::$_tableStructure);

		if ( $result ) {
			if ( static::isIdentityAutoincrement() ) {
				$this->setId(db::insert_id());
			} elseif ( isset($data[$this->getIdentityName()]) ) {
				$this->setId($data[$this->getIdentityName()]);
			}

			if ( static::$_caching ) {
				cache::clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array(static::$_modelName.'_find'));
				cache::set(static::$_modelName, $this->getId(), $this, array(static::$_modelName));
			}

			return true;
		}

		return false;
	}

	/**
	 * Update existing row in table with current model info
	 * @return bool
	*/
	public function update() {
		$data = $this->toArray();
		if ( isset($data[$this->getIdentityName()]) && static::isIdentityAutoincrement() ) {
			unset($data[$this->getIdentityName()]);
		}

		$result = db::update(static::getTableName(), $data, static::getIdentityName()."='".$this->getId()."'", static::$_tableStructure);
		if ( $result ) {
			if ( static::$_caching ) {
				cache::clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array(static::$_modelName.'_find'));
				cache::set(static::$_modelName, $this->getId(), $this, array(static::$_modelName));
			}
		}

		return $result;
	}

	/**
	 * Delete row from table
	 * @return bool
	*/
	public function delete() {
		cache::clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array(static::$_modelName.'_find'));
		return db::query('DELETE FROM '.static::getTableName().' WHERE '.static::getIdentityName()."='".$this->getId()."'");
	}

	/**
	 * Current model identity
	 * @return integer
	*/
	public function getId() {
		return $this->_id;
	}

	/**
	 * Sets identity for current model
	*/
	public function setId($identity) {
		$this->_id = $identity;
		$this->_row[$this->getIdentityName()] = $identity;
	}

	/**
	 * Set model info (table row fields) from array
	*/
	public function setFromArray($data) {
		foreach(static::$_tableStructure as $field=>$type) {
			if ( !isset($data[$field]) ) continue;

			$this->_row[$field] = $this->formatValue($type, $data[$field]);
		}

		if ( isset($this->_row[$this->getIdentityName()]) ) {
			$this->setId($this->_row[$this->getIdentityName()]);
		}
	}

	public function formatValue($type, $value) {

		if ( $value===' ' || $value==='' ) {
			$formatedValue='';

		} elseif ( $type=='int' || $type=='tinyint' ) {
			$formatedValue = (int)$value;

			if ( !$formatedValue ) {
				$formatedValue = 0;
			}

		} elseif ( $type=='float' || $type=='money' || $type=='bigint' ) {
			$formatedValue = (float)$value;

		} elseif ( $type=='datetime' || $type=='date' || $type=='time' || $type=='timestamp' ) {
			if ( empty($value) || '0000-00-00 00:00:00'===$value ) {
				$formatedValue = '';
			} elseif ( is_object($value) ) {
				$formatedValue = $value;
			} elseif ( is_numeric($value) ) {
				$formatedValue = new DateTime(date('d.m.Y H:i:s', $value));
			} else {
				$formatedValue = new DateTime($value);
			}

		} elseif ( $type=='varchar' ) {
			$formatedValue = trim($value);

		} elseif ( $type=='text' ) {
				// если переменная - json строка, то раскодируем в массив
			if ( is_string($value) && null!==($decoded_value = json_decode($value, true)) ) {
				$formatedValue = $decoded_value;
			} else {
				$formatedValue = $value;
			}

		} else {
			$formatedValue = $value;

		}

		return $formatedValue;
	}

	/**
	 * Get model info (db row fields) as an array
	 * @return	array
	*/
	public function toArray() {
		$data = $this->_row;
		return $data;
	}

	/**
	 * Get model info (db row fields) as an array
	 * @return	array
	*/
	public function toJson() {

		$data = $this->toArray();

			// переводим поля даты в таймстамп для js, или ансетим, если пустые
		if ( !empty(static::$_dateFields) ) {
			foreach(static::$_dateFields as $name) {
				if ( isset($data[$name]) ) {
					if ( $data[$name] ) {
						$data[$name] = $data[$name]->getTimestamp();
					} else {
						unset($data[$name]);
					}
				}
			}
		}

		return $data;
	}


	/**
	 * Getter returns identity, field from db row or model variable
	 * @param string field name from table
	 * @return mixed
	*/
	public function __get($varname) {
		return ( isset($this->_row[$varname]) ) ? $this->_row[$varname] : '';
	}

	/**
	 * Setter for model variables (field row or model variable)
	*/
	public function __set($varname, $value) {
		if ( isset(static::$_tableStructure[$varname]) ) {
			$this->_row[$varname] = $this->formatValue(static::$_tableStructure[$varname], $value);
		} else {
				//TODO: throw exeption if try to set not declared variable in model
			print('Фатальная ошибка: Попытка присвоить объекту "'.static::$_modelName.'" не объявленную переменную "'.$varname.'" в этом объекте'); exit();
		}
	}


	public static function getModelList($query) {

		if ( false === static::$_caching || false === ($model_list = cache::get(static::$_modelName, $query)) ) {
			$rowset = db::rows($query);
			if ( $rowset===false ) return false;

			$model_list = array();
			if ( $rowset ) {
				foreach($rowset as $data) {
					$model = new static::$_modelName;
					$model->setFromArray($data);
					if ( $model->getId() ) {
						$model_list[$model->getId()] = $model;
					} else {
						$model_list[] = $model;
					}
				}
				if ( static::$_caching ) {
					cache::set(static::$_modelName, $query, $model_list, array(static::$_modelName.'_find'));
				}
			}
		}

		return $model_list;
	}

	public static function count() {
		$args = static::prepare_args(func_get_args());

		$offset = $args['offset'];
		$limit = $args['limit'];
		$distinct = $args['distinct'];
		$join = $args['join'];
		$where = $args['where'];
		$order = $args['order'];
		$group = $args['group'];

		$query = 'SELECT ' . $distinct . ' COUNT(*) FROM ' . static::getTableName() . implode(',', $join) . $where . $group;

		if ( false === static::$_caching || false === ($count = cache::get(static::$_modelName, $query)) ) {
			$count = db::field($query);
			if ( false === $count && static::$_caching ) {
				cache::set(static::$_modelName, $query, $count, array(static::$_modelName.'_find'));
			}
		}

		return $count;
	}

	/**
	 * Current model table name
	 * @return string
	*/
	public static function getTableName() {
		return static::$_tableName;
	}

	/**
	 * Current model table name
	 * @return string
	*/
	public static function setTableName($tableName) {
		static::$_tableName = $tableName;
	}

	/**
	 * Current model identity field name
	 * @return string
	*/
	public static function getIdentityName() {
		return static::$_rowIdentity['name'];
	}

	/**
	 * Current model identity field name
	 * @return string
	*/
	public static function isIdentityAutoincrement() {
		return static::$_rowIdentity['auto_increment'];
	}

	/**
	 * return true is given field name is date type
	 * @return bool
	*/
	public static function isDateField($field) {
		return isset(static::$_tableStructure[$field]) && ( static::$_tableStructure[$field]=='datetime' || static::$_tableStructure[$field]=='date' || static::$_tableStructure[$field]=='time' || static::$_tableStructure[$field]=='timestamp' );
	}

	public static function cacheToggleOn() {
		static::$_caching = true;
	}

	public static function cacheToggleOff() {
		static::$_caching = false;
	}

}