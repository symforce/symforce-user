<?php

namespace Symforce\UserBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Event\UserEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Model\UserInterface;


/**
 * @Route("/register")
 */
class RegistrationController extends \FOS\UserBundle\Controller\RegistrationController {

    /**
     * @Route("/", name="fos_user_registration_register")
     */
    public function registerAction(Request $request) {
        
        /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
        $userManager = $this->container->get('fos_user.user_manager');
        /** @var $dispatcher \Symfony\Component\EventDispatcher\EventDispatcherInterface */
        $dispatcher = $this->container->get('event_dispatcher');

        $user = $userManager->createUser();
        $user->setEnabled(true);

        $event = new GetResponseUserEvent($user, $request);
        $dispatcher->dispatch(FOSUserEvents::REGISTRATION_INITIALIZE, $event);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $admin = $this->container->get('sf.admin.loader')->getAdminByClass( $user ) ;
         
        $form = $this->createFrom($user, $admin ) ;
        $event = new \Symforce\AdminBundle\Event\FormEvent($form, $request);
        $dispatcher->dispatch('sf.event.form', $event) ;
        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }
        
        if ('POST' === $request->getMethod()) {
            $form->bind($request);

            if ($form->isValid()) {
                $event = new FormEvent($form, $request);
                $dispatcher->dispatch(FOSUserEvents::REGISTRATION_SUCCESS, $event);
                
                $userManager->updateUser($user) ;

                if (null === $response = $event->getResponse()) {
                    $url = $this->container->get('router')->generate('fos_user_registration_confirmed');
                    $response = new RedirectResponse($url);
                } else {
                    if( $this->container->getParameter('kernel.debug') ) {
                        if( $response instanceof RedirectResponse ) {
                            if( $response->getTargetUrl() === $this->container->get('router')->generate('fos_user_registration_check_email') ) {
                                return $this->checkEmailAction() ;
                            }
                        }
                    }
                }

                $dispatcher->dispatch(FOSUserEvents::REGISTRATION_COMPLETED, new FilterUserResponseEvent($user, $request, $response));

                return $response;
            }
            
        }

