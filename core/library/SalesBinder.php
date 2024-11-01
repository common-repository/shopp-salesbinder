<?php

defined('WPINC') || header('HTTP/1.1 403') & exit;

class Shopp_SalesBinder
{
  private static $instance;
  private $subdomain;
  private $api_key;

  public static function plugin()
  {
    if (!self::$instance instanceof self) {
      self::$instance = new self;
    }

    return self::$instance;
  }

  private function __construct()
  {
    add_action('shopp_setup', array($this, 'register_activation_hook'));
    add_action('shopp_init', array($this, 'shopp_init'), 99);
    add_action('shopp_salesbinder_cron', array($this, 'cron'));

    add_filter('cron_schedules', array($this, 'cron_schedules'));

    register_activation_hook(__FILE__, array($this, 'register_activation_hook'));
    register_deactivation_hook(__FILE__, array($this, 'register_deactivation_hook'));
  }

  public function register_activation_hook()
  {
    wp_clear_scheduled_hook('shopp_salesbinder_cron');

    if (function_exists('shopp_setting')) {
      $interval = shopp_setting('salesbinder_sync_interval');
      if (!empty($interval)) {
        wp_schedule_event(time(), $interval, 'shopp_salesbinder_cron');
      }
    }
  }

  public function register_deactivation_hook()
  {
    wp_clear_scheduled_hook('shopp_salesbinder_cron');
  }

  public function cron_schedules($schedules)
  {
    return $schedules + array(
      'onceevery5minutes' => array(
        'interval' => 5 * 60,
        'display' => 'Once Every 5 Minutes',
        'shopp_salesbinder' => true
      ),
      'twicehourly' => array(
        'interval' => 30 * 60,
        'display' => 'Twice Hourly',
        'shopp_salesbinder' => true
      )
    );
  }

  public function cron()
  {
    if (empty($this->subdomain) || empty($this->api_key)) {
      return;
    }

    if (!ini_get('safe_mode')) {
      set_time_limit(0);
    }

    shopp_debug('SalesBinder sync started' . str_repeat('-', 64));

    $this->sync_categories();
    $this->sync_products();

    shopp_empty_search_index();
    shopp_rebuild_search_index();
    delete_option('rewrite_rules');

    shopp_debug('SalesBinder sync completed' . str_repeat('-', 64));
  }

  public function shopp_init()
  {
    $this->subdomain = shopp_setting('salesbinder_subdomain');
    $this->api_key = shopp_setting('salesbinder_api_key');

    add_action('admin_menu', array($this, 'admin_menu'), 50);
    add_action('shopp_order_success', array($this, 'shopp_order_success'));
    add_filter('shopp_cart_taxrate_settings', array($this, 'shopp_cart_taxrate_settings'));
  }

  public function admin_menu()
  {
    shopp_admin_add_submenu('SalesBinder', 'shopp-setup-salesbinder', 'shopp-setup', array(new Shopp_SalesBinderAdminSetup, 'salesbinder'), 'shopp_settings');
  }

  public function shopp_cart_taxrate_settings($taxrates)
  {
    foreach ($taxrates as &$taxrate) {
      $taxrate['label'] = $taxrate['rate'] . ' - ' . (!empty($taxrate['zone']) ? $taxrate['zone'] . ', ' : '') . $taxrate['country'];
    }
    return $taxrates;
  }

  private function account($context, $email)
  {
    $url = 'https://' . $this->subdomain . '.salesbinder.com/api/customers.json?contextId=' . $context . '&email=' . urlencode($email);
    $response = wp_remote_get($url, array(
      'headers' => array(
        'Authorization' => 'Basic ' . base64_encode($this->api_key . ':x')
      )
    ));

    if (wp_remote_retrieve_response_code($response) != 200 || is_wp_error($response)) {
      shopp_debug('SalesBinder sync failed to load ' . $url);
      return;
    }

    $response = json_decode(wp_remote_retrieve_body($response), true);
    if (!empty($response['Customers'][0]['Customer']['id'])) {
      return $response['Customers'][0]['Customer']['id'];
    }
  }

