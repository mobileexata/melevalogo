<?php

/**
 * © Copyright 2013-2021 Codemarket - Todos os direitos reservados.
 * Class ControllerModuleCodeFacebook
 */
class ControllerModuleCodeFacebook extends Controller
{
    private $conf;
    private $log;

    /**
     * ControllerModuleCodeFacebook constructor.
     *
     * @param $registry
     *
     * @throws \Exception
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('module/codemarket_module');
        $conf = $this->model_module_codemarket_module->getModulo('614');
        $this->log = new log('Code-Facebook-' . date('m-Y') . '.log');

        if (
            empty($conf) || empty($conf->code_habilitar) || $conf->code_habilitar == 2 ||
            empty($conf->code_token) || empty($conf->code_imagem_tipo)
        ) {
            $this->log->write('Módulo desativado, verifique a configuração!');
            exit("Módulo desativado!");
        }

        $this->conf = $conf;
    }

    /**
     * Gera o arquivo CSV
     *
     * @param $data
     * @param string $delimiter
     * @param string $enclosure
     *
     * @return string
     */
    private function generateCsv(&$data, $delimiter = ',', $enclosure = '"')
    {
        $handle = fopen('php://temp', 'r+');
        $contents = '';

        fputcsv($handle, array_keys(reset($data)), $delimiter, $enclosure);

        foreach ($data as $line) {
            fputcsv($handle, $line, $delimiter, $enclosure);
        }
        rewind($handle);
        while (!feof($handle)) {
            $contents .= fread($handle, 8192);
        }
        fclose($handle);
        return $contents;
    }

    //Usar CSV ou XML

