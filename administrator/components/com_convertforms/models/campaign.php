<?php

/**
 * @package         Convert Forms
 * @version         4.4.8 Free
 * 
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            https://www.tassos.gr
 * @copyright       Copyright © 2024 Tassos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

defined('_JEXEC') or die('Restricted access');
 
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
 
/**
 * Campaign Model Class
 */
class ConvertFormsModelCampaign extends AdminModel
{
    /**
     * Returns a reference to the a Table object, always creating it.
     *
     * @param       type    The table type to instantiate
     * @param       string  A prefix for the table class name. Optional.
     * @param       array   Configuration array for model. Optional.
     * @return      Table   A database object
     * @since       2.5
     */
    public function getTable($type = 'Campaign', $prefix = 'ConvertFormsTable', $config = array()) 
    {
        return Table::getInstance($type, $prefix, $config);
    }

    /**
     * Method to get the record form.
     *
     * @param       array   $data           Data for the form.
     * @param       boolean $loadData       True if the form is to load its own data (default case), false if not.
     * @return      mixed   A JForm object on success, false on failure
     * @since       2.5
     */
    public function getForm($data = array(), $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm('com_convertforms.campaign', 'campaign', array('control' => 'jform', 'load_data' => $loadData));

        if (empty($form)) 
        {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return    mixed    The data for the form.
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $data = Factory::getApplication()->getUserState('com_convertforms.edit.campaign.data', array());

        if (empty($data))
        {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Method to validate form data.
     */
    public function validate($form, $data, $group = null)
    {
        $newdata = array();
        $params  = array();

        $this->_db->setQuery('SHOW COLUMNS FROM #__convertforms_campaigns');

        $dbkeys = $this->_db->loadObjectList('Field');
        $dbkeys = array_keys($dbkeys);

        foreach ($data as $key => $val)
        {
            if (in_array($key, $dbkeys))
            {
                $newdata[$key] = $val;
            }
            else
            {
                $params[$key] = $val;
            }
        }

        $newdata['params'] = json_encode($params);

        return $newdata;
    }

    /**
     *  [getItem description]
     *
     *  @param   [type]  $pk  [description]
     *
     *  @return  [type]       [description]
     */
    public function getItem($pk = null)
    {
        if ($item = parent::getItem($pk))
        {
            $params = $item->params;

            if (is_array($params) && count($params))
            {
                foreach ($params as $key => $value)
                {
                    if (!isset($item->$key) && !is_object($value))
                    {
                        $item->$key = $value;
                    }
                }
                unset($item->params);
            }
        }

        return $item;
    }

    /**
     * Method to copy an item
     *
     * @access    public
     * @return    boolean    True on success
     */
    function copy($id)
    {
        $item = $this->getItem($id);

        $item->id = 0;
        $item->published = 0;
        $item->name = Text::sprintf('NR_COPY_OF', $item->name);

        $item = $this->validate(null, get_object_vars($item));

        return ($this->save($item));
    }

}

