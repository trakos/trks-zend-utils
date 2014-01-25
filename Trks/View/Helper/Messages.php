<?php

namespace Trks\View\Helper;


use Trks\Struct\MessagesStruct;
use Zend\I18n\View\Helper\AbstractTranslatorHelper;
use Trks\Mvc\Controller\Plugin\TrksFlashMessenger;

class Messages extends AbstractTranslatorHelper
{

    /**
     * Flash messenger plugin
     *
     * @var TrksFlashMessenger
     */
    protected $pluginFlashMessenger;

    /**
     * Set the flash messenger plugin
     *
     * @param  TrksFlashMessenger $pluginFlashMessenger
     * @return Messages
     */
    public function setPluginFlashMessenger(TrksFlashMessenger $pluginFlashMessenger)
    {
        $this->pluginFlashMessenger = $pluginFlashMessenger;
        return $this;
    }

    /**
     * Get the flash messenger plugin
     *
     * @return TrksFlashMessenger
     */
    public function getPluginFlashMessenger()
    {
        if (null === $this->pluginFlashMessenger) {
            $this->setPluginFlashMessenger(new TrksFlashMessenger());
        }

        return $this->pluginFlashMessenger;
    }

    static $partial = null;

    /**
     * Invoke helper as functor
     *
     * Proxies to {@link render()}.
     *
     */
    public function __invoke(MessagesStruct $messages = null)
    {
        if (!$messages) {
            return $this;
        }

        return $this->render($messages);
    }

    /**
     * @return MessagesStruct
     */
    public function getMessages()
    {
        return $this->getPluginFlashMessenger()->getTrksMessages();
    }

    /**
     * @param MessagesStruct $messages
     *
     * @return string
     * @throws \Exception
     */
    public function render(MessagesStruct $messages = null)
    {
        if (!$messages) {
            return $this->render($this->getPluginFlashMessenger()->getTrksMessages());
        }

        if (!self::$partial) {
            throw new \Exception("no partial given for Messages view helper!");
        }

        return $this->view->render(self::$partial, array(
            'messages' => $messages
        ));
    }
} 