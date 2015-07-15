<?php //> Й <- UTF mark

/*

all real database working
static methods only
all functions must take 3 parameters: input, output (metadata), database connection

*/

class J_DB_API {


	/**
	 * generates XML data for the report specified
	 *
	 * params[id] : report identifier to generate XML for
	 *
	 * XML structure:
	 * <report>
	 *   <caption>Entire report caption</caption>
	 *   <id_field>Field which should be ID of each data row</id_field>
	 *   <json>visual enhancements data (see below)</jsonl>
	 *   <header>
	 *     <field_caption>column caption for column 1</field_caption>
	 *     ...
	 *   </header>
	 *   <data_set>
	 *     <data_row>
	 *       <data>value for row 1, field 1</data>
	 *       ...
	 *     </data_row>
	 *     ...
	 *   </data_set>
	 *   <report_menu>
	 *     ... report menu XML. see menu.php generator for comments, I don't want duplicate it here
	 *   </report_menu>
	 *
	 *
	 * <json> entry contains CDATA with the following JSON structure:
	 * {
	 *   "columns" : {           // columns descriptions
	 *     "column1" : {         // field name
	 *       "width" : 100       // field width
	 *     }
	 *   }
	 * }
	 *
	 * @param array $params parameters
	 * @param array $return metadata parameters
	 * @param resource $DB database connection to use
	 * @return string XML string
	 *
	 */
	public static function generateTableXML($input, &$return_metadata, $DB) {

		$R = Registry::GetInstance();

		// check input
		if (isset($input['config'])) {
			$report_id = '*** inline report definition ***';
			$report_config = $input['config'];
		} else {
			if (!isset($input['report_id'])) {
				$return_metadata['status'] = 'ERROR';
				return 'no ID specified';
			}
			$report_id = $input['report_id'];

			if (!isset($R['api_reports'][$report_id])) {
				$return_metadata['status'] = 'ERROR';
				return 'no report config for ID specified ('.$report_id.')';
			}
			$report_config = $R['api_reports'][$report_id];
		}
		// new datablock ID
		$block_id = create_guid();

		// some init
		$xml = new DOMDocument('1.0', 'utf-8');
		$xml->preserveWhiteSpace = false;
		$xml->formatOutput = true;

		$report_root = $xml->createElement('report');
		$xml->appendChild($report_root);

		$json = array();

		// get report main caption id menu link, don't forget report ID
		$report_root->appendChild($xml->createElement('report_id'))     ->nodeValue = $report_id;
		$report_root->appendChild($xml->createElement('datablock_id'))  ->nodeValue = $block_id;
		$report_root->appendChild($xml->createElement('report_caption'))->nodeValue = $report_config['caption'];

		// include menu_id into JSON
		$json['reportId']    = $report_id;
		$json['datablockId'] = $block_id;
		$json['caption']     = $report_config['caption'];

		// report fields captions. also create field definitions cache
		$field_captions = $xml->createElement('header');

		$json['columns'] = array();
		$field_cache = array();

		foreach($report_config['fields'] as $field_part_1 => $field_part_2) {

			$field = J_DB_Helpers::getFullFieldDefinition($field_part_1, $field_part_2);

			if (get_array_value($field, 'out_table', true) === true) {

				// add to the cache to avoid these checks and merges while output data content
				array_push($field_cache, $field);

				// column header info
				$field_caption = $xml->createElement('field_caption');
				$field_caption->setAttribute('field', $field['field']);
				$field_caption->setAttribute('width', $field['width']);
				$field_caption->nodeValue = $field['caption'];
				$field_captions->appendChild($field_caption);

				// populate JSON source
				$json['columns'][$field['field']]['width'] = $field['width'];
			}
		}
		$report_root->appendChild($field_captions);

		// report data content
		$sql = J_DB_Helpers::getReportMainSQL($report_config, $DB);
		$query = $DB->query($sql);
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$all_data_rows = $xml->createElement('data_set');
		while ($data = $query->fetch(PDO::FETCH_ASSOC)) {
			$data_row = $xml->createElement('data_row');
			$data_row->setAttribute('id', $data[ $R['api_fields'][$report_config['id_field']]['field'] ]);
			foreach($field_cache as $field) {
				if (get_array_value($field, 'out_table', true)) {
					$data_cell = $xml->createElement('data');
					$data_cell->setAttribute('field', $field['field']);
					$data_cell->nodeValue = $data[$field['field']];
					$data_row->appendChild($data_cell);
				}
			}
			$all_data_rows->appendChild($data_row);
		}
		$report_root->appendChild($all_data_rows);

		// common report menu information
		/*
		$report_menu_items = isset($report_config['report_menu']) ? $report_config['report_menu'] : array();

		$menu_node = $xml->createDocumentFragment();
		$menu_node->appendXML(generate_menu($report_menu_items, array('root_tag'=>'report_menu')));
		$report_root->appendChild($menu_node);

		*/

		// add JSON
		$report_root->appendChild($xml->createElement('json'))->appendChild(new DOMCdataSection(json_encode($json)));

		return $xml->saveXML($report_root);
	}


