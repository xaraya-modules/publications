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
     * @return array array('static'  => $this->ml('Static Text'),
     * 'textbox' => $this->ml('Text Box'),
     * ...);
     */
    public function __invoke(array $args = [])
    {
        $fieldlist = [
            'static'          => $this->ml('Static Text'),
            'textbox'         => $this->ml('Text Box'),
            'textarea'  => $this->ml('Small Text Area'),
            'textarea_medium' => $this->ml('Medium Text Area'),
            'textarea_large'  => $this->ml('Large Text Area'),
            'dropdown'        => $this->ml('Dropdown List'),
            'textupload'      => $this->ml('Text Upload'),
            'fileupload'      => $this->ml('File Upload'),
            'url'             => $this->ml('URL'),
            'urltitle'        => $this->ml('URL + Title'),
            'image'           => $this->ml('Image'),
            'imagelist'       => $this->ml('Image List'),
            'calendar'        => $this->ml('Calendar'),
            'webpage'         => $this->ml('HTML Page'),
            'username'        => $this->ml('Username'),
            'userlist'        => $this->ml('User List'),
            'state'          => $this->ml('Status'),
            'locale'        => $this->ml('Language List'),
            // TODO: add more property types after testing
            //other 'text' DD property types won't give significant performance hits
        ];

        // Add  'text' dd properites that are dependent on module availability
        $extrafields = [];
        if ($this->mod()->isAvailable('tinymce')) {
            $extrafields = ['tinymce' => $this->ml('TinyMCE GUI')];
            $fieldlist = array_merge($fieldlist, $extrafields);
        }

        return $fieldlist;
    }
}
