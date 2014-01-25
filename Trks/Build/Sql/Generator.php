<?php

namespace Trks\Build\Sql;


use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Doctrine\ORM\Tools\Export\Driver\AnnotationExporter;

class Generator
{
    protected $namespace = "StarboundLog\\Model\\Database\\Rows";
    protected $directory = "./StarboundLog/Model/Database/Rows";
    protected $tableNamespace = "StarboundLog\\Model\\Database\\Tables";
    protected $tablesDirectory = "./StarboundLog/Model/Database/Tables";
    protected $numSpaces = 4;
    protected $classToExtend = "\\Trks\\Model\\TrksAbstractRow";
    protected $tableClassToExtend = "\\Trks\\Model\\TrksAbstractTable";

    protected $metadataFactory;
    protected $databaseDriver;


    public function __construct()
    {
        $entityManager = \Doctrine\ORM\EntityManager::create(
            array(
                'driver'   => 'pdo_mysql',
                'user'     => 'starbound',
                'password' => 'starbound',
                'dbname'   => 'starbound_log_dev',
            ),
            \Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration(
                array("./data"),
                T_DEBUG,
                null,
                null,
                false
            )
        );
        $this->databaseDriver = new \Doctrine\ORM\Mapping\Driver\DatabaseDriver(
            $entityManager->getConnection()->getSchemaManager()
        );
        $entityManager->getConfiguration()->setMetadataDriverImpl(
            $this->databaseDriver
        );
        $this->metadataFactory = new DisconnectedClassMetadataFactory();
        $this->metadataFactory->setEntityManager($entityManager);

        $this->exportEntities();
        $this->exportTables();
    }

    public function exportEntities()
    {
        $this->databaseDriver->setNamespace($this->namespace);
        $metadata = $this->metadataFactory->getAllMetadata();

        foreach ($metadata as $class) {
            /* @var \Doctrine\ORM\Mapping\ClassMetadataInfo $class */
            echo $class->getName() . "\n";
            echo $class->getTableName() . "\n";
        }

        $exporter = new TrksExporter($this->directory);
        $exporter->setOverwriteExistingFiles(true);
        $exporter->prefix = 'Row_';

        $entityGenerator = new TrksEntityGenerator();
        $exporter->setEntityGenerator($entityGenerator);

        $entityGenerator->namespace = $this->namespace;
        $entityGenerator->tableNamespace = $this->tableNamespace;
        $entityGenerator->setNumSpaces($this->numSpaces);
        $entityGenerator->metadatas = $metadata;

        if ($this->classToExtend !== null) {
            $entityGenerator->setClassToExtend($this->classToExtend);
        }

        if (count($metadata)) {
            foreach ($metadata as $class) {
                printf('Processing entity "<info>%s</info>"' . PHP_EOL, $class->name);
            }
            $exporter->setMetadata($metadata);
            $exporter->export();

            printf('Exporting mapping information to "%s"' . PHP_EOL, $this->directory);
        } else {
            echo 'No Metadata Classes to process.' . PHP_EOL;
        }
    }

    public function exportTables()
    {
        $this->databaseDriver->setNamespace($this->tableNamespace);
        $metadata = $this->metadataFactory->getAllMetadata();

        foreach ($metadata as $class) {
            /* @var \Doctrine\ORM\Mapping\ClassMetadataInfo $class */
            echo $class->getName() . "\n";
            echo $class->getTableName() . "\n";
        }

        $exporter = new TrksExporter($this->tablesDirectory);
        $exporter->setOverwriteExistingFiles(true);
        $exporter->prefix = 'Table_';

        $entityGenerator = new TrksTableGenerator();
        $exporter->setEntityGenerator($entityGenerator);

        $entityGenerator->namespace = $this->tableNamespace;
        $entityGenerator->rowNamespace = $this->namespace;
        $entityGenerator->setNumSpaces($this->numSpaces);

        if ($this->tableClassToExtend !== null) {
            $entityGenerator->setClassToExtend($this->tableClassToExtend);
        }

        if (count($metadata)) {
            foreach ($metadata as $class) {
                printf('Processing entity "<info>%s</info>"' . PHP_EOL, $class->name);
            }

            $exporter->setMetadata($metadata);
            $exporter->export();

            printf('Exporting mapping information to "%s"' . PHP_EOL, $this->directory);
        } else {
            echo 'No Metadata Classes to process.' . PHP_EOL;
        }
    }
} 