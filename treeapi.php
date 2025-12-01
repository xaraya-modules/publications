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

use Xaraya\Modules\UserApiClass;

/**
 * Handle the publications tree API
 *
 * Takes a an array of related (parent -> child) values and assigns a depth to
 * each one -- requires that each node in the array has the 'children' field
 * telling how many children it [the current node] has
 * List passed as argument MUST be an ordered list - in the order of
 * ```
 * Parent1 -> child2-> child3 -> child4 -> subchild5 -> sub-subchild6-> subchild7-> child8-> child9-> subchild10 -> Parent11 ->....
 * ```
 * for example, the below list is an -ordered list- in thread order (ie., parent to child relation ships):
 *
 * ```
 *
 *   ID | VISUAL       |   DEPTH
 *   ===+==============+=========
 *    1 | o            |   0
 *      | |            |
 *    2 | +--          |   1
 *      | |            |
 *    3 | +--          |   1
 *      | |            |
 *    4 | +--o         |   1
 *      | |  |         |
 *    5 | +  +--o      |   2
 *      | |  |  |      |
 *    6 | +  +  +--    |   3
 *      | |  |         |
 *    7 | +  +--       |   2
 *      | |            |
 *    8 | +--          |   1
 *      | |            |
 *    9 | +--o         |   1
 *      |    |         |
 *   10 |    +--       |   2
 *      |              |
 *   11 | o            |   0
 *      | |            |
 *   12 | +--o         |   1
 *      |    |         |
 *   13 |    +--       |   2
 *
 * ```
 *
 * @method mixed leftjoin(array $args)
 * @extends UserApiClass<Module>
 */
class TreeApi extends UserApiClass
{
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

    // ...

    /**
     * Maps out the visual structure of a tree based on each
     * node's 'depth' and 'children' fields
     *
     * @author Carl P. Corliss (aka rabbitt)
     * @access public
     * @param array      $items   List of related comments
     * @return array an array of comments with an extra field ('map') for each comment
     *               that's contains the visual representation for that particular node
     * @todo remove the need for both this function and 'depthbuoy' to maintain separate matrix arrays
     */
    public function array_maptree($items)
    {
        // If $items isn't an array or it is empty,
        // raise an exception and return an empty array.
        if (!is_array($items) || count($items) == 0) {
            // TODO: Raise Exception
            return [];
        }

        $current_depth  = 0;         // depth of the current comment in the array
        $next_depth     = 0;         // depth of the next comment in the array (closer to beginning of array)
        $prev_depth     = 0;         // depth of the previous comment in the array (closer to end of array)
        $matrix         = [];   // initialize the matrix to a null array
        $depth_flags    = [];

        $total = count($items);
        $listsize = $total - 1;

        $depth_flags = array_pad([0 => 0], self::MAX_DEPTH, false);

        // Create the matrix starting from the end and working our way towards
        // the beginning.
        // FIXME: the items array is not necessarily indexed by a sequential number.
        for ($counter = $listsize; $counter >= 0; $counter -= 1) {
            // Unmapped matrix for current page.
            $matrix = array_pad([0 => 0], self::MAX_DEPTH, self::NO_CONNECTOR);

            // Make sure to $depth = $depth modulus self::MAX_DEPTH  - because we are only ever showing
            // limited levels of depth.
            $current_depth  = @$items[$counter]['depth'] % self::MAX_DEPTH;
            $next_depth     = (($counter - 1) < 0 ? -1 : @$items[$counter - 1]['depth'] % self::MAX_DEPTH);
            $prev_depth     = (($counter + 1) > $listsize ? -1 : @$items[$counter + 1]['depth'] % self::MAX_DEPTH);

            // first start by placing the depth point in the matrix
            // if the current comment has children place a P connetor
            if (!empty($items[$counter]['child_keys'])) {
                $matrix[$current_depth] = self::P_CONNECTOR;
            } else {
                // if the current comment doesn't have children
                // and it is at depth ZERO it is an O connector
                // otherwise use a dash connector
                if (!$current_depth) {
                    $matrix[$current_depth] = self::O_CONNECTOR;
                } else {
                    $matrix[$current_depth] = self::DASH_CONNECTOR;
                }
            }

            // if the current depth is zero then all that it requires is an O or P connector
            // soooo if the current depth is -not- zero then we have other connectors so
            // below we figure out what the other connectors are...
            if (0 != $current_depth) {
                if ($current_depth != $prev_depth) {
                    $matrix[$current_depth - 1] = self::L_CONNECTOR;
                }

                // In order to have a T connector the current depth must
                // be less than or equal to the previous depth.
                if ($current_depth <= $prev_depth) {
                    // If there is a DepthBuoy set for (current depth -1) then
                    // we need a T connector.
                    if ($current_depth == 0 || $depth_flags[$current_depth - 1]) {
                        $depth_flags[$current_depth - 1] = false;
                        $matrix[$current_depth - 1] = self::T_CONNECTOR;
                    }

                    if ($current_depth == $prev_depth) {
                        $matrix[($current_depth - 1)] = self::T_CONNECTOR;
                    }
                }

                // Once we've got the T and L connectors done, we need to go through
                // the matrix working our way from the indice equal to the current item
                // depth towards the begginning of the array - checking for I connectors
                // and Blank connectors.
                for ($node = $current_depth; $node >= 0; $node -= 1) {
                    // Be sure not to overwrite another node in the matrix
                    if ($matrix[$node] == self::NO_CONNECTOR) {
                        // If a depth buoy was set for this depth, add I connector.
                        if ($depth_flags[$node]) {
                            $matrix[$node] = self::I_CONNECTOR;
                        } else {
                            // Otherwise add a blank connector (a spacer).
                            $matrix[$node] = self::BLANK_CONNECTOR;
                        }
                    }
                }
            }

            // Set depth buoy if the next depth is greater then the current,
            // this way we can remember where to set an I connector.
            if (($next_depth > $current_depth) && ($current_depth != 0)) {
                // JJ
                $depth_flags[$current_depth - 1] = true;
            }

            // TODO: the padded-out matrix is wasteful (many calls to the image translation
            // function done when the number of pages is large). Refactor so no padding is
            // required.
            $items[$counter]['xar_map'] = implode('', array_map([$this, "array_image_substitution"], $matrix));
        }

        return $items;
    }


