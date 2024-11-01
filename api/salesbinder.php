<?php

defined('WPINC') || header('HTTP/1.1 403') & exit;

function shopp_salesbinder($context, $id)
{
  $meta = new ObjectMeta();
  $meta->load(array(
    'context' => $context,
    'type' => 'salesbinder',
    'name' => 'id',
    'value' => $id
  ));

  if (empty($meta->named['id']) || !$meta->named['id']->parent) {
    return false;
  }

  return $meta->named['id']->parent;
}

function shopp_salesbinder_product($id)
{
  $product_id = shopp_salesbinder('product', $id);
  if (!$product_id) {
    return false;
  }

  return shopp_product($product_id);
}

function shopp_salesbinder_category($id)
{
  $category_id = shopp_salesbinder('category', $id);
  if (!$category_id) {
    return false;
  }

  return shopp_product_category($product_id);
}

function shopp_salesbinder_category_id($id)
{
  return shopp_salesbinder('category', $id);
}
