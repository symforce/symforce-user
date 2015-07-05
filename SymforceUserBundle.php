<?php

namespace Symforce\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SymforceUserBundle extends Bundle
{
    
     /**
     * Build this
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new DependencyInjection\Compiler\FormPass());
    }
    
    public function getParent()
    {
        return 'FOSUserBundle';
    }

}