	/**
	 * generates XML for context and other menus
	 *
	 * possible $input keys:
	 *   root_tag   : root node name. "menu" is the default.
	 *   menu_items : menu item list as array
	 *
	 * XML sample:
	 * <menu>
	 *   <menu_item image="images/pencil.png" js="alert(&quot;OK&quot;);" style="color: red;" class="test_class">Sample item</menu_item>
	 *   <menu_divider/>
	 *   <menu_item link="http://ya.ru" image="images/blank.png">New record</menu_item>
	 *   <menu_item image="images/pencil.png">Edit record</menu_item>
	 *   <menu_item api="edit_here">Edit this</menu_item>
	 * </menu>
	 *
	 * @param array $input parameters
	 * @param array $return metadata parameters
	 * @param resource $DB database connection to use
	 * @return string XML structure to transform of false on any error
	 *
	 */
	public static function generateContextMenuXML($input, &$return_metadata, $DB) {

		// some init
		$default_params = array(
			'root_tag' => 'menu'
		);
		$input = array_merge($default_params, $input);

		$xml = new DOMDocument('1.0', 'utf-8');
		$xml->preserveWhiteSpace = false;
		$xml->formatOutput = true;

		$menu_root = $xml->createElement($input['root_tag']);
		$xml->appendChild($menu_root);

		foreach($input['menu_items'] as $name_or_array_1 => $name_or_array_2) {

			$item = J_DB_Helpers::getFullActionDefinition($name_or_array_1, $name_or_array_2);

			// skip hiddens
			if ($item['hidden']) continue;

			// divider is separate tag
			if ($item['caption'] == '') {
				$menu_root->appendChild($xml->createElement('menu_divider'));
				continue;
			}

			// ok, get the element
			$menu_item = $xml->createElement('menu_item');
			$menu_item->nodeValue = $item['caption'];
			$menu_root->appendChild($menu_item);

			// disalble if requested
			if ($item['disabled']) {
				$menu_item->setAttribute('disabled', 'disabled');
				continue;
			}

			// add some attributes
			foreach (array('image', 'link', 'blank', 'js', 'api', 'style', 'class') as $add_attr) {
				if (isset($item[$add_attr])) {
					$menu_item->setAttribute($add_attr, $item[$add_attr]);
				}
			}
		}
		$return_metadata = array('type' => 'xml');
		return $xml->saveXML($menu_root);
	}

	
	/**
	 * generates edit dialog based on report's fields settings
	 *
	 * possible $params keys:
	 *   row_id : row ID
	 *   data   : field values to use
	 *
	 * XML sample:
	 * <edit-dialog>
	 * 	<report_id>1</report_id>
	 * 	<row_id>9A370D0A-8883-4E58-9605-9152D479A208</row_id>
	 * 	<new_record></new_record>
	 * 	<fields>
	 * 		<field field_name="first_name">
	 * 			<caption>Имя</caption>
	 * 			<value><![CDATA[Оз]]></value>
	 * 			<type>edit</type>
	 * 			<categories>
	 * 				<category>common</category>
	 * 			</categories>
	 * 		</field>
	 * 		...
	 * 	<categories>
	 * 		<category>common</category>
	 * 		...
	 * 	</categories>
	 * </edit-dialog>	
	 *
	 * @param array $input parameters
	 * @param array $return metadata parameters
	 * @param resource $DB database connection to use
	 * @return string XML string
	 *
	 */
	public static function generateEditorialXML($input, &$return_metadata, $DB) {

		$R = Registry::GetInstance();

		// check input, of course
		if (!isset($input['report_id'])) {
			$return_metadata = array('status' => 'error');
			return '<b>[JuliaCMS][db module][generateEditorialXML] error</b>: no report ID specified';
		}
		$report_id = $input['report_id'];
		
		if (!isset($R['api_reports'][$report_id])) {
			return '<b>[JuliaCMS][db module][generateEditorialXML] error</b>: no report config for this ID ('.$report_id.')';
		}
		$report_config = $R['api_reports'][$report_id];

		// check if we are creating new record or editing the existing one
		$new_record = get_array_value($input, 'new_record', false);

		// get record data
		if (!$new_record) {
			$lb = $DB->lb;
			$rb = $DB->rb;
			$sql = J_DB_Helpers::getReportMainSQL($report_id, $DB);
			$sql = "select * from ({$sql}) {$lb}ext{$rb} where {$lb}{$R['api_fields'][$report_config['id_field']]['field']}{$rb}='{$input['row_id']}'";
			$query = $DB->query($sql);		
			$data = $query->fetch(PDO::FETCH_ASSOC);
		} else {
			foreach ($report_config['fields'] as $part1 => $part2) {
				$field = J_DB_Helpers::getFullFieldDefinition($part1, $part2);
				$data[$field['field']] = J_DB_Helpers::getFieldDefaultValue($field);
			}
		}

		// some init
		$xml = new DOMDocument('1.0', 'utf-8');
		$xml->preserveWhiteSpace = false;
		$xml->formatOutput = true;

		// add row id
		$dialog_root = $xml->createElement('edit-dialog');
		$dialog_root->appendChild($xml->createElement('report_id'))->nodeValue = $report_id;
		$dialog_root->appendChild($xml->createElement('row_id'))->nodeValue = $input['row_id'];
		$dialog_root->appendChild($xml->createElement('new_record'))->nodeValue = get_array_value($input, 'new_record', false);
		
		// create fields list and compile categories list (will be added later)
		
		$dialog_fields_node = $xml->createElement('fields');
		$dialog_root->appendChild($dialog_fields_node);

		$category_list = array();
		foreach ($report_config['fields'] as $field_part_1 => $field_part_2) {
			$field_definition = J_DB_Helpers::getFullFieldDefinition($field_part_1, $field_part_2);

			// add field description
			if (get_array_value($field_definition, 'out_edit', true) === true) {
				$field_node = $xml->createElement('field');
				
				// field name as it comes from the query
				$field_node->setAttribute('field_name', $field_definition['field']);
				
				// caption. nothing special
				$field_node->appendChild($xml->createElement('caption'))->nodeValue = $field_definition['caption'];
				
				// value comes as CDATA because may contain HTML tags and other XSLT-inapropriate things
				$field_node->appendChild($xml->createElement('value'))->appendChild($xml->createCDATASection($data[$field_definition['field']]));
				
				// edit type - text, select, checkbox or something else
				$field_node->appendChild($xml->createElement('type'))->nodeValue = 'edit';

				// all edit box categories
				$categories_node = $xml->createElement('categories');
				if (isset($field_definition['categories'])) {
					foreach ($field_definition['categories'] as $category) {
						
						$categories_node->appendChild($xml->createElement('category'))->nodeValue = $category;
						
						// also add to list if not yet
						if (!in_array($category, $category_list)) {
							array_push($category_list, $category);
						}
					}
				}
				$field_node->appendChild($categories_node);
				$dialog_fields_node->appendChild($field_node);
			}
		}
		
		// now add compiled category list
		$category_list_node = $xml->createElement('categories'); // TAG_TODO возможно ли стащить в одну строку?
		$dialog_root->appendChild($category_list_node);
		
		// add "all" selector" if at least one category exists
		if (count($category_list) > 0) {
			$select_all_node = $xml->createElement('category');
			$select_all_node->setAttribute('all', 'all');
			$category_list_node->appendChild($select_all_node)->nodeValue = 'all';
		}
		foreach ($category_list as $edit_category) {
			$category_list_node->appendChild($xml->createElement('category'))->nodeValue = $edit_category;
		}
		
		//
		$return_metadata = array('type' => 'xml');
		return $xml->saveXML($dialog_root);
	
	}

