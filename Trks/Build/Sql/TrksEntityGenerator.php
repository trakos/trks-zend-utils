<?php

namespace Trks\Build\Sql;

use Doctrine\ORM\Mapping\ClassMetadataInfo,
    Doctrine\Common\Util\Inflector,
    Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Tools\EntityGenerator;
use ZendService\ReCaptcha\Exception;

/**
 * @property array metadatas
 */
class TrksEntityGenerator extends EntityGenerator
{
    public $tableNamespace;
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
     * Whether or not to generation annotations
     *
     * @var boolean
     */
    private $generateAnnotations = false;

    /**
     * @var string
     */
    private $annotationsPrefix = '';

    /**
     * Whether or not to re-generate entity class if it exists already
     *
     * @var boolean
     */
    private $regenerateEntityIfExists = false;

    /**
     * @var boolean
     */
    private $fieldVisibility = 'public';

    public $namespace;

    /**
     * Hash-map for handle types
     *
     * @var array
     */
    private $typeAlias = array(
        Type::DATETIMETZ    => '\DateTime',
        Type::DATETIME      => '\DateTime',
        Type::DATE          => '\DateTime',
        Type::TIME          => '\DateTime',
        Type::OBJECT        => '\stdClass',
        Type::BIGINT        => 'integer',
        Type::SMALLINT      => 'integer',
        Type::TEXT          => 'string',
        Type::BLOB          => 'string',
        Type::DECIMAL       => 'float',
        Type::JSON_ARRAY    => 'array',
        Type::SIMPLE_ARRAY  => 'array',
    );

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
    protected static $toArrayFieldTemplate = '\'<property>\' => $this-><property>,';

    /**
     * @var string
     */
    private static $classTemplate =
'<?php

<namespace>

<entityAnnotation>
<entityClassName>
{
<entityBody>

    public function exchangeArray($data)
    {
<exchangeArray>
    }

    public function toArray()
    {
        return array(
<toArray>
        );
    }

    /**
     *
     * @return void
     */
    public function save()
    {
        <table_class>::get()->saveRow($this);
    }

    /**
     * @param int $primaryId
     *
     * @return <row_class>|null
     */
    static public function get($primaryId)
    {
        return <table_class>::get()->getRow($primaryId);
    }

<connected_rows_getters>
}
';
    private static $getConnectedTemplate =
'
    /**
     *
     * @return <row_class>|null
     */
    public function get<entity_name>()
    {
        if (!$this-><join_column_name>) return null;
        return <table_class>::get()->getRow($this-><join_column_name>);
    }

    /**
     *
     * @param <row_class> $entity
     *
     * @throws \Exception
     * @return void
     */
    public function set<entity_name>($entity)
    {
        if (!$entity-><foreign_column_name>) throw new \Exception("Row has to be initialized!");
        $this-><join_column_name> = $entity-><foreign_column_name>;
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
        $this->metadatas = $metadatas;

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
            '<entityAnnotation>',
            '<entityClassName>',
            '<entityBody>',
            '<exchangeArray>',
            '<toArray>',
            '<row_class>',
            '<table_class>',
            '<connected_rows_getters>',
        );

        $replacements = array(
            $this->generateEntityNamespace($metadata),
            $this->generateEntityDocBlock($metadata),
            $this->generateEntityClassName($metadata),
            $this->generateEntityBody($metadata),
            $this->generateExchangeArray($metadata),
            $this->generateToArray($metadata),
            $this->getEntityRowClassName($metadata->table['name']),
            $this->getEntityTableClassName($metadata->table['name']),
            $this->generateConnectedRowsGetters($metadata),
        );

