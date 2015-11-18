<?php

/**
 * @author Roman Ozana <ozana@omdesign.cz>
 */

namespace cms {

	/** @var \cms\Sphido $cms */

	// Follow files are used in examples
	require_once __DIR__ . '/../vendor/sphido/json/src/json.php';
	require_once __DIR__ . '/../vendor/sphido/http/src/http.php';
	require_once __DIR__ . '/../vendor/sphido/download/src/download.php';

	// it's return menu items from all pages in content folder
	function menu() {
		return Pages::from(\dir\content(), ['404', \dir\content('example')])->toArraySorted();
	}

	// Custom default error handler... if 404.md missing in root
	on(
		'render.default.error',
		function () {
			echo 'Page not found...';
		}
	);
}


// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

namespace {
	/** @var \cms\Sphido $cms */

	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	return; // follow examples are disabled by intention
	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

	$cms->speed = 'This is the core'; // will be avaliable in Latte as {$speed} variable

	/**
	 * Write {yolo()} for calling function from markdown or Latte template
	 */
	function yolo() {
		require_once __DIR__ . '/../vendor/sphido/http/src/http.php';
		\http\redirect('http://www.omdesign.cz');
	}

	/**
	 * Add cutom URL filter filters
	 */
	add_filter(
		'url',
		function (Url $url) {
			return $url->host('www.sphido.org'); // change host to sphido.org
		}
	);

	/**
	 * Add custom Latte makro {myMacro}
	 *
	 * @see http://doc.nette.org/en/2.3/default-macros
	 */
	add_filter(
		'latte.macroset', function (Latte\Macros\MacroSet $set) {
		$set->addMacro('myMacro', 'echo "This is my custom macro";');
	}
	);

	/**
	 * Change configuration from this place
	 */
	config()->title = 'Sphido';
	config()->myvariable = 'Speed is the core';
	config()->example = 'example';

}