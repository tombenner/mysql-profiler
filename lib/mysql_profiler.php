<?php

class MysqlProfiler {

	private $log = array();
	private $display_files = MP_DISPLAY_FILES;
	private $highlight_syntax = MP_HIGHLIGHT_SYNTAX;
	
	function __construct() {
		add_action('init', array($this, 'init'));
	}
	
	public function init() {
		if ($this->display_profile()) {
			if ($this->highlight_syntax) {
				require_once dirname(__FILE__).'/geshi/geshi.php';
			}
			wp_enqueue_script('datatables', plugins_url('datatables/js/jquery.dataTables.min.js', dirname(__FILE__)), array('jquery'));
			wp_enqueue_style('datatables', plugins_url('datatables/css/jquery.dataTables.css', dirname(__FILE__)));
			wp_enqueue_style('mysql-profiler', plugins_url('css/style.css', dirname(__FILE__)));
			add_filter('query', array($this, 'on_query'));
			add_action('wp_footer', array($this, 'wp_footer'), 1000);
		}
	}

	public function on_query($query) {
		$query = trim($query);
		$entry = array(
			'query' => $query,
			'trace' => $this->get_trace()
		);
		$this->log[] = $entry;
		return $query;
	}

	public function wp_footer() {
		$html = '';
		$html .= '<div class="mysql-profiler">';
		$html .= '<h3>MySQL Profiler</h3>';
		$html .= $this->get_queries();
		$html .= '</div>';
		echo $html;
	}
	
	public function activate() {
		global $wp_roles;
		$wp_roles->add_cap('administrator', 'profile_queries');
	}
	
	public function deactivate() {
		global $wp_roles;
		$wp_roles->remove_cap('administrator', 'profile_queries');
	}
	
	private function display_profile() {
		return current_user_can('profile_queries');
	}
	
	private function get_trace() {
		$trace = array_reverse(debug_backtrace());
		$calls = array();
		// Check why these are necessary in addition to the if conditional...
		array_pop($trace);
		array_pop($trace);
		$trace_count = count($trace);
		foreach ($trace as $index => $call) {
			// Ignore the last two calls, which are related to this class
			if ($index > $trace_count - 3) {
				continue;
			}
			// Filter out wpdb calls and calls to this class
			if (isset($call['class']) && in_array($call['class'], array('wpdb', __CLASS__))) {
				continue;
			}
			$function = isset($call['class']) ? "{$call['class']}->{$call['function']}" : $call['function'];
			$file = isset($call['file']) ? $call['file'] : null;
			if ($file) {
				$file = str_replace(ABSPATH, '', $file);
			}
			$calls[] = array(
				'function' => $function,
				'file' => $file,
				'line' => isset($call['line']) ? $call['line'] : null
			);
		}
		return $calls;		
	}
	
	private function get_trace_html($trace) {
		if (!isset($trace['trace'])) {
			return null;
		}
		$html = '';
		if ($this->display_files) {
			foreach ($trace['trace'] as $call) {
				$reference = null;
				if ($call['file'] && $call['line']) {
					$reference = $call['file'].' ('.$call['line'].')';
				} else if ($call['file']) {
					$reference = $call['file'];
				} else {
					$reference = '(Unavailable)';
				}
				if ($reference) {
					$reference = '<span class="line-reference">'.$reference.'</span>';
				}
				$html .= '<div><span class="function-name">'.$call['function'].'</span>'.$reference.'</div>';
			}
		} else {
			$calls = array();
			foreach ($trace['trace'] as $call) {
				$reference = null;
				if ($call['file'] && $call['line']) {
					$reference = $call['file'].' ('.$call['line'].')';
				} else if ($call['file']) {
					$reference = $call['file'];
				}
				if ($reference) {
					$reference = ' title="'.$reference.'"';
				}
				$calls[] = '<span class="function-name"'.$reference.'>'.$call['function'].'</span>';
			}
			$html = implode(', ', $calls);
		}
		return $html;
	}
	
	private function get_trace_by_query($query) {
		foreach ($this->log as $key => $call) {
			if ($call['query'] == $query) {
				unset($this->log[$key]);
				return $call;
			}
		}
		return null;
	}
	
