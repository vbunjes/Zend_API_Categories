<?php
/**
 * Created by PhpStorm.
 * User: vincent
 * Date: 2/10/14
 * Time: 8:31 PM
 */

/**
 * Create, Update, Get, Delete Category
 * Class Users, validation here
 * @package api
 */
class Contentcategories_Class_ContentCategories implements Contentcategories_Interface_ICrud, Contentcategories_Interface_ICrudTree {

    /**
     * @var Contentcategories_Class_ContentCategory
     */
    private $_crud;

    /**
     * @var Main_Class_Attributes
     */
    private $_attributes;

    /**
     * @var Doctrine_Pager
     */
    private $_pager;

    /**
     * @var string
     */
    private $_model;

    /**
     * @var Doctrine_Record;
     */
    private $_item;

    /**
     * @var Doctrine_Collection
     */
    private $_items;

    /**
     * @var Doctrine_Node_NestedSet
     */
    private $_node;

    /**
     * @var Doctrine_Node
     */
    private $_root;

    /**
     * @var bool
     */
    public $getTree = false;

    /**
     * @param Contentcategories_Interface_IContentCategory $crud
     * @param $model
     * @param $attributes
     */
    function __construct(Contentcategories_Interface_IContentCategory $crud, $model, Main_Class_Attributes $attributes) {
        $this->_crud = $crud;
        $this->_model = $model;
        $this->_attributes = $attributes;
    }

    /**
     * [TESTED]
     * Retrieve all items
     * @param $page
     * @param $itemsPerPage
     * @return Doctrine_Collection
     */
    public function index($page, $itemsPerPage)
    {
        $result = Doctrine_Query::create()
            ->from($this->_model)
            ->where('level = ?', 0);

        $pager = new Doctrine_Pager($result, $page, $itemsPerPage);
        $result = $pager->execute();
        $this->setPager($pager, "/content/categories/list");

        if($result->count() > 0) {

            $cnt = 0;
            foreach($result as $item){

                $this->_crud->setContentCategory($item);
                $this->_crud->setAttributes($this->_attributes->index($this->_crud->getContentCategory()->id));

                $items[$cnt] = $this->_crud->process();
                $cnt++;
            }

            $this->setItems($items);

        } else {

            $this->setItems(null);
        }

        return $this->getItems();
    }

    /**
     * [TESTED]
     * Create a item
     * @param $item
     * @return Model_ContentCategories
     */
    public function create($item){

        $root = $this->getRoot();
        $node = $this->getNode();

        $m = new Model_ContentCategories();
        $m->name = $item->name;
        $m->description = $item->description;
        $m->user_id = Zend_Auth::getInstance()->getIdentity()->id;
        $m->sef = App_Custom_Functions::generateSef($item->name);

        if($item->publish == false){
            $m->publish = NULL;
        } else {
            $m->publish = $item->publish;
        }

        $this->_crud->setContentCategory($m);

        if($this->_crud->isComplete()) {

            // if there is a root, we start insertAsLastChild process
            if($root > 0) {

                $root = Doctrine_Core::getTable ( $this->_model )->find ( $root );

                // if there is a node, we add to it this node, which is also a child of the root
                if($node > 0) {
                    $root = Doctrine_Core::getTable ( $this->_model )->find ( $node );
                }

                $m->save ();
                $m->getNode ()->insertAsLastChildOf ( $root );

            } else {
                $tree = Doctrine_Core::getTable ( $this->_model )->getTree ();
                $m->save ();
                $tree->createRoot ( $m );
            }

            if($item->attributes) {
                foreach ($item->attributes as $key => $attribute) {
                    $this->_attributes->create($key, $attribute->value, $m->id);
                }
            }

        } else {
            $m = null;
        }
        return $m;
    }

    /**
     * [TESTED]
     * reads one item
     * @param null $id
     * @return Doctrine_Record
     */
    public function read($id = null)
    {

        $q = Doctrine_Query::create()
            ->from($this->_model)
            ->where('id = ?',$id)
            ->execute();

        if($q->count() > 0) {

            $this->_crud->setContentCategory($q->getFirst());
            $this->_crud->setAttributes($this->_attributes->index($this->_crud->getContentCategory()->id));

            $this->setItem($this->_crud->process($this->getTree));

        } else {

            $this->_crud->setContentCategory(null);
        }

        return $this->getItem();
    }

