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

        $container->addCompilerPass(new LoadersCompilerPass());
        // Disable create cache directories for now; it's not needed anyway (structure is being created on the run).
//            ->addCompilerPass(new CreateCacheDirectoriesCompilerPass(), PassConfig::TYPE_AFTER_REMOVING);
    }
}
