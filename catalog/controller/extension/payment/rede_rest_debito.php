<?php
require_once DIR_SYSTEM . 'library/rede-rest/engine.php';

class ControllerExtensionPaymentRedeRestDebito extends Controller {
    use RedeRestEngine;

    const TYPE = 'payment_';
    const NAME = 'rede_rest_debito';
    const CODE = self::TYPE . self::NAME;
    const EXTENSION = 'extension/payment/' . self::NAME;
    const MODEL = 'model_extension_payment_' . self::NAME;
    const MODULE_CODE = 'module_rede_rest';

    public function index() {
        $data = $this->load->language(self::EXTENSION);

        $data['version'] = $this->getRedeRestVersion();

        $data['sandbox'] = $this->config->get(self::MODULE_CODE . '_sandbox');

        $data['instrucoes'] = '';
        if ($this->config->get(self::CODE . '_information_id')) {
            $this->load->model('catalog/information');
            $information_info = $this->model_catalog_information->getInformation($this->config->get(self::CODE . '_information_id'));

            if ($information_info) {
                $data['instrucoes'] = html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8');
            }
        }

        $data['cor_normal_texto'] = $this->config->get(self::CODE . '_cor_normal_texto');
        $data['cor_normal_fundo'] = $this->config->get(self::CODE . '_cor_normal_fundo');
        $data['cor_normal_borda'] = $this->config->get(self::CODE . '_cor_normal_borda');
        $data['cor_efeito_texto'] = $this->config->get(self::CODE . '_cor_efeito_texto');
        $data['cor_efeito_fundo'] = $this->config->get(self::CODE . '_cor_efeito_fundo');
        $data['cor_efeito_borda'] = $this->config->get(self::CODE . '_cor_efeito_borda');

        $data['estilo_botao'] = $this->config->get(self::CODE . '_estilo_botao_b3');
        $data['texto_botao'] = $this->config->get(self::CODE . '_texto_botao');
        $data['container_botao'] = $this->config->get(self::CODE . '_container_botao');

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $data['total'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], true);

        $i = 0;
        $bandeiras = array();

        foreach ($this->bandeiras() as $bandeira) {
            if ($this->config->get(self::CODE . '_' . $bandeira)) {
                $bandeiras[] = array(
                    'bandeira' => $bandeira,
                    'titulo' => strtoupper($bandeira)
                );
                $i++;
            }
        }

        $data['bandeiras'] = json_encode($bandeiras);
        $data['habilitado'] = $i;

        if (!isset($this->session->data['attempts'])) {
            $this->session->data['attempts'] = 6;
        } elseif ($data['sandbox']) {
            $this->session->data['attempts'] = 6;
        }

        $data['alerta'] = '';

        if (
            isset($this->session->data[self::NAME . '_erro'])
            && !empty($this->session->data[self::NAME . '_erro'])
        ) {
            $data['alerta'] = $this->session->data[self::NAME . '_erro'];
        }

        $tema = $this->config->get(self::CODE . '_tema');

