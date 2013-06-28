<?php

class dmJsCompilerService extends dmConfigurable  {

    protected
        $serviceContainer,
        $eventLog,
        $consoleLog,
        $finder,
        $compiler,
        $i18n;

    public function __construct($serviceContainer, $eventLog, $consoleLog, $options)
    {
        $this->serviceContainer = $serviceContainer;
        $this->eventLog = $eventLog;
        $this->consoleLog = $consoleLog;
        $this->i18n = $serviceContainer->getService('i18n');
        $this->initialize($options);
    }

    protected function initialize($options)
    {
        if ($options && is_array($options)) $this->configure($options);

        $finderClass = $this->getOption('js_finder_class', 'dmJsFileFinder');
        $compilerClass = $this->getOption('compiler_class', 'dmJavaScriptPackerJsCompiler');

        $this->finder = new $finderClass();
        $this->compiler = new $compilerClass($this->serviceContainer, $this->getOption('compiler_options', array()));
    }

    /**
     * Compiles JS
     *
     * @param string $source Path to source file
     * @param string $target Path to output file
     * @param bool $force Force compile, do not check cache, default false
     * @param bool $writeEmpty Should empty source or empty compiled file be written, default false
     * @param bool $preserveCredits Should credits (comments before JS code) be preserved, default true
     * @param array $compilerOptions Compiler options, if any
     * @return int flag of compilation result
     * @throws dmJsCompilerException
     *
     * @see dmAbstractJsCompiler::compile
     */
    public function compile($source, $target, $force = false, $writeEmpty = false, $preserveCredits = true, $compilerOptions = array())
    {
        return $this->compiler->compile($source, $target, $force, $writeEmpty, $preserveCredits, $compilerOptions);
    }

