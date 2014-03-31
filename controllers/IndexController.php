<?php

/**
 * API access to Content Categories
 * @property REST_response $_response
 */
class Contentcategories_IndexController extends REST_Controller
{
    /**
     * @var Contentcategories_Class_ContentCategories
     */
    private $_contentCategories;

    /**
     * [TESTED]
     * Wire up standard request params and dependencies
     */
    public function preDispatch() {

        // wire up dependencies
        $mailer = new App_Custom_Mailer();
        $attributes = new Main_Class_Attributes(Model_ContentCategories::MODEL);

        $this->_contentCategories = new Contentcategories_Class_ContentCategories(
            new Contentcategories_Class_ContentCategory($mailer, $attributes, Model_ContentCategories::MODEL),
            Model_ContentCategories::MODEL,
            $attributes
        );

        // Wire up some default request params
        /*$this->view->ApiKey = $this->getRequest ()->getHeader('X-ApiKey');
        $this->view->AuthToken = $this->getRequest ()->getHeader('X-AuthToken');
        $this->view->headers = $this->_response->getHeaders();*/
        $this->view->params = $this->_request->getParams();

    }

     /**
     * [TESTED]
     * The index action handles index/list requests; it should respond with a
     * list of the requested resources.
     */
    public function indexAction()
    {

        $itemsPerPage = $this->_getParam("itemsPerPage", Resources_Settings::MAX_ITEMS_PER_PAGE);
        $page = $this->_getParam("page", 1);

        $result = $this->_contentCategories->index($page, $itemsPerPage);

        $this->view->items = $result;
        $this->view->pager = $this->_contentCategories->getPager();
        $this->view->message = sprintf('#%s Items Found', count($result));
        $this->_response->ok();

    }

    /**
     * [TESTED]
     * The get action handles GET requests and receives an 'id' parameter; it
     * should respond with the server resource state of the resource identified
     * by the 'id' value.
     */
    public function getAction()
    {

        $id = $this->_getParam('id', null);
        $this->_contentCategories->getTree = $this->_getParam('getTree', false);

        if(!$id) {

            $this->view->message = 'no id provided';
            $this->_response->badRequest();

        } else {

            $result = $this->_contentCategories->read($id);

            if($result == null) {

                $this->view->message = 'no item found';
                $this->_response->notFound();

            } else {

                $this->view->item = $result;
                //$this->view->user = Zend_Auth::getInstance()->getIdentity()->toArray();
                $this->view->id = $id;
                $this->view->message = sprintf('Resource #%s', $id);
                $this->_response->ok();

            }
        }
    }

    /**
     * [TESTED]
     * The post action handles POST requests; it should accept and digest a
     * POSTed resource representation and persist the resource state.
     */
    public function postAction()
    {

        $item = $this->_getParam('item');
        $this->_contentCategories->setRoot($this->_getParam('root', null));
        $this->_contentCategories->setNode($this->_getParam('node', null));

        if(!$item) {

            $this->view->message = 'no item provided';
            $this->_response->badRequest();

        } else {

            $result = $this->_contentCategories->create($item);

            if($result == null) {

                $this->view->message = 'no item created';
                $this->_response->badRequest();

            } else {

                $this->view->id = $result->id;
                $this->view->item = $result;
                $this->view->params = $this->_request->getParams();
                $this->view->message = sprintf('Item created #%s', $result['name']);
                $this->_response->created();
            }

        }

    }

    /**
     * [TESTED]
     * The put action handles PUT requests and receives an 'id' parameter; it
     * should update the server resource state of the resource identified by
     * the 'id' value.
     */
    public function putAction()
    {

        $id = $this->_getParam('id', null);
        $item = $this->_getParam('item', null);

        if($item == null && $id == null) {

            $this->view->message = 'no id or item provided';
            $this->_response->badRequest();

        } else {

            $result = $this->_contentCategories->update($id, $item);

            if($result == null) {

                $this->view->message = 'no item update';
                $this->_response->badRequest();

            } else {
                $this->view->item = $result->toArray();
                $this->view->params = $this->_request->getParams();
                $this->view->message = sprintf('Resource #%s Updated', $id);
                $this->_response->ok();
            }

        }

    }

    /**
     * [TESTED]
     * The delete action handles DELETE requests and receives an 'id'
     * parameter; it should update the server resource state of the resource
     * identified by the 'id' value.
     */
    public function deleteAction()
    {

        $id = $this->_getParam('id', null);

        if(!$id) {

            $this->view->message = 'no id provided';
            $this->_response->badRequest();

        } else {

            $result = $this->_contentCategories->delete($id);


            if($result == null) {

                $this->view->message = 'no item deleted';
                $this->_response->notFound();

            } else {

                $this->view->id = $id;
                $this->view->message = sprintf('Resource #%s Deleted', $id);
                $this->_response->ok();

            }

        }
    }

    /**
     * [TESTED]
     * Here we will process all the moves of a node
     * up, down, top, bottom, left, right
     */
    public function moveAction(){

        $id = $this->_getParam('id', null);
        $action = $this->_getParam('method', null);

        if(!$id) {

            $this->view->message = 'no id provided';
            $this->_response->badRequest();

        } else {

            $result = $this->_contentCategories->move($id, $action);

            if($result == null) {

                $this->view->message = 'no item moved';
                $this->_response->notFound();

            } else {

                $this->view->item = $result;
                $this->view->id = $id;
                $this->view->message = sprintf('Resource moved #%s', $id);
                $this->_response->ok();

            }
        }
    }

    /**
     * The head action handles HEAD requests; it should respond with an
     * identical response to the one that would correspond to a GET request,
     * but without the response body.
     */
    public function headAction()
    {
        $this->_response->ok();
    }

}