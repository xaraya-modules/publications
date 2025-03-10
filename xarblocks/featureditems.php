<?php
/**
 * Publications Module
 *
 * @package modules
 * @subpackage publications module
 * @category Third Party Xaraya Module
 * @version 2.0.0
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @author mikespub
 */
/**
 * Featured initialise block
 *
 * @author Jonn Beams (based on code from TopItems block)
 *
 */
sys::import('xaraya.structures.containers.blocks.basicblock');

class Publications_FeatureditemsBlock extends BasicBlock implements iBlock
{
    // File Information, supplied by developer, never changes during a versions lifetime, required
    protected $type             = 'featureditems';
    protected $module           = 'publications'; // module block type belongs to, if any
    protected $text_type        = 'Featured Items';  // Block type display name
    protected $text_type_long   = 'Show featured publications'; // Block type description
    // Additional info, supplied by developer, optional
    protected $type_category    = 'block'; // options [(block)|group]
    protected $author           = '';
    protected $contact          = '';
    protected $credits          = '';
    protected $license          = '';

    // blocks subsystem flags
    protected $show_preview = true;  // let the subsystem know if it's ok to show a preview
    // @todo: drop the show_help flag, and go back to checking if help method is declared
    protected $show_help    = false; // let the subsystem know if this block type has a help() method

    public $numitems            = 5;
    public $pubtype_id          = 0;
    public $linkpubtype         = true;
    public $itemlimit           = 0;
    public $featuredid          = 0;
    public $catfilter           = 0;
    public $linkcat             = false;
    public $includechildren     = false;
    public $nocatlimit          = true;
    public $alttitle            = '';
    public $altsummary          = '';
    public $showvalue           = true;
    public $moreitems           = [];
    public $showfeaturedsum     = false;
    public $showfeaturedbod     = false;
    public $showsummary         = false;
    // chris: state is a reserved property name used by blocks
    //public $state               = '2,3';
    public $pubstate            = '2,3';
    public $toptype             = 'ratings';

    public function display()
    {
        $data = $this->getContent();

        // defaults
        $featuredid = $data['featuredid'];

        $fields = ['id', 'title', 'cids'];

        $fields[] = 'dynamicdata';

        // Initialize arrays
        $data['items'] = [];

        // Load the query class and the publications tables
        sys::import('xaraya.structures.query');
        $this->mod()->apiLoad('publications');
        $tables = & $this->db()->getTables();

        // Get all the publications types
        sys::import('modules.dynamicdata.class.objects.factory');
        $pubtypeobject = $this->data()->getObjectList(['name' => 'publications_types']);
        $types = $pubtypeobject->getItems();

        # ------------------------------------------------------------
        # Set up the featured item
        #
        if ($data['featuredid'] > 0) {
            // Get the database entry of the featured item
            $q = new Query('SELECT', $tables['publications']);
            $q->eq('id', $data['featuredid']);
            $q->run();
            $result = $q->row();

            // Use that information to get the featured item as an object
            $featuredtype = $types[$result['pubtype_id']]['name'];
            $data['featured'] = $this->data()->getObject(['name' => $featuredtype]);
            $data['featured']->getItem(['itemid' => $data['featuredid']]);
            $feature = $data['featured']->getFieldValues([], 1);
            $data['properties'] = & $data['featured']->properties;

            $feature['link'] = $this->mod()->getURL(
                'user',
                'display',
                [
                                            'itemid' => $data['properties']['id']->value,
                                        ]
            );
            $feature['alttitle']   = $data['alttitle'];
            $feature['altsummary'] = $data['altsummary'];
            $feature['showfeaturedsum'] = $data['showfeaturedsum'];
            $feature['showfeaturedbod'] = $data['showfeaturedbod'];
            $data['feature'] = $feature;
        }

        # ------------------------------------------------------------
        # Set up additional items
        #
        if (!empty($data['moreitems'])) {
            if ($data['toptype'] == 'rating') {
                $fields[] = 'rating';
                $sort = 'rating';
            } elseif ($data['toptype'] == 'hits') {
                $fields[] = 'counter';
                $sort = 'hits';
            } elseif ($data['toptype'] == 'date') {
                $fields[] = 'pubdate';
                $sort = 'date';
            } else {
                $sort = $data['toptype'];
            }

            $publications = $this->mod()->apiMethod(
                'publications',
                'user',
                'getall',
                [
                    'ids' => $data['moreitems'],
                    'enddate' => time(),
                    'fields' => $fields,
                    'sort' => $sort,
                ]
            );

            // See if we're currently displaying a publication
            // We do this to remove a link form a featured item if that item is already being displayed
            if ($this->var()->isCached('Blocks.publications', 'id')) {
                $curid = $this->var()->getCached('Blocks.publications', 'id');
            } else {
                $curid = -1;
            }

            // Since each item could potentially be a different publication type
            // we need to go through a loop.
            $data['items'] = [];
            foreach ($publications as $publication) {
                $itemname = $types[$publication['pubtype_id']]['name'];
                $object = $this->data()->getObject(['name' => $itemname]);
                $object->getItem(['itemid' => $publication['id']]);
                $itemvalues = $object->getFieldValues([], 1);

                if ($publication['id'] != $curid) {
                    $link = $this->mod()->getURL(
                        'user',
                        'display',
                        [
                            'itemid' => $publication['id'],
                        ]
                    );
                } else {
                    $link = '';
                }
                $itemvalues['featured_link'] = $link;
                if (empty($data['showsummary'])) {
                    $itemvalue['description'] = '';
                }
                $data['items'][$publication['id']] = $itemvalues;
            }
        }

        # ------------------------------------------------------------
        # Suppress the block and its title if there is nothing to display
        #
        if (empty($data['featuredid']) && empty($data['items'])) {
            return;
        }
        return $data;
    }
}
