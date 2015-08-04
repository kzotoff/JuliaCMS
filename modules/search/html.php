<?php

// один результат поиска
define('MODULE_SEARCH_RESULT_DEFAULT', <<<'HTML'
<li class="search_result">
	<a href="%alias%">
	<span class="search_header">%title%</span><br />
	<span class="search_highlight">%highlight%</span>
	</a>
</li>
HTML
);

// общая обертка
define('MODULE_SEARCH_ENTIRE_CONTENT', <<<'HTML'
<p class="search_info">Поиск по запросу: <span class="search_pattern">%search_pattern%</span>. Всего результатов: %result_count%</p>
<ol>%html%</ol>

HTML
);

?>