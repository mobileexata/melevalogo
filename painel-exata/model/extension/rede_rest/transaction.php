<?php
class ModelExtensionRedeRestTransaction extends Model {
    public function getTransactions($filter = array()) {
        $sql = "
            SELECT orr.order_id, orr.order_rede_rest_id, o.date_added, CONCAT(o.firstname, ' ', o.lastname) as customer, orr.type, orr.status
            FROM `" . DB_PREFIX . "order_rede_rest` orr
            INNER JOIN `" . DB_PREFIX . "order` o ON (o.order_id = orr.order_id)
        ";

        if (isset($filter['order_id'])) {
            $sql .= 'WHERE orr.order_id = "' . (int) $filter['order_id'] . '"';
        } else {
            $sql .= "WHERE orr.order_id > '0'";
        }

        if (isset($filter['filter_initial_date']) && !empty($filter['filter_initial_date'])) {
            $sql .= " AND DATE(o.date_added) >= DATE('" . $this->db->escape($filter['filter_initial_date']) . "')";
        }

        if (isset($filter['filter_final_date']) && !empty($filter['filter_final_date'])) {
            $sql .= " AND DATE(o.date_added) <= DATE('" . $this->db->escape($filter['filter_final_date']) . "')";
        }

        if (isset($filter['filter_status']) && !empty($filter['filter_status'])) {
            $sql .= " AND orr.status = '" . $this->db->escape($filter['filter_status']) . "'";
        }

        $sql .= " ORDER BY orr.order_id DESC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getTransaction($order_rede_rest_id) {
        if ($order_rede_rest_id > 0) {
            $query = $this->db->query("
                SELECT orr.*, o.date_added, o.store_id
                FROM `" . DB_PREFIX . "order_rede_rest` orr
                INNER JOIN `" . DB_PREFIX . "order` o ON (o.order_id = orr.order_id)
                WHERE orr.order_rede_rest_id = '" . (int) $order_rede_rest_id . "'
            ");

            if ($query->num_rows) {
                return $query->row;
            }
        }

        return array();
    }

    public function updateTransaction($data) {
        if (is_array($data)) {
            $this->db->query("
                UPDATE `" . DB_PREFIX . "order_rede_rest`
                SET
                    status = '" . $this->db->escape($data['status']) . "',
                    card_brand = '" . $this->db->escape($data['card_brand']) . "',
                    card_bin = '" . $this->db->escape($data['card_bin']) . "',
                    card_end = '" . $this->db->escape($data['card_end']) . "',
                    card_holder = '" . $this->db->escape($data['card_holder']) . "',
                    tid = '" . $this->db->escape($data['tid']) . "',
                    nsu = '" . $this->db->escape($data['nsu']) . "',
                    authorization_code = '" . $this->db->escape($data['authorization_code']) . "',
                    authorized_date = '" . $this->db->escape($data['authorized_date']) . "',
                    authorized_total = '" . $this->db->escape($data['authorized_total']) . "',
                    captured_date = '" . $this->db->escape($data['captured_date']) . "',
                    captured_total = '" . $this->db->escape($data['captured_total']) . "',
                    canceled_date = '" . $this->db->escape($data['canceled_date']) . "',
                    canceled_total = '" . $this->db->escape($data['canceled_total']) . "',
                    json_last_response = '" . $this->db->escape($data['json_last_response']) . "'
                WHERE
                    order_rede_rest_id = '" . (int) $data['order_rede_rest_id'] . "'
            ");
        }
    }

    public function captureTransaction($data) {
        if (is_array($data)) {
            $this->db->query("
                UPDATE `" . DB_PREFIX . "order_rede_rest`
                SET
                    status = '" . $this->db->escape($data['status']) . "',
                    json_last_response = '" . $data['json_last_response'] . "'
                WHERE
                    order_rede_rest_id = '" . (int) $data['order_rede_rest_id'] . "'
            ");
        }
    }

    public function cancelTransaction($data) {
        if (is_array($data)) {
            $this->db->query("
                UPDATE `" . DB_PREFIX . "order_rede_rest`
                SET
                    canceled_total = '" . $this->db->escape($data['canceled_total']) . "',
                    status = '" . $this->db->escape($data['status']) . "',
                    json_last_response = '" . $data['json_last_response'] . "'
                WHERE
                    order_rede_rest_id = '" . (int) $data['order_rede_rest_id'] . "'
            ");
        }
    }

    public function deleteTransaction($order_rede_rest_id) {
        if ($order_rede_rest_id > 0) {
            $this->db->query("
                DELETE FROM `" . DB_PREFIX . "order_rede_rest`
                WHERE order_rede_rest_id = '" . (int) $order_rede_rest_id . "'
            ");
        }
    }

    public function getOrder($data, $order_id) {
        if (is_array($data) && $order_id > 0) {
            $columns = implode(", ", array_values($data));

            $query = $this->db->query("
                SELECT " . $columns . "
                FROM `" . DB_PREFIX . "order`
                WHERE order_id = '" . (int) $order_id . "';
            ");

            if ($query->num_rows) {
                return $query->row;
            }
        }

        return array();
    }

    public function getOrderShipping($order_id) {
        $result = array();

        if ($order_id > 0) {
            $query = $this->db->query("
                SELECT * FROM `" . DB_PREFIX . "order_total`
                WHERE order_id = '" . (int) $order_id . "' ORDER BY sort_order ASC
            ");

            foreach ($query->rows as $total) {
                if ($total['value'] > 0) {
                    if ($total['code'] == "shipping") {
                        $result[] = array(
                            'code'  => $total['code'],
                            'title' => $total['title'],
                            'value' => $total['value']
                        );
                    }
                }
            }
        }

        return $result;
    }
}
