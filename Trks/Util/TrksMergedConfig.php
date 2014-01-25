<?php
/**
 * Created by IntelliJ IDEA.
 * User: trakos
 * Date: 05.01.14
 * Time: 11:26
 */

namespace Trks\Util;


use Traversable;
use Zend\ModuleManager\Listener\ConfigMergerInterface;
use Zend\Stdlib\ArrayUtils;
use Zend\Stdlib\Glob;

class TrksMergedConfig implements ConfigMergerInterface
{
    protected $mergedConfig = [];

    public function __construct($globPaths, $cacheFilePath, $enableCaching)
    {
        $configs = array();
        if (!is_array($globPaths)) {
            $globPaths = [$globPaths];
        }
        if (!$enableCaching || !$this->hasCachedConfig($cacheFilePath)) {
            foreach ($globPaths as $path) {
                foreach (Glob::glob($path, Glob::GLOB_BRACE) as $file) {
                    $configs[$file] = \Zend\Config\Factory::fromFile($file);
                }
            }
            foreach ($configs as $config) {
                $this->mergedConfig = ArrayUtils::merge($this->mergedConfig, $config);
            }
            if ($enableCaching) {
                $this->writeArrayToFile($cacheFilePath, $this->mergedConfig);
            }
        } else {
            $this->mergedConfig = $this->getCachedConfig($cacheFilePath);
        }
    }

    /**
     * Write a simple array of scalars to a file
     *
     * @param  string $filePath
     * @param  array  $array
     *
     * @return AbstractListener
     */
    protected function writeArrayToFile($filePath, $array)
    {
        $content = "<?php\nreturn " . var_export($array, 1) . ';';
        file_put_contents($filePath, $content);
        return $this;
    }

    protected function getCachedConfig($filePath)
    {
        /** @noinspection PhpIncludeInspection */
        return require $filePath;
    }

    protected function hasCachedConfig($cacheFilePath)
    {
        return file_exists($cacheFilePath);
    }

    public function getMergedConfig($returnConfigAsObject = false)
    {
        if ($returnConfigAsObject) throw new \Exception('unimplemented');
        return $this->mergedConfig;
    }

    /**
     * setMergedConfig
     *
     * @param  array $config
     *
     * @return ConfigMergerInterface
     */
    public function setMergedConfig(array $config)
    {
        $this->mergedConfig       = $config;
        return $this;
    }
}