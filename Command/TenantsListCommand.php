<?php
/**
 * Copyright © 2017 WRonX <wronx[at]wronx.net> https://github.com/WRonX
 * This work is free. You can redistribute it and/or modify it under the
 * terms of the Do What The Fuck You Want To Public License, Version 2,
 * as published by Sam Hocevar. See http://www.wtfpl.net/ for more details.
 */
namespace WRonX\MultiTenancyBundle\Command;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TenantsListCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('tenants:list')
            ->setDescription('Lists available tenant names');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if(!$this->getContainer()->getParameter('wronx_multitenancy.enabled'))
            throw new \RuntimeException("Multitenancy disabled. Enable multitenancy in config (wronx_multitenancy.enabled parameter).");
    
        $tenantQuery = "SELECT name, host FROM tenants";
        
        /**
         * @var $connection Connection
         */
        $connection = $this->getContainer()->get('doctrine')->getConnection();
        $statement = $connection->executeQuery($tenantQuery);
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        
        if(count($result) == 0)
        {
            $output->writeln("\r\n\t\t<error>No tenants found</error>");
            
            return;
        }
        
        $tenantsCount = count($result);
        $output->writeln("\r\n\t<info>Available tenants (<comment>$tenantsCount</comment>):</info>\r\n");
        
        foreach($result as $tenantInfo)
            $output->writeln(
                sprintf("\t\tname: <question>%s</question> \r\n\t\thost: <comment>%s</comment>\r\n",
                        $tenantInfo['name'], $tenantInfo['host'])
            );
    }
}