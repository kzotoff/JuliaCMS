<?php //> Й <- UTF mark

/**
 * base module class
 *
 * @package JuliaCMSModule
 */

/*************************************************************************************************************************

base module class
has 4 primary public functions that accessed from CMS engine:
	* request parser. main function - read _GET and _POST data and handle them if required
	* content generator. adds module-generated content into template page (or replaces entire page with own data)
	* ajax handler. called by engine if special parameter exists in the GET request.
	  All other modules are ignored in this case, request parser and content generator are also paseed by
	* admin page content. In admin mode, engine will replace entire page content with admin-function generated content
	
class provides some common variables:
	* $R - global storage facility
	* $CONFIG - parsed module config from config.json (section "config")
	
базовый класс модуля
имеет 4 основных public функции, по которым к нему обращается основной движок:
	* обработчик запросов. Основная функция - обработать GET и POST и сделать какие-то действия
	* генератор содержимого. На вход подается страничка в текущем состоянии, функция как-то его меняет
	  и возвращает обратно в движок.
	* обработчик AJAX-запросов. Вызыывается движком при наличии специального параметра в GET-запросе.
	  В таком случае остальные модули игнорируются, обработчик запросов и генератор контента тоже пропускаются.
	* генератор админки. В режиме администрирования движок заменит весь код странички на то, что выдаст эта функция.

*************************************************************************************************************************/

class JuliaCMSModule {
	
	/**
	 * Global registry storage
	 *
	 * @name $R
	 */	
	var $R;
	
	/**
	 * Module instance definition
	 * 
	 * @name $CONFIG
	 */
	var $CONFIG;

	/**
	 * by default, global registry is connected on instance creation
	 *
	 */
	function __construct($module_name) {
		$this->R = Registry::GetInstance();
		$this->CONFIG = isset($this->R['modules'][$module_name]['config']) ? $this->R['modules'][$module_name]['config'] : array();
	}

	/**
	 * Returns configuration parameter specified
	 *
	 * @param string $param_name parameter name to return
	 * @param string $default_value value to return if no parameter found
	 * @return mixed configuration parameter
	 */
	public function getConfig($param_name, $default_value) {
		if (isset($this->CONFIG[$param_name])) {
			return $this->CONFIG[$param_name];
		} else {
			return $default_value;
		}
	}

	/**
	 * _GET and _POST handler
	 *
	 * @param string $template page HTML, modified or not with previous modules
	 * @return string $template new page HTML version
	 */
	public function requestParser($template) {

		return $template;
	}
	
	
	/**
	 * Primary module content generator. Provided with page HTML code,
	 * modifies it for own needs.
	 *
	 * @param string $template page HTML
	 * @return string new version
	 */
	public function contentGenerator($template) {
		
		return $template;		
	}
	
	/**
	 * AJAX calls handler
	 *
	 * @return string response body
	 */
	public function AJAXHandler() {
		
		return 'OK!';
	}
	
	/**
	 * Admin page content generator
	 *
	 * @return string response body
	 */
	public function adminGenerator() {
		
		return 'no special administration';
	}

}

?>