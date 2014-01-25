<?php

namespace Trks\Build\Sql;

use Doctrine\ORM\Mapping\ClassMetadataInfo,
    Doctrine\Common\Util\Inflector,
    Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Tools\EntityGenerator;
use Zend\Db\TableGateway\TableGateway;

class TrksTableGenerator extends EntityGenerator
{
    /**
     * @var bool
     */
    private $backupExisting = true;

    /**
     * The extension to use for written php files
     *
     * @var string
     */
    private $extension = '.php';

    /**
     * Whether or not the current ClassMetadataInfo instance is new or old
     *
     * @var boolean
     */
    private $isNew = true;

    /**
     * @var array
     */
    private $staticReflection = array();

    /**
     * Number of spaces to use for indention in generated code
     */
    private $numSpaces = 4;

    /**
     * The actual spaces to use for indention
     *
     * @var string
     */
    private $spaces = '    ';

    /**
     * The class all generated entities should extend
     *
     * @var string
     */
    private $classToExtend;

    /**
     * Whether or not to re-generate entity class if it exists already
     *
     * @var boolean
     */
    private $regenerateEntityIfExists = false;

    public $rowNamespace;
    public $namespace;

    /**
     * @var array Hash-map to handle generator types string.
     */
    protected static $generatorStrategyMap = array(
        ClassMetadataInfo::GENERATOR_TYPE_AUTO      => 'AUTO',
        ClassMetadataInfo::GENERATOR_TYPE_SEQUENCE  => 'SEQUENCE',
        ClassMetadataInfo::GENERATOR_TYPE_TABLE     => 'TABLE',
        ClassMetadataInfo::GENERATOR_TYPE_IDENTITY  => 'IDENTITY',
        ClassMetadataInfo::GENERATOR_TYPE_NONE      => 'NONE',
        ClassMetadataInfo::GENERATOR_TYPE_UUID      => 'UUID',
        ClassMetadataInfo::GENERATOR_TYPE_CUSTOM    => 'CUSTOM'
    );

    protected static $arrayExchangeFieldTemplate = '$this-><property> = (isset($data[\'<property>\'])) ? $data[\'<property>\'] : null;';

