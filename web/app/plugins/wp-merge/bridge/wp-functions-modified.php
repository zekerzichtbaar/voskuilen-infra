<?php
/**
 * WordPress 
 */
//All functions
if (!function_exists('__')) {
	function __($name) {
		return $name;
	}
}

if (!function_exists('apply_filters')) {
	function apply_filters($this_name, $this_value, $ignorable = array()) {
		return $this_value;
	}
}

//WP common functions

if (!function_exists('trailingslashit')) {
	function trailingslashit($string) {
		return untrailingslashit($string) . '/';
	}
}

if (!function_exists('did_action')) {
	function did_action($string) {
		return 0;
	}
}

if (!function_exists('wp_die')) {
	function wp_die($string = '') {
		exit($string);
	}
}

if (!function_exists('untrailingslashit')) {
	function untrailingslashit($string) {
		return rtrim($string, '/\\');
	}
}

if (!function_exists('get_option')) {
	function get_option($option) {
		global $wpdb;

		$value = false;
		$row = $wpdb->get_row($wpdb->prepare("SELECT option_value FROM " . $wpdb->base_prefix . "options WHERE option_name = %s LIMIT 1", $option));

		if (is_object($row) && isset($row->option_value)) {
			$value = $row->option_value;
		}

		return maybe_unserialize( $value );
	}
}

if (!function_exists('current_time')) {
	function current_time($type, $gmt = 0) {
		switch ($type) {
		case 'mysql':
			return ($gmt) ? gmdate('Y-m-d H:i:s') : gmdate('Y-m-d H:i:s', (time() + (get_option('gmt_offset') * HOUR_IN_SECONDS)));
		case 'timestamp':
			return ($gmt) ? time() : time() + (get_option('gmt_offset') * HOUR_IN_SECONDS);
		default:
			return ($gmt) ? date($type) : date($type, time() + (get_option('gmt_offset') * HOUR_IN_SECONDS));
		}
	}
}

if (!function_exists('set_url_scheme')) {
	function set_url_scheme($url, $scheme = null) {
		$orig_scheme = $scheme;

		if (!$scheme) {
			$scheme = is_ssl() ? 'https' : 'http';
		} elseif ($scheme === 'admin' || $scheme === 'login' || $scheme === 'login_post' || $scheme === 'rpc') {
			$scheme = is_ssl() || force_ssl_admin() ? 'https' : 'http';
		} elseif ($scheme !== 'http' && $scheme !== 'https' && $scheme !== 'relative') {
			$scheme = is_ssl() ? 'https' : 'http';
		}

		$url = trim($url);
		if (substr($url, 0, 2) === '//') {
			$url = 'http:' . $url;
		}

		if ('relative' == $scheme) {
			$url = ltrim(preg_replace('#^\w+://[^/]*#', '', $url));
			if ($url !== '' && $url[0] === '/') {
				$url = '/' . ltrim($url, "/ \t\n\r\0\x0B");
			}

		} else {
			$url = preg_replace('#^\w+://#', $scheme . '://', $url);
		}

		/**
		 * Filter the resulting URL after setting the scheme.
		 *
		 * @since 3.4.0
		 *
		 * @param string $url         The complete URL including scheme and path.
		 * @param string $scheme      Scheme applied to the URL. One of 'http', 'https', or 'relative'.
		 * @param string $orig_scheme Scheme requested for the URL. One of 'http', 'https', 'login',
		 *                            'login_post', 'admin', 'rpc', or 'relative'.
		 */
		return $url;
	}
}

if (!function_exists('is_ssl')) {
	function is_ssl() {
		if (isset($_SERVER['HTTPS'])) {
			if ('on' == strtolower($_SERVER['HTTPS'])) {
				return true;
			}

			if ('1' == $_SERVER['HTTPS']) {
				return true;
			}

		} elseif (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
			return true;
		}
		return false;
	}
}

if (!function_exists('force_ssl_admin')) {
	function force_ssl_admin($force = null) {
		static $forced = false;

		if (!is_null($force)) {
			$old_forced = $forced;
			$forced = $force;
			return $old_forced;
		}

		return $forced;
	}
}