  public function shopp_order_success($purchase)
  {
    $account_context = shopp_setting('salesbinder_context_account');

    $account_id = $this->account($account_context, $purchase->email);
    if (empty($account_id)) {
      $account = array(
        'Customer' => array(
          'context_id' => $account_context ?: 8,
          'name' => $purchase->company ?: $purchase->firstname . ' ' . $purchase->lastname,
          'office_email' => $purchase->email,
          'office_phone' => $purchase->phone,
          'billing_address_1' => $purchase->address,
          'billing_address_2' => $purchase->xaddress,
          'billing_city' => $purchase->city,
          'billing_region' => $purchase->state,
          'billing_country' => $purchase->country,
          'billing_postal_code' => $purchase->postcode,
          'shipping_address_1' => $purchase->shipaddress,
          'shipping_address_2' => $purchase->shipxaddress,
          'shipping_city' => $purchase->shipcity,
          'shipping_region' => $purchase->shipstate,
          'shipping_country' => $purchase->shipcountry,
          'shipping_postal_code' => $purchase->shippostcode
        )
      );

      $url = 'https://' . $this->subdomain . '.salesbinder.com/api/customers.json';
      $response = wp_remote_post($url, array(
        'headers' => array(
          'Authorization' => 'Basic ' . base64_encode($this->api_key . ':x')
        ),
        'body' => json_encode($account),
        'redirection' => 5
      ));

      if (wp_remote_retrieve_response_code($response) != 200 || is_wp_error($response)) {
        shopp_debug('SalesBinder sync failed to load ' . $url);
        return;
      }

      $account = json_decode($response['body'], true);
      if (empty($account['Customer']['id'])) {
        shopp_debug('SalesBinder sync failed to load ' . $url);
        return;
      }

      $account_id = $account['Customer']['id'];
    }

    $document_context = shopp_setting('salesbinder_context_document');
    $document = array(
      'Document' => array(
        'context_id' => $document_context ?: 4,
        'customer_id' => $account_id,
        'issue_date' => date('Y-m-d', $purchase->created),
        'shipping_address' => $purchase->shipaddress . (!empty($purchase->shipxaddress) ? PHP_EOL . $purchase->shipxaddress : '') . PHP_EOL . $purchase->shipcity . (!empty($purchase->shipstate) ? ', ' . $purchase->shipstate : '') . '  ' . $purchase->shippostcode . PHP_EOL . $purchase->shipcountry
      ),
      'DocumentsItem' => array()
    );

    $taxes = array_values($purchase->taxes());

    foreach (shopp_order_lines($purchase->id) as $order_line) {
      $meta = shopp_meta($order_line->product, 'product', 'id', 'salesbinder');
      if (!empty($meta)) {
        $item = array(
          'item_id' => $meta,
          'quantity' => $order_line->quantity,
          'price' => $order_line->unitprice
        );

        if ($order_line->unittax != 0) {
          if (!empty($taxes[0])) {
            $item['tax'] = $taxes[0]->rate * 100;
          }

          if (!empty($taxes[1])) {
            $item['tax2'] = $taxes[1]->rate * 100;
          }
        }

        $document['DocumentsItem'][] = $item;
      }
    }

    $url = 'https://' . $this->subdomain . '.salesbinder.com/api/documents.json';
    $response = wp_remote_post($url, array(
      'headers' => array(
        'Authorization' => 'Basic ' . base64_encode($this->api_key . ':x')
      ),
      'body' => json_encode($document),
      'redirection' => 5
    ));

    if (wp_remote_retrieve_response_code($response) != 200 || is_wp_error($response)) {
      shopp_debug('SalesBinder sync failed to load ' . $url);
      return;
    }

    $customer = json_decode($response['body'], true);
    if (empty($customer['Document']['id'])) {
      shopp_debug('SalesBinder sync failed to load ' . $url);
      return;
    }

    shopp_set_meta($purchase->id, 'purchase', 'id', $customer['Document']['id'], 'salesbinder');
  }

