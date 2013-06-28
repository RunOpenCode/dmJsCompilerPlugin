<?php

define('DM_JS_COMPILER_SERVICE_DIEM_EXTENDED_DIR_NAME', 'diem-extended');
define('DM_JS_COMPILER_SERVICE_WEB_DIR_NAME', 'js');

class dmJsFileFinder {


    /**
     * Searches for all JS files in project or plugin
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
     * @return array List of all JS files
     */
    public function findJSFiles($pluginName = null, $enabledOnly = false, $innerDirs = null, $maxDepth = null)
    {

        $innerDirs = $this->getInnerDirs($innerDirs);
        $residueDirs = $this->getSearchDirs($pluginName, $enabledOnly);
        $searchLocations = array();
        foreach ($residueDirs as $key => $locations) {
            switch ($key) {
                case 'web':
                    $searchLocations = array_merge($searchLocations, $locations);
                    break;
                case 'plugins':
                    foreach ($locations as $location) {
                        foreach ($innerDirs as $innerDir) {
                            $searchLocations[] = dmOs::join($location, 'web', $innerDir);
                        }
                    }
                    break;
                default:
                    foreach ($locations as $location) {
                        foreach ($innerDirs as $innerDir) {
                            $searchLocations[] = dmOs::join($location, $innerDir);
                        }
                    }
                    break;
            }
        }
        $files = array();
        foreach ($searchLocations as $searchLocation) {
            $files = array_merge($files, sfFinder::type('file')->name('*.js')->maxdepth(($maxDepth) ? $maxDepth : sfConfig::get('dm_dmJsCompilerPlugin_search_max_depth'))->in($searchLocation));
        }
        return $files;
    }

    /**
     * Searches for source JS files in project or plugin
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
     * @return array List of source JS files
     */
    public function findSourceFiles($pluginName = null, $enabledOnly = false, $innerDirs = null, $maxDepth = null)
    {
        $files = $this->findJSFiles($pluginName, $enabledOnly, $innerDirs, $maxDepth);
        $compiledSuffix = sfConfig::get('dm_dmJsCompilerPlugin_compiled_js_files_suffix');

        $results = array();

        foreach ($files as $file) {
            $source = true;
            foreach ($compiledSuffix as $suffix) {
                if (dmString::endsWith($file, sprintf('.%s.js', $suffix))) {
                    $source = false;
                    break;
                }
            }
            if ($source) {
                $results[] = $file;
            }
        }

        return $results;
    }

    /**
     * Searches compiled source JS files in project or plugin
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
     * @return array List of all JS files
     */
    public function findCompiledSourceFiles($pluginName = null, $enabledOnly = false, $innerDirs = null, $maxDepth = null)
    {
        $files = $this->findJSFiles($pluginName, $enabledOnly, $innerDirs, $maxDepth);
        $compiledSuffix = sfConfig::get('dm_dmJsCompilerPlugin_compiled_js_files_suffix');

        $results = array();

        foreach ($files as $file) {
            $source = true;
            foreach ($compiledSuffix as $suffix) {
                if (dmString::endsWith($file, sprintf('.%s.js', $suffix))) {
                    $source = false;
                    break;
                }
            }
            if ($source) {
                $compiled = false;
                foreach ($compiledSuffix as $suffix) {
                    $sourceFile = dmOs::join(dirname($file), pathinfo($file, PATHINFO_FILENAME) . '.' . $suffix . '.js');
                    if (file_exists($sourceFile)) {
                        $compiled = true;
                        break;
                    }
                }
                if ($compiled) {
                    $results[] = $file;
                }
            }
        }

        return $results;
    }

    /**
     * Searches for compiled JS files in project or plugin
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
     * @return array List of compiled JS files
     */
    public function findCompiledFiles($pluginName = null, $enabledOnly = false, $innerDirs = null, $maxDepth = null)
    {
        $files = $this->findJSFiles($pluginName, $enabledOnly, $innerDirs, $maxDepth);
        $compiledSuffix = sfConfig::get('dm_dmJsCompilerPlugin_compiled_js_files_suffix');

        $results = array();

        foreach ($files as $file) {
            $compiled = false;
            foreach ($compiledSuffix as $suffix) {
                if (dmString::endsWith($file, sprintf('.%s.js', $suffix))) {
                    $sourceFile = dmOs::join(dirname($file), str_replace(sprintf('.%s.js', $suffix), '.js', pathinfo($file, PATHINFO_BASENAME)));
                    if (file_exists($sourceFile)) {
                        $compiled = true;
                    }
                    break;
                }
            }
            if ($compiled) {
                $results[] = $file;
            }
        }

        return $results;
    }


