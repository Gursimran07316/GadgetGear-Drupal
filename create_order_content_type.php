<?php

use Drupal\node\Entity\NodeType;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

// Step 1: Create the content type
if (!NodeType::load('order')) {
  NodeType::create([
    'type' => 'order',
    'name' => 'Order',
    'description' => 'Stores customer orders with items, totals, and status.',
  ])->save();
}

// Field definitions
$fields = [
  'field_user' => [
    'type' => 'entity_reference',
    'label' => 'User',
    'settings' => ['target_type' => 'user'],
  ],
  'field_shipping' => [
    'type' => 'text_long',
    'label' => 'Shipping Address',
  ],
  'field_payment_method' => [
    'type' => 'list_string',
    'label' => 'Payment Method',
    'settings' => ['allowed_values' => ['PayPal' => 'PayPal', 'Credit Card' => 'Credit Card']],
  ],
  'field_items_price' => [
    'type' => 'decimal',
    'label' => 'Items Price',
  ],
  'field_tax_price' => [
    'type' => 'decimal',
    'label' => 'Tax Price',
  ],
  'field_shipping_price' => [
    'type' => 'decimal',
    'label' => 'Shipping Price',
  ],
  'field_total_price' => [
    'type' => 'decimal',
    'label' => 'Total Price',
  ],
  'field_is_paid' => [
    'type' => 'boolean',
    'label' => 'Is Paid',
  ],
  'field_is_delivered' => [
    'type' => 'boolean',
    'label' => 'Is Delivered',
  ],
];

// Step 2: Create field storage & instance for each field
foreach ($fields as $field_name => $info) {
  if (!FieldStorageConfig::loadByName('node', $field_name)) {
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => $info['type'],
      'settings' => $info['settings'] ?? [],
      'cardinality' => 1,
    ])->save();
  }

  if (!FieldConfig::loadByName('node', 'order', $field_name)) {
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => 'order',
      'label' => $info['label'],
      'settings' => $info['settings'] ?? [],
    ])->save();
  }
}

print "✅ Order content type and fields created.\n";