    /**
     * Compiles JavaScript files in project
     *
     * @param string $pluginName Plugin in which to search for JS files
     * Can be any plugin in plugins dir, or some aliases can be used as well:
     *      - web: searches in web/js
     *      - diem: searches in diem-extended/dmAdminPlugin/web, diem-extended/dmCorePlugin/web, diem-extended/dmFrontPlugin/web
     *      - admin: diem-extended/dmAdminPlugin/web
     *      - core: diem-extended/dmCorePlugin/web
     *      - front: diem-extended/dmFrontPlugin/web
     *      - plugins: project/plugins/*
     * @param bool $enabledOnly Search for only enabled plugins, default false
     * @param mixed $innerDirs List of inner dirs of web dir in which to search, beside configured. Can be array or string separated with comma.
     * @param int $maxDepth Max depth of recursive search. If no value is provided, the value from config will be used
     * @param bool $force Force compile, do not check cache, default false
     * @param bool $writeEmpty Should empty source or empty compiled file be written, default false
     * @param bool $preserveCredits Should comments on top of the file (usually credits) be preserved, default true
     * @param array $compilerOptions Compiler options, if any
     * @return array files grouped by status
     */
    public function compileProject($pluginName = null, $enabledOnly = false, $innerDirs = null, $maxDepth = null, $force = false, $writeEmpty = false, $preserveCredits = true, $compilerOptions = array())
    {
        $start = microtime(true);

        $success = array();
        $errors = array();
        $skipped = array();

        $jsFiles = $this->finder->findSourceFiles($pluginName, $enabledOnly, $innerDirs, $maxDepth);

        if (count($jsFiles) == 0) {
            $this->consoleLog->logBlock($this->i18n->__('Nothing to compile.', array(), 'dmJsCompilerPlugin'), 'ERROR');
            return array(
                'success' => $success,
                'errors' => $errors,
                'skipped' => $skipped
            );
        } else {
            $compilerOptions = array_merge(array(), $this->compiler->getOptions(), $compilerOptions);
            $compilerOpts= array();
            foreach ($compilerOptions as $key => $opt) {
                $compilerOpts[] = $key . '=' . ((is_bool($opt)) ? (($opt) ? 'true' : 'false') : $opt);
            }

            $this->consoleLog->logSettings(
                $this->i18n->__('Compiling JavaScript files for project with settings:', array(), 'dmJsCompilerPlugin'),
                array(
                    $this->i18n->__('Plugins', array(), 'dmJsCompilerPlugin') => ((is_null($pluginName)) ? $this->i18n->__('ALL', array(), 'dmJsCompilerPlugin') : $pluginName),
                    $this->i18n->__('Enabled plugins only', array(), 'dmJsCompilerPlugin') => ($enabledOnly) ? $this->i18n->__('YES', array(), 'dmJsCompilerPlugin') : $this->i18n->__('NO', array(), 'dmJsCompilerPlugin'),
                    $this->i18n->__('Inner directories', array(), 'dmJsCompilerPlugin') => implode(',', $this->finder->getInnerDirs($innerDirs)),
                    $this->i18n->__('Max depth', array(), 'dmJsCompilerPlugin') => ($maxDepth) ? $maxDepth : sfConfig::get('dm_dmJsCompilerPlugin_search_max_depth'),
                    $this->i18n->__('Force compile', array(), 'dmJsCompilerPlugin') => ($force) ? $this->i18n->__('YES', array(), 'dmJsCompilerPlugin') : $this->i18n->__('NO', array(), 'dmJsCompilerPlugin'),
                    $this->i18n->__('Write empty files', array(), 'dmJsCompilerPlugin') => ($writeEmpty) ? $this->i18n->__('YES', array(), 'dmJsCompilerPlugin') : $this->i18n->__('NO', array(), 'dmJsCompilerPlugin'),
                    $this->i18n->__('Preserve credits', array(), 'dmJsCompilerPlugin') => ($preserveCredits) ? $this->i18n->__('YES', array(), 'dmJsCompilerPlugin') : $this->i18n->__('NO', array(), 'dmJsCompilerPlugin'),
                    $this->i18n->__('Compiled files suffix', array(), 'dmJsCompilerPlugin') => $this->getOption('compiled_js_sufix'),
                    $this->i18n->__('Compiler', array(), 'dmJsCompilerPlugin') => $this->compiler->getInfo(),
                    $this->i18n->__('Compiler options', array(), 'dmJsCompilerPlugin') => implode(' ', $compilerOpts),
                )
            );

            $this->consoleLog->logSection('js:compile', $this->i18n->__('Attempting to compile %count% JavaScript files...', array('%count%' => count($jsFiles)), 'dmJsCompilerPlugin'));
            $this->consoleLog->logHorizontalRule();

            foreach ($jsFiles as $file) {
                try {
                    $target =  dmOs::join(dirname($file) , pathinfo($file, PATHINFO_FILENAME) . '.' . $this->getOption('compiled_js_sufix') . '.js');
                    $this->consoleLog->logBlock($this->i18n->__('Compiling: %file%', array('%file%' => $file), 'dmJsCompilerPlugin'), 'COMMENT');
                    $this->consoleLog->logBlock($this->i18n->__('Into:      %file%', array('%file%' => $target), 'dmJsCompilerPlugin'), 'COMMENT');
                    $flag = $this->compile(
                        $file,
                        $target,
                        $force,
                        $writeEmpty,
                        $preserveCredits,
                        $compilerOptions
                    );
                    switch ($flag) {
                        case DM_JS_COMPILER_RESULT_SKIPPED_EMPTY_OUTPUT:
                            $this->consoleLog->logSection('js:compiler', $this->i18n->__('Skipped - empty output.', array(), 'dmJsCompilerPlugin'));
                            $skipped[] = $file;
                            break;
                        case DM_JS_COMPILER_RESULT_SKIPPED_EMPTY_SOURCE:
                            $this->consoleLog->logSection('js:compiler', $this->i18n->__('Skipped - empty source.', array(), 'dmJsCompilerPlugin'));
                            $skipped[] = $file;
                            break;
                        case DM_JS_COMPILER_RESULT_SKIPPED_CACHE:
                            $this->consoleLog->logSection('js:compiler', $this->i18n->__('Skipped - already compiled.', array(), 'dmJsCompilerPlugin'));
                            $skipped[] = $file;
                            break;
                        default:
                            $this->consoleLog->logSection('js:compiler', $this->i18n->__('Compiled.', array(), 'dmJsCompilerPlugin'));
                            $success[] = $file;
                            break;
                    }

                } catch (dmJsCompilerException $e) {
                    $this->consoleLog->logSection('js:compiler', $this->i18n->__('COMPILER ERROR: %message%', array('%message%' => $e->getMessage()), 'dmJsCompilerPlugin'), null, 'ERROR');
                    $errors[] = $file;
                } catch (Exception $e) {
                    $this->consoleLog->logSection('js:compiler', $this->i18n->__('UNEXPECTED ERROR: %message%', array('%message%' => $e->getMessage()), 'dmJsCompilerPlugin'), null, 'ERROR');
                    $errors[] = $file;
                }
            }

            $this->consoleLog->logStatus(
                $this->i18n->__('Status:', array(), 'dmJsCompilerPlugin'),
                array(
                    $this->i18n->__('Compiled files', array(), 'dmJsCompilerPlugin') => count($success),
                    $this->i18n->__('Skipped files', array(), 'dmJsCompilerPlugin') => count($skipped),
                    $this->i18n->__('Not compiled', array(), 'dmJsCompilerPlugin') => array(
                        'message' => count($errors),
                        'style' => (count($errors)) ? 'ERROR' : 'INFO'
                    ),
                ),
                round(microtime(true) - $start, 2)
            );

            $this->eventLog->log(array(
                'server'  => $_SERVER,
                'action'  => (count($errors)) ? 'exception' : 'info',
                'type'    => $this->i18n->__('Compile JavaScript', array(), 'dmJsCompilerPlugin'),
                'subject' =>  $this->i18n->__('JavaScript files compiled, %errors% errors', array('%errors%' => count($errors)), 'dmJsCompilerPlugin')
            ));

            return array(
                'success' => $success,
                'errors' => $errors,
                'skipped' => $skipped
            );
        }
    }