    /**
     * Retorna o Feed dos Produtos
     *
     * @throws \Exception
     */
    public function feed()
    {
        if (empty($this->conf->code_token) || $this->conf->code_token != $this->request->get['token']) {
            $this->log->write('Informe um Token válido!');
            exit("Informe um Token válido!");
        }

        $ip = !empty($this->request->server['REMOTE_ADDR']) ? 'IP: ' . $this->request->server['REMOTE_ADDR'] : '';
        $this->log->write('Codemarket Facebook Catálogo - Rodando o Feed dos Produtos ' . $ip);

        $this->load->model('tool/image');
        $start = !empty($this->request->get['start']) ? (int) $this->request->get['start'] : 0;
        $limit = !empty($this->request->get['limit']) ? (int) $this->request->get['limit'] : 100000000;
        $filter_category_id = !empty($this->request->get['filter_category_id']) ? (int) $this->request->get['filter_category_id'] : '';
        $filter_manufacturer_id = !empty($this->request->get['filter_manufacturer_id']) ? (int) $this->request->get['filter_manufacturer_id'] : '';
        $store_id = !empty($this->request->get['store_id']) ? (int) $this->request->get['store_id'] : (int) $this->config->get('config_store_id');
        $status = (isset($this->request->get['status']) && $this->request->get['status'] == 0) ? 0 : 1;
        $test = (isset($this->request->get['test']) && $this->request->get['test'] == 1) ? 1 : 0;

        $filter_data = [
            'filter_name'            => $this->request->get['filter_name'],
            'start'                  => $start,
            'limit'                  => $limit,
            'filter_category_id'     => $filter_category_id,
            'filter_manufacturer_id' => $filter_manufacturer_id,
            'store_id'               => $store_id,
            'status'                 => $status,
        ];

        $products = $this->getProducts($filter_data);

        if (empty($products)) {
            exit("<h1>Sem Produtos retornados, verifique os Filtros usados e se tem Produtos na Loja</h1>");
        }

        $feedArray = [];
        foreach ($products as $product) {
            /*
             * Sobre Imagens
             * https://www.facebook.com/business/help/686259348512056?id=725943027795860
             * Precisa ter o mesmo número de colunas entre cada Produto
             */

            $resolution = !empty($this->conf->code_resolution) ? (int) $this->conf->code_resolution : 600;
            $product_info = $product;

            if (empty($product_info['image'])) {
                $this->log->write('Produto: ' . $product['product_id'] . ' sem imagem, não adicionado ao Feed');
                continue;
            }

            if ($product_info['quantity'] > 0) {
                $productStatus = 'in stock';
            } else {
                $productStatus = 'out of stock';
            }

            //https://www.facebook.com/business/help/120325381656392?id=725943027795860

            if (!empty($product_info['meta_description'])) {
                $description = substr($product_info['meta_description'], 0, 5000);
            } else if (!empty(trim($product_info['description']))) {
                $description = substr(strip_tags(trim($product_info['description'])), 0, 5000);
            } else {
                $description = '';
            }

            /*
            if($product_info['product_id'] == 92) {
                print_r($product_info); exit();
            }
            */

            $feedArray[$product['product_id']] = [
                'id'           => $product['product_id'],
                'title'        => substr(trim($product_info['name']), 0, 150),
                'description'  => $description,
                'availability' => $productStatus,
                'condition'    => 'new',
                'price'        => $this->priceFormat($product_info['price'], $product_info),
                'link'         => str_replace('&amp;', '&', $this->url->link('product/product', 'product_id=' . $product_info['product_id'])),
                'visibility'   => 'published',
            ];

            if (!empty($this->conf->code_imagem_tipo) && $this->conf->code_imagem_tipo == 2) {
                $feedArray[$product['product_id']]['image_link'] = $this->model_tool_image->resize($product_info['image'], $resolution, $resolution);
            } else {
                $feedArray[$product['product_id']]['image_link'] = HTTPS_SERVER . "image/" . $product_info['image'];
            }

            $feedArray[$product['product_id']]['brand'] = '';
            if (!empty($product_info['manufacturer'])) {
                $feedArray[$product['product_id']]['brand'] = substr($product_info['manufacturer'], 0, 100);
            } else if (!empty($this->conf->code_produto_gtin) && !empty($product_info[$this->conf->code_produto_gtin])) {
                $feedArray[$product['product_id']]['brand'] = substr($product_info[$this->conf->code_produto_gtin], 0, 100);
            } else if (!empty($product_info['mpn'])) {
                $feedArray[$product['product_id']]['brand'] = substr($product_info['mpn'], 0, 100);
            } else if (!empty($product_info['mpn'])) {
                $feedArray[$product['product_id']]['brand'] = substr($product_info['sku'], 0, 100);
            } else if (!empty($product_info['model'])) {
                $feedArray[$product['product_id']]['brand'] = substr($product_info['model'], 0, 100);
            }

            // Extras
            $feedArray[$product['product_id']]['sale_price'] = '';
            if ((float) $product_info['special']) {
                $feedArray[$product['product_id']]['sale_price'] = $this->priceFormat($product_info['special'], $product_info);
            }

            $feedArray[$product['product_id']]['additional_image_link'] = '';

            // Imagens
            $imagesDb = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_image 
                WHERE product_id = '" . (int) $product['product_id'] . "' ORDER BY sort_order ASC, product_image_id ASC
            ")->rows;

            if (!empty($imagesDb[0]['product_id'])) {
                $images = [];

                foreach ($imagesDb as $k => $img) {
                    if (empty($img['image'])) {
                        continue;
                    }

                    if (!empty($this->conf->code_imagem_tipo) && $this->conf->code_imagem_tipo == 2) {
                        $images[] = $this->model_tool_image->resize($img['image'], $resolution, $resolution);
                    } else {
                        $images[] = HTTPS_SERVER . "image/" . $img['image'];
                    }
                }

                if (!empty($images)) {
                    $feedArray[$product['product_id']]['additional_image_link'] = implode(',', $images);
                }
            }

            $this->log->write('Produto: ' . $product['product_id'] . ' adicionado ao Feed');
            //TODO - Implementar para puxar o google_product_category - Versão 1.2 ou 1.3
        }

        $this->log->write('Rodado com sucesso o Feed dos Produtos');

        if ($test) {
            echo "<h1>Codemarket Facebook Catálogo - Modo Teste</h1>";
            echo "<pre>";
            print_r($feedArray);
            exit("</pre>");
        }

        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=feed.csv");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo $this->generateCsv($feedArray);
    }

    // AUXILIARES

