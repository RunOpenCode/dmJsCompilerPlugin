<?php

class dmJavaScriptPackerJsCompiler extends dmAbstractJsCompiler {

    protected function initialize()
    {
        $this->addOption('encoding', 62);
        $this->addOption('fast_decode', true);
        $this->addOption('special_chars', false);
    }

    /**
     * Compiles the source code of javascript and returns sourcecode
     *
     * @param $source The source code
     * @param array $compilerOptions Concrete compiler options:
     *
     * Available options:
     *
     *  * encoding:                 level of encoding, int or string:  0,10,62,95 or 'None', 'Numeric', 'Normal', 'High ASCII', default: 62.
     *  * fast_decode:              include the fast decoder in the packed result, boolean, default : true.
     *  * special_chars:            if you are flagged your private and local variables in the script, boolean, default: false.
     *
     * @return string Compiled JS code
     * @throws dmJsCompilerException
     * @see JavaScriptPacker
     */
    protected function doCompile($source, $compilerOptions = array())
    {
        $packer = new JavaScriptPacker($source, $compilerOptions['encoding'], $compilerOptions['fast_decode'], $compilerOptions['special_chars']);

        try {
            return $packer->pack();
        } catch (Exception $e) {
            throw new dmJsCompilerException(sprintf('Compiler exception: %s.', $e->getMessage()));
        }
    }

    /**
     * Info about used compiler
     *
     * @return string
     */
    public function getInfo()
    {
        return 'PHP Version of the Dean Edwards\'s Packer, http://joliclic.free.fr/php/javascript-packer/en/index.php';
    }

}