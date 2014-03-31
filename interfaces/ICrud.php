<?php
/**
 * Created by PhpStorm.
 * User: vincent
 * Date: 2/10/14
 * Time: 8:33 PM
 */

/**
 * Interface for CRUD operations
 * We define a CRUD here
 */
interface Contentcategories_Interface_ICrud {

    public function index($page, $itemsPerPage);
    public function create($item);
    public function read($id=null);
    public function update($id, $item);
    public function delete($id);

    public function setItem($item);
    public function getItem();

    public function setItems($items);
    public function getItems();

    public function setPager($pager, $listUrl);
    public function getPager();

} 