	/**
	 * generates edit dialog based on report's fields settings
	 *
	 * possible $params keys:
	 * TAG_TODO
	 *
	 * XML sample:
	 * TAG_TODO
	 *
	 * @param array $input parameters
	 * @param array $return metadata parameters
	 * @param resource $DB database connection to use
	 * @return string XML string
	 *
	 */
	public static function generateCommentsXML($input, &$return_metadata, $DB) {

		// check ID first
		if (($object_id = get_array_value($input, 'row_id', false)) === false) {
			$return_metadata = array('status'=>'ERROR');
			return 'bad row id';
		}

		if (($report_id = get_array_value($input, 'report_id', false)) === false) {
			// TAG_TODO сделать автоматическое определение репорта
			echo 'WARNING: NO REPORT ID';
		} 

		// object list. used for some special situations when comments must be retrieved for the
		// selected object and some its related objects (i.e., all user's comments for all objects)
		$object_list = array($object_id);
		
		// generate list for SQL
		// TAG_TODO вынести в функцию (зачем?)
		$object_list_for_sql = "'".implode("','", $object_list)."'";
		
		// create SQL. note that it can be slightly different for some reports
		$sql = 'select * from ('.J_DB_Helpers::getReportMainSQL('report_comments', $DB).') int where object_id in ('.$object_list_for_sql.') order by stamp desc';
		
		// create XML for all the comments
		$xml = new DOMDocument('1.0', 'utf-8');
		$xml->preserveWhiteSpace = false;
		$xml->formatOutput = true;

		$xml_root = $xml->createElement('comments');
		$xml->appendChild($xml_root);
		
		$xml_root->appendChild($xml->createElement('report_id'))->nodeValue = $report_id;
		$xml_root->appendChild($xml->createElement('main_object_id'))->nodeValue = $object_id;

		$query = $DB->query($sql);
		while ($data = $query->fetch(PDO::FETCH_ASSOC)) {
			$comment_node = $xml->createElement('comment');
			$xml_root->appendChild($comment_node);
			foreach ($data as $key=>$value) {
				$comment_node->appendChild($xml->createElement($key))->nodeValue = $value;
			}
		}

		$return_metadata = array('type' => 'xml');
		return $xml->saveXML();
	}

