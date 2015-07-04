<?php

namespace Symforce\UserBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


/**
 * @Route("/profile")
 */
class ProfileController extends \FOS\UserBundle\Controller\ProfileController {
    
    /**
     * @Route("/", name="fos_user_profile_show")
     * Template("AppWebBundle:Profile:info.html.twig")
     */
    public function showAction()
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }
        
        return array(
            'user'  => $user ,
        );
    }
    
    /**
     * @Route("/edit", name="fos_user_profile_edit")
     * Template("AppWebBundle:Profile:edit.html.twig")
     */
    public function editAction(\Symfony\Component\HttpFoundation\Request $request)
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        /** @var $dispatcher \Symfony\Component\EventDispatcher\EventDispatcherInterface */
        $dispatcher = $this->container->get('event_dispatcher');

        $event = new GetResponseUserEvent($user, $request);
        $dispatcher->dispatch(FOSUserEvents::PROFILE_EDIT_INITIALIZE, $event);

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
                /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
                $userManager = $this->container->get('fos_user.user_manager');

                $event = new FormEvent($form, $request);
                $dispatcher->dispatch(FOSUserEvents::PROFILE_EDIT_SUCCESS, $event);

                $userManager->updateUser($user);

                if (null === $response = $event->getResponse()) {
                    $url = $this->container->get('router')->generate('fos_user_profile_show');
                    $response = new RedirectResponse($url);
                }

                $dispatcher->dispatch(FOSUserEvents::PROFILE_EDIT_COMPLETED, new FilterUserResponseEvent($user, $request, $response));

                return $response;
            }
        }
        return array(
                    'form' => $form->createView() ,
                ) ;
    }
    
    
    private function createFrom($user, $admin){
         $domain    = $admin->getDomain() ;
         $sf_domain    = $admin->getSymforceDomain() ;
         $tr     = $this->container->get('translator');
         
         $constraints   = array() ;
         
        if( !$this->container->getParameter('kernel.debug') ) {
            
        }
         
         $builder = $this->container->get('form.factory')->createBuilder( 'form', $user , array(
             'show_legend' => false ,
             'constraints' => $constraints ,
             'translation_domain' => $domain ,
         ));
         
         $admin->addFormElement($builder, 'real_name', array(
             'input_width' => 60 ,
             'required' => true ,
         ));
         
         $admin->addFormElement($builder, 'id_card', array(
             'required' => true ,
             'constraints' =>  array (
                         new \Symfony\Component\Validator\Constraints\NotBlank() ,
                         // new \Symfony\Component\Validator\Constraints\Length(array("min" => 17 , "max"=>18 )),
                         new \Symforce\UserBundle\Form\Constraints\IdCard(array(
                             'message'  => $tr->trans('sf_user.form.id_card.error', array(), $domain) ,
                         )) ,
                     ) ,
         ));
         
         $admin->addFormElement($builder, 'gender', array(
             'input_width' => 60 ,
         ));
         
         $admin->addFormElement($builder, 'avatar', array(
             'input_width' => 60 ,
         ));
         
         $admin->addFormElement($builder, 'phone_number', array(
             'input_width' => 60 ,
         ));
         
         $admin->addFormElement($builder, 'mobile_phone_number', array(
             'input_width' => 60 ,
             'required' => true ,
         ));
         
         $builder
            ->add('old_password', 'password', array(
                'label' => '当前密码' ,
                'input_width' => 60 ,
                'constraints' => array(
                    new \Symfony\Component\Security\Core\Validator\Constraints\UserPassword(array(  'message'   => "当前密码错误!",   ))
                ),
            ))
           ;
            
        return $builder->getForm();
    }   
}