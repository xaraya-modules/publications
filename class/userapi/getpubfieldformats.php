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
     * @return array array('static'  => xarML('Static Text'),
     * 'textbox' => xarML('Text Box'),
     * ...);
     */
    public function __invoke(array $args = [])
    {
        $fieldlist = [
            'static'          => xarML('Static Text'),
            'textbox'         => xarML('Text Box'),
            'textarea'  => xarML('Small Text Area'),
            'textarea_medium' => xarML('Medium Text Area'),
            'textarea_large'  => xarML('Large Text Area'),
            'dropdown'        => xarML('Dropdown List'),
            'textupload'      => xarML('Text Upload'),
            'fileupload'      => xarML('File Upload'),
            'url'             => xarML('URL'),
            'urltitle'        => xarML('URL + Title'),
            'image'           => xarML('Image'),
            'imagelist'       => xarML('Image List'),
            'calendar'        => xarML('Calendar'),
            'webpage'         => xarML('HTML Page'),
            'username'        => xarML('Username'),
            'userlist'        => xarML('User List'),
            'state'          => xarML('Status'),
            'locale'        => xarML('Language List'),
            // TODO: add more property types after testing
            //other 'text' DD property types won't give significant performance hits
        ];

        // Add  'text' dd properites that are dependent on module availability
        $extrafields = [];
        if (xarMod::isAvailable('tinymce')) {
            $extrafields = ['tinymce' => xarML('TinyMCE GUI')];
            $fieldlist = array_merge($fieldlist, $extrafields);
        }

        return $fieldlist;
    }
}