if (!function_exists('get_temp_dir')) {
	function get_temp_dir() {
		static $temp;
		if (defined('WP_TEMP_DIR')) {
			return trailingslashit(WP_TEMP_DIR);
		}

		if ($temp) {
			return trailingslashit($temp);
		}

		if (function_exists('sys_get_temp_dir')) {
			$temp = sys_get_temp_dir();
			if (@is_dir($temp) && wp_is_writable($temp)) {
				return trailingslashit($temp);
			}

		}

		$temp = ini_get('upload_tmp_dir');
		if (@is_dir($temp) && wp_is_writable($temp)) {
			return trailingslashit($temp);
		}

		$temp = WP_CONTENT_DIR . '/';
		if (is_dir($temp) && wp_is_writable($temp)) {
			return $temp;
		}

		$temp = '/tmp/';
		return $temp;
	}
}

if (!function_exists('wp_is_writable')) {
	function wp_is_writable($path) {
		if ('WIN' === strtoupper(substr(PHP_OS, 0, 3))) {
			return win_is_writable($path);
		} else {
			return @is_writable($path);
		}

	}
}

if (!function_exists('win_is_writable')) {
	function win_is_writable($path) {

		if ($path[strlen($path) - 1] == '/') // if it looks like a directory, check a random file within the directory
		{
			return win_is_writable($path . uniqid(mt_rand()) . '.tmp');
		} else if (is_dir($path)) // If it's a directory (and not a file) check a random file within the directory
		{
			return win_is_writable($path . '/' . uniqid(mt_rand()) . '.tmp');
		}

		// check tmp file for read/write capabilities
		$should_delete_tmp_file = !file_exists($path);
		$f = @fopen($path, 'a');
		if ($f === false) {
			return false;
		}

		fclose($f);
		if ($should_delete_tmp_file) {
			@unlink($path);
		}

		return true;
	}
}

if (!function_exists('mbstring_binary_safe_encoding')) {
	function mbstring_binary_safe_encoding($reset = false) {
		static $encodings = array();
		static $overloaded = null;

		if (is_null($overloaded)) {
			$overloaded = function_exists('mb_internal_encoding') && (ini_get('mbstring.func_overload') & 2);
		}

		if (false === $overloaded) {
			return;
		}

		if (!$reset) {
			$encoding = mb_internal_encoding();
			array_push($encodings, $encoding);
			mb_internal_encoding('ISO-8859-1');
		}

		if ($reset && $encodings) {
			$encoding = array_pop($encodings);
			mb_internal_encoding($encoding);
		}
	}
}

if (!function_exists('reset_mbstring_encoding')) {
	function reset_mbstring_encoding() {
		mbstring_binary_safe_encoding(true);
	}
}

if (!function_exists('wp_installing')) {
	function wp_installing($is_installing = null) {
		return false;
	}
}

if (!function_exists('update_option')) {
	function update_option($option, $value) {
		global $wpdb;

		$option = trim($option);
		if (empty($option)) {
			return false;
		}

		if (is_object($value)) {
			$value = clone $value;
		}

		$old_value = get_option($option);

		if ($value === $old_value) {
			return false;
		}

		$serialized_value = maybe_serialize($value);

		$result = $wpdb->update( $wpdb->base_prefix . "options", array('option_value' => $serialized_value), array('option_name' => $option));
		if (!$result) {
			return false;
		}

		return true;
	}
}

if (!function_exists('is_serialized')) {
	function is_serialized($data, $strict = true) {
		// if it isn't a string, it isn't serialized.
		if (!is_string($data)) {
			return false;
		}
		$data = trim($data);
		if ('N;' == $data) {
			return true;
		}
		if (strlen($data) < 4) {
			return false;
		}
		if (':' !== $data[1]) {
			return false;
		}
		if ($strict) {
			$lastc = substr($data, -1);
			if (';' !== $lastc && '}' !== $lastc) {
				return false;
			}
		} else {
			$semicolon = strpos($data, ';');
			$brace = strpos($data, '}');
			// Either ; or } must exist.
			if (false === $semicolon && false === $brace) {
				return false;
			}

			// But neither must be in the first X characters.
			if (false !== $semicolon && $semicolon < 3) {
				return false;
			}

			if (false !== $brace && $brace < 4) {
				return false;
			}

		}
		$token = $data[0];
		switch ($token) {
		case 's':
			if ($strict) {
				if ('"' !== substr($data, -2, 1)) {
					return false;
				}
			} elseif (false === strpos($data, '"')) {
				return false;
			}
		// or else fall through
		case 'a':
		case 'O':
			return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
		case 'b':
		case 'i':
		case 'd':
			$end = $strict ? '$' : '';
			return (bool) preg_match("/^{$token}:[0-9.E-]+;$end/", $data);
		}
		return false;
	}
}

