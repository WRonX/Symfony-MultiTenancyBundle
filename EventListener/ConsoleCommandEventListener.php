<?php
/*
Thanks to Wouter J
https://php-and-symfony.matthiasnoback.nl/2013/11/symfony2-add-a-global-option-to-console-commands-and-generate-pid-file/#comment-2373132541
*/

namespace WRonX\MultiTenancyBundle\EventListener;

use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ConsoleCommandEventListener
{
    use ContainerAwareTrait;
    
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        if($this->container->getParameter('connection_wrapper') === null)
            return $event;
        
        $definition = $event->getCommand()->getDefinition();
        $input = $event->getInput();
        $option = new InputOption('tenant', '', InputOption::VALUE_REQUIRED, 'Tenant name for multitenancy installations', null);
        
        $definition->addOption($option);
        $input->bind($definition);
        
        $definition = $event->getCommand()->getApplication()->getDefinition();
        $definition->addOption($option);
        
        return $event;
    }
}
