<?php
class ModelExtensionModuleRedeRest extends Model {
    public function getTransaction($tid) {
        if (!empty($tid)) {
            $query = $this->db->query("
            SELECT o.order_id, o.store_id, o.currency_code, o.order_status_id, orr.order_rede_rest_id, orr.type, orr.tid
                FROM `" . DB_PREFIX . "order_rede_rest` orr
                INNER JOIN `" . DB_PREFIX . "order` o ON (o.order_id = orr.order_id)
                WHERE orr.tid = '" . $this->db->escape($tid) . "'
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
}
