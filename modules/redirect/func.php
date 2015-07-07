<?php

class J_Redirect extends JuliaCMSModule {

	function requestParser($template) {
        
		// now some bunch of samples
		//
        // ! absolute redirect area, take care !
		//
        ////////////////////////////////////////////////////////
        // common version
        ////////////////////////////////////////////////////////
        //if (
        //        (($_GET['key1'] == 'value1') && ($_GET['key2'] == 'value2')) 
        //        || ($_SERVER['QUERY_STRING'] == 'key1=value1&key2=value2')
        //        
        //) {
        //        header('HTTP/1.1 301 Moved Permanently');
        //        header('Location: chillers');
        //        terminate();
        //}
        ////////////////////////////////////////////////////////
		// use this if you need only some GET keys to match or
        ////////////////////////////////////////////////////////
        //if (($_GET['key1'] == 'value1') && ($_GET['key2'] == 'value2')) {
        //        header('HTTP/1.1 301 Moved Permanently');
        //        header('Location: chillers');
        //        terminate();
        //}
        ////////////////////////////////////////////////////////
		// full match version
        ////////////////////////////////////////////////////////
        //if ($_SERVER['QUERY_STRING'] == 'key1=value1&key2=value2') {
        //        header('HTTP/1.1 301 Moved Permanently');
        //        header('Location: chillers');
        //        terminate();
        //}
        ////////////////////////////////////////////////////////

		if ((@$_POST['module'] == 'redirect') || (@$_GET['module'] == 'redirect')) {
			// фильтруем вход
			$input_filter = array(
				'action'  => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^(redirect|no_redirect)$~ui')),
				'target'  => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => REGEXP_ALIAS))
			);
				
			$R = get_filtered_input($input_filter, array(FILTER_GET_FULL, FILTER_POST_FULL));
				
			switch ($R['action']) {
				// случай простого перенаправления
				case 'redirect':
					header('Location: ./'.$R['target']);
					terminate();
					break;
			}
		}
		return $template;
	}

}

?>