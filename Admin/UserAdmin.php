<?php

namespace Symforce\UserBundle\Admin ;

use Symfony\Bundle\FrameworkBundle\Controller\Controller ;
use Symfony\Component\HttpFoundation\Request ;
use Symforce\AdminBundle\Compiler\Cache\ActionCache ;
use Symfony\Component\Form\Form ;

use Symfony\Component\Security\Core\SecurityContext;

abstract class UserAdmin extends \Symforce\AdminBundle\Compiler\Cache\AdminCache {
    
    public function adjustFormOptions($object, $property, array & $options){
        if( $property === 'id_card' ) {
            $options['constraints'][] = new \Symforce\UserBundle\Form\Constraints\IdCard(array(
                    'message'  => 
                        $this->trans('sf_user.form.id_card.error', array(), $this->tr_domain ) ,
                )) ;
        }
    }
    
    public function setUserRegistration(\Symforce\UserBundle\Entity\User $user, \Symfony\Component\HttpFoundation\Request $request) {
        $group_admin  = $this->admin_loader->getAdminByName('sf_group') ;
        
        $group  = $group_admin->getRepository()->findOneBy( array('default_group'=>true)) ;
        if( !$group ) {
            throw new \Exception('need create default group');
        }
        $user->setUserGroup($group) ;
        
        $log    = new \Symforce\UserBundle\Entity\UserLog() ;
        $log->setUser($user) ;
        $log->setType( \Symforce\UserBundle\Entity\UserLog::TYPE_USER_REG ) ; 
        $log_admin  = $this->admin_loader->getAdminByClass($log) ;
        $log_admin->initByRequest($log, $request) ;
        $this->getManager()->persist( $log ) ;
    }
    
    public function addMember(\Symforce\UserBundle\Entity\User $user, \Symfony\Component\HttpFoundation\Request $request){
        $group_admin  = $this->admin_loader->getAdminByName('sf_group') ;
        
        $group  = $group_admin->getRepository()->findOneBy( array('trust_group'=>true) ) ;
        if( !$group ) {
            throw new \Exception('need create trust group');
        }
        $user->setUserGroup($group) ;
        $em = $this->getManager() ;
        $em->persist( $user ) ;
        
        $log    = new \Symforce\UserBundle\Entity\UserLog() ;
        $log->setUser($user) ;
        $log->setType( \Symforce\UserBundle\Entity\UserLog::TYPE_USER_BANK ) ;
        $log_admin  = $this->admin_loader->getAdminByClass($log) ;
        $log_admin->initByRequest($log, $request) ;
        $em->persist( $log ) ;
    }


    public function getLoginForm(\Symfony\Component\HttpFoundation\Request $request = null, array $options = array(), $with_title = true ) {
        
        if( $request ) {
            if ( $request->attributes->has(SecurityContext::AUTHENTICATION_ERROR) ) {
                $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
            } else {
                $error = $request->getSession()->get(SecurityContext::AUTHENTICATION_ERROR);
                $request->getSession()->set( SecurityContext::AUTHENTICATION_ERROR , null ) ;
            }
        } else {
            $error  = null ;
        }
        
        $tr = $this->container->get('translator') ;
        $sf_domain  = $this->container->getParameter('sf.admin.domain') ;
      
        $form_options   = array(
        ) ;
        
        if( $with_title ) {
            $form_options['label']  = 'sf.login.label' ;
            $form_options['translation_domain']  = $sf_domain;
        } else {
            $form_options['label_render']  = false  ;
        }
        
        \Dev::merge( $form_options, $options ) ;
       
        $builder = $this->container->get('form.factory')->createNamedBuilder('login', 'form', null, $form_options ) ; 
        
        $router  = $this->container->get('router') ;
        $session  = $this->container->get('session') ;
        
        $builder
                    ->add('username', 'text', array(
                        'label' => 'sf.login.username.label' ,
                        'translation_domain' => $sf_domain ,
                        'data'  => $session->get(SecurityContext::LAST_USERNAME) ,
                        // 'horizontal_input_wrapper_class' => 'col-xs-6',
                        'input_width'   => 46 ,
                        'attr' => array(
                            'placeholder' => 'sf.login.username.placeholder' ,
                        )
                    ) )
                    ->add('password', 'password', array(
                        'label'  => 'sf.login.password.label' ,
                        'translation_domain' => $sf_domain ,
                        'input_width'   => 46 ,
                        // 'horizontal_input_wrapper_class' => 'col-xs-6',
                        'attr' => array(
                            
                        )
                    ) )
                
                    ->add('captcha', 'sf_captcha', array(
                        'label' => 'sf.form.captcha.label' ,
                        'translation_domain' => $sf_domain ,
                    ))
                
                    ->add('remembme', 'sf_checkbox', array(
                        'label_render' => false ,
                        'translation_domain' => $sf_domain ,
                        'required'  => false ,
                        'value_text'  =>'sf.login.remembme.label' ,
                        'inline_help' => $tr->trans('sf.login.remembme.resetting' ,  array(
                            '%url%' => $router->generate('fos_user_resetting_request') ,
                        ), $sf_domain) ,
                    ))
                ;
        
        $form     = $builder->getForm() ;
        
        if( $error ) {
            if( $error instanceof \Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException ) {
                $_error = new \Symfony\Component\Form\FormError( $tr->trans('sf.login.error.crsf', array(), $sf_domain ) ) ;
                $form->addError( $_error  ) ;
            } else if ( $error instanceof \Symforce\UserBundle\Exception\CaptchaException ) {
                $_error = new \Symfony\Component\Form\FormError( $tr->trans('sf.login.error.captcha' , array(), $sf_domain ) ) ;
                $form->get('captcha')->addError( $_error ) ;
            } else if( $error instanceof \Symfony\Component\Security\Core\Exception\BadCredentialsException ) { 
                $_error = new \Symfony\Component\Form\FormError( $tr->trans('sf.login.error.credentials' , array(), $sf_domain ) ) ;
                $form->get('username')->addError( $_error ) ;
            } else if( $error instanceof \Symfony\Component\Security\Core\Exception\DisabledException ) {
                $_error = new \Symfony\Component\Form\FormError( $tr->trans('sf.login.error.disabled' , array(), $sf_domain ) ) ;
                $form->get('username')->addError( $_error ) ;
            } else {
                $_error = new \Symfony\Component\Form\FormError( $error->getMessage() ) ;
                if( $this->container->getParameter('kernel.debug') ) {
                    \Dev::dump(  $error ) ;
                }
                $form->get('username')->addError( $_error ) ;
            }
        }
        
        return $form ;
    }
    
    public function getLoginErrors() {
        $tr = $this->container->get('translator') ;
        $sf_domain  = $this->container->getParameter('sf.admin.domain') ;
        return array(
            'captchaexception'  => $tr->trans('sf.login.error.captcha' , array(), $sf_domain ) ,
            'badcredentialsexception'  => $tr->trans('sf.login.error.credentials' , array(), $sf_domain ) ,
            'disabledexception'  => $tr->trans('sf.login.error.disabled', array(), $sf_domain ) ,
            'invalidcsrftokenexception'  => $tr->trans('sf.login.error.crsf', array(), $sf_domain ) ,
        );
    }
}