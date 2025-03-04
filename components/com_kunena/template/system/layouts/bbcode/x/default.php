<?php

/**
 * Kunena Component
 *
 * @package         Kunena.Template.System
 * @subpackage      Layout.BBCode
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

namespace Kunena\Forum\Site;

\defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Kunena\Forum\Libraries\Date\KunenaDate;

// [tweet]112233445566[/tweet]

// Display individual tweet.
?>

<div id="kunena_x_widget"
     style="background: none repeat scroll 0 0 #fff;    border-radius: 5px;    padding: 8px 8px 0;"
     class="root ltr x-tweet not-touch var-narrow" lang="en" data-scribe="page:tweet"
     data-iframe-title="Embedded Tweet" data-dt-pm="PM"
     data-dt-am="AM" data-dt-full="%{hours12}:%{minutes} %{amPm} - %{day} %{month} %{year}"
     data-dt-months="Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec" dir="ltr" data-x-event-id="4">
    <blockquote class="tweet subject expanded h-entry" data-scribe="section:subject"
                cite="https://x.com/<?php echo $this->user_name ?>/status/<?php echo $this->tweetid ?>"
                data-tweet-id="<?php echo $this->tweetid ?>">
        <div class="header">
            <div class="h-card p-author with-verification" data-scribe="component:author">
                <a class="u-url profile" data-scribe="element:user_link"
                   aria-label="<?php echo $this->user_name ?> (screen name: <?php echo $this->user_screen_name ?>)"
                   href="https://x.com/<?php echo $this->user_screen_name ?>">
                    <img loading=lazy class="u-photo avatar" data-scribe="element:avatar"
                         data-src-2x="<?php echo $this->user_profile_url_big ?>"
                         src="<?php echo $this->user_profile_url_normal ?>" alt="<?php echo $this->user_name ?>">
                    <span class="full-name">
                    <span class="p-name customisable-highlight"
                          data-scribe="element:name"><?php echo $this->user_name ?></span>
                        <?php if ($this->verified) :
                            ?>
                            <span class="verified" data-scribe="element:verified_badge" aria-label="Verified Account"
                                  data-bs-toggle="tooltip" title="Verified Account">
                            <b>✔</b>
                        </span>
                        <?php endif; ?>
                </span>
                    <span class="p-nickname" data-scribe="element:screen_name" dir="ltr">
                    @<b><?php echo $this->user_screen_name ?></b>
                </span>
                </a>
            </div>
            <div class="content e-entry-content" data-scribe="component:tweet">
                <p class="e-entry-title">
                    <?php echo $this->tweet_text ?>
                </p>
                <div class="dateline collapsible-container">
                    <a class="u-url customisable-highlight long-permalink" data-scribe="element:full_timestamp"
                       data-datetime="<?php echo Factory::getDate($this->tweet_created_at)->toISO8601(); ?>"
                       href="https://x.com/<?php echo $this->user_name ?>/status/<?php echo $this->tweetid ?>">
                        <time class="dt-updated"
                              data-bs-toggle="tooltip" title="Time posted: <?php echo KunenaDate::getInstance($this->tweet_created_at)->toKunena('ago'); ?>"
                              datetime="<?php echo Factory::getDate($this->tweet_created_at)->toISO8601(); ?>">
                            <?php echo KunenaDate::getInstance($this->tweet_created_at)->toKunena('datetime'); ?>
                        </time>
                    </a>
                </div>
            </div>
            <div class="footer customisable-border" data-scribe="component:footer">
            <span class="stats-narrow customisable-border">
                <span class="stats" data-scribe="component:stats">
                    <a data-scribe="element:retweet_count" data-bs-toggle="tooltip" title="View Tweet on X"
                       href="https://x.com/<?php echo $this->user_screen_name ?>/status/<?php echo $this->tweetid ?>">
                        <span class="stats-retweets">
                            <strong><?php echo $this->retweet_count; ?></strong>
                            Retweets
                        </span>
                    </a>
                    <a data-scribe="element:favorite_count" data-bs-toggle="tooltip" title="View Tweet on X"
                       href="https://x.com/<?php echo $this->user_screen_name ?>/status/<?php echo $this->tweetid ?>">
                        <span class="stats-favorites">
                            <strong><?php echo $this->favorite_count; ?></strong>
                            favorites
                        </span>
                    </a>
                </span>
            </span>
                <ul class="tweet-actions" data-scribe="component:actions" aria-label="Tweet actions" role="menu">
                    <li>
                        <a class="reply-action web-intent" data-scribe="element:reply" data-bs-toggle="tooltip" title="Reply"
                           href="https://x.com/intent/tweet?in_reply_to=<?php echo $this->tweetid ?>">
                            <i class="ic-reply ic-mask"></i>
                            <b>Reply</b>
                        </a>
                    </li>
                    <li>
                        <a class="retweet-action web-intent" data-scribe="element:retweet" data-bs-toggle="tooltip" title="Retweet"
                           href="https://x.com/intent/retweet?tweet_id=<?php echo $this->tweetid ?>">
                            <i class="ic-retweet ic-mask"></i>
                            <b>Retweet</b>
                        </a>
                    </li>
                    <li>
                        <a class="favorite-action web-intent" data-scribe="element:favorite" title="Favorite"
                           href="https://x.com/intent/favorite?tweet_id=<?php echo $this->tweetid ?>">
                            <i class="ic-fav ic-mask"></i>
                            <b>Favorite</b>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </blockquote>
</div>
