<?php
declare(strict_types = 1);

namespace App\Helpers;

use App\Libs\CPanelClient;

class CPanelApiHelper
{
	/*
     * FIELDS
     */

	private $client;

    /*
     * GETTERS / SETTERS
     */
	
	/*
     * CONSTRUCTOR / INITIALIZER
     */

	function __construct(string $host, string $username, string $password, int $version = 2)
	{
		$this->client = new CPanelClient($host, $username, $password, $version);
	}
	
	function __destruct()
	{
	}

	/*
     * PUBLIC METHODS
     */

	public function fetchZones() : array
	{
		$response = $this->send('ZoneEdit', 'fetchzones');

		return $response['zones'];
	}
	
	public function fetchZone(array $record) : array
	{
		$response = $this->send('ZoneEdit', 'fetchzone', $record);

		$results = array_map(function ($result) use ($record) {
			$result['domain'] = $record['domain'];
			unset($result['Line']);
			unset($result['record']);

			return $result;
		}, $response['record']);
		
		return $results;
	}

	public function findZone(string $fqdn) : ?string
	{
		$fqdn = rtrim($fqdn, '.');
		$zones = array_keys($this->fetchZones());

        while (in_array($fqdn, $zones) === false && ($dotPos = strpos($fqdn, '.')) !== false) {
            $fqdn = substr($fqdn, $dotPos + 1);
        }

        if ($dotPos === false) {
            return null;
        }

        return $fqdn;
	}

    public function addZoneRecord(array $record) : void
	{
		$this->send('ZoneEdit', 'add_zone_record', $record);
	}

	
	public function editZoneRecord(array $record) : void
	{
		$this->send('ZoneEdit', 'edit_zone_record', $record);
	}

	public function removeZoneRecord(array $record) : void
	{
		$this->send('ZoneEdit', 'remove_zone_record', $record);
	}

	/*
     * PRIVATE / PROTECTED METHODS
     */

	private function send(string $module, string $function, array $params = []) : array
	{
		$this->prepareParams($params);

		$response = $this->client->send($module, $function, $params);
		$response = $this->handleResponse($response);

		if (isset($response['result'])) {
			$response = $response['result'];
		}

		if (@$response['status'] !== 1) {
			throw new \Exception($response['statusmsg']);
		}

		return $response;
	}

    private function prepareParams(array &$record) : void
    {
        if (isset($record['name'])) {
        	$record['name'] = rtrim($record['name'], '.') . '.';
		}
    }

	private function handleResponse(array $response) : array
	{
		// Global API error handling
		if (array_key_exists('error', $response)) {
			throw new \Exception($response['error']);
		}

		$result = $response['data'][0];

		return $result;
	}

	/*
     * STATIC METHODS
     */
}
