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
 * @author Marc Lutolf <mfl@netspan.ch>
 */

sys::import('modules.publications.xarblocks.filler');

class Publications_FillerBlockAdmin extends Publications_FillerBlock
{
    public function modify()
    {
        $data = $this->getContent();

        if (!is_array($data['pubstate'])) {
            $statearray = [$data['pubstate']];
        } else {
            $statearray = $data['pubstate'];
        }

        // Only include pubtype if a specific pubtype is selected
        if (!empty($data['pubtype_id'])) {
            $article_args['ptid'] = $data['pubtype_id'];
        }

        // Add the rest of the arguments
        $article_args['state'] = $statearray;

        $data['filtereditems'] = $this->mod()->apiMethod(
            'publications',
            'user',
            'getall',
            $article_args
        );

        $data['pubtypes'] = $this->mod()->apiMethod('publications', 'user', 'get_pubtypes');
        $data['stateoptions'] = [
            ['id' => '', 'name' => $this->ml('All Published')],
            ['id' => '3', 'name' => $this->ml('Frontpage')],
            ['id' => '2', 'name' => $this->ml('Approved')],
        ];

        return $data;
    }

    public function update($data = [])
    {
        $args = [];
        $this->var()->fetch('pubtype_id', 'int', $args['pubtype_id'], $this->pubtype_id, xarVar::NOT_REQUIRED);
        $this->var()->fetch('pubstate', 'str', $args['pubstate'], $this->pubstate, xarVar::NOT_REQUIRED);
        $this->var()->fetch('displaytype', 'str', $args['displaytype'], $this->displaytype, xarVar::NOT_REQUIRED);
        $this->var()->fetch('fillerid', 'id', $args['fillerid'], $this->fillerid, xarVar::NOT_REQUIRED);
        $this->var()->fetch('alttitle', 'str', $args['alttitle'], $this->alttitle, xarVar::NOT_REQUIRED);
        $this->var()->fetch('alttext', 'str', $args['alttext'], $this->alttext, xarVar::NOT_REQUIRED);
        $this->setContent($args);
        return true;
    }
}