    /**
     * Gets list of inner dirs to search for JS files
     *
     * @param mixed $innerDirs
     * @return array List of inner dirs to search for JS files
     */
    public function getInnerDirs($innerDirs = null)
    {
        if ($innerDirs && is_array($innerDirs)) {
            $innerDirs = array_merge($innerDirs, sfConfig::get('dm_dmJsCompilerPlugin_inner_dirs'));
        } elseif ($innerDirs && is_string($innerDirs)) {
            $innerDirs = array_merge(array_map('trim', explode(',', $innerDirs)), sfConfig::get('dm_dmJsCompilerPlugin_inner_dirs'));
        } else {
            $innerDirs = sfConfig::get('dm_dmJsCompilerPlugin_inner_dirs');
        }
        return $innerDirs;
    }

    /**
     * Gets list of plugins for which JavaScript files should be searched for
     *
     * @param mixed $pluginName Plugin in which to search for JS files
     * Can be any plugin in plugins dir, or some aliases can be used as well:
     *      - web: searches in web/theme and web/themeAdmin dir
     *      - diem: searches in diem-extended/dmAdminPlugin/web, diem-extended/dmCorePlugin/web, diem-extended/dmFrontPlugin/web
     *      - admin: diem-extended/dmAdminPlugin/web
     *      - core: diem-extended/dmCorePlugin/web
     *      - front: diem-extended/dmFrontPlugin/web
     * @param bool $enabledOnly Search for only enabled plugins, default false
     * @return array List of plugins to search for JS files
     */
    protected function getSearchDirs($pluginName = null, $enabledOnly = false)
    {
        $webDirs = array(
            dmOs::join(sfConfig::get('sf_web_dir'), DM_JS_COMPILER_SERVICE_WEB_DIR_NAME)
        );

        $adminDirs = array(
            dmOs::join(sfConfig::get('sf_root_dir'), DM_JS_COMPILER_SERVICE_DIEM_EXTENDED_DIR_NAME, 'dmAdminPlugin', 'web')
        );

        $coreDirs = array(
            dmOs::join(sfConfig::get('sf_root_dir'), DM_JS_COMPILER_SERVICE_DIEM_EXTENDED_DIR_NAME, 'dmCorePlugin', 'web')
        );

        $frontDirs = array(
            dmOs::join(sfConfig::get('sf_root_dir'), DM_JS_COMPILER_SERVICE_DIEM_EXTENDED_DIR_NAME, 'dmFrontPlugin', 'web')
        );

        $pluginDirs = $this->getPlugins($enabledOnly);


        if ($pluginName) {
            $result = array();
            if (!is_array($pluginName)) {
                $pluginName = array_map('trim', explode(',', $pluginName));
                foreach ($pluginName as $plugin) {
                    switch ($plugin) {
                        case 'web':
                            $result['web'] = $webDirs;
                            break;
                        case 'admin':
                            // fall trough
                        case 'dmAdminPlugin':
                            $result['admin'] = $adminDirs;
                            break;
                        case 'core':
                            // fall trough
                        case 'dmCoreDir':
                            $result['core'] = $coreDirs;
                            break;
                        case 'front':
                            // fall trough
                        case 'dmFrontDir':
                            $result['front'] = $frontDirs;
                            break;
                        case 'diem':
                            $result['admin'] = $adminDirs;
                            $result['core'] = $coreDirs;
                            $result['front'] = $frontDirs;
                            break;
                        case 'plugins':
                            foreach ($pluginDirs as $key => $val) {
                                if (!isset($result['plugins'])) {
                                    $result['plugins'] = array();
                                }
                                $result['plugins'][] = $val;
                            }
                            break;
                        default:
                            foreach ($pluginDirs as $key => $val) {
                                if ($key == $plugin) {
                                    if (!isset($result['plugins'])) {
                                        $result['plugins'] = array();
                                    }
                                    $result['plugins'][] = $val;
                                    break;
                                }
                            }
                            break;
                    }
                }
            }
            return $result;
        } else {
            return array(
                'web' => $webDirs,
                'admin' => $adminDirs,
                'core' => $coreDirs,
                'front' => $frontDirs,
                'plugins' => $pluginDirs
            );
        }
    }

    /**
     * Gets list of plugins in plugins dir
     *
     * @param bool $enabledOnly Search for only enabled plugins, default false
     * @return array List of plugins
     */
    protected function getPlugins($enabledOnly = false)
    {
        $result = array();
        if ($enabledOnly) {
            if (!class_exists('dmLoadPluginsConfiguration')) {
                require_once dmOs::join(sfConfig::get('sf_root_dir'), 'config', 'dmLoadPluginsConfiguration.class.php');
            }
            $plugins = dmLoadPluginsConfiguration::getPlugins();
            foreach ($plugins as $plugin) {
                $result[$plugin] = dmOs::join(sfConfig::get('sf_plugins_dir'), $plugin);
            }
        } else {
            $plugins = sfFinder::type('dir')->name('*Plugin')->maxdepth(0)->in(sfConfig::get('sf_plugins_dir'));
            foreach ($plugins as $plugin) {
                $result[pathinfo($plugin, PATHINFO_FILENAME)] = $plugin;
            }
        }
        return $result;
    }
}