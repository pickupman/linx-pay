<?php
namespace Pickupman;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;
use Sainsburys\Guzzle\Oauth2\GrantType\RefreshToken;
use Sainsburys\Guzzle\Oauth2\GrantType\ClientCredentials;
use Sainsburys\Guzzle\Oauth2\GrantType\PasswordCredentials;
use Sainsburys\Guzzle\Oauth2\Middleware\OAuthMiddleware;

class LinxPay
{
	public $base_uri = 'https://linxpay-staging.linxkiosk.com';
	public $username;
	public $password;
	public $client_id;
	public $client_secret;
	protected $token_url = '/oauth/token';
	protected $endpoint;
	protected $endpointMethod;
	protected $endpointRequiredFields = [];


	public function __construct($options = [])
	{
		foreach($options as $item => $key)
		{
			$this->$item = $key;
		}

		foreach($this->getOauthDefaults() as $required)
		{
			if ( empty($this->$required) )
				throw new Exception('Missing required configuration parameter: ' . $this->$required);
		}
	}

	/**
	 * Get default fields for OAuth2 flow
	 * @return array required fields
	 */
	public function getOauthDefaults()
	{
		return ['client_id', 'client_secret'];
	}

	/**
	 * Get required fields for API endpoint
	 * @return array required fields
	 */
	private function getRequiredFields()
	{
		return $this->endpointRequiredFields;
	}

	/**
	 * Execute call to API endpoint
	 * @param  mixed $fields array of fields to send to API
	 * @return object json response object
	 */
	private function execute($fields = false)
	{

		if ( empty($this->endpoint) OR empty($this->endpointMethod) )
		{
			throw new Exception('Invalid endpoint');
		}

		$handlerStack = HandlerStack::create();
		$client       = new Client(['handler'=> $handlerStack, 'base_uri' => $this->base_uri, 'auth' => 'oauth2']);

		$config = [
			PasswordCredentials::CONFIG_USERNAME      => $this->username,
			PasswordCredentials::CONFIG_PASSWORD      => $this->password,
			PasswordCredentials::CONFIG_CLIENT_SECRET => $this->client_secret,
			PasswordCredentials::CONFIG_CLIENT_ID     => $this->client_id,
			PasswordCredentials::CONFIG_TOKEN_URL     => $this->token_url
		];

		try
		{
			$token        = new ClientCredentials($client, $config);
			$refreshToken = new RefreshToken($client, $config);
			$middleware   = new OAuthMiddleware($client, $token, $refreshToken);
		} catch (Exception $e) {
			return $e->getMessage();
		}

		$handlerStack->push($middleware->onBefore());
		$handlerStack->push($middleware->onFailure(5));

		try {
			if ( $this->endpointMethod == 'GET' ) {
				$response = $client->get($this->endpoint);
			}

			if ( $this->endpointMethod == 'POST' ) {
				$response = $client->post($this->endpoint, [
					'form_params' => $fields
				]);
			}
		}
		catch(ClientException $e)
		{
			return json_decode((string)$e->getResponse()->getBody());
		}

		return json_decode($response->getBody()->getContents());

	}

	/**
	 * Poll API
	 * @return object json object
	 */
	public function poll()
	{
		$this->endpoint = '/api/v1/poll';
		$this->endpointMethod = 'GET';
		return $this->execute();
	}

	/**
	 * Redeem a Linx card transaction
	 * @param  array  $fields fields to send to API
	 * @return object json response object
	 */
	public function redemption($fields = [])
	{
		$this->endpoint = '/api/v1/redemptions/redemption';
		$this->endpointMethod = 'POST';
		$this->setRequiredFields([
			'linx_card_number',
			'customer',
			'product_type',
			'store_location',
			'budtender',
			'amount',
		]);

		$this->validate($fields);
		return $this->execute($fields);
	}

	/**
	 * Set username for OAuth2
	 * @param string $username
	 * @return $this
	 */
	public function setUsername($username)
	{
		$this->username = $username;
		return $this;
	}

	/**
	 * Set password for OAuth2
	 * @param string $password
	 * @return $this
	 */
	public function setPassword($password)
	{
		$this->password = $password;
		return $this;
	}

	/**
	 * Set client_id for OAuth2
	 * @param string $client_id
	 * @return $this
	 */
	public function setClientId($client_id)
	{
		$this->client_id = $client_id;
		return $this;
	}

	/**
	 * Set client_secret
	 * @param string $client_secret
	 * @return $this
	 */
	public function setClientSecret($client_secret)
	{
		$this->client_secret = $client_secret;
		return $this;
	}

	/**
	 * Set fields that are required in the endpoint
	 * @param array $fields
	 * @return $this
	 */
	private function setRequiredFields($fields = [])
	{
		foreach($fields as $field)
		{
			array_push($this->endpointRequiredFields, $field);
		}
		return $this;
	}

	/**
	 * Validate the the required fields are set
	 * @param  array $fields fields
	 * @return boolean
	 */
	private function validate($fields)
	{
		// Make sure all fields are set that are required by the API endpoint
		foreach($this->getRequiredFields() as $field)
		{
			if ( ! array_key_exists($field, $fields) )
			{
				throw new Exception('Missing required field ' . $field);
			}
		}

		if ( isset($fields['product_type']) AND ($fields['product_type'] !== 'recreational' AND $fields['product_type'] !== 'medicinal'))
			throw new Exception('Invalid product_type. Valid values are "recreational" or "medicinal"');

		if ( isset($fields['amount']) AND !is_numeric($fields['amount']))
			throw new Exception('Invalid amount. amount must be numeric');

		if ( isset($fields['amount']) AND $fields['amount'] == 0)
			throw new Exception('Invalid amount. amount can not be 0');

		if ( isset($fields['customer']) )
		{
			if ( ! isset($fields['customer']['type']) )
				throw new Exception('Invalid customer type. Must be "drivers_license" or "passport"');

			if ( $fields['customer']['type'] !== 'drivers_license' AND $fields['customer_type'] !== 'passport')
				throw new Exception('Invalid customer type. Must be "drivers_license" or "passport"');

			if ( ! isset($fields['customer']['id_number']) )
				throw new Exception('Invalid customer id_number.');

			if ( $fields['customer']['type'] == 'drivers_license' AND ! isset($fields['customer']['state']) )
				throw new Exception('Invalid customer state. Must provide customer state with a drivers_license type');

			if ( $fields['customer']['type'] == 'passport' AND ! isset($fields['customer']['country']) )
				throw new Exception('Invalid customer state. Must provide customer country with a passport type');
		}

		if ( isset($fields['store_location']) AND ! isset($fields['store_location']['name']) )
				throw new Exception('Invalid store name.');

		if ( isset($fields['budtender']) AND ! isset($fields['budtender']['name']) )
				throw new Exception('Invalid budtender name.');

		return true;
	}
}