        return $this->container->get('templating')->renderResponse('SymforceUserBundle:Registration:register.html.twig', array(
            'form' => $form->createView(),
            'admin' => $admin ,
        ));
    }
    
    /**
     * @Route("/check-email", name="fos_user_registration_check_email", methods="GET")
     */
    public function checkEmailAction() {
        $admin = $this->container->get('sf.admin.loader')->getAdminByName( 'user') ;
        $email = $this->container->get('session')->get('fos_user_send_confirmation_email/email') ;
        if( !$email ) {
            return $this->container->get('templating')->renderResponse('SymforceUserBundle:Registration:checkEmail.html.twig' , array(
                'user' => null ,
                'admin' => $admin ,
            ));
        }
        $this->container->get('session')->remove('fos_user_send_confirmation_email/email') ;
        $user = $this->container->get('fos_user.user_manager')->findUserByEmail($email) ;
        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with email "%s" does not exist', $email));
        }

        return $this->container->get('templating')->renderResponse('SymforceUserBundle:Registration:checkEmail.html.twig' , array(
            'user' => $user ,
             'admin' => $admin ,
        ));
    }

    /**
     * @Route("/confirm/{token}", name="fos_user_registration_confirm", methods="GET")
     */
    public function confirmAction(Request $request, $token) {
        /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
        $userManager = $this->container->get('fos_user.user_manager');

        $user = $userManager->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with confirmation token "%s" does not exist', $token));
        }
        
        /** @var $dispatcher \Symfony\Component\EventDispatcher\EventDispatcherInterface */
        $dispatcher = $this->container->get('event_dispatcher');

        $user->setConfirmationToken(null) ;
        $user->setEnabled(true) ;
        
        
        $event = new GetResponseUserEvent($user, $request);
        $dispatcher->dispatch(FOSUserEvents::REGISTRATION_CONFIRM, $event);

        $userManager->updateUser($user);

        if (null === $response = $event->getResponse()) {
            $url = $this->container->get('router')->generate('fos_user_registration_confirmed');
            $response = new RedirectResponse($url);
        }

        $dispatcher->dispatch(FOSUserEvents::REGISTRATION_CONFIRMED, new FilterUserResponseEvent($user, $request, $response));
        
        return $response ;
    }

    /**
     * @Route("/confirmed", name="fos_user_registration_confirmed", methods="GET")
     * @Template("SymforceUserBundle:Registration:confirmed.html.twig")
     */
    public function confirmedAction() {
        return parent::confirmedAction();
    }
    
    
    private function createFrom($user, $admin ){
         $domain    = $admin->getDomain() ;
         $sf_domain    = $admin->getSymforceDomain() ;
         $tr     = $this->container->get('translator');
         
         $constraints   = array() ;
         
        if( !$this->container->getParameter('kernel.debug') ) {
             $constraints[] = new \Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity(array( 
                    "fields" => "id_card", 
                    "message" => $tr->trans("sf_user.form.id_card.unique", array(), $domain ) ,
                 )) ;
        }
         
         $title = $tr->trans('sf_user.registration.title', array(
                    '%brand%' =>   $tr->trans('sf.admin.brand', array(), $admin->getSymforceDomain() ) ,
                ), $domain) ;
         
         $builder = $this->container->get('form.factory')->createBuilder( 'form', $user , array(
             'label' => $title ,
             'constraints' => $constraints ,
         ));
         
         $builder
            ->add('email', 'email', array(
                    'label' => 'sf_user.form.email.label' ,
                    'translation_domain' => $domain ,
                    'inline_help' => $tr->trans('sf_user.form.email.help' ,  array(
                        
                    ), $domain ) ,
                    'constraints' =>  array (
                         new \Symfony\Component\Validator\Constraints\NotBlank() ,
                         new \Symfony\Component\Validator\Constraints\Email(array(
                                "checkMX" => false ,
                              )) ,
                     )
                ))
                 
            ->add('username', null, array(
                    'label' => 'sf_user.form.username.label' ,
                    'translation_domain' => $domain ,
                    'inline_help' => $tr->trans('sf_user.form.username.help' ,  array(
                        
                    ), $domain ) ,
                    'constraints' =>  array (
                         new \Symfony\Component\Validator\Constraints\NotBlank() ,
                         new \Symfony\Component\Validator\Constraints\Length(array("min" => 2 , "max"=>16 )),
                         new \Symforce\UserBundle\Form\Constraints\UserName() ,
                     )
                ))
                 
            ->add('plainPassword', 'repeated', array(
                'type' => 'password' ,
                'options' => array(
                    'translation_domain' => $domain ,
                 ),
                'first_options' => array(
                        'label' => 'sf_user.form.plainPassword.label' ,
                        'inline_help' => $tr->trans('sf_user.form.plainPassword.help' ,  array(

                        ), $domain ) ,
                    ),
                'second_options' => array(
                        'label' => 'sf_user.form.password_confirmation.label' ,
                        'input_width'   => false ,
                    ),
                'invalid_message' => 'fos_user.password.mismatch',
                'constraints' =>  array (
                         new \Symfony\Component\Validator\Constraints\NotBlank() ,
                         new \Symfony\Component\Validator\Constraints\Length(array("min" => 6, "max"=>"16" )),
                         new \Symforce\UserBundle\Form\Constraints\Password(array(
                             'message'  => $tr->trans('sf_user.form.plainPassword.error', array(), $domain) ,
                         )) ,
                     )
            )) 
            
            ->add('mobile_phone_number', 'text', array(
                'label' => 'sf_user.form.mobile_phone_number.label' ,
                'translation_domain' => $domain ,
                'inline_help' => $tr->trans('sf_user.form.mobile_phone_number.help' ,  array(

                        ), $domain ) ,
                'constraints' =>  array (
                         new \Symfony\Component\Validator\Constraints\NotBlank() ,
                         // new \Symfony\Component\Validator\Constraints\Length(array("min" => 17 , "max"=>18 )),
                         new \Symforce\UserBundle\Form\Constraints\MobilePhone(array(
                             'message'  => $tr->trans('sf_user.form.mobile_phone_number.error', array(), $domain) ,
                         )) ,
                     )
            )) 
                 
            ->add('real_name', 'text', array(
                'label' => 'sf_user.form.real_name.label' ,
                'translation_domain' => $domain ,
                'inline_help' => $tr->trans('sf_user.form.real_name.help' ,  array(

                        ), $domain ) ,
                'constraints' =>  array (
                         new \Symfony\Component\Validator\Constraints\NotBlank() ,
                         new \Symforce\UserBundle\Form\Constraints\ChineseName(array(
                             'message'  => $tr->trans('sf_user.form.real_name.error', array(), $domain) ,
                         )) ,
                     )
            )) 
                 
            ->add('id_card', 'text', array(
                'label' => 'sf_user.form.id_card.label' ,
                'translation_domain' => $domain ,
                'inline_help' => $tr->trans('sf_user.form.id_card.help' ,  array(

                        ), $domain ) ,
                'constraints' =>  array (
                         new \Symfony\Component\Validator\Constraints\NotBlank() ,
                         // new \Symfony\Component\Validator\Constraints\Length(array("min" => 17 , "max"=>18 )),
                         new \Symforce\UserBundle\Form\Constraints\IdCard(array(
                             'message'  => $tr->trans('sf_user.form.id_card.error', array(), $domain) ,
                         )) ,
                     )
            )) 
                 
                 
            ->add('id_term', 'sf_checkbox', array(
                'label_render' => false ,
                'translation_domain' => $domain ,
                'value_text'  => $tr->trans('sf_user.form.id_term.text', array(
                    '%brand%' =>   $tr->trans('sf.admin.brand', array(), $admin->getSymforceDomain() ) ,
                ), $domain) ,
                'constraints' =>  array (
                         new \Symfony\Component\Validator\Constraints\NotBlank( array(
                             'message'  => $tr->trans('sf_user.form.id_term.error', array(), $domain) ,
                         )) ,
                     )
            )) 
                 
            ->add('captcha', 'sf_captcha', array(
                'label' => 'sf.form.captcha.label' ,
                'translation_domain' => $sf_domain ,
            ))
            
            ->add('registration_term', 'sf_checkbox', array(
                'label' => 'sf_user.form.registration_term.label' ,
                'translation_domain' => $domain ,
                'value_text'  => $tr->trans('sf_user.form.registration_term.text', array(
                    '%brand%' =>   $tr->trans('sf.admin.brand', array(), $admin->getSymforceDomain() ) ,
                    '%url%'   => $this->container->get('router')->generate('fos_user_registration_term') ,
                ), $domain) ,
                'constraints' =>  array (
                         new \Symfony\Component\Validator\Constraints\NotBlank( array(
                             'message'  => $tr->trans('sf_user.form.registration_term.error', array(), $domain) ,
                         )) ,
                     )
            )) 
                 
                 
           ;
            
        return $builder->getForm();
    }
    
    
    /**
     * @Route("/term", name="fos_user_registration_term", methods="GET")
     * @Template
     */
    public function termAction() {
        return array(
            
        );
    }


}