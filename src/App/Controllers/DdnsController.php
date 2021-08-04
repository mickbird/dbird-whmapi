<?php
declare(strict_types = 1);

namespace App\Controllers;

use App\Config;
use App\Helpers\CPanelApiHelper;
use Core\Application;

class DdnsController extends AppController
{
    /*
     * FIELDS
     */

     private CPanelApiHelper $apiHelper;
     private Config $config;

    /*
     * GETTERS / SETTERS
     */

    /*
     * CONSTRUCTOR / INITIALIZER
     */

    /**
     * Initialize the controller. Acts like a lightweight constructor
     */
    protected function initialize() : void
    {
        $this->disableAutorender();
        $this->config = Application::current()->getConfig();
        $this->apiHelper = new CPanelApiHelper($this->config->getCPanelHost(), $this->config->getCPanelUser(), $this->config->getCPanelPass());
    }

    /*
     * PUBLIC METHODS
     */

    public function updateAction(string $hostname, ?string $ip = null) : void
    {
        if (@$_SERVER['PHP_AUTH_USER'] !== $this->config->getCPanelUser() || @$_SERVER['PHP_AUTH_PW'] !== $this->config->getCPanelPass()) {
            $this->getResponse()
                ->setHeader('WWW-Authenticate', 'Basic realm="oZYXTs3X878r7JD5Jf", charset="UTF-8"')
                ->setHeader('HTTP/1.0 401 Unauthorized')
                ->setBody('badauth');
            return;
        }


        if (($domain = $this->apiHelper->findZone($hostname)) === null) {
            $this->getResponse()
                ->setBody('nohost');
            return;
        }

        $ip ??= $_SERVER['REMOTE_ADDR'];
        
        
        $records = $this->apiHelper->fetchZone([
            'domain' => $domain,
            'name' => $hostname,
            'type' => 'A'
        ]);

        foreach ($records as $record) {
            $record['address'] = $ip;
            $this->apiHelper->editZoneRecord($record);
        }


        $this->getResponse()
                ->setBody('good');
    }

    /*
     * PRIVATE / PROTECTED METHODS
     */

    /*
     * STATIC METHODS
     */
}