	/**
	 * Adds a comment
	 *
	 * $input keys supported:
	 *   row_id       : object to add comment to
	 *   comment_text : comment itself
	 *
	 * Files attachment is also supported
	 *
	 * @param array $input parameters
	 * @param array $return metadata parameters
	 * @param resource $DB database connection to use
	 * @return string 'OK' or some error text
	 */
	public static function commentsAdd($input, &$return_metadata, $DB) {

		// check ID first
		if (($object_id = $input['row_id']) == '') {
			$return_metadata = array('status'=>'ERROR');
			return 'bad row ID';
		}
		
		$user_id = 'admin'; // TAG_TODO
		$stamp = date('Y.m.d H:i:s');
		
		// get specials for the input
		$input_filter = array(
			'comment_text' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Zа-яА-Я0-9\!\"\№\;\%\:\?\*\(\)\-\_\=\+\s]+$~smui'))
		);
		
		$filtered_input = get_filtered_input($input_filter, array(FILTER_GET_FULL, FILTER_POST_FULL));
		
		$sql = 'insert into comments (id, object_id, user_id, stamp, comment_text, attached_name, comment_state) values (:id, :object_id, :user_id, :stamp, :comment_text, :attached_name, :comment_state)';
		$prepared = $DB->prepare($sql);

		// iterate uploaded files, add comment for each
		for($file_index = 0; $file_index < count($_FILES['attachthis']['name']); $file_index++) {

			$comment_id = create_guid();
			
			// full comment text - append "(1/10)" in case of multiple files
			$comment_text_full = $filtered_input['comment_text'];
			if (count($_FILES['attachthis']['name']) > 1) {
				$comment_text_full = '('.($file_index+1).'/'.count($_FILES['attachthis']['name']).') '.$comment_text_full;
			}

			// copy attached file, mark comment if failed
			if ($_FILES['attachthis']['name'][$file_index] > '') {
				$copy_result = move_uploaded_file($_FILES['attachthis']['tmp_name'][$file_index],'userattached/'.$comment_id);
				if (!$copy_result) {
					$comment_text_full .= '(file not copied - re-attach)';
				}
			}
			
			// add the comment to the base
			$prepared->execute(array(
				':id'            => $comment_id,
				':object_id'     => $object_id,
				':user_id'       => $user_id,
				':stamp'         => $stamp,
				':comment_text'  => $comment_text_full,
				':attached_name' => $_FILES['attachthis']['name'][$file_index],
				':comment_state' => 'new comment'
			));

		}
		return 'OK';
	}

