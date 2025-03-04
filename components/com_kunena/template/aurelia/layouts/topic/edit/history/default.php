<?php
/**
 * Kunena Component
 *
 * @package         Kunena.Template.Aurelia
 * @subpackage      Topic
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/
namespace Kunena\Forum\Site;
\defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Kunena\Forum\Libraries\Factory\KunenaFactory;
use Kunena\Forum\Libraries\Html\KunenaParser;
use Kunena\Forum\Libraries\Icons\KunenaIcons;
?>
<div class="float-end">
    <div class="btn btn-outline-primary border btn-small" data-bs-toggle="collapse" data-bs-target="#history">X</div>
</div>
<h3>
    <?php echo Text::_('COM_KUNENA_POST_TOPIC_HISTORY') ?>:
    <?php echo $this->escape($this->topic->subject) ?>
</h3>
<div id="history" class="collapse show">
    <p>
        <?php echo Text::_('COM_KUNENA_POST_TOPIC_HISTORY_MAX') . ' ' . $this->escape($this->config->historyLimit) . ' ' . Text::_('COM_KUNENA_POST_TOPIC_HISTORY_LAST') ?>
    </p>
    <?php foreach ($this->history as $this->message) : ?>
        <div class="row">
            <div class="col-md-2 center">
                <ul class="unstyled center profilebox">
                    <li>
                        <strong><?php echo $this->message->getAuthor()->getLink(null, null, '', '', null, $this->topic->getcategory()->id) ?></strong>
                    </li>
                    <li>
                        <?php
                        $profile    = KunenaFactory::getUser(\intval($this->message->userid));
                        $useravatar = $profile->getAvatarImage(KunenaFactory::getTemplate()->params->get('avatarType'), 'profile');
                        if ($useravatar) :
                            echo $this->message->getAuthor()->getLink($useravatar, null, '', '', null, $this->topic->getcategory()->id);
                        endif;
                        ?>
                    </li>
                </ul>
            </div>
            <div class="col-md-10">
                <small class="text-muted float-end d-none d-sm-block" style="margin-top:-5px;">
                    <?php echo KunenaIcons::clock(); ?><?php echo $this->message->getTime()->toSpan('config_postDateFormat', 'config_postDateFormatHover'); ?>
                </small>
                <div class="badger-left badger-info khistory"
                     data-badger="<?php echo $this->message->displayField('subject'); ?>">
                    <div class="kmessage">
                        <p class="kmsg"><?php echo KunenaParser::parseBBCode($this->message->message, $this) ?></p>
                    </div>
                    <?php
                    $attachments = $this->message->getAttachments();
                    $hasVisibleAttachments = false;
                    
                    // Filter out private attachments
                    $visibleAttachments = array_filter($attachments, function($attachment) {
                        return $attachment->protected != 32; // Exclude private attachments
                    });
                    
                    if (!empty($visibleAttachments)) :
                        foreach ($visibleAttachments as $attachment) {
                            if (!$attachment->inline) {
                                $hasVisibleAttachments = true;
                                break;
                            }
                        }
                    endif;

                    if ($hasVisibleAttachments) : ?>
                        <div class="card pb-3 pd-3 mb-3">
                            <div class="card-header">
                                <?php echo Text::_('COM_KUNENA_ATTACHMENTS'); ?>
                            </div>
                            <div class="card-body kattach">
                                <ul class="thumbnails">
                                    <?php foreach ($visibleAttachments as $attachment) :
                                        if (!$attachment->inline) : ?>
                                            <li class="col-md-3 text-center">
                                                <div class="thumbnail">
                                                    <?php echo $attachment->getLayout()->render('thumbnail'); ?>
                                                    <?php echo $attachment->getLayout()->render('textlink'); ?>
                                                </div>
                                            </li>
                                        <?php endif;
                                    endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
