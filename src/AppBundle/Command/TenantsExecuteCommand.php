<?php
/**
 * Multitenancy Tenants Execute Command
 *
 * Copyright © 2017 WRonX <wronx[at]wronx.net>
 * This work is free. You can redistribute it and/or modify it under the
 * terms of the Do What The Fuck You Want To Public License, Version 2,
 * as published by Sam Hocevar. See http://www.wtfpl.net/ for more details.
 */
namespace AppBundle\Command;


use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class TenantsExecuteCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('tenants:execute')
            ->setDescription('Executes command for all tenants sequentially')
            ->addArgument('commandLine', InputArgument::REQUIRED, "Full command <error>(in quotes)</error> to execute on all tenants");
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tenantQuery = "SELECT name FROM tenants";
        
        /**
         * @var $connection Connection
         */
        $connection = $this->getContainer()->get('doctrine')->getConnection();
        $statement = $connection->executeQuery($tenantQuery);
        $result = $statement->fetchAll(\PDO::FETCH_COLUMN);
        
        $tenantsCount = count($result);
        
        if(count($tenantsCount) == 0)
        {
            $output->writeln("\r\n\t\t<error>No tenants found</error>");
            
            return;
        }
        
        $commandLine = $input->getArgument('commandLine');
        
        
        $commandName = preg_split('/ /', $commandLine, -1, PREG_SPLIT_NO_EMPTY)[0];
        
        
        $this->getApplication()->find($commandName); // let's let it throw exception and abort further execution in case of non-existing command
        
        $output->writeln("\r\n<info>Executing command</info>\r\n\t<question>$commandLine</question>\r\non <info>$tenantsCount</info> tenants:\r\n");
        $output->writeln('============================================================');
        
        $errorsCount = 0;
        
        foreach($result as $key => $tenantName)
        {
            $key++;
            $newCommand = sprintf("php %s %s --tenant=%s",
                                  realpath($this->getContainer()->get('kernel')->getRootDir() . '/console'),
                                  $commandLine,
                                  $tenantName
            );
            $output->writeln("  <question>TENANT</question>: <info>$key</info> of <info>$tenantsCount</info>\r\n    <question>NAME</question>: <info>$tenantName</info>\r\n <question>COMMAND</question>: <info>$newCommand</info>");
            $process = new Process($newCommand);
            $process->start();
            while($process->isRunning())
                ;
            $output->writeln('  <question>OUTPUT</question>:');
            $output->writeln($process->getOutput());
            if($process->getErrorOutput())
            {
                $output->writeln('   <error>ERROR</error>:');
                $output->writeln($process->getErrorOutput());
                $errorsCount++;
            }
            $output->writeln(sprintf("<question>EXITCODE</question>: <info>%s</info> (<info>%s)</info>", $process->getExitCode(), $process->getExitCodeText()));
            
            $output->writeln('============================================================');
        }
        
        $output->writeln("\r\n<question> =============== </question>\r\n<question> tenants:execute </question> finished for <info>$tenantsCount</info> tenants with <info>$errorsCount</info> errors.\r\n<question> =============== </question>\r\n");
    }
}