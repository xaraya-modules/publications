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

use Xaraya\Modules\AdminApiClass;

/**
 * Handle the publications admin API
 *
 * @method mixed browse(array $args)
 * @method mixed create(array $args)
 * @method mixed createpubtype(array $args)
 * @method mixed delete(array $args)
 * @method mixed deletepubtype(array $args)
 * @method mixed getconfighook(array $args)
 * @method mixed getpageaccessconstraints(array $args)
 * @method mixed getpubtypeaccess(array $args)
 * @method mixed getstats(array $args)
 * @method mixed importpubtype(array $args)
 * @method mixed promoteAlias(array $args)
 * @method mixed readFile(array $args)
 * @method mixed saveVersion(array $args)
 * @method mixed updatepubtype(array $args)
 * @method mixed writeFile(array $args)
 * @extends AdminApiClass<Module>
 */
class AdminApi extends AdminApiClass
{
    // ...
}