        return $this->load->view('extension/payment/rede_rest/debito_'. $tema, $data);
    }

    public function transacao() {
        $json = array();

        $this->language->load(self::EXTENSION);

        if (
            $this->validar_basico() == false
            || $this->validar_post() == false
        ) {
            $json['error'] = $this->language->get('error_permissao');
        }

        $order_id = $this->session->data['order_id'];

        if (!$json) {
            $this->load->model(self::EXTENSION);
            $pedido_pago = $this->{self::MODEL}->getTransactionPaid($order_id);

            if ($pedido_pago == true) {
                $json['redirect'] = $this->url->link('checkout/success', '', true);
            }
        }

        if (!$json && $this->session->data['attempts'] <= 0) {
            if ($this->session->data['attempts'] == 0) {
                $this->session->data['attempts']--;

                $this->load->model('checkout/order');
                $this->model_checkout_order->addOrderHistory($order_id, $this->config->get(self::CODE . '_situacao_nao_autorizada_id'), $this->language->get('text_tentativas'), true);
            }

            if (isset($this->session->data['payment_method'])) {
                unset($this->session->data['payment_method']);
            }

            $json['error'] = $this->language->get('error_tentativas');
        }

        if (!$json) {
            $cartao_bandeira = $this->limpar_string(strtolower($this->request->post['bandeira']));
            $cartao_nome = $this->limpar_string($this->request->post['nome']);
            $cartao_numero = preg_replace("/[^0-9]/", '', $this->request->post['cartao']);
            $cartao_mes = preg_replace("/[^0-9]/", '', $this->request->post['mes']);
            $cartao_ano = preg_replace("/[^0-9]/", '', $this->request->post['ano']);
            $cartao_cvv = preg_replace("/[^0-9]/", '', $this->request->post['codigo']);

            $campos = array($cartao_bandeira, $cartao_nome, $cartao_numero, $cartao_mes, $cartao_ano, $cartao_cvv);
            if (
                $this->validar_campos($campos) == false
                || $this->validar_bandeira($cartao_bandeira) == false
            ) {
                $json['error'] = $this->language->get('error_preenchimento');
            }
        }

        if (!$json) {
            $this->atualizar_pedido();

            $this->load->model('checkout/order');
            $order_info = $this->model_checkout_order->getOrder($order_id);

            $total = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);

            $dados['reference'] = $order_id;
            $dados['amount'] = number_format($total, 2, '', '');
            $dados['cardholderName'] = $cartao_nome;
            $dados['cardNumber'] = $cartao_numero;
            $dados['expirationMonth'] = $cartao_mes;
            $dados['expirationYear'] = $cartao_ano;
            $dados['securityCode'] = $cartao_cvv;

            $dados['softDescriptor'] = $this->config->get(self::CODE . '_soft_descriptor');

            if ($this->config->get('config_seo_url') == '1') {
                $url_retorno = HTTPS_SERVER . 'rede/debito/retorno';
            } else {
                $url_retorno = $this->url->link(self::EXTENSION . '/retorno', '', true);
            }

            $dados['successUrl'] = $url_retorno;
            $dados['failureUrl'] = $url_retorno;

            $chave = $this->config->get(self::MODULE_CODE . '_chave');
            $dados['chave'] = $chave[$this->config->get('config_store_id')];
            $dados['sandbox'] = $this->config->get(self::MODULE_CODE . '_sandbox');
            $dados['debug'] = $this->config->get(self::MODULE_CODE . '_debug');
            $dados['filiacao'] = $this->config->get(self::MODULE_CODE . '_filiacao');
            $dados['token'] = $this->config->get(self::MODULE_CODE . '_token');

            require_once(DIR_SYSTEM . 'library/rede-rest/rede.php');
            $rede = new Rede();
            $rede->setParametros($dados);
            $resposta = $rede->setTransactionDebit();

            if ($resposta) {
                if (isset($resposta->returnCode)) {
                    $return_code = $resposta->returnCode;

                    switch ($return_code) {
                        case '220':
                            if (isset($this->session->data[self::NAME . '_instrucoes'])) {
                                unset($this->session->data[self::NAME . '_instrucoes']);
                            }
                            $this->session->data[self::NAME . '_instrucoes'] = sprintf($this->language->get('text_instrucoes'), $resposta->threeDSecure->url);

                            $json['redirect'] = $this->url->link('checkout/success', '', true);

                            $comment = $this->language->get('text_pendente');

                            $order_status_id = $this->config->get(self::CODE . '_situacao_pendente_id');

                            $this->model_checkout_order->addOrderHistory($order_id, $order_status_id, $comment, false);

                            break;
                        default:
                            $codes = array('1', '2', '3', '15', '16', '33', '35', '36', '37', '38', '55', '59');

                            if (in_array($return_code, $codes)) {
                                $json['error'] = $this->language->get('error_dados');
                            } elseif (
                                ($return_code >= '4' && $return_code <= '85')
                                || ($return_code >= '87' && $return_code <= '100')
                                || ($return_code >= '132' && $return_code <= '176')
                                || ($return_code == '203')
                                || ($return_code >= '250' && $return_code <= '261')
                                || ($return_code == '899')
                                || ($return_code >= '1018' && $return_code <= '1034')
                            ) {
                                $json['error'] = $this->language->get('error_configuracao');
                            } else {
                                $json['error'] = $this->language->get('error_autorizacao');
                            }

                            break;
                    }
                } else {
                    $json['error'] = $this->language->get('error_json');
                }
            } else {
                $json['error'] = $this->language->get('error_configuracao');
            }
        }

        if (isset($json['redirect']) && !empty($json['redirect'])) {
            $this->session->data['attempts'] = 6;
        }

        unset($this->session->data[self::NAME . '_erro']);
        if (isset($json['error']) && !empty($json['error'])) {
            $this->session->data[self::NAME . '_erro'] = $json['error'];
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function retorno() {
        if (!isset($this->request->post['reference'])) {
            $this->response->redirect($this->url->link('error/not_found'));
        }

        $order_id = preg_replace('/[^0-9]/', '', $this->request->post['reference']);

        if (empty($order_id)) {
            $this->response->redirect($this->url->link('error/not_found'));
        }

        if (isset($this->session->data[self::NAME . '_comprovante'])) {
            unset($this->session->data[self::NAME . '_comprovante']);
        }

        if (isset($this->session->data[self::NAME . '_instrucoes'])) {
            unset($this->session->data[self::NAME . '_instrucoes']);
        }

        if (!isset($this->request->post['tid'])) {
            $this->response->redirect($this->url->link('error/not_found'));
        }

        $this->language->load(self::EXTENSION);

        $tid = filter_var($this->request->post['tid'], FILTER_SANITIZE_STRING);

        if (empty($tid)) {
            $this->session->data[self::NAME . '_instrucoes'] = $this->language->get('text_falhou');

            $colunas = array('order_id');

            $this->load->model(self::EXTENSION);
            $order_info = $this->{self::MODEL}->getOrder($colunas, $order_id);

            if (empty($order_info)) {
                $this->response->redirect($this->url->link('error/not_found'));
            }

            $comment = $this->language->get('text_nao_autorizado');

            $this->load->model('checkout/order');
            $this->model_checkout_order->addOrderHistory($order_id, $this->config->get(self::CODE . '_situacao_nao_autorizada_id'), $comment, true);

            $this->response->redirect($this->url->link('checkout/success', '', true));
        }

        $this->load->model(self::EXTENSION);
        $pedido_pago = $this->{self::MODEL}->getTransactionPaid($order_id);

        if ($pedido_pago == true) {
            $this->response->redirect($this->url->link('checkout/success', '', true));
        }

        $chave = $this->config->get(self::MODULE_CODE . '_chave');
        $dados['chave'] = $chave[$this->config->get('config_store_id')];
        $dados['sandbox'] = $this->config->get(self::MODULE_CODE . '_sandbox');
        $dados['debug'] = $this->config->get(self::MODULE_CODE . '_debug');
        $dados['filiacao'] = $this->config->get(self::MODULE_CODE . '_filiacao');
        $dados['token'] = $this->config->get(self::MODULE_CODE . '_token');
        $dados['tid'] = $tid;

        require_once(DIR_SYSTEM . 'library/rede-rest/rede.php');
        $rede = new Rede();
        $rede->setParametros($dados);
        $resposta = $rede->getTransaction();

        if (!isset($resposta->authorization->reference) || !isset($resposta->authorization->returnCode)) {
            $this->response->redirect($this->url->link('error/not_found'));
        }

        $colunas = array('currency_code');
        $order_id = preg_replace('/[^0-9]/', '', $resposta->authorization->reference);

        $order_info = $this->{self::MODEL}->getOrder($colunas, $order_id);

        if (empty($order_info)) {
            $this->response->redirect($this->url->link('error/not_found'));
        }

        $this->load->model('checkout/order');

        $return_code = $resposta->authorization->returnCode;

        if ($return_code == '00') {
            $tid = $resposta->authorization->tid;
            $nsu = $resposta->authorization->nsu;
            $authorized_date = $resposta->authorization->dateTime;
            $authorized_total = $resposta->authorization->amount / 100;
            $authorization_code = isset($resposta->authorization->brand->authorizationCode) ? $resposta->authorization->brand->authorizationCode : $resposta->authorization->authorizationCode;
            $captured_date = isset($resposta->capture->dateTime) ? $resposta->capture->dateTime : $authorized_date;
            $captured_total = isset($resposta->capture->amount) ? $resposta->capture->amount / 100 : $authorized_total;
            $card_brand = isset($resposta->authorization->brand->name) ? ucfirst(strtolower($resposta->authorization->brand->name)) : '';

            $campos = array(
                'order_id' => $order_id,
                'return_code' => $return_code,
                'status' => 'capturada',
                'type' => 'debito',
                'card_brand' => $card_brand,
                'installments' => '1',
                'tid' => $tid,
                'nsu' => $nsu,
                'authorization_code' => $authorization_code,
                'authorized_date' => $authorized_date,
                'authorized_total' => $authorized_total,
                'captured_date' => $captured_date,
                'captured_total' => $captured_total,
                'json_first_response' => json_encode($resposta)
            );

            $date = date('d/m/Y H:i', strtotime($authorized_date));
            $amount = $this->currency->format($authorized_total, $order_info['currency_code'], '1.00', true);

            $comment = $this->language->get('entry_pedido') . $order_id . "\n";
            $comment .= $this->language->get('entry_data') . $date . ' ' . $this->language->get('text_fuso_horario') . "\n";
            $comment .= $this->language->get('entry_tid') . $tid . "\n";
            $comment .= $this->language->get('entry_tipo') . $this->language->get('text_cartao_debito') . ' ' . $card_brand . "\n";
            $comment .= $this->language->get('entry_total') . $amount . "\n";
            $comment .= $this->language->get('entry_status') . $this->language->get('text_capturado');

            $this->session->data[self::NAME . '_comprovante'] = $this->language->get('text_comprovante') . "\n" . $comment;

            $this->{self::MODEL}->addTransaction($campos);

            $this->model_checkout_order->addOrderHistory($order_id, $this->config->get(self::CODE . '_situacao_capturada_id'), $comment, true);
        } else {
            $this->session->data[self::NAME . '_instrucoes'] = $this->language->get('text_falhou');

            $order_status_id = $this->config->get(self::CODE . '_situacao_nao_autorizada_id');

            $comment = $this->language->get('text_nao_autorizado');

            $this->model_checkout_order->addOrderHistory($order_id, $order_status_id, $comment, true);
        }

        $this->response->redirect($this->url->link('checkout/success', '', true));
    }

    private function bandeiras() {
        return array(
            "visa",
            "mastercard"
        );
    }

    private function limpar_string($string) {
        $string = strip_tags($string);
        $string = preg_replace('/[\n\t\r]/', ' ', $string);
        $string = preg_replace('/( ){2,}/', '$1', $string);

        return trim($string);
    }

    private function validar_basico() {
        if (
            isset($this->session->data['order_id'])
            && isset($this->session->data['payment_method']['code'])
            && isset($this->session->data['attempts'])
            && $this->session->data['payment_method']['code'] == self::NAME
            && $this->session->data['attempts'] >= 0
            && $this->session->data['attempts'] <= 6
        ) {
            return true;
        }

        return false;
    }

    private function validar_post() {
        $campos = array(
            'bandeira',
            'nome',
            'cartao',
            'mes',
            'ano',
            'codigo'
        );

        $erros = 0;
        foreach ($campos as $campo) {
            if (!isset($this->request->post[$campo])) {
                $erros++;
                break;
            }
        }

        if ($erros == 0) {
            return true;
        } else {
            return false;
        }
    }

    private function validar_campos($campos) {
        $erros = 0;

        foreach ($campos as $campo) {
            if (empty($campo)) {
                $erros++;
                break;
            }
        }

        if ($erros == 0) {
            return true;
        } else {
            return false;
        }
    }

    private function validar_bandeira($bandeira) {
        $bandeiras = $this->bandeiras();

        return in_array($bandeira, $bandeiras);
    }

    private function atualizar_pedido() {
        $order_data['custom_field'] = array();

        if ($this->customer->isLogged()) {
            $this->load->model('account/customer');
            $customer_info = $this->model_account_customer->getCustomer($this->customer->getId());

            $order_data['custom_field'] = json_decode($customer_info['custom_field'], true);
        } elseif (isset($this->session->data['guest'])) {
            $order_data['custom_field'] = $this->session->data['guest']['custom_field'];
        }

        $order_data['payment_custom_field'] = (isset($this->session->data['payment_address']['custom_field']) ? $this->session->data['payment_address']['custom_field'] : array());

        if ($this->cart->hasShipping()) {
            $order_data['shipping_custom_field'] = (isset($this->session->data['shipping_address']['custom_field']) ? $this->session->data['shipping_address']['custom_field'] : array());
        } else {
            $order_data['shipping_custom_field'] = array();
        }

        $this->load->model(self::EXTENSION);
        $this->{self::MODEL}->editOrder($order_data, $this->session->data['order_id']);
    }
}
