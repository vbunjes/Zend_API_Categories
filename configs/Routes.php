<?php

class Contentcategories_Config_Routes
{

    function __construct ()
    {
        $front = Zend_Controller_Front::getInstance();
        $router = $front->getRouter();

        $router->addRoute ( "move", new Zend_Controller_Router_Route ( "/contentcategories/move", array ("module" => "contentcategories", "controller" => "index", "action" => "move") ) );

    }
}