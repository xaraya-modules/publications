<?php

/**
 * @package modules\publications
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Publications\UserApi;


use Xaraya\Modules\Publications\UserApi;
use Xaraya\Modules\MethodClass;
use xarMod;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications userapi getpubfieldformats function
 * @extends MethodClass<UserApi>
 */
class GetpubfieldformatsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get array of field formats for publication types
     * @TODO : move this to some common place in Xaraya (base module ?)
     * + replace with dynamic_propertytypes table
     *
     * + extend with other pre-defined formats
     * @return array array('static'  => $this->translate('Static Text'),
     * 'textbox' => $this->translate('Text Box'),
     * ...);
     */
    public function __invoke(array $args = [])
    {
        $fieldlist = [
            'static'          => $this->translate('Static Text'),
            'textbox'         => $this->translate('Text Box'),
            'textarea'  => $this->translate('Small Text Area'),
            'textarea_medium' => $this->translate('Medium Text Area'),
            'textarea_large'  => $this->translate('Large Text Area'),
            'dropdown'        => $this->translate('Dropdown List'),
            'textupload'      => $this->translate('Text Upload'),
            'fileupload'      => $this->translate('File Upload'),
            'url'             => $this->translate('URL'),
            'urltitle'        => $this->translate('URL + Title'),
            'image'           => $this->translate('Image'),
            'imagelist'       => $this->translate('Image List'),
            'calendar'        => $this->translate('Calendar'),
            'webpage'         => $this->translate('HTML Page'),
            'username'        => $this->translate('Username'),
            'userlist'        => $this->translate('User List'),
            'state'          => $this->translate('Status'),
            'locale'        => $this->translate('Language List'),
            // TODO: add more property types after testing
            //other 'text' DD property types won't give significant performance hits
        ];

        // Add  'text' dd properites that are dependent on module availability
        $extrafields = [];
        if (xarMod::isAvailable('tinymce')) {
            $extrafields = ['tinymce' => $this->translate('TinyMCE GUI')];
            $fieldlist = array_merge($fieldlist, $extrafields);
        }

        return $fieldlist;
    }
}