if (!function_exists('is_serialized_string')) {
	function is_serialized_string($data) {
		// if it isn't a string, it isn't a serialized string.
		if (!is_string($data)) {
			return false;
		}
		$data = trim($data);
		if (strlen($data) < 4) {
			return false;
		} elseif (':' !== $data[1]) {
			return false;
		} elseif (';' !== substr($data, -1)) {
			return false;
		} elseif ($data[0] !== 's') {
			return false;
		} elseif ('"' !== substr($data, -2, 1)) {
			return false;
		} else {
			return true;
		}
	}
}

if (!function_exists('maybe_serialize')) {
	function maybe_serialize($data) {
		if (is_array($data) || is_object($data)) {
			return serialize($data);
		}

		// Double serialization is required for backward compatibility.
		// See https://core.trac.wordpress.org/ticket/12930
		if (is_serialized($data, false)) {
			return serialize($data);
		}

		return $data;
	}
}

if (!function_exists('maybe_unserialize')) {
	function maybe_unserialize( $original ) {
		if ( is_serialized( $original ) ) // don't attempt to unserialize data that wasn't serialized going in
			return @unserialize( $original );
		return $original;
	}
}

if (!function_exists('wp_generate_password')) {
	function wp_generate_password($length = 12, $special_chars = true, $extra_special_chars = false) {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		if ($special_chars) {
			$chars .= '!@#$%^&*()';
		}

		if ($extra_special_chars) {
			$chars .= '-_ []{}<>~`+=,.;:/?|';
		}

		$password = '';
		for ($i = 0; $i < $length; $i++) {
			$password .= substr($chars, wp_rand(0, strlen($chars) - 1), 1);
		}

		/**
		 * Filter the randomly-generated password.
		 *
		 * @since 3.0.0
		 *
		 * @param string $password The generated password.
		 */
		return apply_filters('random_password', $password);
	}
}

if (!function_exists('wp_rand')) {
	function wp_rand($min = 0, $max = 0) {
		global $rnd_value;

		// Reset $rnd_value after 14 uses
		// 32(md5) + 40(sha1) + 40(sha1) / 8 = 14 random numbers from $rnd_value
		if (strlen($rnd_value) < 8) {
			static $seed = '';

			$rnd_value = md5(uniqid(microtime() . mt_rand(), true) . $seed);
			$rnd_value .= sha1($rnd_value);
			$rnd_value .= sha1($rnd_value . $seed);
			$seed = md5($seed . $rnd_value);

		}

		// Take the first 8 digits for our value
		$value = substr($rnd_value, 0, 8);

		// Strip the first eight, leaving the remainder for the next call to wp_rand().
		$rnd_value = substr($rnd_value, 8);

		$value = abs(hexdec($value));

		// Some misconfigured 32bit environments (Entropy PHP, for example) truncate integers larger than PHP_INT_MAX to PHP_INT_MAX rather than overflowing them to floats.
		$max_random_number = 3000000000 === 2147483647 ? (float) "4294967295" : 4294967295; // 4294967295 = 0xffffffff

		// Reduce the value to be within the min - max range
		if ($max != 0) {
			$value = $min + ($max - $min + 1) * $value / ($max_random_number + 1);
		}

		return abs(intval($value));
	}
}

