<?php

namespace Trks\View;

use Trks\Struct\MessagesStruct;
use Zend\Form\ElementInterface;
use Zend\Form\FormInterface;
use Zend\View\Renderer\PhpRenderer;

/**
 * This class exists only for the annotations below and code completion inside views, it is not in fact used anywhere!
 * @inheritdoc
 *
 * @package Trks\Build\View
 *
 * @property int pageCount;
 * @property int itemCountPerPage;
 * @property int first;
 * @property int current;
 * @property int last;
 * @property int previous;
 * @property int next;
 * @property int[] pagesInRange;
 * @property int lastPageInRange;
 * @property int currentItemCount;
 * @property int totalItemCount;
 * @property int firstItemNumber;
 * @property int lastItemNumber;
 *
 */
class TrksPhpRendererWithPaginator extends TrksPhpRenderer
{
}