<?php
/**
 * Publications Module
 *
 * @package modules
 * @subpackage publications module
 * @category Third Party Xaraya Module
 * @version 2.0.0
 * @copyright (C) 2012 Netspan AG
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @author mikespub
 * @author Marc Lutolf <mfl@netspan.ch>
 */
/**
 * initialise block
 * @author Jim McDonald
 */
sys::import('modules.publications.xarblocks.related');

class Publications_RelatedBlockAdmin extends Publications_RelatedBlock
{
    public function modify(array $data = [])
    {
        $data = $this->getContent();

        return $data;
    }

    public function update($data = [])
    {
        $args = [];
        $this->var()->find('numitems', $args['numitems'], 'int', $this->numitems);
        $this->var()->find('showvalue', $args['showvalue'], 'checkbox', 0);

        $this->var()->find('showpubtype', $args['showpubtype'], 'checkbox', 0);
        $this->var()->find('showcategory', $args['showcategory'], 'checkbox', 0);
        $this->var()->find('showauthor', $args['showauthor'], 'checkbox', 0);
        $this->setContent($args);
        return true;
    }
}
