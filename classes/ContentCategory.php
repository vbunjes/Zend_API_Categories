<?php
/**
 * Class Contentcategories_Class_ContentCategory
 * @property Doctrine_Node_NestedSet $_node
 */
class Contentcategories_Class_ContentCategory implements Contentcategories_Interface_IContentCategory {

    /**
     * Fields that are required for a create
     * @var array
     */
    private $_fields;

    /**
     * @var Model_ContentCategories
     */
    private $_contentCategory;

    /**
     * @var App_Custom_Mailer
     */
    private $_mailer;

    /**
     * @var Doctrine_Record
     */
    private $_attributes;

    /**
     * @var Main_Class_Attributes
     */
    private $_mainClassAttributes;

    /**
     * @var Doctrine_Node_NestedSet
     */
    private $_node;

    /**
     * @var string
     */
    private $_model;

    /**
     * [TESTED]
     * Inject Dependencies into class
     * @param App_Custom_Mailer $mailer
     * @param array $fields
     * @param Main_Class_Attributes $attributes
     * @param string $model
     */
    function __construct(App_Custom_Mailer $mailer, Main_Class_Attributes $attributes, $model, array $fields = null) {
        $this->setMailer($mailer);

        $this->_model = $model;

        if(!$fields) {
            $this->setRequiredFields(array(
                'name',
                'description',
                'user_id'));
        } else {
            $this->setRequiredFields($fields);
        }

        $this->_mainClassAttributes = $attributes;
    }

    /**
     * Check if item is complete and ready to be saved
     * @return bool
     */
    public function isComplete()
    {
        $r = true;
        $fields = $this->getRequiredFields();

        foreach($fields as $k => $v) {
            if(!$this->getContentCategory()->$v) {
                $r = false;
            }
        }

        return $r;
    }

    /**
     * [TESTED]
     * Processed a item to have everything needed for passing to JSON
     * @param null $getTree
     * @return array|mixed
     */
    public function process($getTree=null)
    {

        $contentCategory = $this->getContentCategory();
        $item = $contentCategory->toArray();
        $this->setNode(Doctrine_Core::getTable(Model_ContentCategories::MODEL)->findOneById($contentCategory->id)->getNode());

        // Link all attributes as if field, and as object
        $item = $this->getAttributes($item);

        $item['numberDescendants'] = $this->_node->getNumberDescendants();

        if($getTree != null) {
            $item = $this->getNode($item);
        }

        // because we process the parent and the descendants seperate.
        $item = $this->processContentCategory($item);

        return $item;
    }


    public function setContentCategory($category)
    {
        $this->_contentCategory = $category;
    }

    public function getContentCategory()
    {
       return $this->_contentCategory;
    }

    public function setMailer($mailer)
    {
        $this->_mailer = $mailer;
    }

    public function getMailer()
    {
        return $this->_mailer;
    }

    public function setRequiredFields($field)
    {
        $this->_fields = $field;
    }

    public function getRequiredFields()
    {
        return $this->_fields;
    }

    public function setAttributes($attributes)
    {
        $this->_attributes = $attributes;
    }

    /**
     * @param $item
     * @return array
     */
    public function getAttributes($item)
    {
        if($this->_attributes->count() > 0) {

            $attributes = $this->_attributes->toArray();
            foreach($attributes as $k => $v){
                $item[$attributes[$k]['title']] = $attributes[$k]['value'];
                $item['attributes'][$attributes[$k]['title']] = $attributes[$k];
            }
        }

        return $item;
    }

    public function setNode($node)
    {
        $this->_node = $node;
    }

    /**
     * @param $item
     * @return mixed
     */
    public function getNode($item)
    {

        $tree["numChildren"] = $this->_node->getNumberChildren();
        $tree["numDescendants"] = $this->_node->getNumberDescendants();

        if($this->_node->getNumberDescendants() > 0) {
            foreach($this->_node->getDescendants() as $descendant) {
                $tree["descendants"][] = $this->processDescendant($descendant->getNode(), $descendant);
            }
        }

        $item['tree'] = $tree;
        return $item;

        /*
        $tree["isLeaf"] = $this->_node->isLeaf();
        $tree["isRoot"] = $this->_node->isRoot();
        $tree["hasChildren"] = $this->_node->hasChildren();

        if($tree["hasChildren"]) {

            // add is lastChild
            // add is firstChild

            $tree["children"] = $this->_node->getChildren()->toArray();

            if($this->_node->getFirstChild()) {
                $tree["firstChild"] = $this->_node->getFirstChild()->toArray();
            }

            $tree["lastChild"]  = $this->_node->getLastChild()->toArray();
        }

        $tree["hasParent"]   = $this->_node->hasParent();

        if($tree["hasParent"]) {
            $tree["parent"] = $this->_node->getParent()->toArray();
            $tree["ancestors"] = $this->_node->getAncestors()->toArray();
        }

        $tree["hasNextSib"] = $this->_node->hasNextSibling();
        if($tree["hasNextSib"]) {
            $tree["nextSib"] = $this->_node->getNextSibling()->toArray();
        }

        $tree["hasPrevSib"] = $this->_node->hasPrevSibling();

        if($tree["hasPrevSib"]) {
            $tree["prevSib"] = $this->_node->getPrevSibling()->toArray();
        }


        */
    }

    public function getContentCount($categoryId)
    {
        $q = Doctrine_Query::create()
            ->select('count(*) as totalRecords')
            ->from(Model_Content::MODEL)
            ->where('content_categories_id = ?', $categoryId)
            ->fetchOne();

        if($q->totalRecords > 0){
            return $q->totalRecords;
        } else {
            return '-';
        }
    }

    /**
     * @param $descendant Model_ContentCategories
     * @param $node Doctrine_Node_NestedSet
     * @var $parent Doctrine_Node_NestedSet
     * @return array
     */
    private function processDescendant($node, $descendant){

        $data = $descendant->toArray();

        // set attributes for each descendants
        $this->setAttributes($this->_mainClassAttributes->index($data['id']));
        $data = $this->getAttributes($data);

        if($node->hasParent()) {
            $parent = $node->getParent()->getNode();

            $data['hasParent'] = $node->hasParent();

            if($parent->getNumberChildren() > 1) {
                $data['isLastChild'] = $descendant->id == $parent->getLastChild()->id;
                $data['isFirstChild'] = $descendant->id == $parent->getFirstChild()->id;
            }
        }

        $data['hasNextSibling'] = $node->hasNextSibling();
        $data['hasPrevSibling'] = $node->hasPrevSibling();
        $data['hasChildren'] = $node->hasChildren();

        $data = $this->processContentCategory($data);

        return $data;

    }


    private function processContentCategory($item) {

        if($item['image']) {
            $item['imageDisplay'] = App_Custom_Functions::getFullUrl($item['image']);
            $item['imageDisplayThumbnail'] = App_Custom_Functions::getFullUrl(App_Model_Image::resizev2($item['image'], 195 ,195, true));
        }

        $item['contentCount'] = $this->getContentCount($item['id']);
        return $item;
    }


}