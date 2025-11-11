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
        $this->var()->find('locale', $data['locale'], 'str', '');
        $this->var()->find('alttitle', $data['alttitle'], 'str', '');
        $this->var()->find('altsummary', $data['altsummary'], 'str', '');
        $this->var()->find('showtitle', $data['showtitle'], 'checkbox', false);
        $this->var()->find('showsummary', $data['showsummary'], 'checkbox', false);
        $this->var()->find('showpubdate', $data['showpubdate'], 'checkbox', false);
        $this->var()->find('showauthor', $data['showauthor'], 'checkbox', false);
        $this->var()->find('showsubmit', $data['showsubmit'], 'checkbox', false);
        $this->setContent($data);
        return true;
    }
}
