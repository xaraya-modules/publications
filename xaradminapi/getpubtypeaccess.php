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

sys::import('modules.dynamicdata.class.objects.factory');

function publications_adminapi_getpubtypeaccess(array $args = [], $context = null)
{
    if (!isset($args['ptid'])) {
        throw new Exception(xarML('Missing ptid param in publications_adminapi_getpubtypeaccess'));
    }

    $pubtypeobject = DataObjectFactory::getObject(['name' => 'publications_types']);
    if (null == $pubtypeobject) {
        return false;
    }

    $pubtypeobject->getItem(['itemid' => $args['ptid']]);
    if (empty($pubtypeobject->properties['access']->value)) {
        return "a:0:{}";
    }

    return $pubtypeobject->properties['access']->value;
}
