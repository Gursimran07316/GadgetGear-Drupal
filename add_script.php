use Drupal\node\Entity\Node;

$products = [
  [
    'name' => 'Beats Studio Bluetooth Headphones',
    'description' => 'Experience the world of premium sound with Beats Studio Bluetooth Headphones...',
    'brand' => 'Beats',
    'category' => 'Electronics',
    'price' => 89.99,
    'stock' => 10,
    'rating' => 4.5,
    'reviews' => 12,
  ],
  [
    'name' => 'Samsung galaxy s23',
    'description' => 'Discover the Samsung Galaxy S23, featuring a revolutionary triple-camera system...',
    'brand' => 'Samsung',
    'category' => 'Electronics',
    'price' => 599.99,
    'stock' => 7,
    'rating' => 4.0,
    'reviews' => 8,
  ],
  [
    'name' => 'Alexa',
    'description' => 'Meet Alexa, the brain behind the Amazon Echo Dot...',
    'brand' => 'Amazon',
    'category' => 'Electronics',
    'price' => 929.99,
    'stock' => 5,
    'rating' => 3,
    'reviews' => 12,
  ],
  [
    'name' => 'Xbox',
    'description' => 'Enter the new era of gaming with Xbox...',
    'brand' => 'Microsoft',
    'category' => 'Electronics',
    'price' => 399.99,
    'stock' => 11,
    'rating' => 5,
    'reviews' => 12,
  ],
  [
    'name' => 'Ninetendo Switch',
    'description' => 'Dive into a flexible gaming experience with the Ninetendo Switch...',
    'brand' => 'Nintendo',
    'category' => 'Electronics',
    'price' => 49.99,
    'stock' => 7,
    'rating' => 3.5,
    'reviews' => 10,
  ],
  [
    'name' => 'JBL flip 5',
    'description' => 'Bring your music to life wherever you go with the JBL Flip 5...',
    'brand' => 'JBL',
    'category' => 'Electronics',
    'price' => 29.99,
    'stock' => 0,
    'rating' => 4,
    'reviews' => 12,
  ],
];

foreach ($products as $p) {
  $node = Node::create([
    'type' => 'product',
    'title' => $p['name'],
    'field_brand' => $p['brand'],
    'field_category' => $p['category'],
    'field_description' => $p['description'],
    'field_price' => $p['price'],
    'field_stock__quantity' => $p['stock'],
    'field_rating' => $p['rating'],
    'field_num_reviews' => $p['reviews'],
  ]);
  $node->save();
}
