<?php

/**
 * Kunena Component
 *
 * @package         Kunena.Site
 * @subpackage      Controller.Topic
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

namespace Kunena\Forum\Site\Controller\Topic\Report;

\defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Language\Text;
use Kunena\Forum\Libraries\Controller\KunenaControllerDisplay;
use Kunena\Forum\Libraries\Exception\KunenaExceptionAuthorise;
use Kunena\Forum\Libraries\Forum\Message\KunenaMessage;
use Kunena\Forum\Libraries\Forum\Message\KunenaMessageHelper;
use Kunena\Forum\Libraries\Forum\Topic\KunenaTopic;
use Kunena\Forum\Libraries\Forum\Topic\KunenaTopicHelper;
use Kunena\Forum\Libraries\User\KunenaUserHelper;

/**
 * Class ComponentTopicControllerReportDisplay
 *
 * @since   Kunena 4.0
 */
class TopicReportDisplay extends KunenaControllerDisplay
{
    /**
     * @var     KunenaTopic
     * @since   Kunena 6.0
     */
    public $topic;

    /**
     * @var     KunenaMessage|null
     * @since   Kunena 6.0
     */
    public $message;

    /**
     * @var     string
     * @since   Kunena 6.0
     */
    public $uri;

    /**
     * @var     string
     * @since   Kunena 6.0
     */
    protected $name = 'Topic/Report';

    public $category;

    /**
     * Prepare report message form.
     *
     * @return  void
     *
     * @throws  null
     * @since   Kunena 6.0
     */
    protected function before()
    {
        parent::before();

        $id    = $this->input->getInt('id');
        $mesid = $this->input->getInt('mesid');

        $me = KunenaUserHelper::getMyself();

        if (!$this->config->reportMsg) {
            // Deny access if report feature has been disabled.
            throw new KunenaExceptionAuthorise(Text::_('COM_KUNENA_NO_ACCESS'), 404);
        }

        if (!$me->exists()) {
            // Deny access if user is guest.
            throw new KunenaExceptionAuthorise(Text::_('COM_KUNENA_NO_ACCESS'), 401);
        }

        if (!$mesid) {
            $this->topic = KunenaTopicHelper::get($id);
            $this->topic->tryAuthorise();
        } else {
            $this->message = KunenaMessageHelper::get($mesid);
            $this->message->tryAuthorise();
            $this->topic = $this->message->getTopic();
        }

        $this->category = $this->topic->getCategory();

        $this->uri = "index.php?option=com_kunena&view=topic&layout=report&catid={$this->category->id}" .
            "&id={$this->topic->id}" . ($this->message ? "&mesid={$this->message->id}" : '');
    }

    /**
     * Prepare document.
     *
     * @return  void
     *
     * @throws  Exception
     * @since   Kunena 6.0
     */
    protected function prepareDocument()
    {
        $menu_item = $this->app->getMenu()->getActive();

        if ($menu_item) {
            $params             = $menu_item->getParams();
            $params_title       = $params->get('page_title');
            $params_description = $params->get('menu-meta_description');

            if (!empty($params_title)) {
                $title = $params->get('page_title');
                $this->setTitle($title);
            } else {
                $this->setTitle(Text::_('COM_KUNENA_REPORT_TO_MODERATOR'));
            }

            if (!empty($params_description)) {
                $description = $params->get('menu-meta_description');
                $this->setDescription($description);
            } else {
                $this->setDescription(Text::_('COM_KUNENA_REPORT_TO_MODERATOR'));
            }
        }
    }
}