    /**
     * [TESTED]
     * updates one item
     * @param $id
     * @param $item
     * @return Model_ContentCategories|null
     */
    public function update($id, $item)
    {
        $m = new Model_ContentCategories();
        $m->assignIdentifier($id);
        $m->name = $item->name;
        $m->sef = App_Custom_Functions::generateSef($item->name);
        $m->description = $item->description;
        $m->user_id = Zend_Auth::getInstance()->getIdentity()->id;

        if($item->publish == false){
            $m->publish = NULL;
        } else {
            $m->publish = $item->publish;
        }

        $this->_crud->setContentCategory($m);

        if($this->_crud->isComplete()) {
            $m->save();
            if($item->attributes) {
                foreach ($item->attributes as $key => $attribute) {
                    $this->_attributes->create($key, $attribute->value, $m->id);
                }
            }
        } else {
            $m = null;
        }

        return $m;
    }

    /**
     * [TESTED]
     * Deletes one item
     * @param $id
     * @return Doctrine_Collection
     */
    public function delete($id)
    {
        $this->setNode(Doctrine_Core::getTable($this->_model)->findOneById($id)->getNode());

        $this->_attributes->clean($id);

        return $this->getNode()->delete();
    }

    /**
     * Move nodes around
     * @param $id
     * @param $action
     * @return bool
     */
    public function move($id, $action)
    {
        // get node on id
        $this->setNode(Doctrine_Core::getTable ( $this->_model )->findOneById ( $id )->getNode());
        $node = $this->getNode();

        switch ( $action ) {

            case "top" :
                while ($node->hasPrevSibling()) {
                    $sibling = $node->getPrevSibling ();
                    $node->moveAsPrevSiblingOf ( $sibling );
                }
                break;

            case "bottom" :
                while ($node->hasNextSibling()) {
                    $sibling = $node->getNextSibling ();
                    $node->moveAsNextSiblingOf ( $sibling );
                }
                break;

            case "up" :
                // if up, find getPrevSibling
                $sibling = $node->getPrevSibling ();
                if ($sibling) {
                    $node->moveAsPrevSiblingOf ( $sibling );
                }
                break;

            case "down" :
                // if down, find getNextSibling
                $sibling = $node->getNextSibling ();
                if ($sibling) {
                    $node->moveAsNextSiblingOf ( $sibling );
                }
                break;

            case "left" :
                // if left, find getNextSibling
                $parent = $node->getParent();
                if ($parent) {
                    $node->moveAsPrevSiblingOf($parent);
                }
                break;

            case "right" :
                // if right, find getNextSibling
                $sibling = $node->getNextSibling ();
                if ($sibling) {
                    $node->moveAsFirstChildOf ( $sibling );
                }
                break;
        }

        return true;
    }


    /**
     * @param $item
     */
    public function setItem($item)
    {
        $this->_item = $item;
    }

    /**
     * @return Doctrine_Record
     */
    public function getItem()
    {
       return $this->_item;
    }

    /**
     * @param $items
     */
    public function setItems($items)
    {
        $this->_items = $items;
    }

    /**
     * @return Doctrine_Collection
     */
    public function getItems()
    {
       return $this->_items;
    }

    /**
     * @param $pager Doctrine_Pager
     * @param $listUrl
     */
    public function setPager($pager, $listUrl)
    {
        $pager->execute();
        $this->_pager->haveToPaginate = $pager->haveToPaginate();

        if($this->_pager->haveToPaginate) {
            $this->_pager->maxPerPage = $pager->getMaxPerPage();
            $this->_pager->numResults = $pager->getNumResults();

            $this->_pager->firstPage = $pager->getFirstPage();
            $this->_pager->lastPageUrl = $listUrl."/".$pager->getFirstPage();

            $this->_pager->lastPage = $pager->getLastPage();
            $this->_pager->lastPageUrl = $listUrl."/".$pager->getLastPage();

            $this->_pager->nextPage = $pager->getNextPage();
            $this->_pager->nextPageUrl = $listUrl."/".$pager->getNextPage();

            $getPage = $pager->getPage();
            if($pager->getPage() > 1) {
                $getPage = $pager->getPage() - 1;
            }
            $this->_pager->prevPage = $pager->getPage();
            $this->_pager->prevPageUrl = $listUrl."/".$getPage;

            $this->_pager->current = $pager->getPage();
            $this->_pager->currentPageUrl = $listUrl."/".$pager->getPage();
        }

        for( $i=0; $i < $pager->getLastPage(); $i++){
            $currentPage = $i+1;
            $this->_pager->pages[$i]['number'] = $currentPage;
            $this->_pager->pages[$i]['url'] = $listUrl."/".$currentPage;
        }
    }

    public function getPager()
    {
        return $this->_pager;
    }

    public function setNode($node)
    {
        $this->_node = $node;
    }

    public function getNode()
    {
        return $this->_node;
    }

    public function setRoot($root)
    {
        $this->_root = $root;
    }

    public function getRoot()
    {
        return $this->_root;
    }

}
