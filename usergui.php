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

use Xaraya\Modules\UserGuiClass;
use Xaraya\Modules\UserApiInterface;

/**
 * Handle the publications user GUI
 *
 * @method mixed archive(array $args)
 * @method mixed clone(array $args)
 * @method mixed create(array $args)
 * @method mixed delete(array $args)
 * @method mixed display(array $args)
 * @method mixed download(array $args)
 * @method mixed main(array $args)
 * @method mixed modify(array $args)
 * @method mixed new(array $args)
 * @method mixed preview(array $args)
 * @method mixed redirect(array $args)
 * @method mixed search(array $args)
 * @method mixed update(array $args)
 * @method mixed view(array $args)
 * @method mixed viewPages(array $args)
 * @method mixed viewmap(array $args)
 * @extends UserGuiClass<Module>
 */
class UserGui extends UserGuiClass
{
    // ...

    /**
     * Get module tree API class for this module
     */
    public function treeapi(): ?UserApiInterface
    {
        $component = $this->getModule()->getComponent('TreeApi');
        assert($component instanceof UserApiInterface);
        return $component;
    }
}
