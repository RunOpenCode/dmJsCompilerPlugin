<?php

define('DM_JS_COMPILER_RESULT_COMPILED', 1);
define('DM_JS_COMPILER_RESULT_SKIPPED_CACHE', 2);
define('DM_JS_COMPILER_RESULT_SKIPPED_EMPTY_SOURCE', 3);
define('DM_JS_COMPILER_RESULT_SKIPPED_EMPTY_OUTPUT', 4);

abstract class dmAbstractJsCompiler extends dmConfigurable {

    protected $serviceContainer;

    public function __construct($serviceContainer, array $compilerOptions = array())
    {
        $this->serviceContainer = $serviceContainer;

        $this->initialize();

        $this->configure($compilerOptions);
    }

    protected abstract function initialize();

    /**
     * Compiles JS file
     *
     * This function manages cache internally and uses doCompile function for actual compilation process.
     *
     * @param string $source Source code file path
     * @param string $target Target compiled file path
     * @param bool $force Force compile, do not check cache, default false
     * @param bool $writeEmpty Should empty source or empty compiled file be written, default false
     * @param bool $preserveCredits Should comments on top of the file (usually credits) be preserved, default true
     * @param array $compilerOptions Compiler options, if any
     * @return int flag of compilation result
     * @throws dmJsCompilerException
     */
    public function compile($source, $target, $force = false, $writeEmpty = false, $preserveCredits = true, $compilerOptions = array())
    {
        if (!file_exists($source)) {
            throw new dmJsCompilerException(sprintf('The source file %s does not exists.', $source));
        }

        if (!$force && file_exists($target) && filemtime($source) == filemtime($target)) return DM_JS_COMPILER_RESULT_SKIPPED_CACHE;

        $sourceCode = file_get_contents($source);
        if (trim($sourceCode) == '' && !$writeEmpty) return DM_JS_COMPILER_RESULT_SKIPPED_EMPTY_SOURCE;

        $compiledCode = (($preserveCredits) ? $this->extractCredits($sourceCode) : '') . $this->doCompile($sourceCode, array_merge(array(), $this->options, $compilerOptions));

        if (trim($compiledCode) == '' && !$writeEmpty) return DM_JS_COMPILER_RESULT_SKIPPED_EMPTY_OUTPUT;

        if (file_put_contents($target, $compiledCode) === false) {
            throw new dmJsCompilerException(sprintf('Could not write compiled file on path %s.', $target));
        } else {
            touch($target, filemtime($source));
            return DM_JS_COMPILER_RESULT_COMPILED;
        }
    }

    /**
     * Compiles the source code of javascript and returns sourcecode
     *
     * @param $source The source code
     * @param array $compilerOptions Concrete compiler options, if any
     * @return string Compiled JS code
     * @throws dmJsCompilerException
     */
    protected abstract function doCompile($source, $compilerOptions = array());

    /**
     * Info about used compiler
     *
     * @return string
     */
    public abstract function getInfo();

    /**
     * Strips comments from the source code
     *
     * @param $source Source code
     * @return string Code without comments
     */
    protected function stripComments($source)
    {
        // TODO - how to do this?
        return $source;
    }

    /**
     * Find comments from the source code defined before any source code is provided
     * Those lines are considered as credits
     *
     * @param $source Source code
     * @return string Code without comments
     */
    protected function extractCredits($source)
    {
        // TODO - how to do this?
        return '';
    }
}