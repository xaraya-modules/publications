<?php
/**
 * Publications Module
 *
 * @package modules
 * @subpackage publications module
 * @category Third Party Xaraya Module
 * @version 2.0.0
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @author mikespub
 */
/**
 * modify block settings
 * @author Jonn Beames et al
 */

sys::import('modules.publications.xarblocks.featureditems');

class Publications_FeatureditemsBlockAdmin extends Publications_FeatureditemsBlock
{
    public function modify()
    {
        $data = $this->getContent();

        $data['fields'] = ['id', 'name'];

        if (!is_array($data['pubstate'])) {
            $statearray = [$data['pubstate']];
        } else {
            $statearray = $data['pubstate'];
        }

        if (!empty($data['catfilter'])) {
            $cidsarray = [$data['catfilter']];
        } else {
            $cidsarray = [];
        }

        # ------------------------------------------------------------
        # Set up the different conditions for getting the items that can be featured
        #
        $conditions = [];

        // Only include pubtype if a specific pubtype is selected
        if (!empty($data['pubtype_id'])) {
            $conditions['ptid'] = $data['pubtype_id'];
        }

        // If itemlimit is set to 0, then don't pass to getall
        if ($data['itemlimit'] != 0) {
            $conditions['numitems'] = $data['itemlimit'];
        }

        // Add the rest of the arguments
        $conditions['cids'] = $cidsarray;
        $conditions['enddate'] = time();
        $conditions['state'] = $statearray;
        $conditions['fields'] = $data['fields'];
        $conditions['sort'] = $data['toptype'];

        # ------------------------------------------------------------
        # Get the items for the dropdown based on the conditions
        #
        $items = $this->mod()->apiMethod('publications', 'user', 'getall', $conditions);

        // Limit the titles to less than 50 characters
        $data['filtereditems'] = [];
        foreach ($items as $key => $value) {
            if (strlen($value['title']) > 50) {
                $value['title'] = substr($value['title'], 0, 47) . '...';
            }
            $value['original_name'] = $value['name'];
            $value['name'] = $value['title'];
            $data['filtereditems'][$value['id']] = $value;
        }

        // Remove the featured item and reuse the items for the additional headlines multiselect
        $data['morepublications'] = $data['filtereditems'];
        unset($data['morepublications'][$this->featuredid]);

        # ------------------------------------------------------------
        # Get the data for other dropdowns
        #
        $data['pubtypes'] = $this->mod()->apiMethod('publications', 'user', 'get_pubtypes');
        $data['categorylist'] = $this->mod()->apiFunc('categories', 'user', 'getcat');
        $data['sortoptions'] = [
            ['id' => 'author', 'name' => $this->ml('Author')],
            ['id' => 'date', 'name' => $this->ml('Date')],
            ['id' => 'hits', 'name' => $this->ml('Hit Count')],
            ['id' => 'rating', 'name' => $this->ml('Rating')],
            ['id' => 'title', 'name' => $this->ml('Title')],
        ];

        return $data;
    }

    public function update($data = [])
    {
        $args = [];
        $this->var()->fetch('pubtype_id', 'int', $args['pubtype_id'], 0, xarVar::NOT_REQUIRED);
        $this->var()->fetch('catfilter', 'id', $args['catfilter'], $this->catfilter, xarVar::NOT_REQUIRED);
        $this->var()->fetch('nocatlimit', 'checkbox', $args['nocatlimit'], $this->nocatlimit, xarVar::NOT_REQUIRED);
        $this->var()->fetch('pubstate', 'str', $args['pubstate'], $this->pubstate, xarVar::NOT_REQUIRED);
        $this->var()->fetch('itemlimit', 'int:1', $args['itemlimit'], $this->itemlimit, xarVar::NOT_REQUIRED);
        $this->var()->fetch('toptype', 'enum:author:date:hits:rating:title', $args['toptype'], $this->toptype, xarVar::NOT_REQUIRED);
        $this->var()->fetch('featuredid', 'int', $args['featuredid'], $this->featuredid, xarVar::NOT_REQUIRED);
        $this->var()->fetch('alttitle', 'str', $args['alttitle'], $this->alttitle, xarVar::NOT_REQUIRED);
        $this->var()->fetch('altsummary', 'str', $args['altsummary'], $this->altsummary, xarVar::NOT_REQUIRED);
        $this->var()->fetch('showfeaturedbod', 'checkbox', $args['showfeaturedbod'], 0, xarVar::NOT_REQUIRED);
        $this->var()->fetch('showfeaturedsum', 'checkbox', $args['showfeaturedsum'], 0, xarVar::NOT_REQUIRED);
        $this->var()->fetch('showsummary', 'checkbox', $args['showsummary'], 0, xarVar::NOT_REQUIRED);
        $this->var()->fetch('showvalue', 'checkbox', $args['showvalue'], 0, xarVar::NOT_REQUIRED);
        $this->var()->fetch('linkpubtype', 'checkbox', $args['linkpubtype'], 0, xarVar::NOT_REQUIRED);
        $this->var()->fetch('linkcat', 'checkbox', $args['linkcat'], 0, xarVar::NOT_REQUIRED);

        sys::import('modules.dynamicdata.class.properties.master');
        $multiselect = $this->prop()->getProperty(['name' => 'multiselect']);
        // We cheat a bit here. Allowing override means we don't need to load the options
        $multiselect->validation_override = true;
        $multiselect->checkInput('moreitems');
        $args['moreitems'] = $multiselect->getValue();

        $this->setContent($args);
        return true;
    }
}
