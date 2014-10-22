// -------------------------------------
// Grunt copy
// -------------------------------------

module.exports = function (grunt) {

  return {
    // ----- Copy code files ----- //
    all: {
      files: [
	  
	  {
        cwd: '.',
        src: 'plugin.php',
        dest: 'dist/',
        expand: true
      }, {
	  
        cwd: 'css/',
        src: '*.css',
        dest: 'dist/css',
        expand: true
      }, {
        cwd: 'img/',
        src: '*.*',
        dest: 'dist/img',
        expand: true
      }, {
        cwd: 'js/',
        src: '*.js',
        dest: 'dist/js',
        expand: true
      }, {
        cwd: 'lang/',
        src: '*.*',
        dest: 'dist/lang',
        expand: true
      }, {
        cwd: 'models/',
        src: '*.php',
        dest: 'dist/models',
        expand: true
      }, {
        cwd: 'views/',
        src: '*.php',
        dest: 'dist/views',
        expand: true
      }, {
        cwd: 'widgets/',
        src: '*.php',
        dest: 'dist/widgets',
        expand: true
      }, 
	  {
        cwd: 'lib/',
        src: '**',
        dest: 'dist/lib',
        expand: true
      }
		]
    }
  }
}; 
