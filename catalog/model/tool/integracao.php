<?php

class ModelToolIntegracao extends Model
{

	public function product($product)
	{
		$product_id = (int)$product->product_id;
		$product_name = $product->product_name;
		$model = $product->model;
		$price = (float)$product->price;
		$quantity = (int)$product->quantity;
		$manufacturer = (int)$product->manufacturer;

		$query_product = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product` WHERE product_id = " . $product_id . "");

		$this->deletesProduct($product_id);

		$length = 0.2;
		$width = 0.2;
		$height = 0.1;
		$weight = 0.5;

		if ($query_product->num_rows) {
			//`manufacturer_id` = '" . $this->db->escape($manufacturer) . "',
			$this->db->query("UPDATE `" . DB_PREFIX . "product` SET 
					`model` = '" . $this->db->escape($model) . "',
					`quantity` = " . $quantity . ", 
					`price` = " . $price . ",
					`date_modified` = NOW(),
					`length` = $length,
					`width` = $width,
					`height` = $height,
					`weight` = $weight
				WHERE `product_id` = '$product_id'");
		} else {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "product` (`product_id`, `model`, `quantity`, `stock_status_id`, `manufacturer_id`, `shipping`,
					 `price`, `tax_class_id`, `date_available`, `weight_class_id`, `length_class_id`, `subtract`, `minimum`, `status`, `date_added`, `date_modified`, 
					 `length`, `width`, `height`, `weight`) VALUES
				(" . $product_id . ", '" . $this->db->escape($model) . "', " . $quantity . ", 5, '" . $this->db->escape($manufacturer) . "', 1,
				" . $price . ", 0, NOW(), 1, 1, 1, 1, 0, NOW(), NOW(), 
						$length, $width, $height, $weight)");

			//PRODUCT DESCRIPTION
			$query_product_description = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_description` WHERE product_id = $product_id");
			if (!$query_product_description->num_rows)
				$this->db->query("INSERT INTO `" . DB_PREFIX . "product_description` (`product_id`, `language_id`, `name`, `description`, `tag`, `meta_title`, `meta_description`, `meta_keyword`) VALUES 
    					(" . $product_id . ", 2, '" . $this->db->escape($product_name) . "', '', '', '" . $this->db->escape($product_name) . "', '', '')");

			//PRODUCT TO STORE AND LAYOUT
			$this->db->query("INSERT INTO `" . DB_PREFIX . "product_to_store` (`product_id`,`store_id`) VALUES ('" . $product_id . "', '0')");
			$this->db->query("INSERT INTO `" . DB_PREFIX . "product_to_layout` (`product_id`,`store_id`,`layout_id`) VALUES ('" . $product_id . "', '0', '0')");

			//CATEGORY
			if (isset($product->category) && count($product->category)) {
				$query_category = "INSERT INTO `" . DB_PREFIX . "product_to_category`(`product_id`,`category_id`) VALUES ";
				$values_category = [];
				foreach ($product->category as $c)
					$values_category[] = "('$product_id', '" . (int)$c . "')";

				$query_category .= $this->returnValuesQuery($values_category);

				$this->db->query($query_category);
			}
		}

		//DICOUNT
		if (isset($product->discount) && count($product->discount))
			foreach ($product->discount as $discount)
				$this->db->query("INSERT INTO `" . DB_PREFIX . "product_special` SET product_id = '" . $product_id . "', customer_group_id = '1', priority = '0', price = '" . (float)$discount->price . "', date_start = '" . $this->db->escape($discount->start_date) . "', date_end = '" . $this->db->escape($discount->end_date) . "'");

		//OPTIONS
		if (isset($product->options) && count($product->options))
			foreach ($product->options as $option) {
				$option_id = (int)$option->option_id;
				$this->db->query("INSERT INTO `" . DB_PREFIX . "product_option` (`product_option_id`, `product_id`, `option_id`, `value`, `required`) VALUES (NULL, '$product_id', '" . $option_id . "', '', '1')");

				$product_option_id = (int)$this->db->getLastId();

				$query_product_option_value = "INSERT INTO `" . DB_PREFIX . "product_option_value` (`product_option_value_id`, `product_option_id`, `product_id`, `option_id`, `option_value_id`, `quantity`, `subtract`, `price`, `price_prefix`, `points`, `points_prefix`, `weight`, `weight_prefix`) VALUES ";

				$values_product_option_value = [];
				foreach ($option->values as $value) {
					$option_value_id = (int)$value->option_value_id;
					$quantity = (int)$value->quantity;
					$values_product_option_value[] = "(NULL, '$product_option_id', '$product_id', $option_id, '$option_value_id', '$quantity', '1', 0, '+', 1, '+', '0.00000000', '+')";
				}
				$query_product_option_value .= $this->returnValuesQuery($values_product_option_value);

				$this->db->query($query_product_option_value);
			}

		//SEO URL
		// $keyword = $this->getKeywordCeoUrl($product_name);
		// $this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE `keyword` = '" . $this->db->escape($keyword) . "' or `query` = 'product_id=" . $product_id . "'");
		// $this->db->query("INSERT INTO `" . DB_PREFIX . "seo_url` (`store_id`, `language_id`, `query`, `keyword`) VALUES ('0', '2', 'product_id=" . $product_id . "', '" . $this->db->escape($keyword) . "')");

		//EMBALAGEM PARA PRESENTE
		$this->db->query("INSERT INTO `" . DB_PREFIX . "product_option` (`product_option_id`, `product_id`, `option_id`, `value`, `required`) VALUES (NULL, '$product_id', '999', '', '0')");
		$product_option_id = (int)$this->db->getLastId();
		$this->db->query("INSERT INTO `" . DB_PREFIX . "product_option_value` (`product_option_value_id`, `product_option_id`, `product_id`, `option_id`, `option_value_id`, `quantity`, `subtract`, `price`, `price_prefix`, `points`, `points_prefix`, `weight`, `weight_prefix`) VALUES (NULL, '$product_option_id', '$product_id', '999', '639', '0', '0', '0.0000', '+', '0', '+', '0.00000000', '+')");
	}

	public function category($category)
	{
		$category_id = (int)$category->category_id;
		$category_name = $category->category_name;
		$category_parent_id = (isset($category->parent_category_id) && (int)$category->parent_category_id > 0) ? (int)$category->parent_category_id : 0;

		$this->deletesCategory($category_id);

		$this->db->query("INSERT INTO " . DB_PREFIX . "category SET category_id = $category_id, parent_id = '" . $category_parent_id . "', `top` = 0, `column` = 0, sort_order = 1, status = 1, date_modified = NOW(), date_added = NOW()");
		$this->db->query("INSERT INTO " . DB_PREFIX . "category_description SET category_id = '" . $category_id . "', language_id = '2', name = '" . $this->db->escape($category_name) . "', description = '', meta_title = '" . $this->db->escape($category_name) . "', meta_description = '', meta_keyword = ''");

		// MySQL Hierarchical Data Closure Table Pattern
		$level = 0;

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . $category_parent_id . "' ORDER BY `level` ASC");

		foreach ($query->rows as $result) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$category_id . "', `path_id` = '" . (int)$result['path_id'] . "', `level` = '" . (int)$level . "'");
			$level++;
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . $category_id . "', `path_id` = '" . $category_id . "', `level` = '" . (int)$level . "'");
		$this->db->query("INSERT INTO " . DB_PREFIX . "category_to_store SET category_id = '" . $category_id . "', store_id = '0'");

		// $keyword = $this->getKeywordCeoUrl($category_name);
		// $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '0', language_id = '2', query = 'category_id=" . (int)$category_id . "', keyword = '" . $this->db->escape($keyword) . "'");	

		$this->db->query("INSERT INTO " . DB_PREFIX . "category_to_layout SET category_id = '" . $category_id . "', store_id = '0', layout_id = '0'");
	}

	public function manufacturer($manufacturer)
	{
		$manufacturer_id = (int)$manufacturer->manufacturer_id;
		$manufacturer_name = $manufacturer->manufacturer_name;

		$this->deletesManufacturer($manufacturer_id);

		$this->db->query("INSERT INTO " . DB_PREFIX . "manufacturer SET manufacturer_id = '" . $manufacturer_id . "', name = '" . $this->db->escape($manufacturer_name) . "', sort_order = '1'");
		$this->db->query("INSERT INTO " . DB_PREFIX . "manufacturer_to_store SET manufacturer_id = '" . $manufacturer_id . "', store_id = '0'");

		// $keyword = $this->getKeywordCeoUrl($manufacturer_name);
		// $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '0', language_id = '2', query = 'manufacturer_id=" . $manufacturer_id . "', keyword = '" . $this->db->escape($keyword) . "'");
	}

	public function option($option)
	{
		$option_id = (int)$option->option_id;
		$option_name = $option->option_name;

		$this->deletesOption($option_id);

		$this->db->query("INSERT INTO `" . DB_PREFIX . "option` SET option_id = '" . $option_id . "', type = 'radio', sort_order = '1'");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "option_description` SET option_id = '" . $option_id . "', language_id = '2', name = '" . $this->db->escape($option_name) . "'");

		if (isset($option->option_values) && count($option->option_values))
			foreach ($option->option_values as $option_value) {
				$option_value_id = (int)$option_value->option_value_id;
				$option_value_name = $option_value->option_value_name;
				$option_value_order = $option_value->order ?: 1;

				$this->db->query("INSERT INTO `" . DB_PREFIX . "option_value` SET option_value_id = '" . $option_value_id . "', option_id = '" . $option_id . "', image = '', sort_order = '" . $option_value_order . "'");

				$this->db->query("INSERT INTO `" . DB_PREFIX . "option_value_description` SET option_value_id = '" . $option_value_id . "', language_id = '2', option_id = '" . $option_id . "', name = '" . $this->db->escape($option_value_name) . "'");
			}
	}

