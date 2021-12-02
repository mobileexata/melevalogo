<?php
require_once DIR_SYSTEM . 'library/rede-rest/engine.php';

class ControllerExtensionPaymentRedeRestCredito extends Controller {
    use RedeRestEngine;

    const TYPE = 'payment_';
    const NAME = 'rede_rest_credito';
    const CODE = self::TYPE . self::NAME;
    const EXTENSION = 'extension/payment/' . self::NAME;
    const MODEL = 'model_extension_payment_' . self::NAME;
    const EXTENSIONS = 'marketplace/extension';
    const TRANSACTION = 'extension/rede_rest/transaction';
    const TRANSACTION_MODEL = 'model_extension_rede_rest_transaction';

    const BANDEIRAS = array(
        'visa',
        'mastercard',
        'elo',
        'amex',
        'diners',
        'hipercard',
        'hiper',
        'jcb',
        'credz',
        'banescard',
        'sorocred',
        'cabal',
        'mais'
    );

    private $error = array();

    public function index() {
        $data = $this->load->language(self::EXTENSION);

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting(self::CODE, $this->format_fields());

            $this->session->data['success'] = $this->language->get('text_success');

            if (isset($this->request->post['save_stay']) && ($this->request->post['save_stay'] = 1)) {
                $this->response->redirect($this->url->link(self::EXTENSION, 'user_token=' . $this->session->data['user_token'], true));
            } else {
                $this->response->redirect($this->url->link(self::EXTENSIONS, 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
            }
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['version'] = $this->getRedeRestVersion();

        $data['user_token'] = $this->session->data['user_token'];

        $erros = array(
            'warning',
            'stores',
            'customer_groups',
            'soft_descriptor',
            'dias_capturar',
            'dias_cancelar_autorizacao',
            'dias_cancelar_captura',
            'taxas',
            'razao',
            'cnpj',
            'cpf',
            'numero_fatura',
            'numero_entrega',
            'complemento_fatura',
            'complemento_entrega',
            'cpf_obrigatorio',
            'numero_obrigatorio',
            'titulo',
            'texto_botao',
            'codigo_css',
            'recaptcha_site_key',
            'recaptcha_secret_key',
            'clearsale_codigo',
            'clearsale_ambiente'
        );

        foreach ($erros as $erro) {
            if (isset($this->error[$erro])) {
                $data['error_' . $erro] = $this->error[$erro];
            } else {
                $data['error_' . $erro] = '';
            }
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link(self::EXTENSIONS, 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link(self::EXTENSION, 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link(self::EXTENSION, 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link(self::EXTENSIONS, 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        $bandeiras = array();
        foreach (self::BANDEIRAS as $bandeira) {
            $bandeiras[$bandeira] = array(
                'nome' => $bandeira,
                'ativa' => '',
                'parcelas' => '12',
                'sem_juros' => '1',
                'juros' => array()
            );
        }

        $regras = array();
        for ($i = 2; $i <= 12; $i++) {
            $regras[$i] = array(
                'parcela' => $i,
                'total' => '0.00'
            );
        }

        $codigo_css = <<<'EOT'
#rede_rest {
  max-width: 300px !important;
  border-style: dashed !important;
  border-width: 2px !important;
  border-color: #777777 !important;
  padding: 10px !important;
  margin-bottom: 10px !important;
  color: #777777 !important;
}
EOT;

        $campos = array(
            'stores' => array(0),
            'customer_groups' => array(0),
            'total' => '',
            'geo_zone_id' => '',
            'status' => '',
            'sort_order' => '',
            'soft_descriptor' => '',
            'captura' => '',
            'dias_capturar' => '29',
            'dias_cancelar_autorizacao' => '29',
            'dias_cancelar_captura' => '90',
            'minimo' => '',
            'desconto' => '',
            'bandeiras' => $bandeiras,
            'regras' => $regras,
            'situacao_pendente_id' => '',
            'situacao_autorizada_id' => '',
            'situacao_nao_autorizada_id' => '',
            'situacao_capturada_id' => '',
            'situacao_cancelada_id' => '',
            'custom_razao_id' => '',
            'razao_coluna' => '',
            'custom_cnpj_id' => '',
            'cnpj_coluna' => '',
            'custom_cpf_id' => '',
            'cpf_coluna' => '',
            'custom_numero_id' => '',
            'numero_fatura_coluna' => '',
            'numero_entrega_coluna' => '',
            'custom_complemento_id' => '',
            'complemento_fatura_coluna' => '',
            'complemento_entrega_coluna' => '',
            'titulo' => 'Cartão de crédito',
            'imagem' => '',
            'information_id' => '',
            'exibir_juros' => '',
            'tema' => 'skeleton',
            'estilo_botao_b3' => 'primary',
            'cor_normal_texto' => '#FFFFFF',
            'cor_normal_fundo' => '#33b0f0',
            'cor_normal_borda' => '#33b0f0',
            'cor_efeito_texto' => '#FFFFFF',
            'cor_efeito_fundo' => '#0487b0',
            'cor_efeito_borda' => '#0487b0',
            'texto_botao' => 'Confirmar pagamento',
            'container_botao' => '#button-confirm',
            'codigo_css' => $codigo_css,
            'recaptcha_site_key' => '',
            'recaptcha_secret_key' => '',
            'recaptcha_status' => '',
            'clearsale_codigo' => '',
            'clearsale_ambiente' => '',
            'clearsale_status' => ''
        );

        $campos_array = array('bandeiras', 'regras');

        foreach ($campos as $campo => $valor) {
            if (isset($this->request->post[$campo])) {
                $data[$campo] = $this->request->post[$campo];
            } else {
                $valor = !is_null($this->config->get(self::CODE . '_' . $campo)) ? $this->config->get(self::CODE . '_' . $campo) : $valor;

                if (in_array($campo, $campos_array)) {
                    if ($campo == 'bandeiras') {
                        $data[$campo] = $this->array_update($bandeiras, $valor);
                    } elseif ($campo == 'regras') {
                        $novo_valor = $this->array_update($regras, $valor);
                        ksort($novo_valor);
                        $data[$campo] = $novo_valor;
                    }
                } else {
                    $data[$campo] = $valor;
                }
            }
        }

        for ($i = 1; $i <= 12; $i++) {
            $data['parcelas_data'][] = $i;
        }

        $data['stores_data'][] = array(
            'store_id' => 0,
            'name' => $this->config->get('config_name')
        );

        $this->load->model('setting/store');
        $stores = $this->model_setting_store->getStores();

        foreach ($stores as $store) {
            $data['stores_data'][] = array(
                'store_id' => $store['store_id'],
                'name' => $store['name']
            );
        }

        $this->load->model('customer/customer_group');
        $data['customer_groups_data'] = $this->model_customer_customer_group->getCustomerGroups();

        $this->load->model('localisation/geo_zone');
        $data['geo_zones_data'] = $this->model_localisation_geo_zone->getGeoZones();

        $this->load->model('localisation/order_status');
        $data['order_statuses_data'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['custom_fields_data'] = array();

        $this->load->model('customer/custom_field');
        $custom_fields = $this->model_customer_custom_field->getCustomFields();

        foreach ($custom_fields as $custom_field) {
            $data['custom_fields_data'][] = array(
                'custom_field_id' => $custom_field['custom_field_id'],
                'name' => $custom_field['name'],
                'type' => $custom_field['type'],
                'location' => $custom_field['location']
            );
        }

        $this->load->model(self::EXTENSION);
        $data['columns_data'] = $this->{self::MODEL}->getOrderColumns();

        $this->load->model('catalog/information');
        $data['informations_data'] = $this->model_catalog_information->getInformations();

        $data['themes_data'] = array(
            'bootstrap_v3' => $this->language->get('text_bootstrap_v3'),
            'skeleton' => $this->language->get('text_skeleton')
        );

        $data['styles_b3_data'] = array(
            'default' => $this->language->get('text_btn_default'),
            'primary' => $this->language->get('text_btn_primary'),
            'success' => $this->language->get('text_btn_success'),
            'info' => $this->language->get('text_btn_info'),
            'warning' => $this->language->get('text_btn_warning'),
            'danger' => $this->language->get('text_btn_danger')
        );

        $this->load->model('tool/image');
        if (isset($this->request->post['_imagem']) && is_file(DIR_IMAGE . $this->request->post['imagem'])) {
            $data['thumb'] = $this->model_tool_image->resize($this->request->post['imagem'], 100, 100);
        } elseif (is_file(DIR_IMAGE . $this->config->get('imagem'))) {
            $data['thumb'] = $this->model_tool_image->resize($this->config->get('imagem'), 100, 100);
        } else {
            $data['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
        }
        $data['no_image'] = $this->model_tool_image->resize('no_image.png', 100, 100);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view(self::EXTENSION, $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', self::EXTENSION)) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['stores'])) {
            $this->error['stores'] = $this->language->get('error_stores');
        }

        if (empty($this->request->post['customer_groups'])) {
            $this->error['customer_groups'] = $this->language->get('error_customer_groups');
        }

        if (strlen($this->request->post['soft_descriptor']) <= 13) {
            if (!preg_match('/^[A-Z0-9]+$/', $this->request->post['soft_descriptor'])) {
                $this->error['soft_descriptor'] = $this->language->get('error_soft_descriptor');
            }
        } else {
            $this->error['soft_descriptor'] = $this->language->get('error_soft_descriptor');
        }

        $erros_dias = array(
            'dias_capturar',
            'dias_cancelar_autorizacao',
            'dias_cancelar_captura'
        );

        foreach ($erros_dias as $campo) {
            if (!preg_match('/^[0-9]+$/', $this->request->post[$campo])) {
                $this->error[$campo] = $this->language->get('error_dias');
            }
        }

        if (!empty($this->request->post['bandeiras'])) {
            $bandeiras = $this->request->post['bandeiras'];

            foreach (self::BANDEIRAS as $bandeira) {
                if (isset($bandeiras[$bandeira]['ativa']) && $bandeiras[$bandeira]['ativa'] == 'on') {
                    if ($bandeiras[$bandeira]['parcelas'] > $bandeiras[$bandeira]['sem_juros']) {
                        if (isset($bandeiras[$bandeira]['juros'])) {
                            foreach($bandeiras[$bandeira]['juros'] as $juros) {
                                if ($juros <= '0') {
                                    $this->error['taxas'] = $this->language->get('error_taxas');
                                    break;
                                }
                            }
                        } elseif (!isset($bandeiras[$bandeira]['juros'])) {
                            $this->error['taxas'] = $this->language->get('error_taxas');
                        }
                    }
                }
            }
        }

        $erros_campos = array(
            'razao',
            'cnpj',
            'cpf'
        );

        foreach ($erros_campos as $campo) {
            if ($this->request->post['custom_' . $campo . '_id'] == 'C') {
                if (!(trim($this->request->post[$campo . '_coluna']))) {
                    $this->error[$campo] = $this->language->get('error_campos_coluna');
                }
            }
        }

        $erros_campos_numero = array(
            'numero_fatura',
            'numero_entrega'
        );

        if ($this->request->post['custom_numero_id'] == 'C') {
            foreach ($erros_campos_numero as $campo) {
                if (!(trim($this->request->post[$campo . '_coluna']))) {
                    $this->error[$campo] = $this->language->get('error_campos_coluna');
                }
            }
        }

        $erros_campos_complemento = array(
            'complemento_fatura',
            'complemento_entrega'
        );

        if ($this->request->post['custom_complemento_id'] == 'C') {
            foreach ($erros_campos_complemento as $campo) {
                if (!(trim($this->request->post[$campo . '_coluna']))) {
                    $this->error[$campo] = $this->language->get('error_campos_coluna');
                }
            }
        }

        $erros = array(
            'titulo',
            'texto_botao',
            'codigo_css'
        );

        foreach ($erros as $erro) {
            if (!(trim($this->request->post[$erro]))) {
                $this->error[$erro] = $this->language->get('error_obrigatorio');
            }
        }

        if ($this->request->post['recaptcha_status']) {
            if (!$this->request->post['recaptcha_site_key']) {
                $this->error['recaptcha_site_key'] = $this->language->get('error_obrigatorio');
            }

            if (!$this->request->post['recaptcha_secret_key']) {
                $this->error['recaptcha_secret_key'] = $this->language->get('error_obrigatorio');
            }
        }

        if ($this->request->post['clearsale_status']) {
            if (!$this->request->post['clearsale_codigo']) {
                $this->error['clearsale_codigo'] = $this->language->get('error_obrigatorio');
            }

            if ($this->request->post['clearsale_ambiente'] == '') {
                $this->error['clearsale_ambiente'] = $this->language->get('error_obrigatorio');
            }

            $erros_campos = array(
                'cpf',
                'numero'
            );

            foreach ($erros_campos as $campo) {
                if (!$this->request->post['custom_' . $campo . '_id']) {
                    $this->error[$campo . '_obrigatorio'] = $this->language->get('error_campos_opcao');
                }
            }
        }

        if ($this->error && !isset($this->error['warning'])) {
            $this->error['warning'] = $this->language->get('error_warning');
        }

        return !$this->error;
    }

    private function format_fields() {
        $valores = array_values($this->request->post);

        $chaves = array_map(function($field) {
            return self::CODE . '_' . $field;
        }, array_keys($this->request->post));

        return array_combine($chaves, $valores);
    }

    private function array_update($padrao, $atual) {
        foreach ($atual as $indice => $valores) {
            foreach ($valores as $chave => $valor) {
                if (!isset($padrao[$indice][$chave])) {
                    unset($atual[$indice][$chave]);
                }
            }
        }

        foreach ($padrao as $indice => $valores) {
            foreach ($valores as $chave => $valor) {
                if (!isset($atual[$indice][$chave])) {
                    $atual[$indice][$chave] = $valor;
                }
            }
        }

        return $atual;
    }

    public function order() {
        if (isset($this->request->get['order_id'])) {
            $order_id = $this->request->get['order_id'];
        } else {
            $order_id = 0;
        }

        $this->load->model(self::TRANSACTION);
        $transactions = $this->{self::TRANSACTION_MODEL}->getTransactions(array('order_id' => $order_id));

        if ($transactions) {
            $data = $this->load->language(self::TRANSACTION . '_list');

            $data['transactions'] = array();

            foreach ($transactions as $transaction) {
                switch ($transaction['type']) {
                    case 'credito':
                        $type = $this->language->get('text_credito');
                        break;
                    case 'debito':
                        $type = $this->language->get('text_debito');
                        break;
                }

                switch ($transaction['status']) {
                    case 'autorizada':
                        $status_color = '#3c933c';
                        $status_message = $this->language->get('text_autorizada');
                        break;
                    case 'capturada':
                        $status_color = '#1e91cf';
                        $status_message = $this->language->get('text_capturada');
                        break;
                    case 'processando':
                        $status_color = '#607D8B';
                        $status_message = $this->language->get('text_processando');
                        break;
                    case 'cancelada':
                        $status_color = '#e3503e';
                        $status_message = $this->language->get('text_cancelada');
                        break;
                    case 'negada':
                        $status_color = '#e3503e';
                        $status_message = $this->language->get('text_negada');
                        break;
                }

                $data['transactions'][] = array(
                    'date_added' => date('d/m/Y H:i:s', strtotime($transaction['date_added'])),
                    'customer' => $transaction['customer'],
                    'type' => $type,
                    'status_color' => $status_color,
                    'status_message' => $status_message,
                    'view_order' => $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $transaction['order_id'], true),
                    'view_transaction' => $this->url->link(self::TRANSACTION . '/info', 'user_token=' . $this->session->data['user_token'] . '&rede_rest_id=' . $transaction['order_rede_rest_id'], true)
                );
            }

            return $this->load->view(self::TRANSACTION . '_order', $data);
        }
    }
}