  private function sync_categories()
  {
    $page = 1;
    $category_ids = array();
    do {
      $url = 'https://' . $this->subdomain . '.salesbinder.com/api/categories.json?page=' . $page;
      $response = wp_remote_get($url, array(
        'headers' => array(
          'Authorization' => 'Basic ' . base64_encode($this->api_key . ':x')
        )
      ));

      if (wp_remote_retrieve_response_code($response) != 200 || is_wp_error($response)) {
        shopp_debug('SalesBinder sync failed to load ' . $url);
        return;
      }

      $response = json_decode(wp_remote_retrieve_body($response), true);
      if (!empty($response['Categories'])) {
        foreach ($response['Categories'] as $category) {
          $category_id = shopp_add_product_category($category['Category']['name'], $category['Category']['description']);
          if (!$category_id) {
            $term = get_term_by('name', $category['Category']['name'], ProductCategory::$taxon);
            if (!empty($term->term_id)) {
              $category_id = $term->term_id;

              wp_update_term($category_id, ProductCategory::$taxon, array(
                'description' => $category['Category']['description']
              ));
            } else {
              $category_id = null;
            }
          }

          if (!empty($category_id)) {
            shopp_set_meta($category_id, 'category', 'id', $category['Category']['id'], 'salesbinder');

            $category_ids[] = $category_id;
          }
        }
      }
    } while(!empty($response['pages']) && ++$page <= $response['pages']);

    foreach (shopp_meta(false, 'category', 'id', 'salesbinder') as $category) {
      if (!in_array($category->parent, $category_ids)) {
        shopp_rmv_product_category($category->parent);
      }
    }
  }