if (!function_exists('wp_unique_filename')) {
	function wp_unique_filename($dir, $filename, $unique_filename_callback = null) {
		// Sanitize the file name before we begin processing.
		$filename = sanitize_file_name($filename);

		// Separate the filename into a name and extension.
		$info = pathinfo($filename);
		$ext = !empty($info['extension']) ? '.' . $info['extension'] : '';
		$name = basename($filename, $ext);

		// Edge case: if file is named '.ext', treat as an empty name.
		if ($name === $ext) {
			$name = '';
		}

		/*
			 * Increment the file number until we have a unique file to save in $dir.
			 * Use callback if supplied.
		*/
		if ($unique_filename_callback && is_callable($unique_filename_callback)) {
			$filename = call_user_func($unique_filename_callback, $dir, $name, $ext);
		} else {
			$number = '';

			// Change '.ext' to lower case.
			if ($ext && strtolower($ext) != $ext) {
				$ext2 = strtolower($ext);
				$filename2 = preg_replace('|' . preg_quote($ext) . '$|', $ext2, $filename);

				// Check for both lower and upper case extension or image sub-sizes may be overwritten.
				while (file_exists($dir . "/$filename") || file_exists($dir . "/$filename2")) {
					$new_number = $number + 1;
					$filename = str_replace("$number$ext", "$new_number$ext", $filename);
					$filename2 = str_replace("$number$ext2", "$new_number$ext2", $filename2);
					$number = $new_number;
				}
				return $filename2;
			}

			while (file_exists($dir . "/$filename")) {
				if ('' == "$number$ext") {
					$filename = $filename . ++$number . $ext;
				} else {
					$filename = str_replace("$number$ext", ++$number . $ext, $filename);
				}

			}
		}

		return $filename;
	}
}

if (!function_exists('sanitize_file_name')) {
	function sanitize_file_name($filename) {
		$filename_raw = $filename;
		$special_chars = array("?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}", chr(0));
		/**
		 * Filter the list of characters to remove from a filename.
		 *
		 * @since 2.8.0
		 *
		 * @param array  $special_chars Characters to remove.
		 * @param string $filename_raw  Filename as it was passed into sanitize_file_name().
		 */
		$special_chars = apply_filters('sanitize_file_name_chars', $special_chars, $filename_raw);
		$filename = preg_replace("#\x{00a0}#siu", ' ', $filename);
		$filename = str_replace($special_chars, '', $filename);
		$filename = str_replace(array('%20', '+'), '-', $filename);
		$filename = preg_replace('/[\r\n\t -]+/', '-', $filename);
		$filename = trim($filename, '.-_');

		// Split the filename into a base and extension[s]
		$parts = explode('.', $filename);

		// Return if only one extension
		if (count($parts) <= 2) {
			/**
			 * Filter a sanitized filename string.
			 *
			 * @since 2.8.0
			 *
			 * @param string $filename     Sanitized filename.
			 * @param string $filename_raw The filename prior to sanitization.
			 */
			return apply_filters('sanitize_file_name', $filename, $filename_raw);
		}

		// Process multiple extensions
		$filename = array_shift($parts);
		$extension = array_pop($parts);
		$mimes = get_allowed_mime_types();

		/*
			 * Loop over any intermediate extensions. Postfix them with a trailing underscore
			 * if they are a 2 - 5 character long alpha string not in the extension whitelist.
		*/
		foreach ((array) $parts as $part) {
			$filename .= '.' . $part;

			if (preg_match("/^[a-zA-Z]{2,5}\d?$/", $part)) {
				$allowed = false;
				foreach ($mimes as $ext_preg => $mime_match) {
					$ext_preg = '!^(' . $ext_preg . ')$!i';
					if (preg_match($ext_preg, $part)) {
						$allowed = true;
						break;
					}
				}
				if (!$allowed) {
					$filename .= '_';
				}

			}
		}
		$filename .= '.' . $extension;
		/** This filter is documented in wp-includes/formatting.php */
		return apply_filters('sanitize_file_name', $filename, $filename_raw);
	}
}

if (!function_exists('get_allowed_mime_types')) {
	function get_allowed_mime_types($user = null) {
		$t = wp_get_mime_types();

		unset($t['swf'], $t['exe']);

		return apply_filters('upload_mimes', $t, $user);
	}
}

