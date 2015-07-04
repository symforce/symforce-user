<?php

namespace Symforce\UserBundle\Form\Type ;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\AbstractType ;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\DependencyInjection\ContainerInterface ;


/**
 * Description of Authorize
 *
 * @author loong
 */
class AuthorizeType extends AbstractType {

    /**
     * @var ContainerInterface
     */
    protected $container ;

    public function setContainer(ContainerInterface $container){
        $this->container  = $container ;
    }


    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'compound' => false ,
        ));
    }

    /**
     * Pass the image URL to the view
     *
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['admin_tree']   = $this->container->get('sf.admin.loader')->getAdminTree() ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'appauthorize';
    }
}