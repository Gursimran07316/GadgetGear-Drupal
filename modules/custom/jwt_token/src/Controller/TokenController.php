<?php

namespace Drupal\jwt_token\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\user\Entity\User;
use Firebase\JWT\JWT;
use Drupal\key\Entity\Key;

class TokenController {
  public function getToken(Request $request) {
    $data = json_decode($request->getContent(), TRUE);

    if (empty($data['name']) || empty($data['pass'])) {
      return new JsonResponse(['error' => 'Username and password required.'], 400);
    }

    $users = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties(['name' => $data['name']]);

    $user = reset($users);

    if (!$user || !$user->isActive()) {
      return new JsonResponse(['error' => 'Invalid user.'], 401);
    }

    $is_valid = \Drupal::service('user.auth')
      ->authenticate($data['name'], $data['pass']);

    if (!$is_valid) {
      return new JsonResponse(['error' => 'Invalid credentials.'], 403);
    }

    $key = Key::load('simple_oauth'); // Replace with your actual JWT key ID
    $secret = $key ? $key->getKeyValue() : '';

    if (!$secret) {
      return new JsonResponse(['error' => 'JWT secret not configured.'], 500);
    }

    $payload = [
      'uid' => $user->id(),
      'name' => $user->getAccountName(),
      'exp' => time() + 3600, // 1 hour expiry
    ];

    $jwt = JWT::encode($payload, $secret, 'HS256');

    return new JsonResponse(['token' => $jwt]);
  }
}
