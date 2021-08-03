<?php
declare(strict_types = 1);

namespace App\Controllers;

use App\Config;
use App\Helpers\CPanelApiHelper;
use Core\Application;

class AcmeController extends AppController
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
    
    public function presentAction() : void
    {
        if (@$_SERVER['PHP_AUTH_USER'] !== $this->config->getCPanelUser() || @$_SERVER['PHP_AUTH_PW'] !== $this->config->getCPanelPass()) {
            $this->getResponse()
                ->setHeader('WWW-Authenticate', 'Basic realm="oZYXTs3X878r7JD5Jf", charset="UTF-8"')
                ->setHeader('HTTP/1.0 401 Unauthorized')
                ->setBody('badauth');
            return;
        }


        if (($json = json_decode(file_get_contents('php://input'), true)) === null) {
            $this->getResponse()
                ->setBody('nodata');
            return;
        }


        if (($domain = $this->apiHelper->findZone($json['fqdn'])) === null) {
            $this->getResponse()
                ->setBody('nohost');
            return;
        }


        $this->apiHelper->addZoneRecord([
            'domain' => $domain,
            'name' => $json['fqdn'],
            'type' => 'TXT',
            'txtdata' => $json['value'],
            'ttl' => 1
        ]);


        $this->getResponse()
                ->setBody('good');
    }

    public function cleanupAction()
    {
        if (@$_SERVER['PHP_AUTH_USER'] !== $this->config->getCPanelUser() || @$_SERVER['PHP_AUTH_PW'] !== $this->config->getCPanelPass()) {
            $this->getResponse()
                ->setHeader('WWW-Authenticate', 'Basic realm="oZYXTs3X878r7JD5Jf", charset="UTF-8"')
                ->setHeader('HTTP/1.0 401 Unauthorized')
                ->setBody('badauth');
            return;
        }


        if (($json = json_decode(file_get_contents('php://input'), true)) === null) {
            $this->getResponse()
                ->setBody('nodata');
            return;
        }


        if (($domain = $this->apiHelper->findZone($json['fqdn'])) === null) {
            $this->getResponse()
                ->setBody('nohost');
            return;
        }
        

        $records = $this->apiHelper->fetchZone([
            'domain' => $domain,
            'name' => $json['fqdn'],
            'type' => 'TXT',
            'txtdata' => $json['value'],
        ]);

        foreach ($records as $record) {
            $this->apiHelper->removeZoneRecord($record);
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
