<?php
class ControllerExtensionRedeRestTransaction extends Controller {
    const MODULE_CODE = 'module_rede_rest';
    const PAYMENT_CODE = 'payment_rede_rest_credito';
    const TRANSACTION = 'extension/rede_rest/transaction';
    const MODEL = 'model_extension_rede_rest_transaction';

    private $error = array();

    public function index() {
        $data = $this->load->language(self::TRANSACTION . '_list');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->document->addScript('//cdnjs.cloudflare.com/ajax/libs/moment.js/2.8.3/locales.min.js');

        $config_admin_language = $this->config->get('config_admin_language');

        if ($config_admin_language == 'pt-br') {
            $data['calendar_language'] = 'pt-br';
        } else {
            $data['calendar_language'] = 'en-gb';
        }

        $this->document->addStyle('//cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css');
        $this->document->addStyle('//cdn.datatables.net/buttons/1.6.2/css/buttons.bootstrap.min.css');
        $this->document->addScript('//cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js');
        $this->document->addScript('//cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js');
        $this->document->addScript('//cdn.datatables.net/buttons/1.6.2/js/dataTables.buttons.min.js');
        $this->document->addScript('//cdn.datatables.net/buttons/1.6.2/js/buttons.bootstrap.min.js');
        $this->document->addScript('//cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js');
        $this->document->addScript('//cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js');
        $this->document->addScript('//cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js');
        $this->document->addScript('//cdn.datatables.net/buttons/1.6.2/js/buttons.html5.min.js');
        $this->document->addScript('//cdn.datatables.net/buttons/1.6.2/js/buttons.print.min.js');
        $this->document->addScript('//cdn.datatables.net/buttons/1.6.2/js/buttons.colVis.min.js');

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_rede_rest'),
            'href' => ''
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link(self::TRANSACTION, 'user_token=' . $this->session->data['user_token'], true)
        );

        $filters = array();

        if (isset($this->request->get['filter_initial_date']) && $this->validateDate($this->request->get['filter_initial_date'])) {
            $filters['filter_initial_date'] = $this->request->get['filter_initial_date'];
        } else {
            $filters['filter_initial_date'] = (new DateTime('-3 days'))->format('Y-m-d');
        }

        if (isset($this->request->get['filter_final_date']) && $this->validateDate($this->request->get['filter_final_date'])) {
            $filters['filter_final_date'] = $this->request->get['filter_final_date'];
        } else {
            $filters['filter_final_date'] = (new DateTime())->format('Y-m-d');
        }

        if (isset($this->request->get['filter_status'])) {
            $filters['filter_status'] = $this->request->get['filter_status'];
        } else {
            $filters['filter_status'] = '';
        }

        $data = array_merge($data, $filters);

