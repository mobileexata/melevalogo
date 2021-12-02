<?php

class ModelToolUtil extends Model
{

  public function toggleOptionProduct($cart_id)
  {
    // $this->db->query("delete from `" . DB_PREFIX . "product_filter`");
    $resultCart = $this->db->query("select * from `" . DB_PREFIX . "cart` where `cart_id` = " . (int)$cart_id);

    sleep(2);
    return true;
    $this->db->query("insert ignore into `" . DB_PREFIX . "product_filter` (product_id, filter_id)
                      select product_id, filter_id 
                      from (select product_id as product_id, manufacturer_id as filter_id from `" . DB_PREFIX . "product`
                            union
                            select product_id, option_value_id from `" . DB_PREFIX . "product_option_value`) tempFilterTable");
  }

  public function changeQuantityCart($cart_id, $quantity)
  {
    $this->db->query("update `" . DB_PREFIX . "cart` set quantity = '" . (int)$quantity . "' where `cart_id` = " . (int)$cart_id);
    return true;
  }

  public function disableManufacturerWithoutActiveProdutcs()
  {
    $this->db->query("update oc_manufacturer set status = 0 where status = 1");
    $this->db->query("update oc_manufacturer set status = 1 where manufacturer_id in (select DISTINCT manufacturer_id from oc_product where status = 1)");
    return true;
  }
}
