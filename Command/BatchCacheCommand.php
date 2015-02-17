<?php

namespace Avalanche\Bundle\ImagineBundle\Command;

use Avalanche\Bundle\ImagineBundle\Imagine\CacheManager;
use Avalanche\Bundle\ImagineBundle\Imagine\CachePathResolver;
use Avalanche\Bundle\ImagineBundle\Imagine\Filter\FilterManager;
use Avalanche\Bundle\ImagineBundle\Imagine\ParamResolver;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand as BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class BatchCacheCommand extends BaseCommand
{
    public function configure()
    {
        $this
            ->setName('imagine:batch-cache')
            ->setDescription('Perform a caching process on a set of files')
            ->addArgument('files', InputArgument::IS_ARRAY, 'A glob patterns or literal pathnames')
            ->addOption('force', InputArgument::OPTIONAL, 'Flag that will force to regenerate already cached images')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command performs caching on all listed images:

  <info>%command.full_name% web/images/smile.png web/images/know-how.png</info>

Alternatively you can use glob pattern to fetch files:

  <info>%command.full_name% web/images/*.png</info>

  <info>%command.full_name% web/images/*.{png,jpg,jpeg,gif}</info>
EOF
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var CacheManager $cache */
        $cache   = $this->getContainer()->get('imagine.cache.manager');
        /** @var ParamResolver $params */
        $params = $this->getContainer()->get('imagine.param.resolver');
        /** @var CachePathResolver $cachePath */
        $cachePath = $this->getContainer()->get('imagine.cache.path.resolver');
        /** @var Filesystem $fs */
        $fs     = $this->getContainer()->get('filesystem');
        /** @var FilterManager $manager */
        $manager = $this->getContainer()->get('imagine.filter.manager');

        $files = [];
        foreach ($input->getArgument('files') as $pattern) {
            $files = array_merge($files, glob($pattern, GLOB_BRACE | GLOB_NOSORT));
        }
        $files = array_unique(array_filter(array_map('realpath', $files)));

        $success    = [];
        $skipped    = [];
        $failed     = [];
        $sourceRoot = $this->getContainer()->getParameter('imagine.source_root');
        foreach ($manager->getFilterNames() as $filter) {
            $success[$filter] = 0;
            $skipped[$filter] = 0;
            $failed[$filter]  = 0;

            $sourcePrefix = realpath($manager->getOption($filter, 'source_root', $sourceRoot));
            $prefixLength = strlen($sourcePrefix);
            foreach ($files as $file) {
                if (0 !== strpos($file, $sourcePrefix)) {
                    ++$failed[$filter];
                    continue;
                }

                $path = substr($file, $prefixLength);
                if ($cached = $cachePath->getCachedPath($path, $filter, true)) {
                    ++$skipped[$filter];
                    continue;
                }

                if ($cache->cacheImage('', $path, $filter)) {
                    ++$success[$filter];
                } else {
                    ++$failed[$filter];
                }
            }
        }

        $output->writeln(
            [
                '',
                'Done processing image files.',
                '',
                $this->flatten($success, 'Cached: <info>%s</info>'),
                $this->flatten($failed, 'Fialure: <error>%s</error>'),
                $this->flatten($skipped, 'Skipped (already cached): %s'),
                ''
            ]
        );
    }

    private function flatten(array $set, $template)
    {
        $set = array_filter($set, function($number) {
            return !!$number;
        });
        array_walk($set, function (&$item, $key) {
            $item = "$item ($key)";
        });

        return $set ? sprintf($template, implode(', ', $set)) : '';
    }
}