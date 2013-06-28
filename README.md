dmJsCompilerPlugin for Diem Extended
===============================

Author: [TheCelavi](http://www.runopencode.com/about/thecelavi)
Version: 0.5
Stability: Stable  
Date: June 24th, 2013
Courtesy of [Run Open Code](http://www.runopencode.com)   
License: [Free for all](http://www.runopencode.com/terms-and-conditions/free-for-all)

dmJsCompilerPlugin for Diem Extended is JavaScript compiler for Diem Extended projects. For now, it can be used
via task from console, while integration with response object is work in progress.

The following commands are available:

- `php symfony js:compile` or `php symfony jsc` - Compiles the JavaScript files for project. If, per example,
there is file called `example.js` it will be compiled and stored at same directory under the name `example.min.js`
(depending on service settings, see `services.yml`).
- `php symfony js:delete-compiled` or `php symfony js:delc` - Deletes compiled JavaScript files from project. It does that
according the file names (if in directory exists `example.min.js` or `example.compiled.js` - see `config.yml` -
and `example.js` - the conclusion is that JavaScript file is compiled JavaScript file, so it will be removed).
- `php symfony js:delete-source` - Deletes JavaScript source files from the project. Ought to be used ONLY in
production server. Backup your project before using this command.

There are various settings for each task. They are explained here:

Settings for tasks:
---------------------

###`js:compile`:

- `plugin`: You can set which plugin will be searched for JavaScript files. Default is null, so whole project is searched for JavaScript files.
Several predefined constant exists:
    - web: it will search `web/js` dir for JS files
    - core: searches in `diem-extended/dmCorePlugin/web`
    - admin: searches in `diem-extended/dmAdminPlugin/web`
    - front: searches in `diem-extended/dmFrontPlugin/web`
    - diem: searches in `diem-extended/dmAdminPlugin/web`, `diem-extended/dmCorePlugin/web`, `diem-extended/dmFrontPlugin/web`
    - plugins: searches in `plugins` dir
    - anyNameOfPlugin: searches in `project/plugins/anyNameOfPlugin`
    - NOTE: You can provide several search locations separating them with coma, example: `php symfony js:compile --plugin=web,front`
- `enabled-plugins-only`: When searching in plugins dir for plugins, should only enabled plugins in configuration be considered, default is false
- `force`: It will force compiler to compile JavaScript files regardless of cache, default is false
- `write-empty`: If file is empty, or output is empty, it will be written anyway. Default is false.
- `preserve-credits`: Should JavaScript comments before code be preserved or not in compiled JavaScript file, default is false.
This does not work - I can not figure out how to do this.
- `compiler-options` - you can pass in some compiler options as `opt1:val1,opt2,val2` depending on used compiler. For now, only one compiler is implemented, see its docs for settings.
 
### `js:delete-compiled`

- `plugin`: Same as for `js:compile`
- `enabled-plugins-only`: Same as for `js:compile`

### `js:delete-source`

- `plugin`: Same as for `js:compile`
- `enabled-plugins-only`: Same as for `js:compile`


