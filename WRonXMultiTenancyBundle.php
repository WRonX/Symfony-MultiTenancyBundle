<?php
/**
 * Copyright © 2017 WRonX <wronx[at]wronx.net> https://github.com/WRonX
 * This work is free. You can redistribute it and/or modify it under the
 * terms of the Do What The Fuck You Want To Public License, Version 2,
 * as published by Sam Hocevar. See http://www.wtfpl.net/ for more details.
 */
namespace WRonX\MultiTenancyBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use WRonX\MultiTenancyBundle\DependencyInjection\WRonXMultiTenancyExtension;

class WRonXMultiTenancyBundle extends Bundle
{
    public function getContainerExtension()
    {
        if(null === $this->extension)
            $this->extension = new WRonXMultiTenancyExtension();
        
        return $this->extension;
    }
}