    /**
     * Delete compiled JavaScript files in project/plugin
     *
     * @param string $pluginName Plugin in which to search for JS files
     * Can be any plugin in plugins dir, or some aliases can be used as well:
     *      - web: searches in web/js
     *      - diem: searches in diem-extended/dmAdminPlugin/web, diem-extended/dmCorePlugin/web, diem-extended/dmFrontPlugin/web
     *      - admin: diem-extended/dmAdminPlugin/web
     *      - core: diem-extended/dmCorePlugin/web
     *      - front: diem-extended/dmFrontPlugin/web
     *      - plugins: project/plugins/*
     * @param bool $enabledOnly Search for only enabled plugins, default false
     * @param mixed $innerDirs List of inner dirs of web dir in which to search, beside configured. Can be array or string separated with comma.
     * @param int $maxDepth Max depth of recursive search. If no value is provided, the value from config will be used
     * @return array files grouped by status
     */
    public function deleteCompiledJavaScriptFiles($pluginName = null, $enabledOnly = false, $innerDirs = null, $maxDepth = null)
    {
        $start = microtime(true);

        $success = array();
        $errors = array();
        $skipped = array();

        $jsFiles = $this->finder->findCompiledFiles($pluginName, $enabledOnly, $innerDirs, $maxDepth);
        if (count($jsFiles) == 0) {
            $this->consoleLog->logBlock($this->i18n->__('Nothing to delete.', array(), 'dmJsCompilerPlugin'), 'ERROR');
            return array(
                'success' => $success,
                'errors' => $errors,
                'skipped' => $skipped
            );
        } else {
            $this->consoleLog->logSettings(
                $this->i18n->__('Deleting compiled JavaScript files for project with settings:', array(), 'dmJsCompilerPlugin'),
                array(
                    $this->i18n->__('Plugins', array(), 'dmJsCompilerPlugin') => ((is_null($pluginName)) ? $this->i18n->__('ALL', array(), 'dmJsCompilerPlugin') : $pluginName),
                    $this->i18n->__('Enabled plugins only', array(), 'dmJsCompilerPlugin') => ($enabledOnly) ? $this->i18n->__('YES', array(), 'dmJsCompilerPlugin') : $this->i18n->__('NO', array(), 'dmLessLibraryPlugin'),
                    $this->i18n->__('Inner directories', array(), 'dmJsCompilerPlugin') => implode(',', $this->finder->getInnerDirs($innerDirs)),
                    $this->i18n->__('Max depth', array(), 'dmJsCompilerPlugin') => ($maxDepth) ? $maxDepth : sfConfig::get('dm_dmJsCompilerPlugin_search_max_depth')
                )
            );

            $this->consoleLog->logSection('js:delete-compiled', $this->i18n->__('Attempting to delete %count% compiled JavaScript files...', array('%count%' => count($jsFiles)), 'dmJsCompilerPlugin'));
            $this->consoleLog->logHorizontalRule();

            foreach ($jsFiles as $file) {
                if (file_exists($file)) {
                    if (@unlink($file)) {
                        $this->consoleLog->logSection($this->i18n->__('Deleted:', array(), 'dmJsCompilerPlugin'), $file);
                        $success[] = $file;
                    } else {
                        $this->consoleLog->logBlock($this->i18n->__('Compiled JavaScript file %file% could not be deleted', array('%file%' => $file), 'dmJsCompilerPlugin'), 'ERROR');
                        $errors[] = $file;
                    }
                } else {
                    $this->consoleLog->logSection($this->i18n->__('Skipped (not exist):', array(), 'dmJsCompilerPlugin'), $file);
                    $skipped[] = $file;
                }
            }

            $this->consoleLog->logStatus(
                $this->i18n->__('Status:', array(), 'dmJsCompilerPlugin'),
                array(
                    $this->i18n->__('Deleted files', array(), 'dmJsCompilerPlugin') => count($success),
                    $this->i18n->__('Skipped files', array(), 'dmJsCompilerPlugin') => count($skipped),
                    $this->i18n->__('Not deleted', array(), 'dmJsCompilerPlugin') => array(
                        'message' => count($errors),
                        'style' => (count($errors)) ? 'ERROR' : 'INFO'
                    ),
                ),
                round(microtime(true) - $start, 2)
            );

            $this->eventLog->log(array(
                'server'  => $_SERVER,
                'action'  => (count($errors)) ? 'exception' : 'info',
                'type'    => $this->i18n->__('Deleted compiled JavaScript', array(), 'dmJsCompilerPlugin'),
                'subject' =>  $this->i18n->__('Compiled JavaScript files are deleted, %errors% errors', array('%errors%' => count($errors)), 'dmJsCompilerPlugin')
            ));

            return array(
                'success' => $success,
                'errors' => $errors,
                'skipped' => $skipped
            );
        }
    }