        $code = str_replace($placeHolders, $replacements, self::$classTemplate);
        return str_replace('<spaces>', $this->spaces, $code);
    }

    protected function getEntityTableClassName($tableName)
    {
        return '\\' . $this->tableNamespace . '\\' . 'Table_' . $tableName;
    }

    protected function getEntityRowClassName($tableName)
    {
        return '\\' . $this->namespace . '\\' . 'Row_' . $tableName;
    }

    protected function generateEntityNamespace(ClassMetadataInfo $metadata)
    {
        return 'namespace ' . $this->namespace .';';
    }

    protected function generateEntityDocBlock(ClassMetadataInfo $metadata)
    {
        $lines = array(
            '/**',
            ' * ' . 'Row_' . $metadata->table['name'],
            ' * ',
            ' * ' . (isset($metadata->table['schema']) ? 'schema="' . $metadata->table['schema'] . '"' : ''),
            ' * ' . (isset($metadata->table['name']) ? 'name="' . $metadata->table['name'] . '"' : ''),
            ' */'
        );

        return implode("\n", $lines);
    }

    protected function generateEntityClassName(ClassMetadataInfo $metadata)
    {
        return 'class ' . 'Row_' . $metadata->table['name'] .
        ($this->extendsClass() ? ' extends ' . $this->getClassToExtendName() : null);
    }

    protected function generateEntityBody(ClassMetadataInfo $metadata)
    {
        $code = array();
        $code[] = $this->generateEntityFieldMappingProperties($metadata);
        return implode("\n", $code);
    }

    protected function generateEntityFieldMappingProperties(ClassMetadataInfo $metadata)
    {
        $lines = array();

        foreach ($metadata->fieldMappings as $fieldMapping) {
            if ($this->hasProperty($fieldMapping['fieldName'], $metadata) ||
                $metadata->isInheritedField($fieldMapping['fieldName'])) {
                continue;
            }

            $lines[] = $this->generateFieldMappingPropertyDocBlock($fieldMapping, $metadata);
            $lines[] = $this->spaces . $this->fieldVisibility . ' $' . $fieldMapping['columnName']
                . (isset($fieldMapping['default']) ? ' = ' . var_export($fieldMapping['default'], true) : null) . ";\n";
        }

        foreach ($metadata->associationMappings as $associationMapping) {
            if ($this->hasProperty($associationMapping['fieldName'], $metadata)) {
                continue;
            }

            if ($associationMapping['isOwningSide'] == 1) {

                if (
                    !isset($associationMapping['targetToSourceKeyColumns'])
                    || count($associationMapping['targetToSourceKeyColumns']) > 1
                    || $associationMapping['isOwningSide'] != 1
                    || $associationMapping['type'] == ClassMetadataInfo::MANY_TO_MANY
                ) {
                    continue;
                }

                foreach ($associationMapping['targetToSourceKeyColumns'] as $columnName) {
                    $lines[] = $this->spaces . $this->fieldVisibility . ' $' . $columnName
                        . ($associationMapping['type'] == 'manyToMany' ? ' = array()' : null) . ";\n";
                }
            }
        }

        return implode("\n", $lines);
    }

    protected function generateExchangeArray(ClassMetadataInfo $metadata)
    {
        $lines = array();

        foreach ($metadata->fieldMappings as $fieldMapping) {
            $lines[] = $this->spaces . $this->spaces . str_replace('<property>', $fieldMapping['columnName'], self::$arrayExchangeFieldTemplate);
        }
        foreach ($metadata->associationMappings as $associationMapping) {
            if ($this->hasProperty($associationMapping['fieldName'], $metadata)) {
                continue;
            }

            if (
                !isset($associationMapping['targetToSourceKeyColumns'])
                || count($associationMapping['targetToSourceKeyColumns']) > 1
                || $associationMapping['isOwningSide'] != 1
                || $associationMapping['type'] == ClassMetadataInfo::MANY_TO_MANY
            ) {
                continue;
            }

            if ($associationMapping['isOwningSide'] == 1) {
                foreach ($associationMapping['targetToSourceKeyColumns'] as $columnName) {
                    $lines[] = $this->spaces . $this->spaces . str_replace('<property>', $columnName, self::$arrayExchangeFieldTemplate);
                }
            }
        }

        return implode("\n", $lines);
    }

    protected function generateToArray(ClassMetadataInfo $metadata)
    {
        $lines = array();

        foreach ($metadata->fieldMappings as $fieldMapping) {
            $lines[] = $this->spaces . $this->spaces . $this->spaces . str_replace('<property>', $fieldMapping['columnName'], self::$toArrayFieldTemplate);
        }
        foreach ($metadata->associationMappings as $associationMapping) {
            if ($this->hasProperty($associationMapping['fieldName'], $metadata)) {
                continue;
            }

            if ($associationMapping['isOwningSide'] == 1) {
                if ($associationMapping['type'] == ClassMetadataInfo::MANY_TO_MANY) {
                    /*foreach ($associationMapping['relationToTargetKeyColumns'] as $columnName) {
                        $lines[] = $this->spaces . $this->spaces . $this->spaces . str_replace('<property>', $columnName, self::$toArrayFieldTemplate);
                    }*/
                } else {
                    foreach ($associationMapping['targetToSourceKeyColumns'] as $columnName) {
                        $lines[] = $this->spaces . $this->spaces . $this->spaces . str_replace('<property>', $columnName, self::$toArrayFieldTemplate);
                    }
                }
            }
        }

        return implode("\n", $lines);
    }

    protected function generateConnectedRowsGetters(ClassMetadataInfo $metadata)
    {
        $lines = array();

        foreach ($metadata->associationMappings as $associationMapping) {
            if (
                !isset($associationMapping['targetToSourceKeyColumns'])
                || count($associationMapping['targetToSourceKeyColumns']) > 1
                || $associationMapping['isOwningSide'] != 1
                || $associationMapping['type'] == ClassMetadataInfo::MANY_TO_MANY
            ) {
                continue;
            }

            $placeHolders = array(
                '<row_class>',
                '<table_class>',
                '<entity_name>',
                '<join_column_name>',
                '<foreign_column_name>',
            );

            $columnName = current($associationMapping['targetToSourceKeyColumns']);
            $foreignColumnName = current($associationMapping['sourceToTargetKeyColumns']);

            $connectedEntityMetadata = $this->findEntityMetadata($associationMapping['targetEntity']);
            $connectedTableName = $connectedEntityMetadata->table['name'];

            $replacements = array(
                $this->getEntityRowClassName($connectedTableName),
                $this->getEntityTableClassName($connectedTableName),
                ucfirst($associationMapping['fieldName']),
                $columnName,
                $foreignColumnName,
            );
            $code = str_replace($placeHolders, $replacements, self::$getConnectedTemplate);
            $lines[] = str_replace('<spaces>', $this->spaces, $code);

        }

        return implode("\n", $lines);
    }

    protected function findEntityMetadata($entityName)
    {
        foreach ($this->metadatas as $metadata) {
            /* @var ClassMetadataInfo $metadata */
            if ($metadata->name == $entityName) {
                return $metadata;
            }
        }
        throw new \Exception("Failed to find $entityName!");
    }

    protected function generateFieldMappingPropertyDocBlock(array $fieldMapping, ClassMetadataInfo $metadata)
    {
        $lines = array();
        $lines[] = $this->spaces . '/**';
        $lines[] = $this->spaces . ' * @var ' . $this->getType($fieldMapping['type']);

        if ($this->generateAnnotations) {
            $lines[] = $this->spaces . ' *';

            $column = array();
            if (isset($fieldMapping['columnName'])) {
                $column[] = 'name="' . $fieldMapping['columnName'] . '"';
            }

            if (isset($fieldMapping['type'])) {
                $column[] = 'type="' . $fieldMapping['type'] . '"';
            }

            if (isset($fieldMapping['length'])) {
                $column[] = 'length=' . $fieldMapping['length'];
            }

            if (isset($fieldMapping['precision'])) {
                $column[] = 'precision=' .  $fieldMapping['precision'];
            }

            if (isset($fieldMapping['scale'])) {
                $column[] = 'scale=' . $fieldMapping['scale'];
            }

            if (isset($fieldMapping['nullable'])) {
                $column[] = 'nullable=' .  var_export($fieldMapping['nullable'], true);
            }

            if (isset($fieldMapping['columnDefinition'])) {
                $column[] = 'columnDefinition="' . $fieldMapping['columnDefinition'] . '"';
            }

            if (isset($fieldMapping['unique'])) {
                $column[] = 'unique=' . var_export($fieldMapping['unique'], true);
            }

            $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'Column(' . implode(', ', $column) . ')';

            if (isset($fieldMapping['id']) && $fieldMapping['id']) {
                $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'Id';
                $lines[] = $this->spaces.' * @' . $this->annotationsPrefix . 'GeneratedValue(strategy="' . self::$generatorStrategyMap[$metadata->generatorType] . '")';
            }
        }

        $lines[] = $this->spaces . ' */';

        return implode("\n", $lines);
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

    /**
     * @param   string $type
     * @return  string
     */
    private function getType($type)
    {
        if (isset($this->typeAlias[$type])) {
            return $this->typeAlias[$type];
        }

        return $type;
    }
}