	private function deletesProduct($product_id)
	{
		//$this->db->query("DELETE FROM `" . DB_PREFIX . "product_to_store` where product_id = '$product_id'");
		//$this->db->query("DELETE FROM `" . DB_PREFIX . "product_to_category` where product_id = '$product_id'");
		//$this->db->query("DELETE FROM `" . DB_PREFIX . "product_to_layout` where product_id = '$product_id'");
		//$this->db->query("DELETE FROM `" . DB_PREFIX . "product_description` where product_id = '$product_id'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "product_option` where product_id = '$product_id'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "product_option_value` where product_id = '$product_id'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE `query` = 'product_id=" . $product_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "product_special` WHERE product_id =" . $product_id . "");
	}

	private function deletesCategory($category_id)
	{
		$this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE `query` = 'category_id=" . $category_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "category` WHERE `category_id` = '" . $category_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = '" . $category_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "category_filter` WHERE `category_id` = '" . $category_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "category_to_store` WHERE `category_id` = '" . $category_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "category_to_layout` WHERE `category_id` = '" . $category_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "category_description` WHERE `category_id` = '" . $category_id . "'");
	}

	private function deletesManufacturer($manufacturer_id)
	{
		$this->db->query("DELETE FROM `" . DB_PREFIX . "manufacturer` WHERE manufacturer_id = '" . $manufacturer_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "manufacturer_to_store` WHERE manufacturer_id = '" . $manufacturer_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE query = 'manufacturer_id=" . $manufacturer_id . "'");
	}

	private function deletesOption($option_id)
	{
		$this->db->query("DELETE FROM `" . DB_PREFIX . "option` WHERE option_id = '" . $option_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "option_description` WHERE option_id = '" . $option_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "option_value` WHERE option_id = '" . $option_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "option_value_description` WHERE option_id = '" . $option_id . "'");
	}

	private function returnValuesQuery($values)
	{
		$final_values = trim(implode(',', $values));

		if (substr($final_values, -1) == ',')
			$final_values = substr($final_values, 0, strlen($final_values) - 1);
		return $final_values;
	}

	public function inativaProdutosSemEstoque()
	{
		$this->db->query("update `" . DB_PREFIX . "product` set status = 0 where status = 1 and quantity <= 0");
	}
	// private function getKeywordCeoUrl($name)
	// {
	// 	return urlencode($name);
	// }
	public function produtosCategoriaNovidade()
	{
		$this->db->query("delete from `" . DB_PREFIX . "product_to_category` where category_id in (515007, 515056, 515067, 515068, 515069)");

		$this->db->query("insert ignore into `" . DB_PREFIX . "product_to_category` (product_id, category_id) SELECT p.product_id, 515007 as category_id FROM `" . DB_PREFIX . "product` p INNER JOIN `" . DB_PREFIX . "product_to_category` ptc on ptc.product_id = p.product_id and ptc.category_id = 2 LEFT JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '0' and p.date_added > curdate() - interval 30 day ORDER BY p.date_added DESC");

		$this->db->query("insert ignore into `" . DB_PREFIX . "product_to_category` (product_id, category_id) SELECT p.product_id, 515056 as category_id FROM `" . DB_PREFIX . "product` p INNER JOIN `" . DB_PREFIX . "product_to_category` ptc on ptc.product_id = p.product_id and ptc.category_id = 2 LEFT JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '0' and p.date_added > curdate() - interval 30 day ORDER BY p.date_added DESC");

		$this->db->query("insert ignore into `" . DB_PREFIX . "product_to_category` (product_id, category_id) SELECT p.product_id, 515025 as category_id FROM `" . DB_PREFIX . "product` p INNER JOIN `" . DB_PREFIX . "product_to_category` ptc on ptc.product_id = p.product_id and ptc.category_id = 1 LEFT JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '0' and p.date_added > curdate() - interval 30 day ORDER BY p.date_added DESC");

		$this->db->query("insert ignore into `" . DB_PREFIX . "product_to_category` (product_id, category_id) SELECT p.product_id, 515055 as category_id FROM `" . DB_PREFIX . "product` p INNER JOIN `" . DB_PREFIX . "product_to_category` ptc on ptc.product_id = p.product_id and ptc.category_id = 1 LEFT JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '0' and p.date_added > curdate() - interval 30 day ORDER BY p.date_added DESC");
		
		$this->db->query("insert ignore into `" . DB_PREFIX . "product_to_category` (product_id, category_id)
                          SELECT p.product_id, CASE ptc.category_id
					        WHEN 515044 THEN 515067 #acessorios
                            WHEN 1 THEN 515068 #FEMININO
                            WHEN 2 THEN 515069 #acessorios
					        END as category_id 
                          FROM `" . DB_PREFIX . "product` p 
                          INNER JOIN `" . DB_PREFIX . "product_to_category` ptc on ptc.product_id = p.product_id and ptc.category_id IN(515044, 1, 2)
                          INNER JOIN `" . DB_PREFIX . "product_special` ps on ps.product_id = p.product_id and curdate() BETWEEN ps.date_start AND ps.date_end
                          LEFT JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p.product_id = p2s.product_id)
                          WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '0'");
	}
}
