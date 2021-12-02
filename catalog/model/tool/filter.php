<?php

class ModelToolFilter extends Model
{

  private $prefix_manufacturer_id = '5555';
  private $prefix_option_value_id = '6666';

  public function createManufacturerFilters()
  {
    $this->db->query("delete from `" . DB_PREFIX . "filter` where filter_group_id = 1");
    $this->db->query("delete from `" . DB_PREFIX . "filter_description` where language_id =  2 and filter_group_id = 1");

    $this->db->query("insert ignore into `" . DB_PREFIX . "filter` (filter_id, filter_group_id, sort_order)
                      select concat(manufacturer_id, '" . $this->prefix_manufacturer_id . "'), 1, 1
                      from `" . DB_PREFIX . "manufacturer`");
    $this->db->query("insert ignore into `" . DB_PREFIX . "filter_description` (filter_id, language_id, filter_group_id, name)
                      select concat(manufacturer_id, '" . $this->prefix_manufacturer_id . "'), 2, 1, name
                      from `" . DB_PREFIX . "manufacturer`");
  }

  public function createOptionFilters()
  {
    $this->db->query("delete from `" . DB_PREFIX . "filter` where filter_group_id = 2");
    $this->db->query("delete from `" . DB_PREFIX . "filter_description` where language_id =  2 and filter_group_id = 2");

    $this->db->query("insert ignore into `" . DB_PREFIX . "filter` (filter_id, filter_group_id, sort_order)
                      select concat(option_value_id, '" . $this->prefix_option_value_id . "'), 2, 1
                      from `" . DB_PREFIX . "option_value_description`
                      where option_id <> 999");
    $this->db->query("insert ignore into `" . DB_PREFIX . "filter_description` (filter_id, language_id, filter_group_id, name)
                      select concat(option_value_id, '" . $this->prefix_option_value_id . "'), 2, 2, name
                      from `" . DB_PREFIX . "option_value_description`
                      where option_id <> 999");
  }

  public function createCategoryFilters()
  {
    $this->db->query("delete from `" . DB_PREFIX . "category_filter`");
    $this->db->query("insert ignore into `" . DB_PREFIX . "category_filter` (category_id, filter_id)
                      select DISTINCT ptc.category_id, concat(p.manufacturer_id, '" . $this->prefix_manufacturer_id . "')
                      from `" . DB_PREFIX . "product_to_category` ptc
                      inner join `" . DB_PREFIX . "product` p on p.product_id = ptc.product_id");

    $this->db->query("insert ignore into `" . DB_PREFIX . "category_filter` (category_id, filter_id)
                      select distinct ptc.category_id, concat(pov.option_value_id, '" . $this->prefix_option_value_id . "')
                      from `" . DB_PREFIX . "product` p
                      inner join `" . DB_PREFIX . "product_to_category` ptc on p.product_id = ptc.product_id
                      inner join `" . DB_PREFIX . "product_option_value` pov on p.product_id = pov.product_id
                      where pov.option_id <> 999");
  }

  public function createProductsFilters()
  {
    $this->db->query("delete from `" . DB_PREFIX . "product_filter`");
    $this->db->query("insert ignore into `" . DB_PREFIX . "product_filter` (product_id, filter_id)
                      select product_id, filter_id 
                      from (select product_id as product_id, concat(manufacturer_id, '" . $this->prefix_manufacturer_id . "') as filter_id from `" . DB_PREFIX . "product`
                            union
                            select product_id, concat(option_value_id, '" . $this->prefix_option_value_id . "') from `" . DB_PREFIX . "product_option_value` where quantity > 0) tempFilterTable");
  }
}