if (!function_exists('wp_get_mime_types')) {
	function wp_get_mime_types() {
		return apply_filters('mime_types', array(
			// Image formats.
			'jpg|jpeg|jpe' => 'image/jpeg',
			'gif' => 'image/gif',
			'png' => 'image/png',
			'bmp' => 'image/bmp',
			'tif|tiff' => 'image/tiff',
			'ico' => 'image/x-icon',
			// Video formats.
			'asf|asx' => 'video/x-ms-asf',
			'wmv' => 'video/x-ms-wmv',
			'wmx' => 'video/x-ms-wmx',
			'wm' => 'video/x-ms-wm',
			'avi' => 'video/avi',
			'divx' => 'video/divx',
			'flv' => 'video/x-flv',
			'mov|qt' => 'video/quicktime',
			'mpeg|mpg|mpe' => 'video/mpeg',
			'mp4|m4v' => 'video/mp4',
			'ogv' => 'video/ogg',
			'webm' => 'video/webm',
			'mkv' => 'video/x-matroska',
			'3gp|3gpp' => 'video/3gpp', // Can also be audio
			'3g2|3gp2' => 'video/3gpp2', // Can also be audio
			// Text formats.
			'txt|asc|c|cc|h|srt' => 'text/plain',
			'csv' => 'text/csv',
			'tsv' => 'text/tab-separated-values',
			'ics' => 'text/calendar',
			'rtx' => 'text/richtext',
			'css' => 'text/css',
			'htm|html' => 'text/html',
			'vtt' => 'text/vtt',
			'dfxp' => 'application/ttaf+xml',
			// Audio formats.
			'mp3|m4a|m4b' => 'audio/mpeg',
			'ra|ram' => 'audio/x-realaudio',
			'wav' => 'audio/wav',
			'ogg|oga' => 'audio/ogg',
			'mid|midi' => 'audio/midi',
			'wma' => 'audio/x-ms-wma',
			'wax' => 'audio/x-ms-wax',
			'mka' => 'audio/x-matroska',
			// Misc application formats.
			'rtf' => 'application/rtf',
			'js' => 'application/javascript',
			'pdf' => 'application/pdf',
			'swf' => 'application/x-shockwave-flash',
			'class' => 'application/java',
			'tar' => 'application/x-tar',
			'zip' => 'application/zip',
			'gz|gzip' => 'application/x-gzip',
			'rar' => 'application/rar',
			'7z' => 'application/x-7z-compressed',
			'exe' => 'application/x-msdownload',
			'psd' => 'application/octet-stream',
			// MS Office formats.
			'doc' => 'application/msword',
			'pot|pps|ppt' => 'application/vnd.ms-powerpoint',
			'wri' => 'application/vnd.ms-write',
			'xla|xls|xlt|xlw' => 'application/vnd.ms-excel',
			'mdb' => 'application/vnd.ms-access',
			'mpp' => 'application/vnd.ms-project',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'docm' => 'application/vnd.ms-word.document.macroEnabled.12',
			'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
			'dotm' => 'application/vnd.ms-word.template.macroEnabled.12',
			'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
			'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
			'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
			'xltm' => 'application/vnd.ms-excel.template.macroEnabled.12',
			'xlam' => 'application/vnd.ms-excel.addin.macroEnabled.12',
			'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'pptm' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
			'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
			'ppsm' => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
			'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
			'potm' => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
			'ppam' => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
			'sldx' => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
			'sldm' => 'application/vnd.ms-powerpoint.slide.macroEnabled.12',
			'onetoc|onetoc2|onetmp|onepkg' => 'application/onenote',
			'oxps' => 'application/oxps',
			'xps' => 'application/vnd.ms-xpsdocument',
			// OpenOffice formats.
			'odt' => 'application/vnd.oasis.opendocument.text',
			'odp' => 'application/vnd.oasis.opendocument.presentation',
			'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
			'odg' => 'application/vnd.oasis.opendocument.graphics',
			'odc' => 'application/vnd.oasis.opendocument.chart',
			'odb' => 'application/vnd.oasis.opendocument.database',
			'odf' => 'application/vnd.oasis.opendocument.formula',
			// WordPerfect formats.
			'wp|wpd' => 'application/wordperfect',
			// iWork formats.
			'key' => 'application/vnd.apple.keynote',
			'numbers' => 'application/vnd.apple.numbers',
			'pages' => 'application/vnd.apple.pages',
		));
	}
}

if (!function_exists('add_action')) {
	function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
		return true;
	}
}

if (!function_exists('add_filter')) {
	function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		return true;
	}
}

if (!function_exists('current_user_can')) {
	function current_user_can( $capability ) {
		return true;
	}
}

if (!function_exists('is_admin')) {
	function is_admin() {
		return true;
	}
}