  private function sync_products()
  {
    $page = 1;
    $product_ids = array();

    $storage_engines = new StorageEngines();
    $storage_engine = $storage_engines->type('image');
    if ($storage_engine == 'DBStorage') {
      $variables = sDB::query('SHOW VARIABLES LIKE "max_allowed_packet"', 'array');
      if (!empty($variables[0]) && !empty($variables[0]->Value)) {
        $max_allowed_packet = $variables[0]->Value;
      } else {
        $max_allowed_packet = 1048576;
      }
      $max_image_size = ($max_allowed_packet - 48576);
    } else {
      $max_image_size = 67108864;
    }

    do {
      $url = 'https://' . $this->subdomain . '.salesbinder.com/api/items.json?page=' . $page . '&page_limit=50&order_field=modified&order_direction=desc';
      $response = wp_remote_get($url, array(
        'headers' => array(
          'Authorization' => 'Basic ' . base64_encode($this->api_key . ':x')
        )
      ));

      if (wp_remote_retrieve_response_code($response) != 200 || is_wp_error($response)) {
        shopp_debug('SalesBinder sync failed to load ' . $url);
        return;
      }

      $response = json_decode(wp_remote_retrieve_body($response), true);
      if (!empty($response['Items'])) {
        foreach ($response['Items'] as $item) {
          if (!$item['Item']['published']) {
            continue;
          }

          $product = shopp_salesbinder_product($item['Item']['id']);
          if ($product) {
            shopp_update_product($product, $data = array(
              'name' => $item['Item']['name'],
              'description' => $item['Item']['description']
            ));
          } else {
            $product = shopp_add_product(array(
              'name' => $item['Item']['name'],
              'description' => $item['Item']['description'],
              'publish' => array(
                'flag' => true
              ),
              'single' => array(
                'type' => 'Shipped',
                'shipping' => array(
                  'flag' => false
                )
              )
            ));

            if (!$product) {
              shopp_debug('SalesBinder sync failed to add ' . $item['Item']['name'] . ' (' . $item['Item']['id'] . ')');
              continue;
            }

            shopp_set_meta($product->id, 'product', 'id', $item['Item']['id'], 'salesbinder');

            $product->comment_status = 'closed';
            $product->ping_status = 'closed';
            $product->save();
          }

          shopp_product_set_price($product->id, $item['Item']['price']);
          $product->load_data(array('prices','summary'));

          shopp_product_set_inventory($product->id, true, array(
            'stock' => $item['Item']['quantity'],
            'sku' => $item['Item']['sku']
          ));

/*
          $specs = array();
          if (!empty($item['ItemDetail'])) {
            foreach ($item['ItemDetail'] as $detail) {
              if (!empty($detail['CustomField']['publish'])) {
                $specs[$detail['CustomField']['name']] = $detail['value'];
              }
            }

            if (!empty($specs)) {
              shopp_product_set_specs($product->id, $specs);
            }
          }
*/
                    
          $specs = array();
          if (!empty($item['ItemDetail'])) {
            foreach ($item['ItemDetail'] as $detail) {
              if (!empty($detail['CustomField']['publish'])) {
                $specs[$detail['CustomField']['name']] = $detail['value'];
              }
              if (isset($detail['CustomField']['name']) && (strpos(strtolower($detail['CustomField']['name']),'weight') !== false)) {
                // create setting array
                // units are setup in your shipping settings in the Admin
                $ship_settings = array(
                    'weight' => !empty($detail['value']) ? $detail['value'] : 0,
                    'width' => '',
                    'length' => '',
                    'height' => ''
                );
                shopp_product_set_shipping ( $product->id, true, $ship_settings );
              }
            }
        
            if (!empty($specs)) {
              shopp_product_set_specs($product->id, $specs);
            }
          }          

          foreach (shopp_product_specs($product->id) as $spec) {
            if (!array_key_exists($spec->name, $specs)) {
              shopp_product_rmv_spec($product->id, $spec->name);
            }
          }

          $filenames = array();
          $existing_filenames = array();
          $images = shopp_meta($product->id, 'product', false, 'image');
          if (!empty($images)) {
            foreach ($images as $id => $image) {
              $existing_filenames[$id] = $image->value->filename;
            }
          }
          if (!empty($item['Image'])) {
            foreach ($item['Image'] as $image) {
              
              // get url_medium filename
              $path_parts = pathinfo($image['url_medium']);
              $image['filename'] = $path_parts['basename'];
              
              if (!in_array($image['filename'], $existing_filenames)) {
                
                $image_response = wp_remote_get($image['url_medium'], array(
                  'stream' => true
                ));

                if (wp_remote_retrieve_response_code($image_response) != 200 || is_wp_error($image_response) || !is_readable($image_response['filename'])) {
                  shopp_debug('SalesBinder sync failed to download ' . $image['url_medium']);
                  continue;
                }

                if (filesize($image_response['filename']) > $max_image_size) {
                  shopp_debug('SalesBinder sync failed to add ' . $image['url_medium'] . ' because the file is too large (> ' . $max_image_size . ' bytes)');
                  unlink($image_response['filename']);
                  continue;
                }

                shopp_add_image($product->id, 'product', $image_response['filename']);

                unlink($image_response['filename']);
              }

              $filenames[] = $image['filename'];
            }
          }
          if (!empty($existing_filenames)) {
            foreach ($existing_filenames as $id => $existing_filename) {
              if (!in_array($existing_filename, $filenames)) {
                $product->delete_images(array($id));
              }
            }
          }

          $category_ids = array();
          if (!empty($item['Category']['id'])) {
            $category_ids[] = shopp_salesbinder_category_id($item['Category']['id']);
          }
          shopp_product_add_terms($product->id, $category_ids, ProductCategory::$taxon, false);

          $product_ids[] = $product->id;
        }
      }
    } while(!empty($response['pages']) && ++$page <= $response['pages']);

    foreach (shopp_meta(false, 'product', 'id', 'salesbinder') as $product) {
      if (!in_array($product->parent, $product_ids)) {
        shopp_rmv_product($product->parent);
      }
    }
  }
}