	/**
	 * Deletes a comment
	 * 
	 * This requires separate function since attached files should be deleted too
	 *
	 * @param array $input parameters
	 * @param array $return metadata parameters
	 * @param resource $DB database connection to use
	 * @return string 'OK' or some error text
	 */
	public static function commentsDelete($input, &$return_metadata, $DB) {
		
		// check ID first
		if (($object_id = $input['row_id']) == '') {
			$return_metadata = array('status'=>'ERROR');
			return 'bad row ID';
		}
		
		if ($DB->result('select count(*) from comments where id=\''.$object_id.'\'') == '0') {
			$return_metadata = array('status'=>'ERROR');
			return 'now comment with this ID';
		}
		
		unlink('userattached/'.$object_id);
		$DB->exec('delete from comments where id=\''.$object_id.'\'');
		
		return 'OK';
	}

	/**
	 * inserts a record to a table
	 *
	 * @param array $input parameters
	 * @param array $return metadata parameters
	 * @param resource $DB database connection to use
	 * @return string 'OK' or some error text
	 */
	public static function recordInsert($input, &$return_metadata, $DB) {

		$new_record_id = create_guid();
		if (!($report_config = get_array_value(Registry::Get('api_reports'), $input['report_id']))) {
			$return_metadata['status'] = 'error';
			return 'ERROR : no report with this ID';
		}

		$fields = '';       // fields list for INSERT statement
		$placeholders = ''; // values placeholders string, yeah we use prepared statement
		$values = array();  // values themselves
		
		foreach ($report_config['fields'] as $part1 => $part2) {
			$field = J_DB_Helpers::getFullFieldDefinition($part1, $part2);
			
			// field skipped only if it's read-only AND no default value defined
			if ((get_array_value($field, 'readonly', false) === true) && (!isset($field['default']))) { continue; }
			
			$fields       .= ($fields       > '' ? ', ' : '').$DB->lb.$field['table_field'].$DB->rb;
			$placeholders .= ($placeholders > '' ? ', ' : '').':'.$field['table_field'];
			
			$value = get_array_value($input, 'edit_'.$field['field'], J_DB_Helpers::getFieldDefaultValue($field));
			if (!preg_match('~^'.$field['regexp'].'$~smui', $value)) {
				$value = '';
			}
			$values[$field['table_field']] = $value;
		}
		$sql = 'insert into '.$DB->lb.$report_config['main_table'].$DB->rb.' ('.$fields.') values ('.$placeholders.')';
		$prepared = $DB->prepare($sql);
		foreach ($values as $field => $value) {
			$prepared->bindValue(':'.$field, $value);
		}
		$prepared->execute();

		$return_metadata['type']    = 'command';
		$return_metadata['command'] = 'reload';
		return 'OK';
	}

