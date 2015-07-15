<?php //> Й <- UTF mark

/*

All database fields list

item sample (all description elements are required. unpredictable behavior will occur if any options are skipped)
	array(
		'table'       => 'projects',               source table, alias, view, stored proc, real, generated, temporary or other
		'table_field' => 'usn_project',            field name in the source table. used for generating SQL only
		'field'       => 'usn_project',            field name to represent in the query (aka alias)
		'type'        => FIELD_TYPE_CHAR,          field type
		'caption'     => 'ID',                     caption to display
		'out_table'   => true,                     include or not to result set in table form (secutiry option). Default is true
		'out_edit'    => true,                     include or not to result set in edit_form (secutiry option). Default is true
		'width'       => 100,                      display column width
		'regexp'      => DB_REGEXP_INT,            field value must match this (eihter while updatind or validation)
		'comment'     => 'Идентификатор'           human-readable field description
		'categories'  => array('personal', ...)    categories to place field at row-edit dialog
		'readonly'    => true                      means the field cannot be changed directly through common editorial dialog
	)

*/

define('DB_FIELD_TYPE_INT', 1);
define('DB_FIELD_TYPE_TEXT', 2);
define('DB_FIELD_TYPE_DATETIME', 3);
define('DB_FIELD_TYPE_REAL', 4);
define('DB_FIELD_TYPE_BIT', 7);
define('DB_FIELD_TYPE_BLOB', 20);

define('DB_REGEXP_INT'         , '[0-9]+');
define('DB_REGEXP_GUID'        , '[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}');
define('DB_REGEXP_TEXT'        , '[a-zA-Zа-яА-Я\s.,()0-9<>\/]+');
define('DB_REGEXP_EMAIL'       , '[a-zA-Z0-9.\-]+@[a-zA-Z0-9.\-]+');
define('DB_REGEXP_PHONE'       , '[()0-9.,\s\-+]+');
define('DB_REGEXP_FILENAME'    , '[a-zA-Zа-яА-Я0-9\-.,\s]+');
define('DB_REGEXP_HOST'        , '[a-zA-Z0-9.\-]+');
define('DB_REGEXP_TEXT_STRICT' , '[a-zA-Zа-яА-Я\s.,()0-9]+');
define('DB_REGEXP_DATETIME'    , '[0-9\-/:.\s]+');

