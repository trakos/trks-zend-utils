<?php

namespace Trks\Build\Sql;


use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Tools\Export\Driver\AnnotationExporter;

class TrksExporter extends AnnotationExporter
{
    public $prefix = 'Row_';

    /**
     * Generate the path to write the class for the given ClassMetadataInfo instance
     *
     * @param ClassMetadataInfo $metadata
     * @return string $path
     */
    protected function _generateOutputPath(ClassMetadataInfo $metadata)
    {
        return $this->_outputDir . '/' . $this->prefix . $metadata->getTableName() . $this->_extension;
    }
} 