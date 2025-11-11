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
        $this->var()->find('pubtype_id', $args['pubtype_id'], 'int', 0);
        $this->var()->find('catfilter', $args['catfilter'], 'id', $this->catfilter);
        $this->var()->find('nocatlimit', $args['nocatlimit'], 'checkbox', $this->nocatlimit);
        $this->var()->find('pubstate', $args['pubstate'], 'str', $this->pubstate);
        $this->var()->find('itemlimit', $args['itemlimit'], 'int:1', $this->itemlimit);
        $this->var()->find('toptype', $args['toptype'], 'enum:author:date:hits:rating:title', $this->toptype);
        $this->var()->find('featuredid', $args['featuredid'], 'int', $this->featuredid);
        $this->var()->find('alttitle', $args['alttitle'], 'str', $this->alttitle);
        $this->var()->find('altsummary', $args['altsummary'], 'str', $this->altsummary);
        $this->var()->find('showfeaturedbod', $args['showfeaturedbod'], 'checkbox', 0);
        $this->var()->find('showfeaturedsum', $args['showfeaturedsum'], 'checkbox', 0);
        $this->var()->find('showsummary', $args['showsummary'], 'checkbox', 0);
        $this->var()->find('showvalue', $args['showvalue'], 'checkbox', 0);
        $this->var()->find('linkpubtype', $args['linkpubtype'], 'checkbox', 0);
        $this->var()->find('linkcat', $args['linkcat'], 'checkbox', 0);

        $multiselect = $this->prop()->getProperty(['name' => 'multiselect']);
        // We cheat a bit here. Allowing override means we don't need to load the options
        $multiselect->validation_override = true;
        $multiselect->checkInput('moreitems');
        $args['moreitems'] = $multiselect->getValue();

        $this->setContent($args);
        return true;
    }
}
