<?php

namespace Symforce\UserBundle\Form\Constraints ;

class MobilePhone extends \Symfony\Component\Validator\Constraint {
    public $message = 'This value `%s` is not a valid username.' ;
}