    /**
     * Delete source JavaScript files in project/plugin - only if there is compiled version
     *
     * BE CAREFUL USING THIS!!!
     *
     * @param string $pluginName Plugin in which to search for JS files
     * Can be any plugin in plugins dir, or some aliases can be used as well:
     *      - web: searches in web/js
     *      - diem: searches in diem-extended/dmAdminPlugin/web, diem-extended/dmCorePlugin/web, diem-extended/dmFrontPlugin/web
     *      - admin: diem-extended/dmAdminPlugin/web
     *      - core: diem-extended/dmCorePlugin/web
     *      - front: diem-extended/dmFrontPlugin/web
     *      - plugins: project/plugins/*
     * @param bool $enabledOnly Search for only enabled plugins, default false
     * @param mixed $innerDirs List of inner dirs of web dir in which to search, beside configured. Can be array or string separated with comma.
     * @param int $maxDepth Max depth of recursive search. If no value is provided, the value from config will be used
     * @return array files grouped by status
     */
    public function deleteJavaScriptSource($pluginName = null, $enabledOnly = false, $innerDirs = null, $maxDepth = null)
    {
        $start = microtime(true);

        $success = array();
        $errors = array();
        $skipped = array();

        $jsSourceFiles = $this->finder->findCompiledSourceFiles($pluginName, $enabledOnly, $innerDirs, $maxDepth);
        if (count($jsSourceFiles) == 0) {
            $this->consoleLog->logBlock($this->i18n->__('Nothing to delete.', array(), 'dmJsCompilerPlugin'), 'ERROR');
            return array(
                'success' => $success,
                'errors' => $errors,
                'skipped' => $skipped
            );
        } else {
            $this->consoleLog->logSettings(
                $this->i18n->__('Deleting source JavaScript files for project with settings:', array(), 'dmJsCompilerPlugin'),
                array(
                    $this->i18n->__('Plugins', array(), 'dmJsCompilerPlugin') => ((is_null($pluginName)) ? $this->i18n->__('ALL', array(), 'dmJsCompilerPlugin') : $pluginName),
                    $this->i18n->__('Enabled plugins only', array(), 'dmJsCompilerPlugin') => ($enabledOnly) ? $this->i18n->__('YES', array(), 'dmJsCompilerPlugin') : $this->i18n->__('NO', array(), 'dmJsCompilerPlugin'),
                    $this->i18n->__('Inner directories', array(), 'dmJsCompilerPlugin') => implode(',', $this->finder->getInnerDirs($innerDirs)),
                    $this->i18n->__('Max depth', array(), 'dmJsCompilerPlugin') => ($maxDepth) ? $maxDepth : sfConfig::get('dm_dmJsCompilerPlugin_search_max_depth')
                )
            );

            $this->consoleLog->logSection('js:delete-source', $this->i18n->__('Attempting to delete %count% JavaScript source files...', array('%count%' => count($jsSourceFiles)), 'dmJsCompilerPlugin'));
            $this->consoleLog->logHorizontalRule();

            foreach ($jsSourceFiles as $file) {
                if (file_exists($file)) {
                    if (true /*@unlink($file)*/) {
                        $this->consoleLog->logSection($this->i18n->__('Deleted:', array(), 'dmJsCompilerPlugin'), $file);
                        $success[] = $file;
                    } else {
                        $this->consoleLog->logBlock($this->i18n->__('JavaScript source file %file% could not be deleted', array('%file%' => $file), 'dmJsCompilerPlugin'), 'ERROR');
                        $errors[] = $file;
                    }
                } else {
                    $this->consoleLog->logSection($this->i18n->__('Skipped (not exist):', array(), 'dmJsCompilerPlugin'), $file);
                    $skipped[] = $file;
                }
            }

            $this->consoleLog->logStatus(
                $this->i18n->__('Status:', array(), 'dmJsCompilerPlugin'),
                array(
                    $this->i18n->__('Deleted files', array(), 'dmJsCompilerPlugin') => count($success),
                    $this->i18n->__('Skipped files', array(), 'dmJsCompilerPlugin') => count($skipped),
                    $this->i18n->__('Not deleted', array(), 'dmJsCompilerPlugin') => array(
                        'message' => count($errors),
                        'style' => (count($errors)) ? 'ERROR' : 'INFO'
                    ),
                ),
                round(microtime(true) - $start, 2)
            );

            $this->eventLog->log(array(
                'server'  => $_SERVER,
                'action'  => (count($errors)) ? 'exception' : 'info',
                'type'    => $this->i18n->__('Deleted JavaScript source', array(), 'dmJsCompilerPlugin'),
                'subject' =>  $this->i18n->__('JavaScript source files are deleted, %errors% errors', array('%errors%' => count($errors)), 'dmJsCompilerPlugin')
            ));

            return array(
                'success' => $success,
                'errors' => $errors,
                'skipped' => $skipped
            );
        }
    }


    public function getFinder()
    {
        return $this->finder;
    }
}