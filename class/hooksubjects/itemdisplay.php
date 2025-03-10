<?php

/**
 * ItemDisplay hook Subject
 *
 * Handles item display hook observers (these typically return string of template data)
**/
/**
 * GUI type hook, observers should return string template data
 *
 * The notify method returns an array of hookoutputs keyed by hooked module name
 * Called in display function as...
 * $item = array('module' => $module, $itemid => $itemid [, 'itemtype' => $itemtype, ...]);
 * New way of calling hooks
 * $data['hooks'] = xarHooks::notify('ItemDisplay', $item);
 * Legacy way, supported for now, deprecated in future
 * $data['hooks'] = xarModHooks::call('item', 'display', $itemid, $item);
 * Output in display template as
 * <xar:foreach in="$hooks" key="$hookmod" value="$hookoutput">
 *     #$hookoutput#
 * </xar:foreach>
**/
sys::import('xaraya.structures.hooks.guisubject');
class PublicationsItemDisplaySubject extends GuiHookSubject
{
    public $subject = 'ItemDisplay';

    public function __construct($args = [])
    {
        // pass args to parent constructor, it validates module and extrainfo values
        parent::__construct($args);
        // get args populated by constuctor array('objectid', 'extrainfo')
        $args = $this->getArgs();
        // Item observers expect an objectid, if it isn't valid it's pointless notifying them, bail
        if (!isset($args['objectid']) || !is_numeric($args['objectid'])) {
            throw new BadParameterException('objectid');
        }
        // From this point on, any observers notified can safely assume arguments are valid
        // API and GUI observers will be passed $this->getArgs()
        // Class observers can obtain the same args from $subject->getArgs() or
        // just retrieve extrainfo from $subject->getExtrainfo()
    }
}
