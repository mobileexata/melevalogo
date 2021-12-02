<?php
require_once DIR_SYSTEM . 'library/rede-rest/engine.php';

class ControllerExtensionPaymentRedeRestCredito extends Controller {
    use RedeRestEngine;

    const TYPE = 'payment_';
    const NAME = 'rede_rest_credito';
    const CODE = self::TYPE . self::NAME;
    const EXTENSION = 'extension/payment/' . self::NAME;
    const MODEL = 'model_extension_payment_' . self::NAME;
    const MODULE_CODE = 'module_rede_rest';
    const VALIDACAO = 'extension/payment/rede_rest_validacao';

    private $valor_pedido = 0;

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

        $data['exibir_juros'] = $this->config->get(self::CODE . '_exibir_juros');

        $data['cor_normal_texto'] = $this->config->get(self::CODE . '_cor_normal_texto');
        $data['cor_normal_fundo'] = $this->config->get(self::CODE . '_cor_normal_fundo');
        $data['cor_normal_borda'] = $this->config->get(self::CODE . '_cor_normal_borda');
        $data['cor_efeito_texto'] = $this->config->get(self::CODE . '_cor_efeito_texto');
        $data['cor_efeito_fundo'] = $this->config->get(self::CODE . '_cor_efeito_fundo');
        $data['cor_efeito_borda'] = $this->config->get(self::CODE . '_cor_efeito_borda');

        $data['estilo_botao'] = $this->config->get(self::CODE . '_estilo_botao_b3');
        $data['texto_botao'] = $this->config->get(self::CODE . '_texto_botao');
        $data['container_botao'] = $this->config->get(self::CODE . '_container_botao');

        $i = 0;
        $bandeiras = array();

        foreach ($this->bandeiras() as $bandeira) {
            $bandeiras[] = array(
                'bandeira' => $bandeira,
                'titulo' => strtoupper($bandeira)
            );
            $i++;
        }

        $data['bandeiras'] = json_encode($bandeiras);
        $data['habilitado'] = $i;

        $data['captcha'] = $this->config->get(self::CODE . '_recaptcha_status');
        if ($data['captcha']) {
            $data['site_key'] = $this->config->get(self::CODE . '_recaptcha_site_key');
        }

        if (isset($this->session->data['secury_token'])) { unset($this->session->data['secury_token']); }
        require_once(DIR_SYSTEM . 'library/rede-rest/helper.php');
        $this->session->data['secury_token'] = secury_token(32);
        $data['token'] = $this->session->data['secury_token'];

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

