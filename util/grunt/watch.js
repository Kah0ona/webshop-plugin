// -------------------------------------
// Grunt watch
// -------------------------------------

module.exports = {

  // ----- Watch tasks ----- //
  all: {
    files: [
		'plugin.php',
		'css/*',
		'img/*',
		'js/*',
		'lang/*',
		'lib/*',
		'models/*',
		'tests/*',
		'views/*',
		'widgets/*'],
    tasks: [
      'copy:all',
    ]
  },
};
