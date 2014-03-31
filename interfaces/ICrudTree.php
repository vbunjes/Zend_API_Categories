<?php

/**
 * Interface for CRUD operations
 * We define a CRUD here
 */
interface Contentcategories_Interface_ICrudTree {

    public function setNode($node);
    public function getNode();

    public function setRoot($node);
    public function getRoot();

    public function move($id, $action);

} 