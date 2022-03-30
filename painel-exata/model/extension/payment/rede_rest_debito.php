<?php
class ModelExtensionPaymentRedeRestDebito extends Model {
    public function getOrderColumns() {
        $query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "order`");

        return $query->rows;
    }
}
