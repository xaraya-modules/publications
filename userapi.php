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
 * Handle the publications user API
 *
 * @method mixed addcurrentpageflags(array $args)
 * @method mixed checksecurity(array $args)
 * @method mixed countitems(array $args)
 * @method mixed fieldoutput(array $args)
 * @method mixed generatekeywords(array $args)
 * @method mixed get(array $args)
 * @method mixed getMenuPages(array $args)
 * @method mixed getNextname(array $args)
 * @method mixed getPubtypes(array $args)
 * @method mixed getSitemapPages(array $args)
 * @method mixed getall(array $args)
 * @method mixed getauthors(array $args)
 * @method mixed getchildcats(array $args)
 * @method mixed getitemfields(array $args)
 * @method mixed getitemlinks(array $args)
 * @method mixed getitempubtype(array $args)
 * @method mixed getitemtypes(array $args)
 * @method mixed getmenulinks(array $args)
 * @method mixed getmonthcount(array $args)
 * @method mixed getnext(array $args)
 * @method mixed getpages(array $args)
 * @method mixed getpagestree(array $args)
 * @method mixed getparentcats(array $args)
 * @method mixed getprevious(array $args)
 * @method mixed getpubcatcount(array $args)
 * @method mixed getpubcount(array $args)
 * @method mixed getpubfieldformats(array $args)
 * @method mixed getpubfields(array $args)
 * @method mixed getpubfieldtypes(array $args)
 * @method mixed getpublinks(array $args)
 * @method mixed getpubtypeaccess(array $args)
 * @method mixed getrandom(array $args)
 * @method mixed getrelativepages(array $args)
 * @method mixed getrootcats(array $args)
 * @method mixed getsetting(array $args)
 * @method mixed getsettings(array $args)
 * @method mixed getstates(array $args = [])
 * @method mixed gettranslationid(array $args)
 * @method mixed leftjoin(array $args)
 * @method mixed pageintrees(array $args)
 * @method mixed prepareforbl(array $args)
 * @extends UserApiClass<Module>
 */
class UserApi extends UserApiClass
{
    // ...

    public function getglobalsettings(array $args = [])
    {
        $settings = [
            'number_of_columns'     => 1,
            'items_per_page'        => 20,
            'defaultview'           => "Sections",
            'defaultsort'           => "name",
            'show_categories'       => false,
            'show_catcount'         => false,
            'namestring'            => 'pub',
            'show_prevnext'         => true,
            'show_keywords'         => false,
            'show_comments'         => false,
            'show_hitcount'         => false,
            'show_ratings'          => false,
            'show_archives'         => false,
            'show_map'              => false,
            'show_publinks'         => false,
            'show_pubcount'         => true,
            'do_transform'          => true,
            'title_transform'       => true,
            'usealias'              => false,                //CHECKME
            'defaultstate'          => 2,
            'defaultprocessstate'   => 0,
            'showsubmit'            => false,              //CHECKME
            'summary_template'      => '',
            'detail_template'       => '',
            'page_template'         => "",
            'theme'                 => '',
        ];
        return $settings;
    }
}