	/**
	 * Adds an empty record to a table
	 * TAG_TODO: implement field default values in the field descriptions
	 * TAG_TODO: add $DB->lb usage
	 * TAG_CRAZY
	 *
	 * @param array $input parameters
	 * @param array $return metadata parameters
	 * @param resource $DB database connection to use
	 * @return string 'OK' or some error text
	 */
	public static function recordAddEmpty($input, &$return_metadata, $DB) {

		$new_record_id = create_guid();
		if (!($report_config = get_array_value(Registry::Get('api_reports'), $input['report_id']))) {
			$return_metadata['status'] = 'error';
			return 'ERROR : no report with this ID';
		}

		switch($input['report_id']) {
			case '1':
					
//				foreach ($report_config['fields'] as $part1 => $part2) {
//					$default = J_DB_Helpers::getFieldDefaultValue( J_DB_Helpers::getFullFieldDefinition($part1, $part2) );				
//				}
//				$return_metadata['status'] = 'error';
//				return $t;
				$sql = 'insert into clients (id, first_name) values (\''.$new_record_id.'\', \'new client\')';
				break;
			case '3':
				$sql = 'insert into mailfrom (id, caption) values (\''.$new_record_id.'\', \'new address\')';
				break;
			case '4':
				$sql = 'insert into templates (id, caption) values (\''.$new_record_id.'\', \'new template\')';
				break;
		}

		$DB->exec($sql);
		$return_metadata['type']    = 'command';
		$return_metadata['command'] = 'reload';
		return 'OK';
	}

	/**
	 * Updates the record in database
	 *
	 * @param array $input parameters
	 * @param array $return metadata parameters
	 * @param resource $DB database connection to use
	 * @return string 'OK' or some error text
	 */
	public static function recordSave($input, &$return_metadata, $DB) {

		// input check : report
		if (!isset($input['report_id'])) {
			$return_metadata['status'] = 'error';
			return '[recordSave] no report ID specified';
		}
		$report_id = $input['report_id'];

		if (($report_config = get_array_value(Registry::Get('api_reports'), $report_id, false)) === false) {
			$return_metadata['status'] = 'error';
			return '[recordSave] no report with this ID';
		}

		// input check: row identifier
		if (!isset($input['row_id'])) {
			$return_metadata['status'] = 'error';
			return '[recordSave] no record ID specified';
		}
		$row_id = $input['row_id'];
		
		// also must math field check regexp
		$id_field_regexp = get_array_value( J_DB_Helpers::getFullFieldDefinition($report_config['id_field']), 'regexp', '.*');
		if (preg_match('~'.$id_field_regexp.'~', $row_id) === 0) {
			$return_metadata['status'] = 'error';
			return '[recordSave] bad record ID';
		}

		// if there no explicit UPDATE SQL specified, generate it
		if (($sql = get_array_value($report_config, 'sql_update', false)) === false) {
			$return_metadata['status'] = 'error';
			return 'ERROR: no update SQL';
		}

		// ok, prepare SQL statement and add values to it
		$prepared = $DB->prepare($sql);
		foreach ($report_config['fields'] as $part1 => $part2) {
			$field_definition = J_DB_Helpers::getFullFieldDefinition($part1, $part2);
			if (get_array_value($field_definition, 'readonly', false) === true) { continue; }
			$new_value = get_array_value($input, 'edit_'.$field_definition['field'], J_DB_Helpers::getFieldDefaultValue($field_definition));
			$prepared->bindValue(':'.$field_definition['field'], $new_value);
		}
		
		// also add row identifier
		$prepared->bindValue(':row_id', $row_id);
		
		// yeah go on
		$prepared->execute();

		return 'OK';
	}

	/**
	 * Deletes a record
	 * TAG_TODO add call userland API
	 *
	 * @param array $input parameters
	 * @param array $return metadata parameters
	 * @param resource $DB database connection to use
	 * @return string 'OK' or some error text
	 */
	public static function recordDelete($input, &$return_metadata, $DB) {

		$R = Registry::GetInstance();

		$table_name    = $DB->lb . $R['api_fields'][ $R['api_reports'][$input['report_id']]['id_field'] ]['table']       . $DB->rb;
		$id_field_name = $DB->lb . $R['api_fields'][ $R['api_reports'][$input['report_id']]['id_field'] ]['table_field'] . $DB->rb;

		$sql = "delete from $table_name where $id_field_name = :id";
		$prepared = $DB->prepare($sql);
		$prepared->execute(array(':id' => $input['row_id']));

		$return_metadata = array('type'=>'command', 'command'=>'reload');
		return 'OK';
	}



}


?>