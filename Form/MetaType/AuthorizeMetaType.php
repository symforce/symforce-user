<?php

namespace Symforce\UserBundle\Form\MetaType ;

use Symforce\AdminBundle\Compiler\MetaType\Form\Element ;
use Symforce\AdminBundle\Compiler\Annotation\FormType ;

/**
 * @FormType("appauthorize", orm="array")
 */
class AuthorizeMetaType extends Element {
    
    public $view = false ;
    
    public function getFormOptions(){
        $options    =  parent::getFormOptions() ;
        unset($options['required']) ;
        return $options ;
    }
}
