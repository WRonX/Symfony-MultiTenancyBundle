<?php
/**
 * Multitenancy Connection Wrapper
 *
 * Copyright © 2017 WRonX <wronx[at]wronx.net>
 * This work is free. You can redistribute it and/or modify it under the
 * terms of the Do What The Fuck You Want To Public License, Version 2,
 * as published by Sam Hocevar. See http://www.wtfpl.net/ for more details.
 * 
 * Inspired by zulus' answer: https://stackoverflow.com/a/9291896
 */

namespace AppBundle\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;

class ConnectionWrapper extends Connection
{
    const COLUMN_TENANT_DB = "dbName";
    const COLUMN_TENANT_DBHOST = "dbHost";
    const COLUMN_TENANT_NAME = "name";
    const COLUMN_TENANT_PASS = "dbPass";
    const COLUMN_TENANT_USER = "dbUser";
    const COMMANDS_NOT_INCLUDED = [
        'cache:clear',
        'assets:install',
        'assetic:dump',
        'tenants:list',
    ];
    const TENANT_TABLE_NAME = "tenants";
    /**
     * @var string
     */
    public $tenantName = '';
    /**
     * @var bool
     */
    private $_isConnected = false;
    
    /**
     * {@inheritDoc}
     */
    private $_params = array();
    
    /**
     * {@inheritDoc}
     */
    public function connect()
    {
        if($this->isConnected())
            return true;

        if(php_sapi_name() === 'cli')
        {
            // this is really ugly, feel free to suggest better solution
            $arguments = $_SERVER['argv'];
    
            if(in_array($arguments[1], self::COMMANDS_NOT_INCLUDED))
            {
                try
                {
                    $this->_params = parent::getParams();
                    $this->_conn = $this->_driver->connect($this->_params, $this->_params['user'], $this->_params['password'], $this->_params['driverOptions'] ?? array());
            
                    return true;
                }
                catch(\Exception $e)
                {
                    die($e->getMessage());
                }
            }

            $tenantArgumentTag = "--tenant=";
            $tenantArgumentPattern = "$tenantArgumentTag*";
            $tenantArguments = array_values(array_filter($arguments, function($entry) use ($tenantArgumentPattern)
            {
                return fnmatch($tenantArgumentPattern, $entry);
            }));
            
            if(count($tenantArguments) == 0)
                throw new \Exception('Tenant name not provided');
            
            $tenantName = str_replace($tenantArgumentTag, '', $tenantArguments[0]);
            
            $this->_conn = $this->getConnectionByTenantName($tenantName);
        }
        else
        {
            $domain = $_SERVER['HTTP_HOST'];
            
            $this->_conn = $this->getConnectionByTenantHost($domain);
        }
        
        if($this->_eventManager->hasListeners(Events::postConnect))
        {
            $eventArgs = new ConnectionEventArgs($this);
            $this->_eventManager->dispatchEvent(Events::postConnect, $eventArgs);
        }
        
        $this->_isConnected = true;
        
        return true;
    }
    
    /**
     * @return bool
     */
    public function isConnected()
    {
        return $this->_isConnected;
    }

    private function getConnectionByTenantName($tenantName)
    {
        $query = "SELECT * FROM " . $this::TENANT_TABLE_NAME . " WHERE name = ?";
        
        return $this->getConnectionByQuery($query, $tenantName);
    }
    
    private function getConnectionByQuery($queryString, $argumentValue)
    {
        if(strlen($argumentValue) < 1)
            throw new \InvalidArgumentException("Invalid query argument value");

        $params = parent::getParams();
        $driverOptions = isset($params['driverOptions']) ? $params['driverOptions'] : array();
    
        try
        {
            $connection = $this->_driver->connect($params, $params['user'], $params['password'], $driverOptions);
        }
        catch(\Exception $e)
        {
            die();
        }
        $statement = $connection->prepare($queryString);
        $statement->execute([$argumentValue]);
        $result = $statement->fetch(\PDO::FETCH_ASSOC);
        
        if($result === false)
            throw new \Exception('Unknown client');
    
        $this->_params['host'] = $result[$this::COLUMN_TENANT_DBHOST];
        $this->_params['password'] = $result[$this::COLUMN_TENANT_PASS];
        $this->_params['user'] = $result[$this::COLUMN_TENANT_USER];
        $this->_params['dbname'] = $result[$this::COLUMN_TENANT_DB];
        
        $this->tenantName = $result[$this::COLUMN_TENANT_NAME];
    
        try
        {
            $connect = $this->_driver->connect($this->_params, $this->_params['user'], $this->_params['password'], $driverOptions);
        }
        catch(\Exception $e)
        {
            die();
        }
    
        return $connect;
    }
    
    private function getConnectionByTenantHost($hostName)
    {
        $query = "SELECT * FROM " . $this::TENANT_TABLE_NAME . " WHERE ? REGEXP host AND active = 1 ORDER BY CHAR_LENGTH(host) DESC LIMIT 1";
        
        return $this->getConnectionByQuery($query, $hostName);
    }
    
    /**
     * Gets the parameters used during instantiation.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }
    
    public function getSchemaManager()
    {
        if(!$this->_schemaManager)
        {
            $this->_schemaManager = $this->_driver->getSchemaManager($this);
        }
        
        return $this->_schemaManager;
    }
}