Registry::Set('api_fields', array(




	'clients_id' => array(
		'table'       => 'clients',
		'table_field' => 'id',
		'field'       => 'id',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'ID',
		'out_table'   => false,
		'out_edit'    => false,
		'width'       => 200,
		'regexp'      => DB_REGEXP_GUID,
		'default'     => function() { return create_guid(); },
		'readonly'    => true,
	),
	'clients_first_name' => array(
		'table'       => 'clients',
		'table_field' => 'first_name',
		'field'       => 'first_name',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'Имя',
		'width'       => 200,
		'regexp'      => DB_REGEXP_TEXT,
		'default'     => 'Новый',
		'categories'  => array(),
	),
	'clients_patronymic' => array(
		'table'       => 'clients',
		'table_field' => 'patronymic',
		'field'       => 'patronymic',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'Отчество',
		'width'       => 200,
		'regexp'      => DB_REGEXP_TEXT,
		'categories'  => array(),
	),
	'clients_second_name' => array(
		'table'       => 'clients',
		'table_field' => 'second_name',
		'field'       => 'second_name',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'Фамилия',
		'width'       => 200,
		'categories'  => array(),
		'regexp'      => DB_REGEXP_TEXT,
	),
	'clients_phone' => array(
		'table'       => 'clients',
		'table_field' => 'phone',
		'field'       => 'phone',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'Телефон',
		'width'       => 200,
		'regexp'      => DB_REGEXP_PHONE,
		'categories'  => array(),
	),
	'clients_email' => array(
		'table'       => 'clients',
		'table_field' => 'email',
		'field'       => 'email',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'Электронная почта',
		'width'       => 200,
		'regexp'      => DB_REGEXP_EMAIL,
		'categories'  => array(),
	),
	'clients_counter' => array(
		'table'       => 'clients',
		'table_field' => 'counter',
		'field'       => 'counter',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'Номер счетчика',
		'width'       => 200,
		'regexp'      => DB_REGEXP_INT,
		'categories'  => array()
	),
	'clients_address' => array(
		'table'       => 'clients',
		'table_field' => 'address',
		'field'       => 'address',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'Адрес',
		'width'       => 200,
		'regexp'      => DB_REGEXP_TEXT
	),
	'clients_labels' => array(
		'table'       => 'clients',
		'table_field' => 'labels',
		'field'       => 'labels',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'Метки',
		'width'       => 200,
		'regexp'      => DB_REGEXP_TEXT
	),






	'messages_id' => array(
		'table'       => 'messages',
		'table_field' => 'id',
		'field'       => 'id',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'id',
		'out_table'   => false,
		'out_edit'    => false,
		'width'       => 200,
		'regexp'      => DB_REGEXP_GUID,
		'default'     => function() { return create_guid(); },
		'readonly'    => true
	),
	'messages_subject' => array(
		'table'       => 'messages',
		'table_field' => 'subject',
		'field'       => 'subject',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'subject',
		'width'       => 200,
		'regexp'      => DB_REGEXP_TEXT
	),
	'messages_from' => array(
		'table'       => 'messages',
		'table_field' => 'from',
		'field'       => 'from',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'from',
		'width'       => 200,
		'regexp'      => DB_REGEXP_EMAIL,
		'readonly'    => true
		
	),
	'messages_to' => array(
		'table'       => 'messages',
		'table_field' => 'to',
		'field'       => 'to',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'Кому',
		'width'       => 200,
		'regexp'      => DB_REGEXP_EMAIL		
	),
	'messages_datetime' => array(
		'table'       => 'messages',
		'table_field' => 'datetime',
		'field'       => 'datetime',
		'type'        => DB_FIELD_TYPE_REAL,
		'caption'     => 'Время',
		'width'       => 200,
		'regexp'      => "[0-9.:\-\s]+",
		'readonly'    => true
	),
	'messages_message' => array(
		'table'       => 'messages',
		'table_field' => 'message',
		'field'       => 'message',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'Сообщение',
		'width'       => 200,
		'regexp'      => DB_REGEXP_TEXT
	),
	'messages_template' => array(
		'table'       => 'messages',
		'table_field' => 'template',
		'field'       => 'template',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'Шаблон',
		'width'       => 200,
		'regexp'      => DB_REGEXP_TEXT,
		'readonly'    => true
		
	),








	'mailfrom_id' => array(
		'table'       => 'mailfrom',
		'table_field' => 'id',
		'field'       => 'id',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'id',
		'out_table'   => false,
		'out_edit'    => false,
		'width'       => 20,
		'regexp'      => DB_REGEXP_GUID,
		'default'     => function() { return create_guid(); },
		'readonly'    => true
	),
	'mailfrom_caption' => array(
		'table'       => 'mailfrom',
		'table_field' => 'caption',
		'field'       => 'caption',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'Название',
		'width'       => 200,
		'regexp'      => DB_REGEXP_TEXT
	),
	'mailfrom_from_addr' => array(
		'table'       => 'mailfrom',
		'table_field' => 'from_addr',
		'field'       => 'from_addr',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'Адрес почты',
		'out'         => true,
		'width'       => 200,
		'regexp'      => DB_REGEXP_EMAIL
	),
	'mailfrom_server' => array(
		'table'       => 'mailfrom',
		'table_field' => 'server',
		'field'       => 'server',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'Адрес сервера',
		'width'       => 200,
		'regexp'      => DB_REGEXP_HOST
	),
	'mailfrom_port' => array(
		'table'       => 'mailfrom',
		'table_field' => 'port',
		'field'       => 'port',
		'type'        => DB_FIELD_TYPE_INT,
		'caption'     => 'Порт',
		'width'       => 200,
		'regexp'      => DB_REGEXP_INT
	),
	'mailfrom_auth_type' => array(
		'table'       => 'mailfrom',
		'table_field' => 'auth_type',
		'field'       => 'auth_type',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'Тип авторизации',
		'width'       => 200,
		'regexp'      => DB_REGEXP_TEXT_STRICT
	),
	'mailfrom_ehlo' => array(
		'table'       => 'mailfrom',
		'table_field' => 'ehlo',
		'field'       => 'ehlo',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'EHLO',
		'width'       => 200,
		'regexp'      => "[a-zA-Z0-9.\-@]+"
	),
	'mailfrom_login' => array(
		'table'       => 'mailfrom',
		'table_field' => 'login',
		'field'       => 'login',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'Логин',
		'width'       => 200,
		'regexp'      => DB_REGEXP_TEXT_STRICT
	),
	'mailfrom_password' => array(
		'table'       => 'mailfrom',
		'table_field' => 'password',
		'field'       => 'password',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'Пароль',
		'width'       => 200,
		'regexp'      => DB_REGEXP_TEXT
	),






	// message templates
	'templates_id' => array(
		'table'       => 'templates',
		'table_field' => 'id',
		'field'       => 'id',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'ID',
		'out_table'   => false,
		'out_edit'    => false,
		'width'       => 20,
		'regexp'      => DB_REGEXP_GUID,
		'default'     => function() { return create_guid(); },
		'readonly'    => true
	),
	'templates_caption' => array(
		'table'       => 'templates',
		'table_field' => 'caption',
		'field'       => 'caption',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'Заголовок',
		'width'       => 200,
		'regexp'      => DB_REGEXP_TEXT
	),
	'templates_message' => array(
		'table'       => 'templates',
		'table_field' => 'message',
		'field'       => 'message',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'Сообщение',
		'out_table'   => false,
		'out_edit'    => true,
		'width'       => 200,
		'regexp'      => DB_REGEXP_TEXT
	),
	'templates_attachments' => array(
		'table'       => 'templates',
		'table_field' => 'attachments',
		'field'       => 'attachments',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'Приложенные файлы',
		'width'       => 200,
		'regexp'      => DB_REGEXP_TEXT,
		'readonly'    => true
		
	),




	// comments
	'comments_id' => array(
		'table'       => 'comments',
		'table_field' => 'id',
		'field'       => 'id',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'ID',
		'out_table'   => false,
		'out_edit'    => false,
		'width'       => 100,
		'regexp'      => DB_REGEXP_GUID,
		'default'     => function() { return create_guid(); },
		'readonly'    => true
	),
	'comments_object_id' => array(
		'table'       => 'comments',
		'table_field' => 'object_id',
		'field'       => 'object_id',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'Объект',
		'width'       => 100,
		'regexp'      => DB_REGEXP_GUID,
		'readonly'    => true
	),
	'comments_user_id' => array(
		'table'       => 'comments',
		'table_field' => 'user_id',
		'field'       => 'user_id',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'Пользователь',
		'width'       => 100,
		'regexp'      => DB_REGEXP_TEXT,
		'readonly'    => true
	),
	'comments_stamp' => array(
		'table'       => 'comments',
		'table_field' => 'stamp',
		'field'       => 'stamp',
		'type'        => DB_FIELD_TYPE_DATETIME,
		'caption'     => 'Время',
		'width'       => 150,
		'regexp'      => DB_REGEXP_DATETIME,
		'readonly'    => true
	),
	'comments_comment_text' => array(
		'table'       => 'comments',
		'table_field' => 'comment_text',
		'field'       => 'comment_text',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'Комментарий',
		'width'       => 400,
		'regexp'      => DB_REGEXP_TEXT
	),
	'comments_attached_name' => array(
		'table'       => 'comments',
		'table_field' => 'attached_name',
		'field'       => 'attached_name',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'Приложенный файл',
		'width'       => 100,
		'regexp'      => DB_REGEXP_FILENAME,
		'readonly'    => true
	),
	'comments_comment_state' => array(
		'table'       => 'comments',
		'table_field' => 'comment_state',
		'field'       => 'comment_state',
		'type'        => DB_FIELD_TYPE_TEXT,
		'caption'     => 'Состояние',
		'width'       => 100,
		'regexp'      => DB_REGEXP_TEXT
	)


));




?>