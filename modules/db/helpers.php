<?php //> É <- UTF mark

/*

common helpers for API and UI classes
static methods only

*/

class J_DB_Helpers {


	/**
	 * Returns full field description compiled from original description (CMS::$R['db_api_fields']) and
	 * description at report definition, wich may contain overrides or even inline anonymous definition
	 *
	 * The following cases may occur:
	 * 1) number + string : link to original definition, no overrides, umber is array auto-index
	 * 2) string + array  : link to original definition and overrides array
	 * 3) number + array  : inline definition, no link to fields
	 *
	 * @param mixed $field_part1 field name, definition array or some number (see detailed description)
	 * @param mixed $field_part2 definition array or null for inline definition. The default is empty array
	 *                           for use with short syntax (getFullFieldDefinition('field_name'))
	 * @return mixed compiled definition or false on error
	 */
	public static function getFullFieldDefinition($field_part1, $field_part2 = array()) {

		// number + string
		if (is_numeric($field_part1) && is_string($field_part2)) {
			if (isset(CMS::$R['db_api_fields'][$field_part2])) {
				return CMS::$R['db_api_fields'][$field_part2];
			}
			trigger_error('getFullFieldDefinition: no field "'.$field_part2.'" defined', E_USER_ERROR);
			return false;
		}

		// string + array
		if (is_string($field_part1) && is_array($field_part2)) {
			if (isset(CMS::$R['db_api_fields'][$field_part1])) {
				return array_merge(CMS::$R['db_api_fields'][$field_part1], $field_part2);
			} else {
				trigger_error('getFullFieldDefinition: no field "'.$field_part1.'" defined, only override value will be used', E_USER_WARNING);
				return $field_part2;
			}
		}

		// number + array
		if (is_numeric($field_part1) && is_array($field_part2)) {
			return $field_part2;
		}

		// unknown combination
		trigger_error('getFullFieldDefinition: bad parameters: "'.print_r($field_part1, 1).'", "'.print_r($field_part2).'"', E_USER_ERROR);

	}

	
	/**
	 * Returns full action description by the same way as getFullFieldDefinition.
	 *
	 * @param mixed $action_part1 field name, definition array or some number (see detailed description)
	 * @param mixed $action_part2 definition array or null for inline definition
	 * @return mixed compiled definition or false on error
	 */
	public static function getFullActionDefinition($action_part1, $action_part2) {

		$actions = J_DB::$actions;
		if (class_exists('UserLogic')) {
			$actions = array_merge($actions, UserLogic::$actions);
		}

		// will merge every menu item to this default array to avoid massive "isset" checks
		$action_defaults = array(
			'caption'  => '',
			'disabled' => false,
			'hidden'   => false
		);
		
		// number + string
		if (is_numeric($action_part1) && is_string($action_part2)) {
			if (isset($actions[$action_part2])) {
				return array_merge($action_defaults, $actions[$action_part2]);
			}
			trigger_error('getFullFieldDefinition: no field "'.$action_part2.'" defined', E_USER_ERROR);
			return false;
		}

		// string + array
		if (is_string($action_part1) && is_array($action_part2)) {
			if (isset($actions[$action_part1])) {
				return array_merge($action_defaults, $actions[$action_part1], $action_part2);
			} else {
				trigger_error('getFullFieldDefinition: no action "'.$action_part1.'" defined, only override value will be used', E_USER_WARNING);
				return $action_part2;
			}
		}

		// number + array
		if (is_numeric($action_part1) && is_array($action_part2)) {
			return array_merge($action_defaults, $action_part2);
		}

		// unknown combination
		trigger_error('getFullActionDefinition: bad parameters: "'.print_r($action_part1, 1).'", "'.print_r($action_part2).'"', E_USER_ERROR);

	}

	/**
	 * Creates SELECT statement based on report and its field definitions. No filters and security
	 * limitations are applied here
	 *
	 * @param mixed $report_id_or_def report ID to generate SQL for, or direct report definition
	 * @param resource $DB database connection (needed to get brackets samples)
	 * @return string SQL statement
	 */
	public static function getReportMainSQL($report_id_or_def, $DB) {

		if (is_array($report_id_or_def)) {
			$report = $report_id_or_def;
		} else {
			$report = CMS::$R['db_api_reports'][$report_id_or_def];
		}

		// always use manual SQL if "use auto" is not set
		if (($sql = get_array_value($report, 'sql_select', false)) >'') {
			return $sql;
		}

		// just shorthands
		$lb = $DB->lb;
		$rb = $DB->rb;

		// all fields to constuct report from
		$fields = $report['fields'];

		// first, create FROM clause using table list. Main table always in list ;-)
		$table_list = $report['main_table'];

		if (isset($report['joined_tables'])) {
			foreach ($report['joined_tables'] as $joined) {

				// table to join. (join SECOND_TABLE as table_alias). can be real table/view (default) or sub-query
				$joined_table  =
					!isset($joined['type']) || $joined['type'] == 'real'
					? $lb.$joined['table'].$rb
					: '('.$joined['sql'].')';

				// alias to join table as (join second_table as TABLE_ALIAS)
				$joined_alias  = $lb. (isset($joined['alias']) ? $joined['alias'] : $joined['table']) . $rb;

				// field to join BY (join second_table as table_alias on table_alias.SOME_FIELD = first_table.id)
				$join_by_field = $lb.$joined['join_field'].$rb;

				// table to join TO (join second_table as table_alias on table_alias.some_field = FIRST_TABLE.id)
				$join_to_table = $lb.$joined['join_to_table'].$rb;

				// field to join TO (join second_table as table_alias on table_alias.some_field = first_table.ID)
				$join_to_field = $lb.$joined['join_to_field'].$rb;

				// join hint (LEFT, RIGHT or some other)
				$join_hint = isset($joined['join_hint']) ? $joined['join_hint'] : 'left';

				// add to table list
				$table_list .=
					PHP_EOL.
					"$join_hint join $joined_table as $joined_alias on $joined_alias.$join_by_field = $join_to_table.$join_to_field";
			}
		}

		// second, fields to select
		$select_list = '';
		foreach ($fields as $field_part_1 => $field_part_2) {
			$field_definition = self::getFullFieldDefinition($field_part_1, $field_part_2);

			$table_alias = $lb.$field_definition['table'].$rb;         // table alias to select from
			$field_name  = $lb.$field_definition['table_field'].$rb;   // real field name in the source table
			$field_alias = $lb.$field_definition['field'].$rb;         // field name as it will be selected (aka alias)

			// add to list!
			$select_list .= ($select_list > '' ? ', '.PHP_EOL : '')."\t$table_alias.$field_name as $field_alias";
		}

		// combine parts into final SQL
		$sql = 'select '.PHP_EOL.$select_list.PHP_EOL.'from '.$table_list;

		// yeah, finished!
		return $sql;
	}

	/**
	 * Shorthand for retrieving the field's default value
	 *
	 * @param array $field_definition full compliled field definition
	 * @param array $params options for calling if callable instead of string
	 * @return string
	 */
	public static function getFieldDefaultValue($field_definition, $params = array()) {
		if (isset($field_definition['default'])) {
			return is_callable($field_definition['default']) ? $field_definition['default']($params) : $field_definition['default'];
		}
		return null;
	}
	
}





?>