<?php

/**
 * Kunena Component
 *
 * @package         Kunena.Site
 * @subpackage      Controller.Application
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

namespace Kunena\Forum\Site\Controller\Application\Ajax\Initial;

\defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Kunena\Forum\Libraries\Config\KunenaConfig;
use Kunena\Forum\Libraries\Controller\KunenaControllerDisplay;
use Kunena\Forum\Libraries\Exception\KunenaExceptionAuthorise;
use Kunena\Forum\Libraries\Factory\KunenaFactory;
use Kunena\Forum\Libraries\Request\KunenaRequest;
use Kunena\Forum\Libraries\Response\KunenaResponseJson;
use Kunena\Forum\Libraries\User\KunenaUserHelper;

/**
 * Class AjaxDisplay
 *
 * @since   Kunena 4.0
 */
class AjaxDisplay extends KunenaControllerDisplay
{
    /**
     * @var \Kunena\Forum\Libraries\User\KunenaUser|null
     * @since version
     */
    private $me;

    /**
     * Return true if layout exists.
     *
     * @return  boolean
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     */
    public function exists()
    {
        return KunenaFactory::getTemplate()->isHmvc();
    }

    /**
     * Return AJAX for the requested layout.
     *
     * @return  string  String in JSON or RAW.
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     */
    public function execute()
    {
        $format   = $this->input->getWord('format', 'html');
        $function = 'display' . ucfirst($format);

        if (!method_exists($this, $function)) {
            // Invalid page request.
            throw new KunenaExceptionAuthorise(Text::_('COM_KUNENA_NO_ACCESS'), 404);
        }

        // Run before executing action.
        $result = $this->before();

        if ($result === false) {
            $content = new KunenaExceptionAuthorise(Text::_('COM_KUNENA_NO_ACCESS'), 404);
        }
        /*elseif (!Session::checkToken())
        {
            // Invalid access token.
            $content = new KunenaExceptionAuthorise(Text::_('COM_KUNENA_ERROR_TOKEN'), 403);
        }*/
        elseif ($this->config->boardOffline && !$this->me->isAdmin()) {
            // Forum is offline.
            $content = new KunenaExceptionAuthorise(Text::_('COM_KUNENA_FORUM_IS_OFFLINE'), 503);
        } elseif ($this->config->regOnly && !$this->me->exists()) {
            // Forum is for registered users only.
            $content = new KunenaExceptionAuthorise(Text::_('COM_KUNENA_LOGIN_NOTIFICATION'), 401);
        } else {
            $display = $this->input->getCmd('display', 'Undefined') . '/' . $format . '/Display';

            try {
                $content = KunenaRequest::factory($display, $this->input, $this->options)
                    ->setPrimary()->execute()->render();
            } catch (Exception $e) {
                $content = $e;
            }
        }

        return $this->$function($content);
    }

    /**
     * Prepare AJAX display.
     *
     * @return  void
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     */
    protected function before()
    {
        // Load language files.
        KunenaFactory::loadLanguage('com_kunena.sys', 'admin');
        KunenaFactory::loadLanguage('com_kunena.templates');
        KunenaFactory::loadLanguage('com_kunena.models');
        KunenaFactory::loadLanguage('com_kunena.views');

        $this->me       = KunenaUserHelper::getMyself();
        $this->config   = KunenaConfig::getInstance();
        $this->document = $this->app->getDocument();
        $template       = KunenaFactory::getTemplate();
        $template->initialize();
    }

    /**
     * Display output as RAW.
     *
     * @param   mixed  $content  Content to be returned.
     *
     * @return  string
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     */
    public function displayRaw($content)
    {
        if ($content instanceof Exception) {
            $this->setResponseStatus($content->getCode());

            return $content->getCode() . ' ' . $content->getMessage();
        }

        return (string) $content;
    }

    /**
     * Display output as JSON.
     *
     * @param   mixed  $content  Content to be returned.
     *
     * @return  void
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     */
    public function displayJson($content)
    {
        // Tell the browser that our response is in JSON.
        header('Content-type: application/json', true);

        // Create JSON response.
        $response = new KunenaResponseJson($content);

        // In case of an error we want to set HTTP error code.
        if (!$response->success) {
            // We want to wrap the exception to be able to display correct HTTP status code.
            $error = new KunenaExceptionAuthorise($response->message, $response->code);
            header('HTTP/1.1 ' . $error->getResponseStatus(), true);
        }

        echo json_encode($response);

        // It's much faster and safer to exit now than let Joomla to send the response.
        $this->app->close();
    }
}
