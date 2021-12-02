<?php
class ModelExtensionModuleRedeRest extends Model {
    public function install() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "order_rede_rest` (
                `order_rede_rest_id` INT(11) NOT NULL AUTO_INCREMENT,
                `order_id` INT(11) NULL,
                `return_code` VARCHAR(4) NULL,
                `status` VARCHAR(15) NULL,
                `type` VARCHAR(7) NULL,
                `card_brand` VARCHAR(20) NULL,
                `card_bin` VARCHAR(6) NULL,
                `card_end` VARCHAR(4) NULL,
                `card_holder` VARCHAR(30) NULL,
                `card_document` VARCHAR(11) NULL,
                `installments` VARCHAR(2) NULL,
                `tid` VARCHAR(20) NULL,
                `nsu` VARCHAR(12) NULL,
                `authorization_code` VARCHAR(6) NULL,
                `authorized_date` VARCHAR(29) NULL,
                `authorized_total` DECIMAL(15,4) NULL,
                `captured_date` VARCHAR(29) NULL,
                `captured_total` DECIMAL(15,4) NULL,
                `canceled_date` VARCHAR(29) NULL,
                `canceled_total` DECIMAL(15,4) NULL,
                `json_first_response` TEXT NULL,
                `json_last_response` TEXT NULL,
                PRIMARY KEY (`order_rede_rest_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");
    }

    public function update() {
        $this->install();

        $table = DB_PREFIX . 'order_rede_rest';
        $columns = array(
            'returnCode' => '`return_code` VARCHAR(4)',
            'tipo' => '`type` VARCHAR(7)',
            'brand' => '`card_brand` VARCHAR(20)',
            'bin' => '`card_bin` VARCHAR(6)',
            'fim' => '`card_end` VARCHAR(4)',
            'holder' => '`card_holder` VARCHAR(30)',
            'parcelas' => '`installments` VARCHAR(2)',
            'authorizationCode' => '`authorization_code` VARCHAR(6)',
            'autorizacaoData' => '`authorized_date` VARCHAR(29)',
            'autorizacaoValor' => '`authorized_total` DECIMAL(15,4)',
            'capturaData' => '`captured_date` VARCHAR(29)',
            'capturaValor' => '`captured_total` DECIMAL(15,4)',
            'cancelaData' => '`canceled_date` VARCHAR(29)',
            'cancelaValor' => '`canceled_total` DECIMAL(15,4)',
            'json' => '`json_first_response` TEXT'
        );
        $this->migrate($table, $columns);

        $table = DB_PREFIX . 'order_rede_rest';
        $column_primary = 'order_rede_rest_id';
        $columns = array(
            'order_rede_rest_id' => 'INT(11) NOT NULL AUTO_INCREMENT',
            'order_id' => 'INT(11) NULL',
            'return_code' => 'VARCHAR(4) NULL',
            'status' => 'VARCHAR(15) NULL',
            'type' => 'VARCHAR(7) NULL',
            'card_brand' => 'VARCHAR(20) NULL',
            'card_bin' => 'VARCHAR(6) NULL',
            'card_end' => 'VARCHAR(4) NULL',
            'card_holder' => 'VARCHAR(30) NULL',
            'card_document' => 'VARCHAR(11) NULL',
            'installments' => 'VARCHAR(2) NULL',
            'tid' => 'VARCHAR(20) NULL',
            'nsu' => 'VARCHAR(12) NULL',
            'authorization_code' => 'VARCHAR(6) NULL',
            'authorized_date' => 'VARCHAR(29) NULL',
            'authorized_total' => 'DECIMAL(15,4) NULL',
            'captured_date' => 'VARCHAR(29) NULL',
            'captured_total' => 'DECIMAL(15,4) NULL',
            'canceled_date' => 'VARCHAR(29) NULL',
            'canceled_total' => 'DECIMAL(15,4) NULL',
            'json_first_response' => 'TEXT NULL',
            'json_last_response' => 'TEXT NULL'
        );
        $this->upgrade($table, $columns, $column_primary);
    }

    private function migrate($table, $changed_columns) {
        $query = $this->db->query("SHOW COLUMNS FROM `" . $table . "`");
        if (!$query->num_rows) { return; }

        $current_columns = array();
        foreach ($query->rows as $column) {
            $current_columns[$column['Field']] = $column['Type'];
        }

        foreach ($changed_columns as $column_old => $column_new) {
            if (array_key_exists($column_old, $current_columns)) {
                $this->db->query("ALTER TABLE `" . $table . "` CHANGE `" . $column_old . "` " . $column_new);
            }
        }
    }

    private function upgrade($table, $columns_reference, $column_primary) {
        $query = $this->db->query("SHOW COLUMNS FROM `" . $table . "`");
        if (!$query->num_rows) { return; }

        $current_columns = array();
        foreach ($query->rows as $column) {
            $current_columns[$column['Field']] = $column['Type'];
        }

        foreach ($current_columns as $column => $type) {
            if (!array_key_exists($column, $columns_reference) && $column != $column_primary) {
                $this->db->query("ALTER TABLE `" . $table . "` DROP COLUMN `" . $column . "`");
            }
        }

        $this->session->data['after_column'] = $column_primary;

        foreach ($columns_reference as $column => $type) {
            if (!array_key_exists($column, $current_columns)) {
                if ($column == $column_primary) {
                    $this->db->query("ALTER TABLE `" . $table . "` ADD `" . $column . "` " . $type . " FIRST, add PRIMARY KEY (`" . $column . "`)");
                } else {
                    $this->db->query("ALTER TABLE `" . $table . "` ADD `" . $column . "` " . $type . " AFTER `" . $this->session->data['after_column'] . "`");
                }
            } else {
                $this->db->query("ALTER TABLE `" . $table . "` CHANGE COLUMN `" . $column . "` `" . $column . "` " . $type . "");
            }

            $this->session->data['after_column'] = $column;
        }

        unset($this->session->data['after_column']);
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "order_rede_rest`");
    }
}