    /**
     * Used internally by array_maptree(). Takes the nodes in a matrix created for
     * a particular comment and translates them into HTML images.
     *
     * @author Carl P. Corliss (aka rabbitt)
     * @access private
     * @param integer    $matrix    the numerical representation of this segment of the visual map
     * @return string    a visual (html'ified) map of the matrix
     * @todo Wouldn't it be nice to be able to join these images together into a single image for each page and cache them?
     */
    public function array_image_substitution($node)
    {
        static $image_list = null;

        if (!isset($image_list)) {
            $style = 'class="xar-publications-tree"';

            $image_list[self::O_CONNECTOR]
                = '<img ' . $style . ' src="' . $this->tpl()->getImage('n_nosub.gif', 'module', 'publications') . '" alt="0"/>';
            $image_list[self::P_CONNECTOR]
                = '<img ' . $style . ' src="' . $this->tpl()->getImage('n_sub.gif', 'module', 'publications') . '" alt="P"/>';
            $image_list[self::T_CONNECTOR]
                = '<img ' . $style . ' src="' . $this->tpl()->getImage('n_sub_branch_t.gif', 'module', 'publications') . '" alt="t"/>';
            $image_list[self::L_CONNECTOR]
                = '<img ' . $style . ' src="' . $this->tpl()->getImage('n_sub_branch_l.gif', 'module', 'publications') . '" alt="L"/>';
            $image_list[self::I_CONNECTOR]
                = '<img ' . $style . ' src="' . $this->tpl()->getImage('n_sub_line.gif', 'module', 'publications') . '" alt="|"/>';
            $image_list[self::BLANK_CONNECTOR]
                = '<img ' . $style . ' src="' . $this->tpl()->getImage('n_spacer.gif', 'module', 'publications') . '" alt="&#160;"/>';
            $image_list[self::DASH_CONNECTOR]
                = '<img ' . $style . ' src="' . $this->tpl()->getImage('n_sub_end.gif', 'module', 'publications') . '" alt="_"/>';
            $image_list[self::NO_CONNECTOR] = '';
        }

        if (isset($image_list[$node])) {
            return $image_list[$node];
        } else {
            return '';
        }
    }
}
