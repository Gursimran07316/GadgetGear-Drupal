<?php

namespace Drupal\custom_api\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\Entity\Node;

class ProductApiController {

  public function getProducts() {
    $request = \Drupal::request();
    $queryParam = $request->query;
  
    $pageSize = getenv('PAGINATION_LIMIT') ?: 8;
    $page = max(1, (int) $queryParam->get('pageNumber', 1));
    $keyword = $queryParam->get('keyword');
  
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'product')
      ->condition('status', 1);
  
    if ($keyword) {
      $query->condition('title', '%' . $keyword . '%', 'LIKE');
    }
  
    $count = $query->count()->execute();
  
    // Rebuild query for actual fetch
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'product')
      ->condition('status', 1);
  
    if ($keyword) {
      $query->condition('title', '%' . $keyword . '%', 'LIKE');
    }
  
    $nids = $query
      ->range(($page - 1) * $pageSize, $pageSize)
      ->sort('nid', 'DESC')
      ->execute();
  
    $nodes = Node::loadMultiple($nids);
    $products = [];
  
    foreach ($nodes as $node) {
      $products[] = [
        '_id' => $node->id(),
        'name' => $node->getTitle(),
        'brand' => $node->get('field_brand')->value,
        'category' => $node->get('field_category')->value,
        'description' => $node->get('field_description')->value,
        'price' => $node->get('field_price')->value,
        'stock_quantity' => $node->get('field_stock__quantity')->value,
        'rating' => $node->get('field_rating')->value,
        'num_reviews' => $node->get('field_num_reviews')->value,
        'image' => $node->get('field_product_image')->entity
          ? \Drupal::service('file_url_generator')->generateAbsoluteString(
              $node->get('field_product_image')->entity->getFileUri()
            )
          : null,
          'numReviews'=>0
      ];
    }
  
    return new JsonResponse([
      'products' => $products,
      'page' => $page,
      'pages' => ceil($count / $pageSize),
    ]);
  }

  public function getTopProducts() {
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'product')
      ->condition('status', 1)
      ->sort('field_rating', 'DESC')
      ->range(0, 3);
  
    $nids = $query->execute();
    $nodes = Node::loadMultiple($nids);
  
    $products = [];
  
    foreach ($nodes as $node) {
      $products[] = [
        '_id' => $node->id(),
        'name' => $node->getTitle(),
        'brand' => $node->get('field_brand')->value,
        'category' => $node->get('field_category')->value,
        'description' => $node->get('field_description')->value,
        'price' => $node->get('field_price')->value,
        'stock_quantity' => $node->get('field_stock__quantity')->value,
        'rating' => $node->get('field_rating')->value,
        'num_reviews' => $node->get('field_num_reviews')->value,
        'image' => $node->get('field_product_image')->entity
          ? \Drupal::service('file_url_generator')->generateAbsoluteString(
              $node->get('field_product_image')->entity->getFileUri()
            )
          : null,
          'numReviews'=>0
      ];
    }
  
    return new JsonResponse($products);
  }
  
  public function getProductById($id) {
  $node = Node::load($id);

  if ($node && $node->bundle() === 'product' && $node->isPublished()) {
    $product = [
      '_id' => $node->id(),
      'name' => $node->getTitle(),
      'brand' => $node->get('field_brand')->value,
      'category' => $node->get('field_category')->value,
      'description' => $node->get('field_description')->value,
      'price' => $node->get('field_price')->value,
      'stock_quantity' => $node->get('field_stock__quantity')->value,
      'rating' => $node->get('field_rating')->value,
      'num_reviews' => $node->get('field_num_reviews')->value,
      'image' => $node->get('field_product_image')->entity
        ? \Drupal::service('file_url_generator')->generateAbsoluteString(
            $node->get('field_product_image')->entity->getFileUri()
          )
        : null,
      'reviews'=>[]
    ];

    return new JsonResponse($product);
  } else {
    return new JsonResponse(['message' => 'Product not found'], 404);
  }
}

}
