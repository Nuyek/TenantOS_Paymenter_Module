<?php

namespace App\Extensions\Events\TenantOSUserSync;

use App\Classes\Extensions\Event;

class TenantOSUserSync extends Event
{   
    /**
    * Get the extension metadata
    * 
    * @return array
    */
    public function getMetadata()
    {
        return [
            'display_name' => 'TenantOSUserSync',
            'version' => '1.0.0',
            'author' => 'Nuyek, LLC',
            'website' => 'https://nuyek.com',
        ];
    }
    
    /**
     * Get all the configuration for the extension
     * 
     * @return array
     */
    public function getConfig()
    {
        return [
            [
                'name' => 'host',
                'type' => 'text',
                'friendlyName' => 'Webhook URL',
                'required' => true,
            ],
            [
                'name' => 'apiKey',
                'type' => 'text',
                'friendlyName' => 'API KEY',
                'required' => true,
            ],
        ];
    }
}