        $this->load->model(self::TRANSACTION);
        $transactions = $this->{self::MODEL}->getTransactions($filters);

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
                'rede_rest_id' => $transaction['order_rede_rest_id'],
                'order_id' => $transaction['order_id'],
                'date_added' => date('d/m/Y H:i:s', strtotime($transaction['date_added'])),
                'customer' => $transaction['customer'],
                'type' => $type,
                'status_color' => $status_color,
                'status_message' => $status_message,
                'view_order' => $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $transaction['order_id'], true),
                'view_transaction' => $this->url->link(self::TRANSACTION . '/info', 'user_token=' . $this->session->data['user_token'] . '&rede_rest_id=' . $transaction['order_rede_rest_id'], true)
            );
        }

        $data['statuses'] = array(
            '' => $this->language->get('text_all'),
            'autorizada' => $this->language->get('text_autorizada'),
            'capturada' => $this->language->get('text_capturada'),
            'processando' => $this->language->get('text_processando'),
            'cancelada' => $this->language->get('text_cancelada'),
            'negada' => $this->language->get('text_negada')
        );

        $view_filtrar = $this->url->link(self::TRANSACTION, 'user_token=' . $this->session->data['user_token'], true);
        $view_excluir = $this->url->link(self::TRANSACTION . '/excluir', 'user_token=' . $this->session->data['user_token'] . '&rede_rest_id=', true);

        $data['view_filtrar'] = str_replace("&amp;", "&", $view_filtrar);
        $data['view_excluir'] = str_replace("&amp;", "&", $view_excluir);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view(self::TRANSACTION . '_list', $data));
    }

    public function info() {
        if (isset($this->request->get['rede_rest_id'])) {
            $order_rede_rest_id = $this->request->get['rede_rest_id'];
        } else {
            $order_rede_rest_id = 0;
        }

        $this->load->model(self::TRANSACTION);
        $transaction = $this->{self::MODEL}->getTransaction($order_rede_rest_id);

        if ($transaction) {
            $order_id = $transaction['order_id'];

            $this->load->model('sale/order');
            $order_info = $this->model_sale_order->getOrder($order_id);

            $data = $this->load->language(self::TRANSACTION . '_info');

            $this->document->setTitle($this->language->get('heading_title'));

            $data['user_token'] = $this->session->data['user_token'];

            $data['breadcrumbs'] = array();

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_rede_rest'),
                'href' => ''
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_transactions'),
                'href' => $this->url->link(self::TRANSACTION, 'user_token=' . $this->session->data['user_token'], true)
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link(self::TRANSACTION . '/info', 'user_token=' . $this->session->data['user_token'] . '&rede_rest_id=' . $order_rede_rest_id, true)
            );

            switch ($transaction['status']) {
                case 'autorizada':
                    $status = $this->language->get('text_autorizada');
                    break;
                case 'capturada':
                    $status = $this->language->get('text_capturada');
                    break;
                case 'processando':
                    $status = $this->language->get('text_processando');
                    break;
                case 'cancelada':
                    $status = $this->language->get('text_cancelada');
                    break;
                case 'negada':
                    $status = $this->language->get('text_negada');
                    break;
            }

            $data['view_order'] = $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id, true);
            $data['view_customer'] = $this->url->link('customer/customer/edit', 'user_token=' . $this->session->data['user_token'] . '&customer_id=' . $order_info['customer_id'], true);

            $view_consultar = $this->url->link(self::TRANSACTION . '/consultar', 'user_token=' . $this->session->data['user_token'] . '&rede_rest_id=' . $transaction['order_rede_rest_id'], true);
            $view_capturar = $this->url->link(self::TRANSACTION . '/capturar', 'user_token=' . $this->session->data['user_token'] . '&rede_rest_id=' . $transaction['order_rede_rest_id'], true);
            $view_cancelar = $this->url->link(self::TRANSACTION . '/cancelar', 'user_token=' . $this->session->data['user_token'] . '&rede_rest_id=' . $transaction['order_rede_rest_id'], true);

            $data['view_consultar'] = str_replace("&amp;", "&", $view_consultar);
            $data['view_capturar'] = str_replace("&amp;", "&", $view_capturar);
            $data['view_cancelar'] = str_replace("&amp;", "&", $view_cancelar);

            $type = $transaction['type'];

            if ($transaction['captured_date']) {
                $transaction_total = $transaction['captured_total'];
            } else {
                $transaction_total = $transaction['authorized_total'];
            }

            if ($transaction['canceled_total']) {
                $transaction_total = $transaction_total - $transaction['canceled_total'];
            }

            $data['rede_rest_id'] = $transaction['order_rede_rest_id'];
            $data['order_id'] = $order_id;
            $data['transaction_total'] = $transaction_total;
            $data['added'] = date('d/m/Y H:i:s', strtotime($transaction['date_added']));
            $data['customer'] = $order_info['firstname'] . ' ' . $order_info['lastname'];
            $data['total'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], true);
            $data['tid'] = $transaction['tid'];
            $data['nsu'] = $transaction['nsu'];
            $data['authorization_code'] = $transaction['authorization_code'];
            $data['installments'] = $transaction['installments'];
            $data['type'] = $type;
            $data['operacao'] = $type == 'debito' ? $this->language->get('text_debito') : $this->language->get('text_credito');
            $data['data_autorizacao'] = !empty($transaction['authorized_date']) ? date('d/m/Y H:i:s', strtotime(substr($transaction['authorized_date'], 0, -6))) : '';
            $data['valor_autorizado'] = $transaction['authorized_total'] > 0 ? $this->currency->format($transaction['authorized_total'], $this->config->get('config_currency'), '1.00', true) : '';
            $data['data_captura'] = !empty($transaction['captured_date']) ? date('d/m/Y H:i:s', strtotime(substr($transaction['captured_date'], 0, -6))) : '';
            $data['valor_capturado'] = $transaction['captured_total'] > 0 ? $this->currency->format($transaction['captured_total'], $this->config->get('config_currency'), '1.00', true) : '';
            $data['data_cancelamento'] = !empty($transaction['canceled_date']) ? date('d/m/Y H:i:s', strtotime(substr($transaction['canceled_date'], 0, -6))) : '';
            $data['valor_cancelado'] = $transaction['canceled_total'] > 0 ? $this->currency->format($transaction['canceled_total'], $this->config->get('config_currency'), '1.00', true) : '';
            $data['status'] = $status;
            $data['clearsale'] = $this->config->get(self::PAYMENT_CODE . '_clearsale_status');
            $data['json_first_response'] = $transaction['json_first_response'] ? json_encode(json_decode($transaction['json_first_response']), JSON_PRETTY_PRINT) : '';
            $data['json_last_response'] = $transaction['json_last_response'] ? json_encode(json_decode($transaction['json_last_response']), JSON_PRETTY_PRINT) : '';

            $data['dias_capturar'] = '';
            $data['dias_cancelar'] = '';

            $dias_capturar = $this->config->get(self::PAYMENT_CODE . '_dias_capturar');
            $dias_cancelar_captura = $this->config->get(self::PAYMENT_CODE . '_dias_cancelar_captura');
            $dias_cancelar_autorizacao = $this->config->get(self::PAYMENT_CODE . '_dias_cancelar_autorizacao');

            $dias_capturar = $dias_capturar <= '0' ? '29' : $dias_capturar ;
            $dias_cancelar_captura = $dias_cancelar_captura <= '0' ? '7' : $dias_cancelar_captura;
            $dias_cancelar_autorizacao = $dias_cancelar_autorizacao <= '0' ? '29' : $dias_cancelar_autorizacao;

            $atual = strtotime(date('Y-m-d'));

            if (
                $transaction['status'] == 'autorizada'
                && !empty($transaction['authorized_date'])
            ) {
                $inicial = strtotime(date('Y-m-d H:i:s', strtotime(substr($transaction['authorized_date'], 0, -6))));
                $final = strtotime(date('Y-m-d H:i:s', strtotime('+'. $dias_capturar .' days', $inicial)));
                if ($atual <= $final) {
                    $dataFinal = date('d/m/Y H:i:s', $final);
                    $dias = (int) floor(($final - strtotime(date('Y-m-d'))) / (60 * 60 * 24));
                    $desc = $dias > 1 ? $this->language->get('text_dias') : $this->language->get('text_dia');
                    $data['dias_capturar'] = sprintf($this->language->get('text_dias_capturar'), $dataFinal, $dias, $desc);
                }
            }

            if (
                $transaction['status'] == 'autorizada'
                || $transaction['status'] == 'capturada'
            ) {
                if (!empty($transaction['captured_date'])) {
                    $inicial = strtotime(date('Y-m-d H:i:s', strtotime(substr($transaction['captured_date'], 0, -6))));
                    $final = strtotime(date('Y-m-d H:i:s', strtotime('+'. $dias_cancelar_captura .' days', $inicial)));
                } else {
                    $inicial = strtotime(date('Y-m-d H:i:s', strtotime(substr($transaction['authorized_date'], 0, -6))));
                    $final = strtotime(date('Y-m-d H:i:s', strtotime('+'. $dias_cancelar_autorizacao .' days', $inicial)));
                }
                if ($atual <= $final) {
                    $dataFinal = date('d/m/Y H:i:s', $final);
                    $dias = (int) floor(($final - strtotime(date('Y-m-d'))) / (60 * 60 * 24));
                    $desc = $dias > 1 ? $this->language->get('text_dias') : $this->language->get('text_dia');
                    $data['dias_cancelar'] = sprintf($this->language->get('text_dias_cancelar'), $dataFinal, $dias, $desc);
                }
            }

            if ($type == 'credito') {
                $products = $this->model_sale_order->getOrderProducts($order_id);
                $vouchers = $this->model_sale_order->getOrderVouchers($order_id);
                $shippings = $this->{self::MODEL}->getOrderShipping($order_id);

                $parcelas = $transaction['installments'];
                $telefone = preg_replace("/[^0-9]/", '', $order_info['telephone']);
                $email = strtolower($order_info['email']);
                $documento = '';

                $cobranca_nome = '';
                $cobranca_logradouro  = $order_info['payment_address_1'];
                $cobranca_numero = '';
                $cobranca_complemento = '';
                $cobranca_bairro = $order_info['payment_address_2'];
                $cobranca_cidade = $order_info['payment_city'];
                $cobranca_estado = $order_info['payment_zone_code'];
                $cobranca_cep = preg_replace("/[^0-9]/", '', $order_info['payment_postcode']);

                $entrega_nome = $order_info['shipping_firstname'].' '.$order_info['shipping_lastname'];
                $entrega_logradouro  = $order_info['shipping_address_1'];
                $entrega_numero = '';
                $entrega_complemento = '';
                $entrega_bairro = $order_info['shipping_address_2'];
                $entrega_cidade = $order_info['shipping_city'];
                $entrega_estado = $order_info['shipping_zone_code'];
                $entrega_cep = preg_replace("/[^0-9]/", '', $order_info['shipping_postcode']);

                $colunas = array();

                if ($this->config->get(self::PAYMENT_CODE . '_custom_razao_id') == 'C') {
                    array_push($colunas, $this->config->get(self::PAYMENT_CODE . '_razao_coluna'));
                }

                if ($this->config->get(self::PAYMENT_CODE . '_custom_cnpj_id') == 'C') {
                    array_push($colunas, $this->config->get(self::PAYMENT_CODE . '_cnpj_coluna'));
                }

                if ($this->config->get(self::PAYMENT_CODE . '_custom_cpf_id') == 'C') {
                    array_push($colunas, $this->config->get(self::PAYMENT_CODE . '_cpf_coluna'));
                }

                if ($this->config->get(self::PAYMENT_CODE . '_custom_numero_id') == 'C') {
                    array_push($colunas, $this->config->get(self::PAYMENT_CODE . '_numero_fatura_coluna'));
                    array_push($colunas, $this->config->get(self::PAYMENT_CODE . '_numero_entrega_coluna'));
                }

                if ($this->config->get(self::PAYMENT_CODE . '_custom_complemento_id') == 'C') {
                    array_push($colunas, $this->config->get(self::PAYMENT_CODE . '_complemento_fatura_coluna'));
                    array_push($colunas, $this->config->get(self::PAYMENT_CODE . '_complemento_entrega_coluna'));
                }

                if (count($colunas)) {
                    $colunas_info = $this->{self::MODEL}->getOrder($colunas, $order_id);
                }

                if ($this->config->get(self::PAYMENT_CODE . '_custom_razao_id') == 'C') {
                    if (!empty($colunas_info[$this->config->get(self::PAYMENT_CODE . '_razao_coluna')])) {
                        $cobranca_nome = $colunas_info[$this->config->get(self::PAYMENT_CODE . '_razao_coluna')];
                    }
                } else {
                    if ($order_info['custom_field']) {
                        foreach ($order_info['custom_field'] as $key => $value) {
                            if ($this->config->get(self::PAYMENT_CODE . '_custom_razao_id') == $key) {
                                $cobranca_nome = $value;
                            }
                        }
                    }
                }

                if ($this->config->get(self::PAYMENT_CODE . '_custom_cnpj_id') == 'C') {
                    if (!empty($colunas_info[$this->config->get(self::PAYMENT_CODE . '_cnpj_coluna')])) {
                        $documento = preg_replace("/[^0-9]/", '', $colunas_info[$this->config->get(self::PAYMENT_CODE . '_cnpj_coluna')]);
                    }
                } else {
                    if (is_array($order_info['custom_field'])) {
                        foreach ($order_info['custom_field'] as $key => $value) {
                            if ($this->config->get(self::PAYMENT_CODE . '_custom_cnpj_id') == $key) {
                                $documento = preg_replace("/[^0-9]/", '', $value);
                            }
                        }
                    }
                }

                if (empty($cobranca_nome)) {
                    $cobranca_nome = $order_info['payment_firstname'].' '.$order_info['payment_lastname'];

                    if ($this->config->get(self::PAYMENT_CODE . '_custom_cpf_id') == 'C') {
                        if (!empty($colunas_info[$this->config->get(self::PAYMENT_CODE . '_cpf_coluna')])) {
                            $documento = preg_replace("/[^0-9]/", '', $colunas_info[$this->config->get(self::PAYMENT_CODE . '_cpf_coluna')]);
                        }
                    } else {
                        if (is_array($order_info['custom_field'])) {
                            foreach ($order_info['custom_field'] as $key => $value) {
                                if ($this->config->get(self::PAYMENT_CODE . '_custom_cpf_id') == $key) {
                                    $documento = preg_replace("/[^0-9]/", '', $value);
                                }
                            }
                        }
                    }
                }

                if ($this->config->get(self::PAYMENT_CODE . '_custom_numero_id') == 'C') {
                    $cobranca_numero = preg_replace("/[^0-9]/", '', $colunas_info[$this->config->get(self::PAYMENT_CODE . '_numero_fatura_coluna')]);
                    $entrega_numero = preg_replace("/[^0-9]/", '', $colunas_info[$this->config->get(self::PAYMENT_CODE . '_numero_entrega_coluna')]);
                } else {
                    if (is_array($order_info['payment_custom_field'])) {
                        foreach ($order_info['payment_custom_field'] as $key => $value) {
                            if ($this->config->get(self::PAYMENT_CODE . '_custom_numero_id') == $key) {
                                $cobranca_numero = preg_replace("/[^0-9]/", '', $value);
                            }
                        }
                    }
                    if (is_array($order_info['shipping_custom_field'])) {
                        foreach ($order_info['shipping_custom_field'] as $key => $value) {
                            if ($this->config->get(self::PAYMENT_CODE . '_custom_numero_id') == $key) {
                                $entrega_numero = preg_replace("/[^0-9]/", '', $value);
                            }
                        }
                    }
                }

                if ($this->config->get(self::PAYMENT_CODE . '_custom_complemento_id') == 'C') {
                    $cobranca_complemento = substr($colunas_info[$this->config->get(self::PAYMENT_CODE . '_complemento_fatura_coluna')], 0, 250);
                    $entrega_complemento = substr($colunas_info[$this->config->get(self::PAYMENT_CODE . '_complemento_entrega_coluna')], 0, 250);
                } else {
                    if (is_array($order_info['payment_custom_field'])) {
                        foreach ($order_info['payment_custom_field'] as $key => $value) {
                            if ($this->config->get(self::PAYMENT_CODE . '_custom_complemento_id') == $key) {
                                $cobranca_complemento = substr($value, 0, 250);
                            }
                        }
                    }
                    if (is_array($order_info['shipping_custom_field'])) {
                        foreach ($order_info['shipping_custom_field'] as $key => $value) {
                            if ($this->config->get(self::PAYMENT_CODE . '_custom_complemento_id') == $key) {
                                $entrega_complemento = substr($value, 0, 250);
                            }
                        }
                    }
                }

                if ($data['clearsale']) {
                    if ($this->config->get(self::PAYMENT_CODE . '_clearsale_ambiente')) {
                        $clearsale_url = "https://www.clearsale.com.br/start/Entrada/EnviarPedido.aspx";
                    } else {
                        $clearsale_url = "https://homolog.clearsale.com.br/start/Entrada/EnviarPedido.aspx";
                    }

                    $clearsale_itens['CodigoIntegracao'] = $this->config->get(self::PAYMENT_CODE . '_clearsale_codigo');
                    $clearsale_itens['PedidoID'] = $order_id;
                    $clearsale_itens['Data'] = date('d/m/Y h:i:s', strtotime($order_info['date_added']));
                    $clearsale_itens['IP'] = $order_info['ip'];
                    $clearsale_itens['TipoPagamento'] = '1';
                    $clearsale_itens['Parcelas'] = $parcelas;
                    $clearsale_itens['Cobranca_Nome'] = substr($cobranca_nome, 0, 500);
                    $clearsale_itens['Cobranca_Email'] = substr($email, 0, 150);
                    $clearsale_itens['Cobranca_Documento'] = substr($documento, 0, 100);
                    $clearsale_itens['Cobranca_Logradouro'] = substr($cobranca_logradouro, 0, 200);
                    $clearsale_itens['Cobranca_Logradouro_Numero'] = substr($cobranca_numero, 0, 15);
                    $clearsale_itens['Cobranca_Logradouro_Complemento'] = substr($cobranca_complemento, 0, 250);
                    $clearsale_itens['Cobranca_Bairro'] = substr($cobranca_bairro, 0, 150);
                    $clearsale_itens['Cobranca_Cidade'] = substr($cobranca_cidade, 0, 150);
                    $clearsale_itens['Cobranca_Estado' ] = substr($cobranca_estado, 0, 2);
                    $clearsale_itens['Cobranca_CEP'] = $cobranca_cep;
                    $clearsale_itens['Cobranca_Pais'] = 'Bra';
                    $clearsale_itens['Cobranca_DDD_Telefone_1'] = substr($telefone, 0, 2);
                    $clearsale_itens['Cobranca_Telefone_1'] = substr($telefone, 2);

                    if (utf8_strlen($order_info['shipping_method']) > 0) {
                        $clearsale_itens['Entrega_Nome'] = substr($entrega_nome, 0, 500);
                        $clearsale_itens['Entrega_Logradouro'] = substr($entrega_logradouro, 0, 200);
                        $clearsale_itens['Entrega_Logradouro_Numero'] = substr($entrega_numero, 0, 15);
                        $clearsale_itens['Entrega_Logradouro_Complemento'] = substr($entrega_complemento, 0, 250);
                        $clearsale_itens['Entrega_Bairro'] = substr($entrega_bairro, 0, 150);
                        $clearsale_itens['Entrega_Cidade'] = substr($entrega_cidade, 0, 150);
                        $clearsale_itens['Entrega_Estado'] = substr($entrega_estado, 0, 2);
                        $clearsale_itens['Entrega_CEP'] = $entrega_cep;
                        $clearsale_itens['Entrega_Pais'] = 'Bra';
                    }

                    $order_total = 0;

                    $i = 1;
                    foreach ($products as $product) {
                        $item_valor = number_format($product['price'], 2, '.', '');

                        $clearsale_itens['Item_ID_'.$i] = substr($product['product_id'], 0, 50);
                        $clearsale_itens['Item_Nome_'.$i] = substr($product['name'], 0, 150);
                        $clearsale_itens['Item_Qtd_'.$i] = $product['quantity'];
                        $clearsale_itens['Item_Valor_'.$i] = $item_valor;
                        $order_total += ($product['quantity'] * $item_valor);
                        $i++;
                    }

                    foreach ($vouchers as $voucher) {
                        $item_valor = number_format($voucher['amount'], 2, '.', '');

                        $clearsale_itens['Item_ID_'.$i] = substr($voucher['code'], 0, 50);
                        $clearsale_itens['Item_Nome_'.$i] = substr($voucher['description'], 0, 150);
                        $clearsale_itens['Item_Qtd_'.$i] = '1';
                        $clearsale_itens['Item_Valor_'.$i] = $item_valor;
                        $order_total += $item_valor;
                        $i++;
                    }

                    foreach ($shippings as $shipping) {
                        if ($shipping['value'] > 0) {
                            $item_valor = number_format($shipping['value'], 2, '.', '');

                            $clearsale_itens['Item_ID_'.$i] = substr($shipping['code'], 0, 50);
                            $clearsale_itens['Item_Nome_'.$i] = substr(strip_tags($shipping['title']), 0, 150);
                            $clearsale_itens['Item_Qtd_'.$i] = '1';
                            $clearsale_itens['Item_Valor_'.$i] = $item_valor;
                            $order_total += $item_valor;
                            $i++;
                        }
                    }

                    if ($transaction_total < $order_total) {
                        $desconto = $order_total - $transaction_total;
                        $item_valor = number_format($desconto, 2, '.', '');

                        $clearsale_itens['Item_ID_'.$i] = 'desconto';
                        $clearsale_itens['Item_Nome_'.$i] = 'Desconto';
                        $clearsale_itens['Item_Qtd_'.$i] = '1';
                        $clearsale_itens['Item_Valor_'.$i] = -$item_valor;
                        $order_total -= $item_valor;
                        $i++;
                    } elseif ($transaction_total > $order_total) {
                        $taxa = $transaction_total - $order_total;
                        $item_valor = number_format($taxa, 2, '.', '');

                        $clearsale_itens['Item_ID_'.$i] = 'taxa';
                        $clearsale_itens['Item_Nome_'.$i] = 'Taxa';
                        $clearsale_itens['Item_Qtd_'.$i] = '1';
                        $clearsale_itens['Item_Valor_'.$i] = $item_valor;
                        $order_total += $item_valor;
                        $i++;
                    }

                    $clearsale_itens['Total'] = number_format($order_total, 2, '.', '');

                    $data['clearsale_url'] = $clearsale_url;
                    $data['clearsale_itens'] = $clearsale_itens;
                    $data['clearsale_src'] = $clearsale_url . '?codigointegracao=' . $this->config->get(self::PAYMENT_CODE . '_clearsale_codigo') . '&PedidoID=' . $order_id;
                }
            }

            $data['header'] = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');

            $this->response->setOutput($this->load->view(self::TRANSACTION . '_info', $data));
        } else {
            $this->load->language('error/not_found');

            $this->document->setTitle($this->language->get('heading_title'));

            $data['heading_title'] = $this->language->get('heading_title');

            $data['text_not_found'] = $this->language->get('text_not_found');

            $data['breadcrumbs'] = array();

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('error/not_found', 'user_token=' . $this->session->data['user_token'], true)
            );

            $data['header'] = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');

            $this->response->setOutput($this->load->view('error/not_found', $data));
        }
    }

    public function excluir() {
        $json = array();

        $this->load->language(self::TRANSACTION . '_info');

        if (!$this->user->hasPermission('modify', self::TRANSACTION)) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$json && !isset($this->request->get['rede_rest_id'])) {
            $json['error'] = $this->language->get('error_warning');
        }

        if (!$json) {
            $order_rede_rest_id = (int) $this->request->get['rede_rest_id'];

            $this->load->model(self::TRANSACTION);
            $transaction = $this->{self::MODEL}->getTransaction($order_rede_rest_id);

            if (!$transaction) {
                $json['error'] = $this->language->get('error_warning');
            }
        }

        if (!$json) {
            $this->{self::MODEL}->deleteTransaction($order_rede_rest_id);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function consultar() {
        $json = array();

        $this->load->language(self::TRANSACTION . '_info');

        if (!$this->user->hasPermission('modify', self::TRANSACTION)) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$json && !isset($this->request->get['rede_rest_id'])) {
            $json['error'] = $this->language->get('error_warning');
        }

        if (!$json) {
            $order_rede_rest_id = (int) $this->request->get['rede_rest_id'];

            $this->load->model(self::TRANSACTION);
            $transaction = $this->{self::MODEL}->getTransaction($order_rede_rest_id);

            if (!$transaction) {
                $json['error'] = $this->language->get('error_warning');
            }
        }

        if (!$json) {
            $chave = $this->config->get(self::MODULE_CODE . '_chave');
            $dados['chave'] = $chave[$transaction['store_id']];
            $dados['sandbox'] = $this->config->get(self::MODULE_CODE . '_sandbox');
            $dados['debug'] = $this->config->get(self::MODULE_CODE . '_debug');
            $dados['filiacao'] = $this->config->get(self::MODULE_CODE . '_filiacao');
            $dados['token'] = $this->config->get(self::MODULE_CODE . '_token');
            $dados['tid'] = $transaction['tid'];

            try {
                require_once(DIR_SYSTEM . 'library/rede-rest/rede.php');
                $rede = new Rede();
                $rede->setParametros($dados);
                $resposta = $rede->getTransaction();

            } catch (Exception $e) {
                $json['error'] = $this->language->get('error_consultar');
            }
        }

        if (!$json) {
            if (!$resposta) {
                $json['error'] = $this->language->get('error_consultar');
            }

            if (!$json) {
                $status = '';
                $comment = '';
                $canceled_date = '';
                $canceled_total = '';

                if (isset($resposta->refunds)) {
                    foreach ($resposta->refunds as $refund) {
                        switch ($refund->status) {
                            case 'Done': /* cancelamento concluído */
                                $canceled_date = $refund->refundDateTime;
                                $canceled_total = $refund->amount / 100;

                                $status = 'cancelada';
                                $mensagem = $this->language->get('text_cancelada');

                                break;
                            case 'Denied': /* cancelamento negado */
                                $status = 'negada';
                                $mensagem = $this->language->get('text_negada');

                                break;
                            case 'Processing': /* processando cancelamento */
                                $status = 'processando';
                                $mensagem = $this->language->get('text_processando');

                                break;
                        }
                    }
                } elseif (
                    isset($resposta->authorization)
                    || isset($resposta->capture)
                ) {
                    switch ($resposta->authorization->status) {
                        case 'Pending': /* autorizada */
                            $status = 'autorizada';
                            $mensagem = $this->language->get('text_autorizada');

                            break;
                        case 'Approved': /* capturada */
                            $status = 'capturada';
                            $mensagem = $this->language->get('text_capturada');

                            break;
                        case 'Denied': /* cancelamento negado */
                            $status = 'negada';
                            $mensagem = $this->language->get('text_negada');

                            break;
                        case 'Canceled': /* cancelamento confirmado */
                            $status = 'cancelada';
                            $mensagem = $this->language->get('text_cancelada');

                            break;
                    }
                } else {
                    $json['error'] = $this->language->get('error_consultar');
                }

                if (!$json) {
                    $card_brand = isset($resposta->authorization->brand->name) ? $resposta->authorization->brand->name : '';
                    $card_holder = isset($resposta->authorization->brand->cardHolderName) ? $resposta->authorization->brand->cardHolderName : $resposta->authorization->cardHolderName;
                    $authorization_code = isset($resposta->authorization->brand->authorizationCode) ? $resposta->authorization->brand->authorizationCode : $resposta->authorization->authorizationCode;
                    $captured_date = isset($resposta->capture->dateTime) ? $resposta->capture->dateTime : '';
                    $captured_total = isset($resposta->capture->amount) ? $resposta->capture->amount / 100 : '';

                    $dados = array(
                        'order_rede_rest_id' => $order_rede_rest_id,
                        'status' => $status,
                        'card_brand' => $card_brand,
                        'card_bin' => $resposta->authorization->cardBin,
                        'card_end' => $resposta->authorization->last4,
                        'card_holder' => $card_holder,
                        'tid' => $resposta->authorization->tid,
                        'nsu' => $resposta->authorization->nsu,
                        'authorization_code' => $authorization_code,
                        'authorized_date' => $resposta->authorization->dateTime,
                        'authorized_total' => $resposta->authorization->amount / 100,
                        'captured_date' => $captured_date,
                        'captured_total' => $captured_total,
                        'canceled_date' => $canceled_date,
                        'canceled_total' => $canceled_total,
                        'json_last_response' => json_encode($resposta)
                    );

                    $this->{self::MODEL}->updateTransaction($dados);

                    $json['mensagem'] = $mensagem;
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function capturar() {
        $json = array();

        $this->load->language(self::TRANSACTION . '_info');

        if (!$this->user->hasPermission('modify', self::TRANSACTION)) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$json && !isset($this->request->get['rede_rest_id'])) {
            $json['error'] = $this->language->get('error_warning');
        }

        if (!$json) {
            $order_rede_rest_id = (int) $this->request->get['rede_rest_id'];

            $this->load->model(self::TRANSACTION);
            $transaction = $this->{self::MODEL}->getTransaction($order_rede_rest_id);

            if (!$transaction) {
                $json['error'] = $this->language->get('error_warning');
            }
        }

        if (!$json) {
            $chave = $this->config->get(self::MODULE_CODE . '_chave');
            $dados['chave'] = $chave[$transaction['store_id']];
            $dados['sandbox'] = $this->config->get(self::MODULE_CODE . '_sandbox');
            $dados['debug'] = $this->config->get(self::MODULE_CODE . '_debug');
            $dados['filiacao'] = $this->config->get(self::MODULE_CODE . '_filiacao');
            $dados['token'] = $this->config->get(self::MODULE_CODE . '_token');
            $dados['tid'] = $transaction['tid'];
            $dados['amount'] = number_format($transaction['authorized_total'], 2, '', '');

            try {
                require_once(DIR_SYSTEM . 'library/rede-rest/rede.php');
                $rede = new Rede();
                $rede->setParametros($dados);
                $resposta = $rede->setCapture();

            } catch (Exception $e) {
                $json['error'] = $this->language->get('error_capturar');
            }
        }

        if (!$json) {
            if (!$resposta) {
                $json['error'] = $this->language->get('error_capturar');
            }

            if (!$json) {
                if (!isset($resposta->returnCode)) {
                    $json['error'] = $this->language->get('error_capturar');
                }
            }

            if (!$json) {
                switch ($resposta->returnCode) {
                    case '00': /* Success */
                        $dados['order_rede_rest_id'] = $order_rede_rest_id;
                        $dados['status'] = 'capturada';
                        $dados['json_last_response'] = json_encode($resposta);

                        $this->{self::MODEL}->captureTransaction($dados);

                        $json['mensagem'] = $this->language->get('text_capturada');

                        break;
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function cancelar() {
        $json = array();

        $this->load->language(self::TRANSACTION . '_info');

        if (!$this->user->hasPermission('modify', self::TRANSACTION)) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$json && !isset($this->request->get['rede_rest_id'])) {
            $json['error'] = $this->language->get('error_warning');
        }

        if (!$json) {
            $order_rede_rest_id = (int) $this->request->get['rede_rest_id'];

            $this->load->model(self::TRANSACTION);
            $transaction = $this->{self::MODEL}->getTransaction($order_rede_rest_id);

            if (!$transaction) {
                $json['error'] = $this->language->get('error_warning');
            }
        }

        if (!$json) {
            $total = 0;
            $aprovado = 0;
            $cancelado = 0;

            if (isset($this->request->post['total'])) {
                $total = filter_input(INPUT_POST, 'total', FILTER_SANITIZE_NUMBER_INT);

                $total = $total / 100;
            }

            if ($transaction['captured_date']) {
                $aprovado = $transaction['captured_total'];
            } else {
                $aprovado = $transaction['authorized_total'];
            }

            if ($transaction['canceled_total']) {
                $cancelado = $transaction['canceled_total'];
            }

            $disponivel = $aprovado - $cancelado;

            if ($total <= 0 || $total > $disponivel) {
                $json['error'] = $this->language->get('error_cancelar_total');
            }
        }

        if (!$json) {
            $chave = $this->config->get(self::MODULE_CODE . '_chave');
            $dados['chave'] = $chave[$transaction['store_id']];
            $dados['sandbox'] = $this->config->get(self::MODULE_CODE . '_sandbox');
            $dados['debug'] = $this->config->get(self::MODULE_CODE . '_debug');
            $dados['filiacao'] = $this->config->get(self::MODULE_CODE . '_filiacao');
            $dados['token'] = $this->config->get(self::MODULE_CODE. '_token');
            $dados['tid'] = $transaction['tid'];
            $dados['amount'] = number_format($total, 2, '', '');

            try {
                require_once(DIR_SYSTEM . 'library/rede-rest/rede.php');
                $rede = new Rede();
                $rede->setParametros($dados);
                $resposta = $rede->setCancel();

            } catch (Exception $e) {
                $json['error'] = $this->language->get('error_cancelar');
            }
        }

        if (!$json) {
            if (!$resposta) {
                $json['error'] = $this->language->get('error_cancelar');
            }

            if (!$json) {
                if (!isset($resposta->returnCode)) {
                    $json['error'] = $this->language->get('error_cancelar');
                }
            }

            if (!$json) {
                switch ($resposta->returnCode) {
                    case '354': /* expirou o prazo de cancelamento */
                        $json['error'] = $this->language->get('error_cancelar_expirado');

                        break;
                    case '355': /* já foi cancelada */
                    case '359': /* cancelamento confirmado */
                        $dados['order_rede_rest_id'] = $order_rede_rest_id;
                        $dados['canceled_total'] = $total;
                        $dados['status'] = 'cancelada';
                        $dados['json_last_response'] = json_encode($resposta);

                        $this->{self::MODEL}->cancelTransaction($dados);

                        $json['mensagem'] = $this->language->get('text_cancelada');

                        break;
                    case '360': /* processando cancelamento */
                        $dados['order_rede_rest_id'] = $order_rede_rest_id;
                        $dados['canceled_total'] = $total;
                        $dados['status'] = 'processando';
                        $dados['json_last_response'] = json_encode($resposta);

                        $this->{self::MODEL}->cancelTransaction($dados);

                        $json['mensagem'] = $this->language->get('text_processando');

                        break;
                    case '365': /* cancelamento parcial não habilitado */
                        $json['error'] = $this->language->get('error_cancelar_parcial');

                        break;
                    case '371': /* não disponível para cancelamento */
                        $json['error'] = $this->language->get('error_cancelar_falhou');

                        break;
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function validateDate($date, $format = 'Y-m-d'){
        $dt = DateTime::createFromFormat($format, $date);

        return $dt && $dt->format($format) === $date;
    }
}
