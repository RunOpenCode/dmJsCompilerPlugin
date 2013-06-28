<?php

/**
 * Class dmLessLibraryTask
 *
 * @author Nikola Svitlica a.k.a TheCelavi
 */
class dmJsCompilerCompileTask extends dmContextTask {


    protected function configure()
    {
        parent::configure();

        $this->addOptions(array(
        ));

        $this->namespace = 'js';
        $this->name = 'compile';
        $this->aliases = array('jsc');
        $this->briefDescription = 'Compiles all or some of the javascript files in project.';

        $this->detailedDescription = $this->briefDescription;

        $this->addOptions(array(
            new sfCommandOption('plugin', 'p', sfCommandOption::PARAMETER_OPTIONAL, 'Compile for only targeted plugin/plugins', null),
            new sfCommandOption('force', 'f', sfCommandOption::PARAMETER_NONE, 'Force compile'),
            new sfCommandOption('write-empty', 'we', sfCommandOption::PARAMETER_NONE, 'Write empty files'),
            new sfCommandOption('preserve-credits', 'pc', sfCommandOption::PARAMETER_NONE, 'Preserve credits'),
            new sfCommandOption('enabled-plugins-only', 'ep', sfCommandOption::PARAMETER_NONE, 'Enabled plugins only'),
            new sfCommandOption('compiler-options', 'co', sfCommandOption::PARAMETER_OPTIONAL, 'Additional compiler options', null),
        ));


    }

    /**
     * Executes the current task.
     *
     * @param array $arguments  An array of arguments
     * @param array $options    An array of options
     *
     * @return integer 0 if everything went fine, or an error code
     */
    protected function execute($arguments = array(), $options = array())
    {
        $force =  (isset($options['force']) && $options['force']) ? true : false;
        $writeEmpty = (isset($options['write-empty']) && $options['write-empty']) ? true : false;
        $enabledOnly =  (isset($options['enabled-plugins-only']) && $options['enabled-plugins-only']) ? true : false;
        $preserveCredits =  (isset($options['preserve-comments']) && $options['preserve-comments']) ? true : false;

        $compilerOptions = array();
        if ($options['compiler-options']) {
           $opts = array_map('trim', explode(',', $options['compiler-options']));
           foreach ($opts as $opt) {
               $tmp = array_map('trim', explode(':', $opt));
               if (count($tmp) == 2) $compilerOptions[$tmp[0]] = $tmp[1];
               else {
                   $this->logBlock('Compiler options are not passed in adequate format. Please read documentation.', 'ERROR');
                   exit;
               }
           }
        }

        $this->get('js_compiler')->compileProject($options['plugin'], $enabledOnly, null, null, $force, $writeEmpty, $preserveCredits, $compilerOptions);
    }

}