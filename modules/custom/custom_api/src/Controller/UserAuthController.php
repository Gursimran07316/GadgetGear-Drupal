<?php

namespace Drupal\custom_api\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Drupal\key\Entity\Key;
use Firebase\JWT\JWT;

class UserAuthController extends ControllerBase {

  public function loginUser(Request $request) {
    $data = json_decode($request->getContent(), TRUE);
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password)) {
      return new JsonResponse(['message' => 'Email and password are required.'], 400);
    }

    $users = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties(['mail' => $email]);

    $user = reset($users);

    if (!$user || !$user->isActive()) {
      return new JsonResponse(['message' => 'Invalid user.'], 401);
    }

    $is_valid = \Drupal::service('user.auth')
      ->authenticate($user->getAccountName(), $password);

    if (!$is_valid) {
      return new JsonResponse(['message' => 'Invalid credentials.'], 403);
    }

    // Load JWT secret from Key module (make sure 'simple_oauth' key exists)
    $key = Key::load('simple_oauth');
    $secret = $key ? $key->getKeyValue() : '';

    if (!$secret) {
      return new JsonResponse(['message' => 'JWT secret not configured.'], 500);
    }

    // Create JWT token
    $payload = [
      'uid' => $user->id(),
      'name' => $user->getAccountName(),
      'email' => $user->getEmail(),
      'exp' => time() + 30 * 24 * 60 * 60, // 30 days
    ];

    $jwt = JWT::encode($payload, $secret, 'HS256');

    // Create response
    $response = new JsonResponse([
        '_id' => $user->id(),
        'name' => $user->getAccountName(),
        'email' => $user->getEmail(),
        'isAdmin' => in_array('administrator', $user->getRoles()),

    ]);

    // Set JWT as HTTP-only cookie
    $cookie = new Cookie(
      'jwt',                     // name
      $jwt,                      // value
      time() + 30 * 24 * 60 * 60, // expires in 30 days
      '/',                       // path
      null,                      // domain
      false,                     // secure (set to true on HTTPS)
      true,                      // httpOnly
      false,                     // raw
      'Strict'                   // sameSite
    );

    $response->headers->setCookie($cookie);
    return $response;
  }

  public function logoutUser(Request $request) {
    $response = new JsonResponse(['message' => 'Logged out successfully.'], 200);
  
    // Clear the JWT cookie by setting its expiration to the past
    $response->headers->clearCookie('jwt', '/', null, false, true, false, 'Strict');
  
    return $response;
  }
  public function registerUser(Request $request) {
    $data = json_decode($request->getContent(), TRUE);
    $name = $data['name'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
  
    if (empty($name) || empty($email) || empty($password)) {
      return new JsonResponse(['message' => 'Name, email, and password are required.'], 400);
    }
  
    $existing = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties(['mail' => $email]);
  
    if (!empty($existing)) {
      return new JsonResponse(['message' => 'User already exists.'], 400);
    }
  
    // Create new user
    $user = User::create([
      'name' => $name,
      'mail' => $email,
      'pass' => $password,
      'status' => 1,
    ]);
    $user->enforceIsNew();
    $user->save();
  
    // JWT secret
    $key = Key::load('simple_oauth');
    $secret = $key ? $key->getKeyValue() : '';
    if (!$secret) {
      return new JsonResponse(['message' => 'JWT secret not configured.'], 500);
    }
  
    $payload = [
      'uid' => $user->id(),
      'name' => $user->getAccountName(),
      'email' => $user->getEmail(),
      'exp' => time() + 30 * 24 * 60 * 60,
    ];
  
    $jwt = JWT::encode($payload, $secret, 'HS256');
  
    $response = new JsonResponse([
      '_id' => $user->id(),
      'name' => $user->getAccountName(),
      'email' => $user->getEmail(),
      'isAdmin' => in_array('administrator', $user->getRoles()),
    ], 201);
  
    $response->headers->setCookie(
      new \Symfony\Component\HttpFoundation\Cookie(
        'jwt',
        $jwt,
        time() + 30 * 24 * 60 * 60,
        '/',
        null,
        false,
        true,
        false,
        'Strict'
      )
    );
  
    return $response;
  }
  
}
