<?php

class dmJsCompilerDeleteSourceTask extends dmContextTask {

    protected $i18n;

    protected function configure()
    {
        parent::configure();

        $this->addOptions(array(
        ));

        $this->namespace = 'js';
        $this->name = 'delete-source';
        $this->briefDescription = 'Delete JavaScript source files from project.';

        $this->detailedDescription = $this->briefDescription;

        $this->addOptions(array(
            new sfCommandOption('plugin', 'p', sfCommandOption::PARAMETER_OPTIONAL, 'Delete JavaScript source files for only targeted plugin/plugins', null),
            new sfCommandOption('enabled-plugins-only', 'ep', sfCommandOption::PARAMETER_NONE, 'Enabled plugins only')
        ));
    }

    protected function execute($arguments = array(), $options = array())
    {
        $this->i18n = $this->get('i18n');
        $enabledOnly =  (isset($options['enabled-plugins-only']) && $options['enabled-plugins-only']) ? true : false;
        $this->deleteSourceFiles($options['plugin'], $enabledOnly);
    }

    protected function deleteSourceFiles($plugin, $enabledOnly = false)
    {
        $compiler = $this->get('js_compiler');
        $logger = $this->get('console_log');
        $javaScriptSourceFiles = $compiler->getFinder()->findCompiledSourceFiles($plugin, $enabledOnly);

        if (count($javaScriptSourceFiles)) {

            $logger->logHorizontalRule();
            $logger->logBlock($this->i18n->__('The following %count% JavaScript source files will be deleted:', array('%count%'=>count($javaScriptSourceFiles)), 'dmJsCompilerPlugin'));
            $logger->logHorizontalRule();
            foreach ($javaScriptSourceFiles as $file) {
                $logger->log('- '.$file);
            }
            $logger->logHorizontalRule();

            $logger->logBlock(array(
                '',
                $this->i18n->__('WARNING!!!', array(), 'dmLessLibraryPlugin'),
                $this->i18n->__('This task should be used only on production server.', array(), 'dmJsCompilerPlugin'),
                $this->i18n->__('The task will delete JavaScript source files from project.', array(), 'dmJsCompilerPlugin'),
                $this->i18n->__('Please create backup of your source files.', array(), 'dmJsCompilerPlugin'),
                $this->i18n->__('This action can not be undone.', array(), 'dmJsCompilerPlugin'),
                ''
            ), 'QUESTION_LARGE');

            $logger->logBreakLine();

            $confirm = $this->askConfirmation($this->i18n->__('Are you shore?', array(), 'dmJsCompilerPlugin'), 'QUESTION', false);

            if ($confirm) {
                $compiler->deleteJavaScriptSource($plugin, $enabledOnly);
            } else {
                $logger->logBlock($this->i18n->__('Task is not executed - no JavaScript source file is deleted.', array(), 'dmJsCompilerPlugin'));
            }

        } else {
            $this->logBlock($this->i18n->__('Nothing to delete.', array(), 'dmJsCompilerPlugin'), 'ERROR');
        }
    }
}