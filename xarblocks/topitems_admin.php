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
 * initialise block
 * @author Jim McDonald
 */

class Publications_TopitemsBlockAdmin extends Publications_TopitemsBlock
{
    public function modify(array $data = [])
    {
        $data = $this->getContent();
        return $data;
    }

    public function update($data = [])
    {
        $args = [];

        $this->var()->find('numitems', $args['numitems'], 'int:1:200', $this->numitems);
        $this->var()->find('pubtype_id', $args['pubtype_id'], 'id', $this->pubtype_id);
        $this->var()->find('linkpubtype', $args['linkpubtype'], 'checkbox', false);
        $this->var()->find('nopublimit', $args['nopublimit'], 'checkbox', false);
        $this->var()->find('catfilter', $args['catfilter'], 'id', $this->catfilter);
        $this->var()->find('includechildren', $args['includechildren'], 'checkbox', false);
        $this->var()->find('nocatlimit', $args['nocatlimit'], 'checkbox', false);
        $this->var()->find('linkcat', $args['linkcat'], 'checkbox', false);
        $this->var()->find('dynamictitle', $args['dynamictitle'], 'checkbox', false);
        $this->var()->find('showsummary', $args['showsummary'], 'checkbox', false);
        $this->var()->find('showdynamic', $args['showdynamic'], 'checkbox', false);
        $this->var()->find('showvalue', $args['showvalue'], 'checkbox', false);
        $this->var()->find('pubstate', $args['pubstate'], 'strlist:,:int:1:4', $this->pubstate);
        $this->var()->find('toptype', $args['toptype'], 'enum:author:date:hits:rating:title', $this->toptype);

        if ($args['nopublimit'] == true) {
            $args['pubtype_id'] = 0;
        }
        if ($args['nocatlimit']) {
            $args['catfilter'] = 1;
            $args['includechildren'] = 0;
        }
        if ($args['includechildren']) {
            $args['linkcat'] = 0;
        }
        $this->setContent($args);
        return true;
    }
}
