<?php

/**
 * We define a item here
 */
interface Contentcategories_Interface_IContentCategory {

    public function process($getTree=null);
    public function isComplete();

    public function setContentCategory($category);
    public function getContentCategory();

    public function setMailer($mailer);
    public function getMailer();

    public function setAttributes($attributes);
    public function getAttributes($item);

    public function setRequiredFields($fields);
    public function getRequiredFields();

    public function setNode($node);
    public function getNode($item);

    public function getContentCount($categoryId);

} 