    /**
     * @var string
     */
    private static $classTemplate =
'<?php

<namespace>

/**
 * <table_class>
 *
 * @method <row_class>[] fetchAll($q, $a)
 * @method <row_class>|null fetchRow($q, $a)
 * @method <row_class>[] filterRows(array $filterArray)
 * @method <row_class>|null filterRowsGetFirst(array $filterArray)
 * @method <row_class>[] getAllRows()
 * @method <row_class>|null getRow($primaryId)
 */
<entityClassName>
{
    static private $instance;
    static public function get()
    {
        return self::$instance ? : (self::$instance = new <table_class>());
    }

    /**
     *
     * @return string
     */
    public function getTableName()
    {
        return \'<row_table>\';
    }

    /**
     *
     * @return \\Zend\Db\\Adapter\\Adapter
     */
    public function getDbAdapter()
    {
        return \StarboundLog::getApplication()->getServiceManager()->get(\'Zend\Db\\Adapter\\Adapter\');
    }

    /**
     *
     * @return <row_class>
     */
    protected function getPrototype()
    {
        return new <row_class>();
    }

    /**
     *
     * @return string
     */
    protected function getPrimaryKeyName()
    {
        return \'<row_primary_column>\';
    }

    /**
     * @param <row_class>|\Trks\Model\TrksAbstractRow $row
     *
     * @throws \Exception
     * @return int
     */
    public function saveRow(\\Trks\\Model\\TrksAbstractRow $row)
    {
        if (!$row instanceof <row_class>) {
            throw new \Exception("Row is for a wrong table!");
        }
        return parent::saveRow($row);
    }
}
';

    public function __construct()
    {
        $this->regenerateEntityIfExists = true;
    }

    /**
     * Set the number of spaces the exported class should have
     *
     * @param integer $numSpaces
     * @return void
     */
    public function setNumSpaces($numSpaces)
    {
        $this->spaces = str_repeat(' ', $numSpaces);
        $this->numSpaces = $numSpaces;
    }

    /**
     * Set the name of the class the generated classes should extend from
     *
     * @param $classToExtend
     *
     * @return void
     */
    public function setClassToExtend($classToExtend)
    {
        $this->classToExtend = $classToExtend;
    }

    /**
     * Generate and write entity classes for the given array of ClassMetadataInfo instances
     *
     * @param array $metadatas
     * @param string $outputDirectory
     * @return void
     */
    public function generate(array $metadatas, $outputDirectory)
    {
        foreach ($metadatas as $metadata) {
            $this->writeEntityClass($metadata, $outputDirectory);
        }
    }

    /**
     * Generated and write entity class to disk for the given ClassMetadataInfo instance
     *
     * @param ClassMetadataInfo $metadata
     * @param string            $outputDirectory
     *
     * @throws \RuntimeException
     * @return void
     */
    public function writeEntityClass(ClassMetadataInfo $metadata, $outputDirectory)
    {
        $path = $outputDirectory . '/' . str_replace('\\', DIRECTORY_SEPARATOR, $metadata->name) . $this->extension;
        $dir = dirname($path);

        if ( ! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $this->isNew = !file_exists($path) || (file_exists($path) && $this->regenerateEntityIfExists);

        $this->staticReflection[$metadata->name] = array('properties' => array(), 'methods' => array());

        if ($this->backupExisting && file_exists($path)) {
            $backupPath = dirname($path) . DIRECTORY_SEPARATOR . basename($path) . "~";
            if (!copy($path, $backupPath)) {
                throw new \RuntimeException("Attempt to backup overwritten entity file but copy operation failed.");
            }
        }

        // If entity doesn't exist or we're re-generating the entities entirely
        //if ($this->isNew) {
            file_put_contents($path, $this->generateEntityClass($metadata));
            // If entity exists and we're allowed to update the entity class
        /*} else if ( ! $this->isNew && $this->updateEntityIfExists) {
            file_put_contents($path, $this->generateUpdatedEntityClass($metadata, $path));
        }*/
    }

    /**
     * Generate a PHP5 Doctrine 2 entity class from the given ClassMetadataInfo instance
     *
     * @param ClassMetadataInfo $metadata
     * @return string $code
     */
    public function generateEntityClass(ClassMetadataInfo $metadata)
    {
        $placeHolders = array(
            '<namespace>',
            '<entityClassName>',
            '<table_class>',
            '<row_class>',
            '<row_table>',
            '<row_primary_column>',
        );

        $replacements = array(
            $this->generateEntityNamespace($metadata),
            $this->generateEntityTableClassName($metadata),
            $this->getEntityTableClassName($metadata),
            $this->getEntityRowClassName($metadata),
            $metadata->getTableName(),
            $this->getPrimaryIdColumn($metadata),
        );

        $code = str_replace($placeHolders, $replacements, self::$classTemplate);
        return str_replace('<spaces>', $this->spaces, $code);
    }



    protected function generateEntityNamespace(ClassMetadataInfo $metadata)
    {
        return 'namespace ' . $this->namespace .';';
    }

    protected function getEntityTableClassName(ClassMetadataInfo $metadata)
    {
        return 'Table_' . $metadata->table['name'];
    }

    protected function getEntityRowClassName(ClassMetadataInfo $metadata)
    {
        return '\\' . $this->rowNamespace . '\\' . 'Row_' . $metadata->table['name'];
    }

    protected function generateEntityTableClassName(ClassMetadataInfo $metadata)
    {
        return 'class ' . 'Table_' . $metadata->table['name'] .
        ($this->extendsClass() ? ' extends ' . $this->getClassToExtendName() : null);
    }

    protected function getPrimaryIdColumn(ClassMetadataInfo $metadata)
    {
        foreach ($metadata->fieldMappings as $fieldMapping) {
            if (isset($fieldMapping['id']) && $fieldMapping['id']) {
                return $fieldMapping['columnName'];
            }
        }
        throw new \Exception('No PK found in ' . $metadata->getTableName() . '!');
    }

    private function extendsClass()
    {
        return $this->classToExtend ? true : false;
    }

    private function getClassToExtend()
    {
        return $this->classToExtend;
    }

    private function getClassToExtendName()
    {
        $reflection = new \ReflectionClass($this->getClassToExtend());

        return '\\' . $reflection->getName();
    }

    private function hasProperty($property, ClassMetadataInfo $metadata)
    {
        if ($this->extendsClass()) {
            // don't generate property if its already on the base class.
            $reflectionClass = new \ReflectionClass($this->getClassToExtend());
            if ($reflectionClass->hasProperty($property)) {
                return true;
            }
        }

        return (
            isset($this->staticReflection[$metadata->name]) &&
            in_array($property, $this->staticReflection[$metadata->name]['properties'])
        );
    }
}
