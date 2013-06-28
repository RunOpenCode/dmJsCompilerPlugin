<?php

class dmJsCompilerDeleteCompiledTask extends dmContextTask {

    protected function configure()
    {
        parent::configure();

        $this->addOptions(array(
        ));

        $this->namespace = 'js';
        $this->name = 'delete-compiled';
        $this->aliases = array('js:delc');
        $this->briefDescription = 'Delete compiled JavaScript files created from JavaScript source.';

        $this->detailedDescription = $this->briefDescription;

        $this->addOptions(array(
            new sfCommandOption('plugin', 'p', sfCommandOption::PARAMETER_OPTIONAL, 'Delete compiled JavaScript files for only targeted plugin/plugins', null),
            new sfCommandOption('enabled-plugins-only', 'ep', sfCommandOption::PARAMETER_NONE, 'Enabled plugins only')
        ));
    }

    protected function execute($arguments = array(), $options = array())
    {
        $enabledOnly =  (isset($options['enabled-plugins-only']) && $options['enabled-plugins-only']) ? true : false;
        $this->get('js_compiler')->deleteCompiledJavaScriptFiles($options['plugin'], $enabledOnly);
    }

}