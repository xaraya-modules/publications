<?php

/**
 * @package modules\publications
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Publications;

/**
 * Defines constants for the publications module (from xaruser.php and xartreeapi/array_maptree.php)
 */
class Defines
{
    public const STATE_DELETED = 0;
    public const STATE_INACTIVE = 1;
    public const STATE_SUBMITTED = 1;
    public const STATE_DRAFT = 2;
    public const STATE_ONHOLD = 2;
    public const STATE_ACTIVE = 3;
    public const STATE_APPROVED = 3;
    public const STATE_FRONTPAGE = 4;
    public const STATE_CHECKEDOUT = 4;
    public const STATE_PLACEHOLDER = 5;

    // @todo migrate xartreeapi and move this to ArrayMaptreeMethod() class
    /**
    // Maximum allowable branch depth
    public const MAX_DEPTH = 20;

    // These defines are threaded view specific and should be here
    // Used for creation of the visual (threaded) tree
    public const NO_CONNECTOR = 'N';      // '' (zero-width)
    public const O_CONNECTOR = 'O';       // o- (root with no children)
    public const P_CONNECTOR = 'P';       // P  (root with children)
    public const DASH_CONNECTOR = '-';    // --
    public const T_CONNECTOR = '+';       // +- (non-last child in a group)
    public const L_CONNECTOR = 'L';       // |_
    public const I_CONNECTOR = '|';       // |
    public const BLANK_CONNECTOR = 'B';   // '  ' (spacer)
     */
}
