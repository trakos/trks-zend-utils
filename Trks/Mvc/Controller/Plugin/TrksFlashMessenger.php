<?php

namespace Trks\Mvc\Controller\Plugin;

use Trks\Struct\MessagesStruct;
use Zend\Stdlib\SplQueue;
use Zend\Mvc\Controller\Plugin\FlashMessenger as PluginFlashMessenger;

/**
 * Class TrksFlashMessenger, adding new message type - warning - and new method getTrksMessages for retrieving all messages at once, and increasing max hops.
 * @inheritdoc
 *
 * @package Trks\Mvc\Controller\Plugin
 */
class TrksFlashMessenger extends \Zend\Mvc\Controller\Plugin\FlashMessenger
{
    const NAMESPACE_WARNING = 'warning';

    public function getTrksMessages()
    {
        $messageStruct = new MessagesStruct();

        $messageStruct->successList = $this->getSuccessMessages();
        $messageStruct->infoList    = $this->getInfoMessages();
        $messageStruct->errorList   = $this->getErrorMessages();
        $messageStruct->warningList = $this->getWarningMessages();

        $this->clearTrksMessages();

        return $messageStruct;
    }

    public function clearTrksMessages()
    {
        $this->clearMessages(self::NAMESPACE_ERROR);
        $this->clearMessages(self::NAMESPACE_WARNING);
        $this->clearMessages(self::NAMESPACE_INFO);
        $this->clearMessages(self::NAMESPACE_SUCCESS);
    }

    public function addMessages($messages)
    {
        foreach ($messages as $message) {
            $this->addMessage($message);
        }
    }

    /**
     * Add a message with "success" type
     *
     * @param array|string $messages
     * @return TrksFlashMessenger
     */
    public function addSuccessMessage($messages)
    {
        if (is_array($messages)) {
            foreach ($messages as $message) {
                parent::addSuccessMessage($message);
            }
        } else {
            parent::addSuccessMessage($messages);
        }

        return $this;
    }

    /**
     * Add a message with "info" type
     *
     * @param array|string $messages
     * @return TrksFlashMessenger
     */
    public function addInfoMessage($messages)
    {
        if (is_array($messages)) {
            foreach ($messages as $message) {
                parent::addInfoMessage($message);
            }
        } else {
            parent::addInfoMessage($messages);
        }

        return $this;
    }

    /**
     * Add a message with "error" type
     *
     * @param array|string $messages
     * @return TrksFlashMessenger
     */
    public function addErrorMessage($messages)
    {
        if (is_array($messages)) {
            foreach ($messages as $message) {
                parent::addErrorMessage($message);
            }
        } else {
            parent::addErrorMessage($messages);
        }

        return $this;
    }

    /**
     * Add a message
     *
     * @param  string $message
     *
     * @return TrksFlashMessenger Provides a fluent interface
     */
    public function addMessage($message)
    {
        $container = $this->getContainer();
        $namespace = $this->getNamespace();

        if (!$this->messageAdded) {
            $this->getMessagesFromContainer();
            /** @noinspection PhpUndefinedMethodInspection */
            $container->setExpirationHops(PHP_INT_MAX, null);
        }

        if (!isset($container->{$namespace})
            || !($container->{$namespace} instanceof SplQueue)
        ) {
            $container->{$namespace} = new SplQueue();
        }

        $container->{$namespace}->push($message);

        $this->messageAdded = true;

        return $this;
    }

    /**
     * Add a message with "warning" type
     *
     * @param string|array $messages
     * @return TrksFlashMessenger
     */
    public function addWarningMessage($messages)
    {
        if (is_array($messages)) {
            foreach ($messages as $message) {
                $this->addWarningMessage($message);
            }
        } else {
            $namespace = $this->getNamespace();
            $this->setNamespace(self::NAMESPACE_WARNING);
            $this->addMessage($messages);
            $this->setNamespace($namespace);
        }

        return $this;

    }

    /**
     * Get messages from "info" namespace
     *
     * @return array
     */
    public function getWarningMessages()
    {
        $namespace = $this->getNamespace();
        $this->setNamespace(self::NAMESPACE_WARNING);
        $messages = $this->getMessages();
        $this->setNamespace($namespace);

        return $messages;
    }

    /**
     * Whether "info" namespace has messages
     *
     * @return bool
     */
    public function hasWarningMessages()
    {
        $namespace = $this->getNamespace();
        $this->setNamespace(self::NAMESPACE_WARNING);
        $hasMessages = $this->hasMessages();
        $this->setNamespace($namespace);

        return $hasMessages;
    }

    /**
     * Get messages that have been added to the "error"
     * namespace within this request
     *
     * @return array
     */
    public function getCurrentWarningMessages()
    {
        $namespace = $this->getNamespace();
        $this->setNamespace(self::NAMESPACE_WARNING);
        $messages = $this->getCurrentMessages();
        $this->setNamespace($namespace);

        return $messages;
    }
} 