<?php

namespace Drupal\Tests\jwt\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use GuzzleHttp\RequestOptions;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Serializer;

/**
 * Tests HTTP login and JWT tokens.
 *
 * @see \Drupal\Tests\basic_auth\Functional\BasicAuthTest
 *
 * @group jwt
 */
class JwtAuthLoginTest extends BrowserTestBase {

  /**
   * Modules installed for all tests.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'router_test',
    'key',
    'jwt',
    'jwt_auth_issuer',
    'jwt_auth_consumer',
    'jwt_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $encoders = [new JsonEncoder(), new XmlEncoder()];
    $this->serializer = new Serializer([], $encoders);
  }

  /**
   * Tests HTTP login and JWT tokens.
   */
  public function testJwtAuthLogin() {
    $name = $this->randomMachineName();
    $pass = \Drupal::service('password_generator')->generate();
    $user = $this->drupalCreateUser(['access content'], $name);
    $user->setPassword($pass);
    $user->passRaw = $pass;
    $user->save();

    $response = $this->loginRequest($name, $pass);
    $this->assertSame(200, $response->getStatusCode());
    $data = Json::decode($response->getBody());
    $this->assertArrayHasKey('access_token', $data);

    // Use the token to access a protected resource.
    $url = Url::fromRoute('jwt_test.11.1');
    $request_options = [
      RequestOptions::HEADERS => ['Authorization' => 'Bearer ' . $data['access_token']],
    ];
    $response = $this->makeApiRequest('GET', $url, $request_options);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertStringContainsString($user->getAccountName(), (string) $response->getBody());

    // No auth.
    $response = $this->makeApiRequest('GET', $url, []);
    $this->assertSame(403, $response->getStatusCode());

    // Test alternative serializers.
    \Drupal::service('module_installer')->install(['serialization']);
    $this->rebuildAll();

    $response = $this->loginRequest($name, $pass, 'xml');
    $this->assertSame(200, $response->getStatusCode());
    $data = $this->serializer->decode($response->getBody(), 'xml');
    $this->assertArrayHasKey('access_token', $data);

    // Change it so that the JWT is not included in the login response.
    $this->drupalLogin($this->drupalCreateUser(['administer jwt']));
    $this->drupalGet('admin/config/system/jwt');
    $this->submitForm(['jwt_auth_issuer[jwt_in_login_response]' => FALSE], 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->drupalLogout();

    $response = $this->loginRequest($name, $pass);
    $this->assertSame(200, $response->getStatusCode());
    $data = Json::decode($response->getBody());
    // The JWT token will not be present.
    $this->assertArrayNotHasKey('access_token', $data);
  }

  /**
   * Executes a login HTTP request for a given serialization format.
   *
   * @param string $name
   *   The username.
   * @param string $pass
   *   The user password.
   * @param string $format
   *   The format to use to make the request.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The HTTP response.
   */
  protected function loginRequest($name, $pass, $format = 'json') {
    $user_login_url = Url::fromRoute('user.login.http')
      ->setRouteParameter('_format', $format)
      ->setAbsolute();

    $request_body = [
      'name' => $name,
      'pass' => $pass,
    ];

    $result = \Drupal::httpClient()->post($user_login_url->toString(), [
      'body' => $this->serializer->encode($request_body, $format),
      'headers' => [
        'Accept' => "application/$format",
      ],
      'http_errors' => FALSE,
    ]);
    return $result;
  }

  /**
   * Performs an HTTP request. Wraps the Guzzle HTTP client.
   *
   * In Drupal 10+ this is provided by Drupal\Tests\ApiRequestTrait. This
   * code copied in for Drupal 9 support.
   *
   * Why wrap the Guzzle HTTP client? Because we want to keep the actual test
   * code as simple as possible, and hence not require them to specify the
   * 'http_errors = FALSE' request option, nor do we want them to have to
   * convert Drupal Url objects to strings.
   *
   * We also don't want to follow redirects automatically, to ensure these tests
   * are able to detect when redirects are added or removed.
   *
   * @param string $method
   *   HTTP method.
   * @param \Drupal\Core\Url $url
   *   URL to request.
   * @param array $request_options
   *   Request options to apply.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response.
   *
   * @see \GuzzleHttp\ClientInterface::request()
   */
  protected function makeApiRequest($method, Url $url, array $request_options) {
    // HEAD requests do not have bodies. If one is specified, Guzzle will not
    // ignore it and the request will be treated as GET with an overridden
    // method string, and libcurl will expect to read a response body.
    if ($method === 'HEAD' && array_key_exists('body', $request_options)) {
      unset($request_options['body']);
    }
    $this->refreshVariables();
    $request_options[RequestOptions::HTTP_ERRORS] = FALSE;
    $request_options[RequestOptions::ALLOW_REDIRECTS] = FALSE;
    $client = $this->getSession()->getDriver()->getClient()->getClient();
    return $client->request($method, $url->setAbsolute(TRUE)->toString(), $request_options);
  }

}
