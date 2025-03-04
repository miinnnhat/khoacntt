<?php

/**
 * Kunena Component
 *
 * @package         Kunena.Site
 * @subpackage      Controller.User
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

namespace Kunena\Forum\Site\Controller\User\Edit\Avatar;

\defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\String\StringHelper;
use Kunena\Forum\Libraries\Exception\KunenaExceptionAuthorise;
use Kunena\Forum\Libraries\Factory\KunenaFactory;
use Kunena\Forum\Libraries\Integration\KunenaAvatar;
use Kunena\Forum\Site\Controller\User\Edit\UserEditDisplay;

/**
 * Class ComponentUserControllerEditAvatarDisplay
 *
 * @since   Kunena 4.0
 */
class UserEditAvatarDisplay extends UserEditDisplay
{
    /**
     * @var     string
     * @since   Kunena 6.0
     */
    public $gallery;

    /**
     * @var     array
     * @since   Kunena 6.0
     */
    public $galleries;

    /**
     * @var     object
     * @since   Kunena 6.0
     */
    public $galleryOptions;

    /**
     * @var     object
     * @since   Kunena 6.0
     */
    public $galleryImages;

    /**
     * @var     string
     * @since   Kunena 6.0
     */
    public $headerText;

    /**
     * @var     string
     * @since   Kunena 6.0
     */
    protected $name = 'User/Edit/Avatar';

    /**
     * @var     string
     * @since   Kunena 6.0
     */
    protected $imageFilter = '(\.gif|\.png|\.jpg|\.jpeg)$';

    public $avatar;

    public $galleryUri;

    public $doc;

    public $wa;

    /**
     * Prepare avatar form.
     *
     * @return  void
     *
     * @throws  null
     * @since   Kunena 6.0
     */
    protected function before()
    {
        parent::before();

        $this->avatar = KunenaFactory::getAvatarIntegration();

        if (!($this->avatar instanceof KunenaAvatar)) {
            throw new KunenaExceptionAuthorise(Text::_('COM_KUNENA_AUTH_ERROR_USER_EDIT_AVATARS'), 404);
        }

        $path                 = JPATH_ROOT . '/media/kunena/avatars/gallery';
        $this->gallery        = $this->input->getString('gallery', '');
        $this->galleries      = $this->getGalleries($path);
        $this->galleryOptions = $this->getGalleryOptions();
        $this->galleryImages  = isset($this->galleries[$this->gallery])
            ? $this->galleries[$this->gallery]
            : reset($this->galleries);
        $this->galleryUri     = Uri::root(true) . '/media/kunena/avatars/gallery';

        $this->headerText = Text::_('COM_KUNENA_PROFILE_EDIT_AVATAR_TITLE');

        $this->doc = $this->app->getDocument();
        $this->wa  = $this->doc->getWebAssetManager();
    }

    /**
     * Get avatar gallery directories.
     *
     * @param   string  $path  Absolute path for the gallery.
     *
     * @return  array|string[]  List of directories.
     *
     * @since   Kunena 6.0
     */
    protected function getGalleries($path)
    {
        $files  = [];
        $images = $this->getGallery($path);

        if ($images) {
            $files[''] = $images;
        }

        // TODO: Allow recursive paths.
        $folders = Folder::folders($path);

        foreach ($folders as $folder) {
            $images = $this->getGallery("{$path}/{$folder}");

            if ($images) {
                foreach ($images as $image) {
                    $files[$folder][] = "{$folder}/{$image}";
                }
            }
        }

        return $files;
    }

    /**
     * Get files from selected gallery.
     *
     * @param   string  $path  Absolute path for the gallery.
     *
     * @return  array
     *
     * @since   Kunena 6.0
     */
    protected function getGallery($path)
    {
        return Folder::files($path, $this->imageFilter);
    }

    /**
     * Get avatar galleries and make them select option list.
     *
     * @return  array|string[]  List of options.
     *
     * @since   Kunena 6.0
     */
    protected function getGalleryOptions()
    {
        $options = [];

        foreach ($this->galleries as $gallery => $files) {
            $text      = $gallery ? StringHelper::ucwords(str_replace('/', ' / ', $gallery)) : Text::_('COM_KUNENA_DEFAULT_GALLERY');
            $options[] = HTMLHelper::_('select.option', $gallery, $text);
        }

        return \count($options) > 1 ? $options : [];
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
        $this->setTitle($this->headerText);
    }
}
