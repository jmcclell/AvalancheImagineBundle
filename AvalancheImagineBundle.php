<?php

namespace Avalanche\Bundle\ImagineBundle;

use Avalanche\Bundle\ImagineBundle\DependencyInjection\Compiler\CreateCacheDirectoriesCompilerPass;
use Avalanche\Bundle\ImagineBundle\DependencyInjection\Compiler\LoadersCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AvalancheImagineBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new LoadersCompilerPass())
            ->addCompilerPass(new CreateCacheDirectoriesCompilerPass(), PassConfig::TYPE_AFTER_REMOVING);
    }
}