	private function get_queries() {
		global $wpdb;
		
		if (QUERY_CACHE_TYPE_OFF) {
			$wpdb->query('SET SESSION query_cache_type = 0;');
		}
		
		$html = '';
		if ($wpdb->queries) {
			$total_time = timer_stop(FALSE, 22);
			$total_query_time = 0;
			$class = ''; 
			$html .= '<table class="mp-queries display" cellpadding="0" cellspacing="0" border="0" width="100%">';
			$html .= '<thead><tr>';
			$html .= '<th>'.__('ID').'</th>';
			$html .= '<th>'.__('Time').'</th>';
			$html .= '<th>'.__('Query').'</th>';
			$html .= '<th>'.__('Called From').'</th>';
			$html .= '</tr></thead><tbody>';
			
			if ($this->highlight_syntax) {
				$geshi = new GeSHi('', 'mysql');
				$geshi->set_header_type(GESHI_HEADER_NONE);
				$geshi->set_keyword_group_style(1, 'color: #000;', true);
				$geshi->set_keyword_group_style(5, 'color: #000;', true);
				$geshi->set_strings_style('color: #009;', false, 0);
			}
			
			$i = 0;
			foreach ($wpdb->queries as $query_entry) {
				$query = $query_entry[0];
				$time = $query_entry[1];
				$trace = $query_entry[2];
				$total_query_time += $time;
				$full_trace = $this->get_trace_by_query($query);
				$displayed_query = preg_replace('/[\s]+/', ' ', $query);
				if ($this->highlight_syntax) {
					$geshi->set_source($displayed_query);
					$displayed_query = $geshi->parse_code();
				}
				if ($full_trace) {
					$trace_html = $this->get_trace_html($full_trace);
				} else {
					if ($this->display_files) {
						$trace = explode(', ', $trace);
						$trace = implode('<br />', $trace);
					}
					$trace_html = $trace;
				}
				$html .= '<tr'.$class.'>';
				$html .= '<td class="mp-column-id">'.($i+1).'</td>';
				$html .= '<td class="mp-column-time">'.number_format($time, 8).'</td>';
				$html .= '<td class="mp-column-query"><div class="mp-column-query-content">'.$displayed_query.'</div></td>';
				$html .= '<td class="mp-column-trace"><div class="mp-column-trace-content">'.$trace_html.'</div></td>';
				$html .= '</tr>';
				$i++;
			}
			$html .= '</tbody></table>';
			$html .= '
				<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery(".mp-queries").dataTable({
							"bPaginate": false,
							"bLengthChange": false
						});
						jQuery(".mp-queries .mp-column-trace-content").each(function() {
							jQuery(this).scrollTop(jQuery(this)[0].scrollHeight);
						});
					});
				</script>
			';
		}
		
		$php_time = $total_time - $total_query_time;
		$mysql_percentage = number_format_i18n($total_query_time / $total_time * 100, 2);
		$php_percentage   = number_format_i18n($php_time / $total_time * 100, 2);
		
		$html .= '<ul class="mp-general-stats">';
		$html .= '<li><strong>'.__('Total query time:').' '.number_format_i18n($total_query_time, 5).__('s for').' '.count($wpdb->queries).' '.__('queries.').'</strong></li>';
		if (count($wpdb->queries) != get_num_queries()) {
			$html .= '<li><strong>'.__('Total num_query time:').' '.timer_stop().' '.__('for').' '.get_num_queries().' '.__('num_queries.').'</strong></li>';
			$html .= '<li class="none_list">'.__('&raquo; Different values in num_query and query? Please set the constant').' <code>define(\'SAVEQUERIES\', true);</code> '.__('in').' <code>wp-config.php</code>.</li>';
		}
		if ($total_query_time == 0)
			$html .= '<li class="none_list">'.__('&raquo; Query time is null (0)? Please set the constant').' <code>SAVEQUERIES</code> '.' '.__('at').' <code>true</code> '.__('in').' <code>wp-config.php</code>.</li>';
		$html .= '<li>'.__('Page generated in'). ' '.number_format_i18n($total_time, 5).__('s (').$php_percentage.__('% PHP').', '.$mysql_percentage.__('% MySQL').')</li>';
		$html .= '</ul>';
		
		$html .= '<p class="mp-final-message">Be sure to deactivate MySQL Profiler when you\'ve finished debugging!</p>';
		
		return $html;
	}
	
}

?>