    /**
     * Formata o Preço
     *
     * @param $price
     * @param $product_info
     *
     * @return string
     */
    private function priceFormat($price, $product_info)
    {
        $price = $this->currency->format($this->tax->calculate($price, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->config->get('config_currency'), '', '');
        return number_format($price, 2, '.', '') . ' BRL';
    }

    /**
     * Retorna os dados de um Produto
     *
     * @param $product_id
     * @param $store_id
     *
     * @return array|false
     */
    private function getProduct($product_id, $store_id)
    {
        $query = $this->db->query("SELECT DISTINCT *, pd.name AS name, p.image, m.name AS manufacturer, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int) $this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int) $this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special, (SELECT points FROM " . DB_PREFIX . "product_reward pr WHERE pr.product_id = p.product_id AND pr.customer_group_id = '" . (int) $this->config->get('config_customer_group_id') . "') AS reward, (SELECT ss.name FROM " . DB_PREFIX . "stock_status ss WHERE ss.stock_status_id = p.stock_status_id AND ss.language_id = '" . (int) $this->config->get('config_language_id') . "') AS stock_status, (SELECT wcd.unit FROM " . DB_PREFIX . "weight_class_description wcd WHERE p.weight_class_id = wcd.weight_class_id AND wcd.language_id = '" . (int) $this->config->get('config_language_id') . "') AS weight_class, (SELECT lcd.unit FROM " . DB_PREFIX . "length_class_description lcd WHERE p.length_class_id = lcd.length_class_id AND lcd.language_id = '" . (int) $this->config->get('config_language_id') . "') AS length_class, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, (SELECT COUNT(*) AS total FROM " . DB_PREFIX . "review r2 WHERE r2.product_id = p.product_id AND r2.status = '1' GROUP BY r2.product_id) AS reviews, p.sort_order FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) WHERE p.product_id = '" . (int) $product_id . "' AND pd.language_id = '" . (int) $this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int) $store_id . "'");

        if ($query->num_rows) {
            return [
                'product_id'       => $query->row['product_id'],
                'name'             => $query->row['name'],
                'description'      => $query->row['description'],
                'meta_title'       => $query->row['meta_title'],
                'meta_description' => $query->row['meta_description'],
                'meta_keyword'     => $query->row['meta_keyword'],
                'tag'              => $query->row['tag'],
                'model'            => $query->row['model'],
                'sku'              => $query->row['sku'],
                'upc'              => $query->row['upc'],
                'ean'              => $query->row['ean'],
                'jan'              => $query->row['jan'],
                'isbn'             => $query->row['isbn'],
                'mpn'              => $query->row['mpn'],
                'location'         => $query->row['location'],
                'quantity'         => $query->row['quantity'],
                'stock_status'     => $query->row['stock_status'],
                'image'            => $query->row['image'],
                'manufacturer_id'  => $query->row['manufacturer_id'],
                'manufacturer'     => $query->row['manufacturer'],
                'price'            => ($query->row['discount'] ? $query->row['discount'] : $query->row['price']),
                'special'          => $query->row['special'],
                'reward'           => $query->row['reward'],
                'points'           => $query->row['points'],
                'tax_class_id'     => $query->row['tax_class_id'],
                'date_available'   => $query->row['date_available'],
                'weight'           => $query->row['weight'],
                'weight_class_id'  => $query->row['weight_class_id'],
                'length'           => $query->row['length'],
                'width'            => $query->row['width'],
                'height'           => $query->row['height'],
                'length_class_id'  => $query->row['length_class_id'],
                'subtract'         => $query->row['subtract'],
                'rating'           => round($query->row['rating']),
                'reviews'          => $query->row['reviews'] ? $query->row['reviews'] : 0,
                'minimum'          => $query->row['minimum'],
                'sort_order'       => $query->row['sort_order'],
                'status'           => $query->row['status'],
                'date_added'       => $query->row['date_added'],
                'date_modified'    => $query->row['date_modified'],
                'viewed'           => $query->row['viewed'],
            ];
        } else {
            return false;
        }
    }