if (!function_exists('do_action')) {
	function do_action($tag, $arg = '') {
		return true;
	}
}

if (!function_exists('do_action')) {
	function do_action($tag, $arg = '') {
		return true;
	}
}

if (!function_exists('apply_filters')) {
	function apply_filters( $tag, $value ) {
		return true;
	}
}

if (!function_exists('wp_normalize_path')) {
	function wp_normalize_path( $path ) {
		$path = str_replace( '\\', '/', $path );
		$path = preg_replace( '|(?<=.)/+|', '/', $path );
		if ( ':' === substr( $path, 1, 1 ) ) {
			$path = ucfirst( $path );
		}
		return $path;
	}
}

if (!function_exists('get_locale')) {
	function get_locale() {
		return 'en_US';
	}
}

if (!function_exists('is_multisite')) {

	function is_multisite() {
		if ( defined( 'MULTISITE' ) )
			return MULTISITE;

		if ( defined( 'SUBDOMAIN_INSTALL' ) || defined( 'VHOST' ) || defined( 'SUNRISE' ) )
			return true;

		return false;
	}
}

if (!function_exists('is_subdomain_install')) {
	/**
	 * Whether a subdomain configuration is enabled.
	 *
	 * @since 3.0.0
	 *
	 * @return bool True if subdomain configuration is enabled, false otherwise.
	 */
	function is_subdomain_install() {
		if ( defined('SUBDOMAIN_INSTALL') )
			return SUBDOMAIN_INSTALL;

		return ( defined( 'VHOST' ) && VHOST == 'yes' );
	}
}

if (!function_exists('stripslashes_deep')) {
	function stripslashes_deep( $value ) {
		return map_deep( $value, 'stripslashes_from_strings_only' );
	}
}

if (!function_exists('stripslashes_from_strings_only')) {
	function stripslashes_from_strings_only( $value ) {
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}

if (!function_exists('map_deep')) {
	function map_deep( $value, $callback ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $index => $item ) {
				$value[ $index ] = map_deep( $item, $callback );
			}
		} elseif ( is_object( $value ) ) {
			$object_vars = get_object_vars( $value );
			foreach ( $object_vars as $property_name => $property_value ) {
				$value->$property_name = map_deep( $property_value, $callback );
			}
		} else {
			$value = call_user_func( $callback, $value );
		}

		return $value;
	}
}

if (!function_exists('get_site_url')) {
	function get_site_url() {
		return get_option( 'siteurl' );
	}
}

if ( !function_exists('hash_hmac') ){
	function hash_hmac($algo, $data, $key, $raw_output = false) {
		return _hash_hmac($algo, $data, $key, $raw_output);
	}
}
if ( !function_exists('hash_hmac') ){
	function _hash_hmac($algo, $data, $key, $raw_output = false) {
		$packs = array('md5' => 'H32', 'sha1' => 'H40');

		if ( !isset($packs[$algo]) )
			return false;

		$pack = $packs[$algo];

		if (strlen($key) > 64)
			$key = pack($pack, $algo($key));

		$key = str_pad($key, 64, chr(0));

		$ipad = (substr($key, 0, 64) ^ str_repeat(chr(0x36), 64));
		$opad = (substr($key, 0, 64) ^ str_repeat(chr(0x5C), 64));

		$hmac = $algo($opad . pack($pack, $algo($ipad . $data)));

		if ( $raw_output )
			return pack( $pack, $hmac );
		return $hmac;
	}
}

if ( !function_exists('is_wp_error') ){
	function is_wp_error( $thing ) {
		return ( $thing instanceof WP_Error );
	}
}