        return $this->load->view('extension/payment/rede_rest/credito_'. $tema, $data);
    }

    public function parcelas() {
        $json = array();

        if (
            $this->validar_basico() == true
            && isset($this->request->get['bandeira'])
        ) {
            $bandeira = strtolower($this->request->get['bandeira']);

            if ($this->validar_bandeira($bandeira)) {
                $colunas = array('currency_code', 'currency_value', 'total');
                $order_id = $this->session->data['order_id'];

                $this->load->model(self::EXTENSION);
                $order_info = $this->{self::MODEL}->getOrder($colunas, $order_id);

                $total = $order_info['total'];
                $currency_code = strtoupper($order_info['currency_code']);
                $currency_value = $order_info['currency_value'];

                $total = $this->currency->format($total, $currency_code, $currency_value, false);
                $this->valor_pedido = $total;

                $desconto = ($this->config->get(self::CODE . '_desconto') > 0) ? (float) $this->config->get(self::CODE . '_desconto') : 0;
                if ($desconto > 0) {
                    $shipping = $this->{self::MODEL}->getOrderShippingValue($order_id);

                    if ($shipping > 0) {
                        $shipping = $this->currency->format($shipping, $currency_code , $currency_value, false);
                    }
                }

                if ($currency_code == 'BRL') {
                    $valor_minimo = $this->config->get(self::CODE . '_minimo') > 0 ? $this->config->get(self::CODE . '_minimo') : '0';
                    $bandeiras = $this->config->get(self::CODE . '_bandeiras');
                    $regras = $this->config->get(self::CODE . '_regras');

                    $parcelas = $bandeiras[$bandeira]['parcelas'];
                    $sem_juros = $bandeiras[$bandeira]['sem_juros'];

                    $regra = array();

                    if (!empty($regras)) {
                        $regra = array_reduce($regras, function($anterior, $atual) use ($total, $parcelas) {
                            if ($total >= $atual['total'] && $parcelas >= $atual['parcela']) {
                                return $atual;
                            }

                            return $anterior;
                        });
                    }

                    $parcelas = isset($regra['parcela']) ? $regra['parcela'] : $parcelas;

                    for ($i = 1; $i <= $parcelas; $i++) {
                        if ($i <= $sem_juros) {
                            if ($i == 1) {
                                if ($desconto > 0) {
                                    $subtotal = $total - $shipping;
                                    $desconto = ($subtotal * $desconto) / 100;
                                    $valor_parcela = ($subtotal - $desconto) + $shipping;

                                    $desconto = $this->currency->format($desconto, $currency_code, '1.00', true);
                                } else {
                                    $valor_parcela = $total;
                                }

                                $valor_parcela = $this->currency->format($valor_parcela, $currency_code, '1.00', true);

                                $json[] = array(
                                    'parcela' => 1,
                                    'desconto' => $desconto,
                                    'valor' => $valor_parcela,
                                    'juros' => 0,
                                    'total' => $valor_parcela
                                );
                            } else {
                                $valor_parcela = ($total / $i);

                                if ($valor_parcela >= $valor_minimo) {
                                    $json[] = array(
                                        'parcela' => $i,
                                        'desconto' => 0,
                                        'valor' => $this->currency->format($valor_parcela, $currency_code, '1.00', true),
                                        'juros' => 0,
                                        'total' => $this->currency->format($total, $currency_code, '1.00', true)
                                    );
                                }
                            }
                        } else {
                            $resultado = $this->calcular($bandeira, $i, $currency_code);

                            if ($resultado['valor_parcela'] >= $valor_minimo) {
                                $json[] = array(
                                    'parcela' => $i,
                                    'desconto' => 0,
                                    'valor' => $this->currency->format($resultado['valor_parcela'], $currency_code, '1.00', true),
                                    'juros' => $resultado['juros'],
                                    'total' => $this->currency->format($resultado['valor_total'], $currency_code, '1.00', true)
                                );
                            }
                        }
                    }
                } else {
                    if ($desconto > 0) {
                        $subtotal = $total - $shipping;
                        $desconto = ($subtotal * $desconto) / 100;
                        $valor_parcela = ($subtotal - $desconto) + $shipping;

                        $desconto = $this->currency->format($desconto, $currency_code, '1.00', true);
                    } else {
                        $valor_parcela = $total;
                    }

                    $valor_parcela = $this->currency->format($valor_parcela, $currency_code, '1.00', true);

                    $json[] = array(
                        'parcela' => 1,
                        'desconto' => $desconto,
                        'valor' => $valor_parcela,
                        'juros' => 0,
                        'total' => $valor_parcela
                    );
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
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
            $erros_cadastro = $this->validar_cadastro();

            if (!empty($erros_cadastro)) {
                $json['error'] = sprintf($this->language->get('error_validacao'), $erros_cadastro);
            }
        }

        if (!$json) {
            if ($this->validar_captcha() == false) {
                $json['error'] = $this->language->get('error_captcha');
            }
        }

        if (!$json) {
            $cartao_bandeira = $this->limpar_string(strtolower($this->request->post['bandeira']));
            $cartao_numero = preg_replace("/[^0-9]/", '', $this->request->post['cartao']);
            $cartao_mes = preg_replace("/[^0-9]/", '', $this->request->post['mes']);
            $cartao_ano = preg_replace("/[^0-9]/", '', $this->request->post['ano']);
            $cartao_cvv = preg_replace("/[^0-9]/", '', $this->request->post['codigo']);
            $cartao_nome = $this->limpar_string($this->request->post['nome']);
            $cartao_documento = preg_replace("/[^0-9]/", '', $this->request->post['documento']);
            $cartao_parcelas = preg_replace("/[^0-9]/", '', $this->request->post['parcelas']);

            $campos = array($cartao_bandeira, $cartao_numero, $cartao_mes, $cartao_ano, $cartao_cvv, $cartao_nome, $cartao_documento, $cartao_parcelas);

            if (
                $this->validar_campos($campos) == false
                || $this->validar_bandeira($cartao_bandeira) == false
                || $cartao_parcelas > '12'
            ) {
                $json['error'] = $this->language->get('error_preenchimento');
            }
        }

        if (!$json) {
            $this->session->data['attempts']--;

            $this->load->model('checkout/order');
            $order_info = $this->model_checkout_order->getOrder($order_id);

            $currency_code = strtoupper($order_info['currency_code']);
            $currency_value = $order_info['currency_value'];

            $total = $this->currency->format($order_info['total'], $currency_code, $currency_value, false);
            $this->valor_pedido = $total;

            $shipping = $this->{self::MODEL}->getOrderShippingValue($order_id);

            if ($shipping > 0) {
                $shipping = $this->currency->format($shipping, $currency_code, $currency_value, false);
            }

            if ($cartao_parcelas <= '1') {
                $desconto = ($this->config->get(self::CODE . '_desconto') > 0) ? (float) $this->config->get(self::CODE . '_desconto') : 0;

                if ($desconto > 0) {
                    $subtotal = $total-$shipping;
                    $desconto = ($subtotal * $desconto) / 100;
                    $total = ($subtotal-$desconto) + $shipping;
                }
            } else {
                $sem_juros = $this->config->get(self::CODE . '_' . $cartao_bandeira . '_sem_juros');

                if ($cartao_parcelas > $sem_juros) {
                    $resultado = $this->calcular($cartao_bandeira, $cartao_parcelas, $currency_code);
                    $total = $resultado['valor_total'];
                }
            }

            $captura = $this->config->get(self::CODE . '_captura');

            $dados['reference'] = $order_id;
            $dados['amount'] = number_format($total, 2, '', '');
            $dados['cardholderName'] = $cartao_nome;
            $dados['installments'] = $cartao_parcelas;
            $dados['cardNumber'] = $cartao_numero;
            $dados['expirationMonth'] = $cartao_mes;
            $dados['expirationYear'] = $cartao_ano;
            $dados['securityCode'] = $cartao_cvv;

            $dados['softDescriptor'] = $this->config->get(self::CODE . '_soft_descriptor');
            $dados['capture'] = $captura;

            $chave = $this->config->get(self::MODULE_CODE . '_chave');
            $dados['chave'] = $chave[$this->config->get('config_store_id')];
            $dados['sandbox'] = $this->config->get(self::MODULE_CODE . '_sandbox');
            $dados['debug'] = $this->config->get(self::MODULE_CODE . '_debug');
            $dados['filiacao'] = $this->config->get(self::MODULE_CODE . '_filiacao');
            $dados['token'] = $this->config->get(self::MODULE_CODE . '_token');

            require_once(DIR_SYSTEM . 'library/rede-rest/rede.php');
            $rede = new Rede();
            $rede->setParametros($dados);
            $resposta = $rede->setTransactionCredit();

            if ($resposta) {
                if (isset($resposta->returnCode)) {
                    $return_code = $resposta->returnCode;

                    if (isset($resposta->reference) && !empty($resposta->reference)) {
                        $tid = $resposta->tid;
                        $nsu = $resposta->nsu;
                        $date = date('d/m/Y H:i', strtotime(substr($resposta->dateTime, 0, -6)));
                        $amount = $this->currency->format(($resposta->amount / 100), $currency_code, '1.00', true);
                        $card_brand = isset($resposta->brand->name) ? $resposta->brand->name : $cartao_bandeira;
                        $card_brand = ucfirst(strtolower($card_brand));

                        switch ($return_code) {
                            case '00':
                                $status = $captura == true ? 'capturada' : 'autorizada';
                                $authorization_code = isset($resposta->brand->authorizationCode) ? $resposta->brand->authorizationCode : $resposta->authorizationCode;
                                $captured_date = $captura == true ? $resposta->dateTime : '';
                                $captured_total = $captura == true ? $resposta->amount / 100 : '';

                                $campos = array(
                                    'order_id' => $order_id,
                                    'return_code' => $return_code,
                                    'status' => $status,
                                    'type' => 'credito',
                                    'card_brand' => $card_brand,
                                    'card_bin' => $resposta->cardBin,
                                    'card_end' => $resposta->last4,
                                    'card_holder' => $cartao_nome,
                                    'card_document' => $cartao_documento,
                                    'installments' => $cartao_parcelas,
                                    'tid' => $tid,
                                    'nsu' => $nsu,
                                    'authorization_code' => $authorization_code,
                                    'authorized_date' => $resposta->dateTime,
                                    'authorized_total' => $resposta->amount / 100,
                                    'captured_date' => $captured_date,
                                    'captured_total' => $captured_total,
                                    'json_first_response' => json_encode($resposta)
                                );

                                $antifraude = $this->antifraude();
                                if ($antifraude == true) {
                                    $situacao = $this->language->get('text_em_analise');
                                } else {
                                    if ($captura == true) {
                                        $situacao = $this->language->get('text_capturado');
                                    } else {
                                        $situacao = $this->language->get('text_autorizado');
                                    }
                                }

                                $comment = $this->language->get('entry_pedido') . $order_id . "\n";
                                $comment .= $this->language->get('entry_data') . $date . ' ' . $this->language->get('text_fuso_horario') . "\n";
                                $comment .= $this->language->get('entry_tid') . $tid . "\n";
                                $comment .= $this->language->get('entry_nsu') . $nsu . "\n";
                                $comment .= $this->language->get('entry_tipo') . $this->language->get('text_cartao_credito') . ' ' . $card_brand . "\n";
                                $comment .= $this->language->get('entry_total') . $cartao_parcelas . 'x' . $this->language->get('text_total') . $amount . "\n";
                                $comment .= $this->language->get('entry_status') . $situacao;

                                if (isset($this->session->data[self::NAME . '_comprovante'])) {
                                    unset($this->session->data[self::NAME . '_comprovante']);
                                }
                                $this->session->data[self::NAME . '_comprovante'] = $this->language->get('text_comprovante') . "\n" . $comment;

                                $this->{self::MODEL}->addTransaction($campos);

                                $order_status_id = $captura == true ? $this->config->get(self::CODE . '_situacao_capturada_id') : $this->config->get(self::CODE . '_situacao_autorizada_id');

                                $this->model_checkout_order->addOrderHistory($order_id, $order_status_id, $comment, true);

                                $json['redirect'] = $this->url->link('checkout/success', '', true);

                                break;
                            default:
                                $comment = $this->language->get('entry_pedido') . $order_id . "\n";
                                $comment .= $this->language->get('entry_data') . $date . ' ' . $this->language->get('text_fuso_horario') . "\n";
                                $comment .= $this->language->get('entry_tid') . $tid . "\n";
                                if ($nsu) { $comment .= $this->language->get('entry_nsu') . $nsu . "\n"; }
                                $comment .= $this->language->get('entry_tipo') . $this->language->get('text_cartao_credito') . ' ' . $card_brand . "\n";
                                $comment .= $this->language->get('entry_total') . $cartao_parcelas . 'x' . $this->language->get('text_total') . $amount . "\n";
                                $comment .= $this->language->get('entry_status') . $this->language->get('text_nao_autorizado');

                                $this->model_checkout_order->addOrderHistory($order_id, $this->config->get(self::CODE . '_situacao_nao_autorizada_id'), $comment, true);

                                $json['error'] = $this->language->get('error_autorizacao');

                                break;
                        }
                    } else {
                        $codes = array('1', '2', '3', '15', '16', '33', '35', '36', '37', '38', '55', '59');

                        if (in_array($return_code, $codes)) {
                            $json['error'] = $this->language->get('error_dados');
                        } elseif (
                            ($return_code >= '4' && $return_code <= '100')
                            || ($return_code >= '132' && $return_code <= '176')
                            || ($return_code == '899')
                            || ($return_code >= '1018' && $return_code <= '1034')
                        ) {
                            $json['error'] = $this->language->get('error_configuracao');
                        } else {
                            $json['error'] = $this->language->get('error_autorizacao');
                        }
                    }
                } else {
                    $json['error'] = $this->language->get('error_status');
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

    private function bandeiras() {
        $bandeiras = array();

        $bandeiras_data = $this->config->get(self::CODE . '_bandeiras');

        if ($bandeiras_data) {
            foreach ($bandeiras_data as $bandeira) {
                if (isset($bandeira['ativa']) && $bandeira['ativa'] == 'on') {
                    array_push($bandeiras, $bandeira['nome']);
                }
            }
        }

        return $bandeiras;
    }

    private function calcular($bandeira, $parcelas, $currency_code) {
        $valor_pedido = $this->valor_pedido;
        $valor_parcela = $valor_pedido;
        $valor_total = $valor_pedido;
        $juros = 0;

        if ($currency_code == 'BRL') {
            $bandeiras = $this->config->get(self::CODE . '_bandeiras');
            $regras = $this->config->get(self::CODE . '_regras');

            $parcelas = min($bandeiras[$bandeira]['parcelas'], $parcelas);

            $regra = array();

            if (!empty($regras)) {
                $regra = array_reduce($regras, function($anterior, $atual) use ($valor_pedido, $parcelas) {
                    if ($valor_pedido >= $atual['total'] && $parcelas >= $atual['parcela']) {
                      return $atual;
                    }

                    return $anterior;
                });
            }

            $parcelas = isset($regra['parcela']) ? $regra['parcela'] : $parcelas;
            $juros = isset($bandeiras[$bandeira]['juros'][$parcelas]) ? $bandeiras[$bandeira]['juros'][$parcelas] : 0;

            if ($juros > 0) {
                $decimal = (1 - ($juros / 100));
                $total_com_juros = $valor_pedido / $decimal;
                $valor_parcela = round($total_com_juros / $parcelas, 2);
                $valor_total = round($total_com_juros, 2);
            } else {
                $valor_parcela = round($valor_pedido / $parcelas, 2);
            }
        }

        return array(
            'valor_parcela' => $valor_parcela,
            'valor_total' => $valor_total,
            'juros' => $juros
        );
    }

    private function limpar_string($string) {
        $string = strip_tags($string);
        $string = preg_replace('/[\n\t\r]/', ' ', $string);
        $string = preg_replace('/( ){2,}/', '$1', $string);

        return trim($string);
    }

    private function validar_basico() {
        require_once(DIR_SYSTEM . 'library/rede-rest/helper.php');

        if (
            isset($this->session->data['order_id'])
            && isset($this->session->data['payment_method']['code'])
            && isset($this->session->data['secury_token'])
            && isset($this->session->data['attempts'])
            && isset($this->request->get['token'])
            && $this->session->data['payment_method']['code'] == self::NAME
            && hash_equals($this->session->data['secury_token'], trim($this->request->get['token']))
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
            'cartao',
            'mes',
            'ano',
            'codigo',
            'nome',
            'documento',
            'parcelas'
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

    private function validar_captcha() {
        if (!$this->config->get(self::CODE . '_recaptcha_status')) {
            return true;
        }

        if (!isset($this->request->post['g-recaptcha-response'])) {
            return false;
        }

        if (empty($this->request->post['g-recaptcha-response'])) {
            return false;
        }

        $recaptcha = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($this->config->get(self::CODE . '_recaptcha_secret_key')) . '&response=' . $this->request->post['g-recaptcha-response'] . '&remoteip=' . $this->request->server['REMOTE_ADDR']);
        $recaptcha = json_decode($recaptcha);

        if (isset($recaptcha->success)) {
            if ($recaptcha->success) {
                return true;
            }
        }

        return false;
    }

    private function antifraude() {
        $status = false;

        if ($this->config->get(self::CODE . '_clearsale_status')) {
            $status = true;
        }

        return $status;
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

    private function campos() {
        if ($this->customer->isLogged()) {
            $customer_group_id = $this->customer->getGroupId();
        } elseif (isset($this->session->data['guest']['customer_group_id'])) {
            $customer_group_id = $this->session->data['guest']['customer_group_id'];
        } else {
            $customer_group_id = $this->config->get('config_customer_group_id');
        }

        $this->load->model('account/custom_field');
        $custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);

        $fields = array();
        foreach ($custom_fields as $custom_field) {
            array_push($fields, $custom_field['custom_field_id']);
        }

        return $fields;
    }

    private function campo_valor($custom_data, $field_key, $collumn_data, $field_collumn) {
        $field_value = '';

        if ($field_key == 'C') {
            if (isset($collumn_data[$field_collumn]) && !empty($collumn_data[$field_collumn])) {
                $field_value = $collumn_data[$field_collumn];
            }
        } elseif (!empty($field_key) && is_array($custom_data)) {
            foreach ($custom_data as $key => $value) {
                if ($field_key == $key) { $field_value = $value; }
            }
        }

        return $field_value;
    }

    private function validar_cadastro() {
        $antifraude = $this->antifraude();
        if ($antifraude == false) { return ''; }

        $this->load->language(self::VALIDACAO);

        $order_id = $this->session->data['order_id'];

        $this->load->model(self::EXTENSION);
        $custom_field_info = $this->{self::MODEL}->getOrder(array('custom_field', 'payment_custom_field'), $order_id);

        if (!$custom_field_info['custom_field'] || !$custom_field_info['payment_custom_field']) {
            $this->atualizar_pedido();
        }

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);

        $custom_razao_id = $this->config->get(self::CODE . '_custom_razao_id');
        $custom_cnpj_id = $this->config->get(self::CODE . '_custom_cnpj_id');
        $custom_cpf_id = $this->config->get(self::CODE . '_custom_cpf_id');
        $custom_numero_id = $this->config->get(self::CODE . '_custom_numero_id');

        $razao_coluna = $this->config->get(self::CODE . '_razao_coluna');
        $cnpj_coluna = $this->config->get(self::CODE . '_cnpj_coluna');
        $cpf_coluna = $this->config->get(self::CODE . '_cpf_coluna');
        $numero_coluna = $this->config->get(self::CODE . '_numero_fatura_coluna');

        $colunas = array();
        $colunas_info = array();

        $campos = $this->campos();

        if (in_array($custom_razao_id, $campos) && $custom_razao_id == 'C') { array_push($colunas, $razao_coluna); }
        if (in_array($custom_cnpj_id, $campos) && $custom_cnpj_id == 'C') { array_push($colunas, $cnpj_coluna); }
        if (in_array($custom_cpf_id, $campos) && $custom_cpf_id == 'C') { array_push($colunas, $cpf_coluna); }
        if ($custom_numero_id == 'C') { array_push($colunas, $numero_coluna); }

        if (count($colunas)) {
            $this->load->model(self::EXTENSION);
            $colunas_info = $this->{self::MODEL}->getOrder($colunas, $order_id);
        }

        $erros = array();

        $cliente = '';
        if (in_array($custom_razao_id, $campos)) {
            $cliente = $this->campo_valor($order_info['custom_field'], $custom_razao_id, $colunas_info, $razao_coluna);
            $cliente = trim($cliente);
        }

        if (empty($cliente)) {
            $cliente = trim($order_info['firstname'] . ' ' . $order_info['lastname']);
            if (empty($cliente)) {
                $erros[] = $this->language->get('error_cliente');
            }
        }

        $documento = '';
        if (in_array($custom_cnpj_id, $campos)) {
            $documento = $this->campo_valor($order_info['custom_field'], $custom_cnpj_id, $colunas_info, $cnpj_coluna);
            $documento = trim($documento);
        }

        if (in_array($custom_cpf_id, $campos) && empty($documento)) {
            $documento = $this->campo_valor($order_info['custom_field'], $custom_cpf_id, $colunas_info, $cpf_coluna);
            $documento = trim($documento);
        }

        $documento = preg_replace("/[^0-9]/", '', $documento);
        $documento = strlen($documento);
        if ($documento == 14 || $documento == 11) {
        } else {
            $erros[] = $this->language->get('error_documento');
        }

        $telefone = strlen(preg_replace("/[^0-9]/", '', trim($order_info['telephone'])));
        if ($telefone < 10 || $telefone > 11) {
            $erros[] = $this->language->get('error_telefone');
        }

        $cep = preg_replace("/[^0-9]/", '', trim($order_info['payment_postcode']));
        if (strlen($cep) != 8) {
            $erros[] = $this->language->get('error_pagamento_cep');
        }

        $endereco = $this->sanitize_string($order_info['payment_address_1']);
        if (empty($endereco)) {
            $erros[] = $this->language->get('error_pagamento_endereco');
        }

        $numero = $this->campo_valor($order_info['payment_custom_field'], $custom_numero_id, $colunas_info, $numero_coluna);
        $numero = preg_replace("/[^0-9]/", '', $numero);
        if (strlen($numero) < 1) {
            $erros[] = $this->language->get('error_pagamento_numero');
        }

        $bairro = $this->sanitize_string($order_info['payment_address_2']);
        if (empty($bairro)) {
            $erros[] = $this->language->get('error_pagamento_bairro');
        }

        $cidade = $this->sanitize_string($order_info['payment_city']);
        if (empty($cidade)) {
            $erros[] = $this->language->get('error_pagamento_cidade');
        }

        $estado = $this->sanitize_string($order_info['payment_zone_code']);
        if (empty($estado)) {
            $erros[] = $this->language->get('error_pagamento_estado');
        }

        if (count($erros) > 0) {
            $resultado = '';

            foreach ($erros as $key => $value) {
                $resultado .= $value;
            }

            return $resultado;
        } else {
            return '';
        }
    }

    private function sanitize_string($string) {
        $substituir = array('&amp;', '&');
        $string = str_replace($substituir, 'E', $string);

        if ($string !== mb_convert_encoding(mb_convert_encoding($string, 'UTF-32', 'UTF-8'), 'UTF-8', 'UTF-32'))
            $string = mb_convert_encoding($string, 'UTF-8', mb_detect_encoding($string));

        $string = htmlentities($string, ENT_NOQUOTES, 'UTF-8');
        $string = preg_replace('`&([a-z]{1,2})(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig);`i', '\1', $string);
        $string = html_entity_decode($string, ENT_NOQUOTES, 'UTF-8');
        $string = preg_replace(array('`[^a-z0-9]`i','`[-]+`'), ' ', $string);

        $string = preg_replace('/[\n\t\r]/', ' ', $string);
        $string = preg_replace('/( ){2,}/', '$1', $string);
        $string = trim($string);

        return strtoupper($string);
    }
}