    /**
     * Retorna os Produtos
     *
     * @param array $data
     *
     * @return array
     */
    private function getProducts($data = [])
    {
        $sql = "SELECT p.product_id, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int) $this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int) $this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special";

        if (!empty($data['filter_category_id'])) {
            if (!empty($data['filter_sub_category'])) {
                $sql .= " FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (cp.category_id = p2c.category_id)";
            } else {
                $sql .= " FROM " . DB_PREFIX . "product_to_category p2c";
            }

            if (!empty($data['filter_filter'])) {
                $sql .= " LEFT JOIN " . DB_PREFIX . "product_filter pf ON (p2c.product_id = pf.product_id) LEFT JOIN " . DB_PREFIX . "product p ON (pf.product_id = p.product_id)";
            } else {
                $sql .= " LEFT JOIN " . DB_PREFIX . "product p ON (p2c.product_id = p.product_id)";
            }
        } else {
            $sql .= " FROM " . DB_PREFIX . "product p";
        }

        $sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pd.language_id = '" . (int) $this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int) $data['store_id'] . "'";

        if (!empty($data['filter_category_id'])) {
            if (!empty($data['filter_sub_category'])) {
                $sql .= " AND cp.path_id = '" . (int) $data['filter_category_id'] . "'";
            } else {
                $sql .= " AND p2c.category_id = '" . (int) $data['filter_category_id'] . "'";
            }

            if (!empty($data['filter_filter'])) {
                $implode = [];

                $filters = explode(',', $data['filter_filter']);

                foreach ($filters as $filter_id) {
                    $implode[] = (int) $filter_id;
                }

                $sql .= " AND pf.filter_id IN (" . implode(',', $implode) . ")";
            }
        }

        if (!empty($data['filter_name']) || !empty($data['filter_tag'])) {
            $sql .= " AND (";

            if (!empty($data['filter_name'])) {
                $implode = [];

                $words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_name'])));

                foreach ($words as $word) {
                    $implode[] = "pd.name LIKE '%" . $this->db->escape($word) . "%'";
                }

                if ($implode) {
                    $sql .= " " . implode(" AND ", $implode) . "";
                }

                if (!empty($data['filter_description'])) {
                    $sql .= " OR pd.description LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
                }
            }

            if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
                $sql .= " OR ";
            }

            if (!empty($data['filter_tag'])) {
                $implode = [];

                $words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_tag'])));

                foreach ($words as $word) {
                    $implode[] = "pd.tag LIKE '%" . $this->db->escape($word) . "%'";
                }

                if ($implode) {
                    $sql .= " " . implode(" AND ", $implode) . "";
                }
            }

            if (!empty($data['filter_name'])) {
                $sql .= " OR LCASE(p.model) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                $sql .= " OR LCASE(p.sku) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                $sql .= " OR LCASE(p.upc) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                $sql .= " OR LCASE(p.ean) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                $sql .= " OR LCASE(p.jan) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                $sql .= " OR LCASE(p.isbn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                $sql .= " OR LCASE(p.mpn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
            }

            $sql .= ")";
        }

        if (!empty($data['filter_manufacturer_id'])) {
            $sql .= " AND p.manufacturer_id = '" . (int) $data['filter_manufacturer_id'] . "'";
        }

        $sql .= " AND p.status = '" . (int) $data['status'] . "'";
        $sql .= " GROUP BY p.product_id";

        /*
        $sort_data = [
            'pd.name',
            'p.model',
            'p.quantity',
            'p.price',
            'rating',
            'p.sort_order',
            'p.date_added',
        ];

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
                $sql .= " ORDER BY LCASE(" . $data['sort'] . ")";
            } else if ($data['sort'] == 'p.price') {
                $sql .= " ORDER BY (CASE WHEN special IS NOT NULL THEN special WHEN discount IS NOT NULL THEN discount ELSE p.price END)";
            } else {
                $sql .= " ORDER BY " . $data['sort'];
            }
        } else {
            $sql .= " ORDER BY p.sort_order";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC, LCASE(pd.name) DESC";
        } else {
            $sql .= " ASC, LCASE(pd.name) ASC";
        }
        */

        $sql .= " ORDER BY p.product_id ASC";

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int) $data['start'] . "," . (int) $data['limit'];
        }

        $product_data = [];

        $query = $this->db->query($sql);

        foreach ($query->rows as $result) {
            $product_data[$result['product_id']] = $this->getProduct($result['product_id'], $data['store_id']);
        }

        return $product_data;
    }
}