if( !class_exists('WP_Error')){
//wp-includes/class-wp-error.php taken from v4.9.8 start here

/**
 * WordPress Error API.
 *
 * Contains the WP_Error class and the is_wp_error() function.
 *
 * @package WordPress
 */

/**
 * WordPress Error class.
 *
 * Container for checking for WordPress errors and error messages. Return
 * WP_Error and use is_wp_error() to check if this class is returned. Many
 * core WordPress functions pass this class in the event of an error and
 * if not handled properly will result in code errors.
 *
 * @since 2.1.0
 */
class WP_Error {
	/**
	 * Stores the list of errors.
	 *
	 * @since 2.1.0
	 * @var array
	 */
	public $errors = array();

	/**
	 * Stores the list of data for error codes.
	 *
	 * @since 2.1.0
	 * @var array
	 */
	public $error_data = array();

	/**
	 * Initialize the error.
	 *
	 * If `$code` is empty, the other parameters will be ignored.
	 * When `$code` is not empty, `$message` will be used even if
	 * it is empty. The `$data` parameter will be used only if it
	 * is not empty.
	 *
	 * Though the class is constructed with a single error code and
	 * message, multiple codes can be added using the `add()` method.
	 *
	 * @since 2.1.0
	 *
	 * @param string|int $code Error code
	 * @param string $message Error message
	 * @param mixed $data Optional. Error data.
	 */
	public function __construct( $code = '', $message = '', $data = '' ) {
		if ( empty($code) )
			return;

		$this->errors[$code][] = $message;

		if ( ! empty($data) )
			$this->error_data[$code] = $data;
	}

	/**
	 * Retrieve all error codes.
	 *
	 * @since 2.1.0
	 *
	 * @return array List of error codes, if available.
	 */
	public function get_error_codes() {
		if ( empty($this->errors) )
			return array();

		return array_keys($this->errors);
	}

	/**
	 * Retrieve first error code available.
	 *
	 * @since 2.1.0
	 *
	 * @return string|int Empty string, if no error codes.
	 */
	public function get_error_code() {
		$codes = $this->get_error_codes();

		if ( empty($codes) )
			return '';

		return $codes[0];
	}

	/**
	 * Retrieve all error messages or error messages matching code.
	 *
	 * @since 2.1.0
	 *
	 * @param string|int $code Optional. Retrieve messages matching code, if exists.
	 * @return array Error strings on success, or empty array on failure (if using code parameter).
	 */
	public function get_error_messages($code = '') {
		// Return all messages if no code specified.
		if ( empty($code) ) {
			$all_messages = array();
			foreach ( (array) $this->errors as $code => $messages )
				$all_messages = array_merge($all_messages, $messages);

			return $all_messages;
		}

		if ( isset($this->errors[$code]) )
			return $this->errors[$code];
		else
			return array();
	}

	/**
	 * Get single error message.
	 *
	 * This will get the first message available for the code. If no code is
	 * given then the first code available will be used.
	 *
	 * @since 2.1.0
	 *
	 * @param string|int $code Optional. Error code to retrieve message.
	 * @return string
	 */
	public function get_error_message($code = '') {
		if ( empty($code) )
			$code = $this->get_error_code();
		$messages = $this->get_error_messages($code);
		if ( empty($messages) )
			return '';
		return $messages[0];
	}

	/**
	 * Retrieve error data for error code.
	 *
	 * @since 2.1.0
	 *
	 * @param string|int $code Optional. Error code.
	 * @return mixed Error data, if it exists.
	 */
	public function get_error_data($code = '') {
		if ( empty($code) )
			$code = $this->get_error_code();

		if ( isset($this->error_data[$code]) )
			return $this->error_data[$code];
	}

	/**
	 * Add an error or append additional message to an existing error.
	 *
	 * @since 2.1.0
	 *
	 * @param string|int $code Error code.
	 * @param string $message Error message.
	 * @param mixed $data Optional. Error data.
	 */
	public function add($code, $message, $data = '') {
		$this->errors[$code][] = $message;
		if ( ! empty($data) )
			$this->error_data[$code] = $data;
	}

	/**
	 * Add data for error code.
	 *
	 * The error code can only contain one error data.
	 *
	 * @since 2.1.0
	 *
	 * @param mixed $data Error data.
	 * @param string|int $code Error code.
	 */
	public function add_data($data, $code = '') {
		if ( empty($code) )
			$code = $this->get_error_code();

		$this->error_data[$code] = $data;
	}

	/**
	 * Removes the specified error.
	 *
	 * This function removes all error messages associated with the specified
	 * error code, along with any error data for that code.
	 *
	 * @since 4.1.0
	 *
	 * @param string|int $code Error code.
	 */
	public function remove( $code ) {
		unset( $this->errors[ $code ] );
		unset( $this->error_data[ $code ] );
	}
}

//wp-includes/class-wp-error.php taken from v4.9.8 ends here
}