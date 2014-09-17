<?php
namespace vestibulum;

/**
 * Vestibulum: Really deathly simple CMS
 *
 * @author Roman Ožana <ozana@omdesign.cz>
 */
class Vestibulum extends \stdClass {

	use Config;
	use Request;

	/** @var File */
	public $file;
	/** @var string */
	public $content;

	public function __construct() {
		$this->requires();
		$this->file = $this->getFile((array)$this->config()->meta);
		$this->functions();
	}

	/**
	 * Requires PHP first
	 */
	public function requires() {
		// src index.php of request.php
		is_file($php = $this->src() . $this->getRequest() . '/index.php') ? include_once $php : null ||
		is_file($php = $this->src() . $this->getRequest() . '.php') ? include_once $php : null;

		// cwd index.php of request.php
		is_file($php = getcwd() . $this->getRequest() . '/index.php') ? include_once $php : null ||
		is_file($php = getcwd() . $this->getRequest() . '.php') ? include_once $php : null;
	}

	/**
	 * Auto include functions.php
	 */
	public function functions() {
		global $cms;
		$cms = $this; // create link to $this
		is_file($functions = getcwd() . '/functions.php') ? include_once $functions : null;
	}

	/**
	 * Return current file
	 *
	 * @param array $meta
	 * @return File
	 */
	public function getFile(array $meta = []) {

		$files = [
			$this->src() . $this->getRequest() => [],
			$this->src() . dirname($this->getRequest()) . '/404' => [$_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found'],
			$this->src() . '/404' => [$_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found']
		];

		foreach ($files as $path => $headers) {
			if ($file = File::fromPath($path, $meta, $headers)) return $file;
		}

		// 404 page not at all
		return new File(
			$this->src(), $meta, '<h1>404 Page not found</h1>', [$_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found']
		);
	}


	/**
	 * TODO spearate twig to class
	 * TODO caching
	 *
	 * @return string
	 */
	protected function render() {

		// Headers
		foreach ($this->file->getHeaders() as $header) header($header);

		// Content
		$this->content = str_replace('%url%', $this->url(), $this->file->getContent());

		// FIXME and find better way how to save to cache
		if ($this->file->getExtension() === 'md') {

			$cache = isset($this->config()->markdown['cache']) && $this->config()->markdown['cache'] ? realpath(
				$this->config()->markdown['cache']
			) : false;
			if ($cache && is_dir($cache) && is_writable($cache)) {
				$cacheFile = $cache . '/' . md5($this->file);
				if (!is_file($cacheFile) || @filemtime($this->file) > filemtime($cacheFile)) {
					$this->content = \Parsedown::instance()->text($this->content);
					file_put_contents($cacheFile, $this->content);
				} else {
					$this->content = file_get_contents($cacheFile);
				}
			} else {
				$this->content = \Parsedown::instance()->text($this->content);
			}
		}

		$ext = pathinfo($this->file->template, PATHINFO_EXTENSION);

		// phtml - for those who have an performance obsession :-)

		if ($ext === 'phtml' || $ext === 'php') {
			ob_start();
			extract(get_object_vars($this));
			require($this->file->template);
			$output = ob_get_contents();
			ob_end_clean();
			return $output;
		}

		// Twig

		// FIXME if twig is only in
		if ($ext === 'twig') {

			$loader = new \Twig_Loader_Filesystem($this->config->templates);
			$twig = new \Twig_Environment($loader, $this->config->twig);
			$twig->addExtension(new \Twig_Extension_Debug());
			$twig->addExtension(new \Twig_Extension_StringLoader());

			// undefined filters callback
			$twig->registerUndefinedFilterCallback(
				function ($name) {
					return function_exists($name) ?
						new \Twig_SimpleFilter(
							$name, function () use ($name) {
								return call_user_func_array($name, func_get_args());
							}, ['is_safe' => ['html']]
						) : false;
				}
			);

			$twig->addFunction('url', new \Twig_SimpleFunction('url', [$this, 'url']));

			// undefined functions callback
			$twig->registerUndefinedFunctionCallback(
				function ($name) {
					return function_exists($name) ?
						new \Twig_SimpleFunction(
							$name, function () use ($name) {
								return call_user_func_array($name, func_get_args());
							}
						) : false;
				}
			);

			// apply Twig filter to content
			if ($this->file->twig || $this->file->getExtension() === 'twig') {
				$this->content = twig_template_from_string($twig, $this->content)->render(get_object_vars($this));
			}

			return $twig->render($this->file->template, get_object_vars($this));
		}
	}

	/**
	 * Render string content
	 *
	 * @return string
	 */
	public function __toString() {
		try {
			return $this->render();
		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}
}

/**
 * @param $value
 * @param int $options
 * @param int $depth
 */
function json($value, $options = 0, $depth = 512) {
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-Type: application/json');
	die(json_encode($value, $options, $depth));
}

/**
 * Download file
 *
 * @param $file
 * @param null $filename
 */
function download($file, $filename = null) {
	if (!is_file($file)) {
		header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
		die('File not found.');
	}

	$finfo = finfo_open(FILEINFO_MIME_TYPE);
	$mime = finfo_file($finfo, $file);
	finfo_close($finfo);

	$filename = $filename ?: basename($file);
	header(sprintf('Content-Type: %s; name="%s"', $mime, $filename));
	header(sprintf('Content-Disposition: attachment; filename="%s"', $filename));
	header('ContentLength: ' . filesize($file));
	header('Connection: close');

	die(readfile($file));
}