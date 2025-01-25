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
sys::import('modules.publications.xarblocks.random');

class Publications_RandomBlockAdmin extends Publications_RandomBlock
{
    public function modify(array $data = [])
    {
        $data = $this->getContent();
        if (!empty($data['catfilter'])) {
            $cidsarray = [$data['catfilter']];
        } else {
            $cidsarray = [];
        }

        $data['locales'] = xarMLS::listSiteLocales();
        asort($data['locales']);

        return $data;
    }

    public function update($data = [])
    {
        $this->var()->fetch('locale', 'str', $data['locale'], '', xarVar::NOT_REQUIRED);
        $this->var()->fetch('alttitle', 'str', $data['alttitle'], '', xarVar::NOT_REQUIRED);
        $this->var()->fetch('altsummary', 'str', $data['altsummary'], '', xarVar::NOT_REQUIRED);
        $this->var()->fetch('showtitle', 'checkbox', $data['showtitle'], false, xarVar::NOT_REQUIRED);
        $this->var()->fetch('showsummary', 'checkbox', $data['showsummary'], false, xarVar::NOT_REQUIRED);
        $this->var()->fetch('showpubdate', 'checkbox', $data['showpubdate'], false, xarVar::NOT_REQUIRED);
        $this->var()->fetch('showauthor', 'checkbox', $data['showauthor'], false, xarVar::NOT_REQUIRED);
        $this->var()->fetch('showsubmit', 'checkbox', $data['showsubmit'], false, xarVar::NOT_REQUIRED);
        $this->setContent($data);
        return true;
    }
}
