<?php

/**
 *
 * © Copyright 2013-2021 Codemarket - Todos os direitos reservados.
 *
 * Class ModelModuleCodeClearsale
 */
class ModelModuleCodeClearsale extends Model
{
    private $token;
    private $conf;
    private $urlApi;
    private $urlApiData;
    private $status;
    private $order;
    private $type;

    /**
     * ModelModuleCodeClearsale constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);

        if (defined('DIR_CATALOG')) {
            require_once(DIR_CATALOG . 'model/checkout/order.php');
            $this->order = new ModelCheckoutOrder($this->registry);
        } else {
            $this->load->model('checkout/order');
            $this->order = $this->model_checkout_order;
        }

        $this->load->model('module/codemarket_module');
        $conf = $this->model_module_codemarket_module->getModulo('592');
        $this->log = new log('Code-ClearSaleTotal-' . date('m-Y') . '.log');

        if (
            empty($conf) || empty($conf->code_usuario) || empty($conf->code_senha) || empty($conf->code_fingerprint)
            || empty($conf->code_token_seguranca) || empty($conf->code_pagamento) || empty($conf->code_status)
            || empty($conf->code_status) || empty($conf->code_habilitar_producao)
            || empty($conf->code_habilitar) || $conf->code_habilitar == 2 || empty($conf->code_alertar_status)
            || empty($conf->code_status_aprovado) || empty($conf->code_status_negado) || empty($conf->code_status_chargeback)
            || empty($conf->code_status_clearsale_analise) || empty($conf->code_status_clearsale_aprovado)
            || empty($conf->code_status_clearsale_negado) || empty($conf->code_extra_cpf) || !isset($conf->code_extra_cpf2)
            || !isset($conf->code_extra_rg) || !isset($conf->code_extra_inscricao) || empty($conf->code_extra_numero)
            || !isset($conf->code_extra_complemento) || !isset($conf->code_extra_nascimento) || !isset($conf->code_extra_celular)

        ) {
            $this->log->write('construct() - Integração ClearSale Total desativada, verifique a configuração, config:' . print_r($conf, true));
            $this->status = false;
            return false;
        }

        $this->conf = $conf;

        $this->db->query('CREATE TABLE IF NOT EXISTS `' . DB_PREFIX . 'code_payments` (
          `code_payments_id` int(11) NOT NULL AUTO_INCREMENT,
          `order_id` int(11) DEFAULT NULL,
          `data` text DEFAULT NULL,
          `service` varchar(50) DEFAULT NULL,
          `date_created` datetime DEFAULT NULL,
          PRIMARY KEY (`code_payments_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ');

        if (!empty($this->conf->code_habilitar_producao) && $this->conf->code_habilitar_producao == 1) {
            $this->urlApi = 'https://api.clearsale.com.br/v1/';
            $this->urlApiData = 'https://apidata.clearsale.com.br/v1/';
        } else {
            $this->urlApi = 'https://homologacao.clearsale.com.br/api/v1/';
            $this->urlApiData = 'https://homologacao.clearsale.com.br/apidata/v1/';
        }

        $autentificar = $this->autentificar();
        if (empty($autentificar)) {
            $this->log->write('construct() - Sem retorno do token ClearSale');
            return false;
        }

        $this->status = true;
        $this->log->write('construct() - Passou na verificação');
        return true;
    }

    /**
     * Cria a autentificação da ClearSale
     *
     * @return bool
     */
    private function autentificar()
    {
        if (!empty($this->token)) {
            return true;
        }

        $this->token = $this->autentificarAPI();

        if (!empty($this->token['Token'])) {
            $this->token = $this->token['Token'];
            return true;
        } else {
            $this->status = false;
            return false;
        }
    }

    /**
     * Cria o Token de autentificação pela API
     *
     * @return array
     */
    private function autentificarAPI()
    {
        //https://api.clearsale.com.br/docs/how-to-start
        $url = $this->urlApi . 'authenticate';

        /*
        POST https://api.clearsale.com.br/v1/authenticate HTTP/1.1
        Content-Type: application/json
        {
            "name": "{Your User}",
            "password": "{Your Password}"
        }
        */

        $data = [
            "name"     => $this->conf->code_usuario,
            "password" => $this->conf->code_senha,
        ];

        return $this->post($data, $url);
    }

    /**
     * Adicionado o Pedido na ClearSale para certos Status e Pagamentos
     *
     * @param $order_id
     *
     * @return bool
     */
    public function incluirPedidoAPI($order_id, $type = 1)
    {
        if (empty($this->status)) {
            return false;
        }

        $this->type = $type;

        //https://api.clearsale.com.br/docs/total-totalGarantido-application
        $url = $this->urlApi . 'orders/';
        /*
            POST https://api.clearsale.com.br/v1/orders/
            Content-Type: application/json
            Authorization: Bearer {TOKEN}
            {....
        */

        /*
        *
        Lista de Status (de entrada)
        Código  Descrição
        0   Novo (será analisado pelo ClearSale)
        9   Aprovado (irá ao ClearSale já aprovado e não será analisado)
        41  Cancelado pelo cliente (irá ao ClearSale já cancelado e não será analisado)
        45  Reprovado (irá ao ClearSale já reprovado e não será analisado)
        */
        $dados = $this->dadosPedido($order_id);

        if (empty($dados)) {
            return false;
        }

        $retorno = $this->post($dados, $url);
        $this->salvarDadosServico($order_id, $retorno, 'code_clearsale');

        return true;
    }

    /**
     *
     * Retorna os dados do Pedido
     *
     * @param $order_id
     *
     * @return array|bool
     */
    public function dadosPedido($order_id)
    {
        if (empty($this->status)) {
            return false;
        }

        $order = $this->order->getOrder($order_id);

        if (empty($order['order_status_id']) || !in_array($order['order_status_id'], $this->conf->code_status) ||
            !in_array($order['payment_code'], $this->conf->code_pagamento)) {
            $this->log->write('dataPedido() - Status do Pedido diferente do configurado ou Pagamento diferente, 
            order_status_id:' . $order['order_status_id'] . ' payment_code: ' . $order['payment_code']);
            return false;
        }

        $products = $this->order->getOrderProducts($order_id);

        $items = [];
        $subTotal = 0;

        foreach ($products as $p) {
            //$barCode = !empty($p['upc']) ? preg_replace("/[^0-9]/", "", $p['upc']) : '';
            //$barCode = !empty($p['ean']) && empty($barCode) ? preg_replace("/[^0-9]/", "", $p['ean']) : '';

            $items[] = [
                'code'          => (string) $p['product_id'],
                'name'          => trim($p['name']),
                //'barCode'         => $barCode,
                'value'         => $this->currency->format($p['price'], $order['currency_code'], '', ''),
                'amount'        => (int) $p['quantity'],
                //'categoryID'      => trim($p['name']),
                //'categoryName'    => trim($p['name']),
                //'isGift'          => trim($p['name']),
                //'sellerName'      => trim($p['name']),
                //'sellerDocument'  => trim($p['name']),
                'isMarketPlace' => "false",
                //'shippingCompany' => trim($order['shipping_method']),
            ];

            $subTotal = $subTotal + $p['total'];
        }

        $cliente = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE customer_id = '" . (int) $order['customer_id'] . "' LIMIT 1")->row;

        //https://api.clearsale.com.br/docs/total-totalGarantido-application#purchaseInformation-object
        $purchaseInformation = [
            'purchaseLogged' => !empty($cliente['password']) ? true : false,
            'email'          => trim($cliente['email']),
            //'login' => '',
        ];

        $zonep = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE zone_id = '" . (int) $order['payment_zone_id'] . "' LIMIT 1");
        $zone = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE zone_id = '" . (int) $order['shipping_zone_id'] . "' LIMIT 1");

        $cpfcnpj = !empty($order['custom_field'][$this->conf->code_extra_cpf]) ? preg_replace("/[^0-9]/", "", $order['custom_field'][$this->conf->code_extra_cpf]) : '';
        if (empty($cpfcnpj)) {
            $cpfcnpj = !empty($order['custom_field'][$this->conf->code_extra_cpf2]) ? preg_replace("/[^0-9]/", "", $order['custom_field'][$this->conf->code_extra_cpf2]) : '';
        }

        if (strlen($cpfcnpj) <= 11) {
            $type = 1;
        } else {
            $type = 2;
        }

        $rg = !empty($order['custom_field'][$this->conf->code_extra_rg]) ? preg_replace("/[^0-9]/", "", $order['custom_field'][$this->conf->code_extra_rg]) : '';
        if (empty($rg)) {
            $rg = !empty($order['custom_field'][$this->conf->code_extra_inscricao]) ? preg_replace("/[^0-9]/", "", $order['custom_field'][$this->conf->code_extra_inscricao]) : '';
        }

        $celular = !empty($order['custom_field'][$this->conf->code_extra_celular]) ? preg_replace("/[^0-9]/", "", $order['custom_field'][$this->conf->code_extra_celular]) : '';

        if (!empty($order['custom_field'][$this->conf->code_extra_nascimento])) {
            $date = DateTime::createFromFormat('d/m/Y', $order['custom_field'][$this->conf->code_extra_nascimento]);
            $data_nascimento = str_replace('+00:00', '', $date->format('c'));
        } else {
            $data_nascimento = '';
        }

        $numerop = !empty($order['payment_custom_field'][$this->conf->code_extra_numero]) ? trim($order['payment_custom_field'][$this->conf->code_extra_numero]) : '';
        $complementop = !empty($order['payment_custom_field'][$this->conf->code_extra_complemento]) ? trim($order['payment_custom_field'][$this->conf->code_extra_complemento]) : '';

        $numero = !empty($order['shipping_custom_field'][$this->conf->code_extra_numero]) ? trim($order['shipping_custom_field'][$this->conf->code_extra_numero]) : '';
        $complemento = !empty($order['shipping_custom_field'][$this->conf->code_extra_complemento]) ? trim($order['shipping_custom_field'][$this->conf->code_extra_complemento]) : '';

        $telephone = preg_replace("/[^0-9]/", "", $order['telephone']);

        if (!empty($telephone)) {
            $dd = substr($telephone, 0, 2);
            $telephone = substr($telephone, 2);

            $phones[] = [
                'type'      => 0,
                'ddi'       => 55,
                'ddd'       => (int) $dd,
                'number'    => (int) $telephone,
                'extension' => '',
            ];
        }

        if (!empty($celular)) {
            $dd = substr($celular, 0, 2);
            $telephone = substr($celular, 2);

            $phones[] = [
                'type'      => 6,
                'ddi'       => 55,
                'ddd'       => (int) $dd,
                'number'    => (int) $telephone,
                'extension' => '',
            ];
        }

        if (empty($phones)) {
            $this->log->write('dataPedido() - Sem telefone definido, obrigatório');
            return false;
        }

        if (empty($cpfcnpj)) {
            $this->log->write('dataPedido() - Sem CPF ou CNPJ definido, obrigatório');
            return false;
        }

        $billing = [
            'clientID'          => trim($order['customer_id']),
            'type'              => $type,
            'primaryDocument'   => $cpfcnpj,
            'secondaryDocument' => $rg,
            'name'              => trim($order['payment_firstname']) . ' ' . trim($order['payment_lastname']),
            'email'             => trim($order['email']),
            //'gender'          => trim($cliente['customer_id']),
            'address'           => [
                'street'                => trim($order['payment_address_1']),
                'number'                => $numerop,
                'additionalInformation' => $complementop,
                'county'                => trim($order['payment_address_2']),
                'city'                  => trim($order['payment_city']),
                'state'                 => trim($zonep->row['code']),
                'country'               => trim($order['payment_country']),
                'zipcode'               => preg_replace("/[^0-9]/", "", $order['payment_postcode']),
                //'reference'           => '',
            ],
            'phones'            => $phones,
        ];

        if (!empty($data_nascimento)) {
            $billing['birthDate'] = $data_nascimento;
        }

        //Verificando o tipo de entrega
        if ((strpos(strtolower($order['shipping_method']), 'correios') !== false) ||
            (strpos(strtolower($order['shipping_method']), 'pac') !== false) ||
            (strpos(strtolower($order['shipping_method']), 'sedex') !== false) ||
            (strpos(strtolower($order['shipping_method']), 'expresso') !== false)
        ) {
            $deliverType = '11';
        } else if (!empty($this->conf->code_entrega_retirar) &&
            strpos(strtolower($order['shipping_code']), $this->conf->code_entrega_retirar) !== false &&
            !empty($this->conf->code_entrega_retirada_endereco) &&
            !empty($this->conf->code_entrega_retirada_numero) &&
            !empty($this->conf->code_entrega_retirada_bairro) &&
            !empty($this->conf->code_entrega_retirada_cidade) &&
            !empty($this->conf->code_entrega_retirada_estado) &&
            !empty($this->conf->code_entrega_retirada_cep)
        ) {
            $deliverType = '16';
            //Para retirar na loja, precisa implementar o endereço de entrega como o da Loja, implementar em futuras versões se preciso
            //$deliverType = '0';
        } else {
            $deliverType = '0';
        }

        $entrega = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int) $order['order_id'] . "' AND  code = 'shipping' LIMIT 1")->row;
        $entrega_valor = $this->currency->format($entrega['value'], $order['currency_code'], '', '');

        if ($deliverType != 16) {
            $address_shipping = [
                'street'                => trim($order['shipping_address_1']),
                'number'                => $numero,
                'additionalInformation' => $complemento,
                'county'                => trim($order['shipping_address_2']),
                'city'                  => trim($order['shipping_city']),
                'state'                 => trim($zone->row['code']),
                'country'               => trim($order['shipping_country']),
                'zipcode'               => preg_replace("/[^0-9]/", "", $order['shipping_postcode']),
                //'reference'           => '',
            ];
        } else {
            $address_shipping = [
                'street'                => trim($this->conf->code_entrega_retirada_endereco),
                'number'                => trim($this->conf->code_entrega_retirada_numero),
                'additionalInformation' => !empty($this->conf->code_entrega_retirada_complemento) ? trim($this->conf->code_entrega_retirada_complemento) : '',
                'county'                => trim($this->conf->code_entrega_retirada_bairro),
                'city'                  => trim($this->conf->code_entrega_retirada_cidade),
                'state'                 => trim($this->conf->code_entrega_retirada_estado),
                'country'               => trim($order['shipping_country']),
                'zipcode'               => preg_replace("/[^0-9]/", "", trim($this->conf->code_entrega_retirada_cep)),
                //'reference'           => '',
            ];
        }

        $shipping = [
            'clientID'          => trim($order['customer_id']),
            'type'              => $type,
            'primaryDocument'   => $cpfcnpj,
            'secondaryDocument' => $rg,
            'name'              => trim($order['shipping_firstname']) . ' ' . trim($order['shipping_lastname']),
            'email'             => trim($order['email']),
            //'gender'              => trim($cliente['customer_id']),
            'address'           => $address_shipping,
            'phones'            => $phones,
            'deliveryType'      => $deliverType,
            //'deliveryTime'      => trim($order['shipping_method']),
            'price'             => $entrega_valor,
            //'pickUpStoreDocument' => '',
        ];

        //CPF para Retirada
        if ($deliverType == 16) {
            $shipping['pickUpStoreDocument'] = $cpfcnpj;
        }

        if (!empty($data_nascimento)) {
            $shipping['birthDate'] = $data_nascimento;
        }

        if ($this->type == 1) {
            $consultaPagamento = $this->consultarDadosServico($order['order_id'], $order['payment_code']);

            if (!empty($consultaPagamento['data'])) {
                $this->log->write('dataPedido() - Sem os dados do Pagamento');
                return false;
            }
        }

        //Para Teste e Homologação
        if ($this->type == 2) {
            $installments = !empty($this->request->get['installments']) ? (int) $this->request->get['installments'] : 5;
            $number = !empty($this->request->get['number']) ? trim($this->request->get['number']) : '4111 1111 4555 1142';
            $ownerName = !empty($this->request->get['ownerName']) ? trim($this->request->get['ownerName']) : 'Codemarket Teste';
            $document = !empty($this->request->get['document']) ? trim($this->request->get['document']) : '274.391.980-93';

            $consultaPagamento['data'] = [
                'order_id'     => $order['order_id'],
                'type'         => 1,
                'installments' => $installments,
                'value'        => $this->currency->format($order['total'], $order['currency_code'], '', ''),
                'card'         => [
                    'number'    => $number,
                    'bin'       => '',
                    'end'       => '',
                    'type'      => 1,
                    'ownerName' => $ownerName,
                    'document'  => $document,
                ],
            ];
            $consultaPagamento['data'] = json_encode($consultaPagamento['data']);
            $consultaPagamento = json_decode($consultaPagamento['data'], true);

            if (empty($consultaPagamento['order_id'])) {
                $this->log->write('dataPedido() - Sem os dados do Pagamento 2');
                return false;
            }
        }
        //Fim do teste

        //print_r($consultaPagamento); exit();
        $bin = !empty($consultaPagamento['card']['bin']) ? preg_replace("/[^0-9]/", "", $consultaPagamento['card']['bin']) : '';
        $bin = !empty($consultaPagamento['card']['number']) ? substr(preg_replace("/[^0-9]/", "", $consultaPagamento['card']['number']), 0, 6) : $bin;

        $end = !empty($consultaPagamento['card']['end']) ? preg_replace("/[^0-9]/", "", $consultaPagamento['card']['end']) : '';
        $end = !empty($consultaPagamento['card']['number']) ? substr(preg_replace("/[^0-9]/", "", $consultaPagamento['card']['number']), -4) : $end;

        $ownerName = !empty($consultaPagamento['card']['ownerName']) ? $consultaPagamento['card']['ownerName'] : trim($order['payment_firstname']) . ' ' . trim($order['payment_lastname']);
        $document = !empty($consultaPagamento['card']['document']) ? preg_replace("/[^0-9]/", "", $consultaPagamento['card']['document']) : $cpfcnpj;

        if (strlen($document) != 11 && strlen($document) != 14) {
            $document = '';
        }

        $card = [
            //'number'       => '',
            //'hash'         => '',
            'bin'       => $bin,
            'end'       => $end,
            //'type'      => !empty($consultaPagamento['card']['type']) ? (int) $consultaPagamento['card']['type'] : '',
            //'validityDate' => '',
            'ownerName' => $ownerName,
            'document'  => $document,
            //'nsu'          => '',
        ];

        $totalPayment = !empty($consultaPagamento['value']) ? $this->currency->format($consultaPagamento['value'], $order['currency_code'], '', '') : $this->currency->format($order['total'], $order['currency_code'], '', '');
        $this->log->write('dataPedido() - Valor Total Pagamento: ' . $totalPayment . ', Pedido: ' . $order['order_id']);

        $payments[] = [
            //'sequential'   => '',
            'date'         => str_replace('+00:00', '', date('c', strtotime($order['date_modified']))),
            'value'        => $totalPayment,
            'type'         => !empty($consultaPagamento['type']) ? (int) $consultaPagamento['type'] : 1,
            'installments' => !empty($consultaPagamento['installments']) ? (int) $consultaPagamento['installments'] : 1,
            //'interestRate'  => '',
            //'interestValue' => '',
            'currency'     => 986,
            //'voucherOrderOrigin' => '',
            'address'      => [
                'street'                => trim($order['payment_address_1']),
                'number'                => $numerop,
                'additionalInformation' => $complementop,
                'county'                => trim($order['payment_address_2']),
                'city'                  => trim($order['payment_city']),
                'state'                 => trim($zonep->row['code']),
                'country'               => trim($order['payment_country']),
                'zipcode'               => preg_replace("/[^0-9]/", "", $order['payment_postcode']),
                //'reference'           => '',
            ],
            'card'         => $card,
        ];

        $data = [
            'code'                => (string) $order['order_id'],
            'sessionID'           => (string) $this->session->getId(),
            'date'                => str_replace('+00:00', '', date('c', strtotime($order['date_added']))),
            'email'               => trim($order['email']),
            'b2bB2c'              => 'B2C',
            'itemValue'           => $this->currency->format($subTotal, $order['currency_code'], '', ''),
            'totalValue'          => $totalPayment,
            //'numberOfInstallments' => (int) '',
            'ip'                  => $order['ip'],
            'isGift'              => false,
            //'giftMessage'         => '',
            'observation'         => trim($order['comment']),
            'status'              => 0,
            //'origin'         => '',
            //'channelID'         => '',
            //'reservationDate'         => '',
            'country'             => trim($order['payment_country']),
            //'nationality' => trim($order['comment']),
            //'product' => trim($order['comment']),
            //'customSla' => trim($order['comment']),
            //'bankAuthentication' => trim($order['comment']),
            //'subAcquirer' => trim($order['comment']),
            //'list' => trim($order['comment']),
            'purchaseInformation' => $purchaseInformation,
            //'socialNetwork'       => trim($order['comment']),
            'billing'             => $billing,
            'shipping'            => $shipping,
            'payments'            => $payments,
            'items'               => $items,
            //'passengers'          => $passengers,
            //'connections'         => $connections,
            //'hotels' => trim($order['comment']),
        ];

        return $data;
    }

    /**
     *
     * Webhook dos Status - Atualiza no Pedido e pode ser feito a Captura do Pagamento
     *
     * @param $order_id
     *
     * @return bool
     */
    public function webhookAPI($order_id)
    {
        if (empty($this->status)) {
            return false;
        }

        /*
        POST {URL_DO_INTEGRADOR}
        Content-Type: application/json
        {
        "code": "{CODIGO_DO_MEU_PEDIDO}",
        "date": "2016-01-01T10:30:00.9931909-02:00",
        "type": "status"
        }
        */

        $data = $this->consultaStatusAPI($order_id);
        //$this->log->write('consultaStatusAPI retorno: ' . print_r($data, true));

        if (empty($data['code']) || empty($data['status'])) {
            $this->log->write("webhookAPI() - Consulta não encontrada " . print_r($data, true));
            return false;
        }

        /*
         *
         Status     Descrição
         APA    (Aprovação Automática) – Pedido foi aprovado automaticamente segundo parâmetros definidos na regra de aprovação automática
         APM    (Aprovação Manual) – Pedido aprovado manualmente por tomada de decisão de um analista
         RPM    (Reprovado Sem Suspeita) – Pedido Reprovado sem Suspeita por falta de contato com o cliente dentro do período acordado e/ou políticas restritivas de CPF (Irregular, SUS ou Cancelados)
         AMA    (Análise manual) – Pedido está em fila para análise
         NVO    (Novo) – Pedido importado e não classificado Score pela analisadora (processo que roda o Score de cada pedido)
         SUS    (Suspensão Manual) – Pedido Suspenso por suspeita de fraude baseado no contato com o “cliente” ou ainda na base ClearSale
         CAN    (Cancelado pelo Cliente) – Cancelado por solicitação do cliente ou duplicidade do pedido
         FRD    (Fraude Confirmada) – Pedido imputado como Fraude Confirmada por contato com a administradora de cartão e/ou contato com titular do cartão ou CPF do cadastro que desconhecem a compra
         RPA    (Reprovação Automática) – Pedido Reprovado Automaticamente por algum tipo de Regra de Negócio que necessite aplicá-la
         RPP    (Reprovação Por Política) – Pedido reprovado automaticamente por política estabelecida pelo cliente ou Clearsale
         APP    (Aprovação Por Política) – Pedido aprovado automaticamente por política estabelecida pelo cliente ou Clearsale
         */

        $this->salvarDadosServico($order_id, $data, 'code_clearsale_webhook');

        //Falta só analisar se os Status vai precisar por no Painel ou se usamos só alguns como Aprovado, Cancelado que vai abranger N Status e muda no histórico do Pedido
        $order = $this->order->getOrder($order_id);

        switch ($data['status']) {
            case "AMA":
            case "NVO":
                $order_status_id = $this->conf->code_status_clearsale_analise;
                break;
            case "APA":
            case "APM":
            case "APP":
                //$order_status_id = $this->conf->code_status_clearsale_aprovado;
                //Pode ficar a chamada da Captura do Pagamento aqui
                switch ($order['payment_code']) {
                    case "codemarket_iugu":
                        //$this->capturaCodeIugu($order_id, 'APROVAR');
                        break;
                    case "cielo_api_credito":
                    case "cielo_api_debito":
                        $this->PaymentCielo($order_id, 'capture');
                        break;
                    case "rede_rest_credito":
                    case "rede_rest_debito":
                        $this->PaymentRede($order_id, 'capture');
                        break;
                    case "getnet_api_credito":
                    case "getnet_api_debito":
                        $this->PaymentGetnet($order_id, 'capture');
                        break;
                }
                break;
            case "RPM":
            case "SUS":
            case "CAN":
            case "RPA":
            case "RPP":
                //$order_status_id = $this->conf->code_status_clearsale_negado;
                //Pode ficar o cancelamento do Pagamento aqui
                switch ($order['payment_code']) {
                    case "codemarket_iugu":
                        //$this->capturaCodeIugu($order_id, 'RECUSAR');
                        break;
                    case "cielo_api_credito":
                    case "cielo_api_debito":
                        $this->PaymentCielo($order_id, 'cancel');
                        break;
                    case "rede_rest_credito":
                    case "rede_rest_debito":
                        $this->PaymentRede($order_id, 'cancel');
                        break;
                    case "getnet_api_credito":
                    case "getnet_api_debito":
                        $this->PaymentGetnet($order_id, 'cancel');
                        break;
                }

                break;
            case "FRD":
                $order_status_id = $this->conf->code_status_chargeback;
                break;
        }

        if (!empty($order_status_id)) {
            $this->salvarStatusPedido($order_id, $order_status_id);
        }

        return true;
    }

    /**
     *
     * Salva no banco de dados o Status do Pedido
     *
     * @param $order_id
     * @param $order_status_id
     *
     * @return bool
     */
    private function salvarStatusPedido($order_id, $order_status_id)
    {
        if (empty($this->status)) {
            return false;
        }

        $query = $this->db->query("SELECT order_id FROM " . DB_PREFIX . "order_history WHERE order_id = '" . (int) $order_id . "' AND order_status_id = '" . (int) $order_status_id . "'");
        if (!empty($query->row['order_id'])) {
            $this->log->write("salvarStatusPedido - Pedido: " . $order_id . ", já notificado");
            return true;
        }

        if (!empty($this->conf->code_alertar_status) and $this->conf->code_alertar_status == 1) {
            $status_alertar = true;
        }

        $comment = '';

        $this->order->addOrderHistory(
            (int) $order_id,
            (int) $order_status_id,
            $comment,
            (int) $status_alertar
        );

        $this->log->write("salvarStatusPedido - Pedido: " . $order_id . ", notificado com sucesso");
        return true;
    }

    /**
     *
     * Salva os dados de Pagamento, ClearSale e atualiza se já tiver os dados
     *
     * @param $order_id
     * @param $data
     * @param $service
     *
     * @return bool
     */
    public function salvarDadosServico($order_id, $data, $service)
    {
        if (empty($this->status)) {
            return false;
        }

        $consulta = $this->db->query("
            SELECT code_payments_id FROM " . DB_PREFIX . "code_payments 
            WHERE 
            order_id = '" . (int) $order_id . "' AND
            service = '" . $this->db->escape($service) . "'
            LIMIT 1
        ")->row;

        if (!empty($consulta['code_payments_id'])) {
            $this->db->query("
                UPDATE " . DB_PREFIX . "code_payments 
                SET 
                data = '" . json_encode($data) . "', 
                date_created = NOW()
                WHERE 
                code_payments_id = '" . (int) $consulta['code_payments_id'] . "'
            ");
        } else {
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "code_payments 
                SET 
                order_id = '" . (int) $order_id . "', 
                data = '" . json_encode($data) . "', 
                service = '" . $this->db->escape($service) . "',
                date_created = NOW()
            ");
        }

        $this->log->write("salvarDadosServico() - Salvo os dados do Pagamento, serviço: " . $service . " Pedido: " . $order_id);
        return true;
    }

    /**
     *
     * Consulta os dados do Pagamento ou o Status dos serviços como ClearSale
     *
     * @param $order_id
     * @param $service
     *
     * @return false|array
     */
    public function consultarDadosServico($order_id, $service)
    {
        if (empty($this->status)) {
            return false;
        }

        $consulta = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "code_payments 
            WHERE 
            order_id = '" . (int) $order_id . "' AND
            service =   '" . $this->db->escape($service) . "'
            LIMIT 1
        ")->row;

        $this->log->write("consultarDadosServico() - Consultando o Pedido: " . $order_id . ", Serviço: " . $service);

        if (empty($consulta['data'])) {
            $this->log->write("consultarDadosServico() - Sem dados retornados");
            return false;
        }

        return json_decode($consulta['data'], true);
    }

    /**
     *
     * Consulta o Status de um Pedido na ClearSale
     *
     * @param $order_id
     *
     * @return bool|mixed
     */
    public function consultaStatusAPI($order_id)
    {
        if (empty($this->status)) {
            return false;
        }

        $url = $this->urlApi . 'orders/' . $order_id . '/status';
        /*
            GET https://api.clearsale.com.br/v1/orders/{CODIGO_DO_MEU_PEDIDO}/status
            Accept: application/json
            Authorization: Bearer {TOKEN}
        */

        return $this->get($url);
    }

    /**
     *
     * Atualiza o Status de um Pedido na ClearSale
     *
     * @param $order_id
     * @param $status
     *
     * @return bool|mixed
     */
    public function atualizaStatusAPI($order_id, $status)
    {
        if (empty($this->status)) {
            return false;
        }

        $url = $this->urlApi . 'orders/' . $order_id . '/status';
        /*
        PUT https://api.clearsale.com.br/v1/orders/{CODIGO_DO_MEU_PEDIDO}/status
        Content-Type: application/json
        Authorization: Bearer {TOKEN}
        {
            "status ": "Sigla do status"
        }
        */

        $data = [
            'status' => $status,
        ];

        return $this->put($data, $url);
    }

    /**
     * Atualiza o Status de um Pedido na ClearSale para Pedido Aprovado, Pedido Reprovado ou Chargeback
     * Apenas para Pedidos Aprovados na ClearSale
     *
     * @param $order_id
     * @param $order_status_id
     * @param string $msg
     *
     * @return bool
     */
    public function atualizasStatus($order_id, $order_status_id, $msg = 'O Pagamento teve um Chargeback')
    {
        if (empty($this->status)) {
            return false;
        }

        $this->log->write('atualizasStatus - Dentro, order_id: ' . $order_id . ' order_status_id: ' . $order_status_id);
        $status = $this->consultarDadosServico($order_id, 'code_clearsale_webhook');

        if (empty($status['status'])) {
            $this->log->write('atualizasStatus - Não foi encontrado o Status do Pedido na ClearSale');
            return false;
        }

        $status = $status['status'];

        /*
            Apenas para Status Aprovado
            Status  Descrição
            APA (Aprovação Automática) – Pedido foi aprovado automaticamente segundo parâmetros definidos na regra de aprovação automática
            APM (Aprovação Manual) – Pedido aprovado manualmente por tomada de decisão de um analista
            APP (Aprovação Por Política) – Pedido aprovado automaticamente por política estabelecida pelo cliente ou Clearsale
        */

        // Em  caso de Chargeback
        if ($this->conf->code_status_chargeback == $order_status_id &&
            ($status == 'APA' || $status == 'APM' || $status == 'APP')
        ) {
            $msg = 'O Pagamento teve um Chargeback';
            $this->marcacaoChargebackAPI($order_id, $msg);
            $this->log->write('atualizasStatus - Avisado sobre Chargeback: ' . $order_id . ' order_status_id: ' . $order_status_id);
        }

        /*
         * Só pode mudar Status de Pagamento e apenas para Status Aprovado na ClearSale APA, APM ou APP
            Status de pagamento
            Código  Descrição
            PGA Pedido Aprovado
            PGR Pedido Reprovado
         */

        //Pedido Aprovado
        if ($this->conf->code_status_aprovado == $order_status_id &&
            ($status == 'APA' || $status == 'APM' || $status == 'APP')
        ) {
            $this->atualizaStatusAPI($order_id, 'PGA');
            $this->log->write('atualizasStatus - Avisado sobre Pedido Aprovado: ' . $order_id . ' order_status_id: ' . $order_status_id);
        }

        //Pedido Reprovado
        if ($this->conf->code_status_negado == $order_status_id &&
            ($status == 'APA' || $status == 'APM' || $status == 'APP')
        ) {
            $this->atualizaStatusAPI($order_id, 'PGR');
            $this->log->write('atualizasStatus - Avisado sobre Pedido Reprovado: ' . $order_id . ' order_status_id: ' . $order_status_id);
        }

        return true;
    }

    /**
     * Marca que um Pedido teve Chargeback na ClearSale
     *
     * @param $order_id
     * @param $mensagem
     *
     * @return bool|mixed
     */
    public function marcacaoChargebackAPI($order_id, $msg)
    {
        if (empty($this->status)) {
            return false;
        }

        //https://api.clearsale.com.br/docs/how-to-start
        $url = $this->urlApi . 'chargeback';
        /*
            POST https://api.clearsale.com.br/v1/chargeback
            Content-Type: application/json
            Authorization: Bearer {TOKEN}
            {
                "message" : "Mensagem de Exemplo",
                "orders" : ["{CODIGO_DO_MEU_PEDIDO}"]
            }
        */

        $data = [
            'message' => $msg,
            'orders'  => [$order_id],
        ];

        return $this->post($data, $url);
    }

    /**
     * AUXILIARES POST E GET
     */

    /**
     * @param $data array
     * @param $url string
     *
     * @return bool|mixed
     */
    private function post($data, $url)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => 'UTF-8',
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => [
                "Cache-Control: no-cache",
                "Content-Type: application/json",
                "Authorization: Bearer " . $this->token,
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            $this->log->write('post() URL:' . $url . ' - Error Curl ' . print_r($err, true));
            return false;
        } else {
            $this->log->write('post() URL:' . $url . ' - Dados retornados ' . print_r(json_decode($response, true), true));
            return json_decode($response, true);
        }
    }

    /**
     * @param $data array
     * @param $url string
     *
     * @return bool|mixed
     */
    private function put($data, $url)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => 'UTF-8',
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "PUT",
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => [
                "Cache-Control: no-cache",
                "Content-Type: application/json",
                "Authorization: Bearer " . $this->token,
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            $this->log->write('put() URL:' . $url . ' - Error Curl ' . print_r($err, true));
            return false;
        } else {
            $this->log->write('put() URL:' . $url . ' - Dados retornados ' . print_r(json_decode($response, true), true));
            return json_decode($response, true);
        }
    }

    /**
     * @param $url string
     *
     * @return bool|mixed
     */
    private function get($url)
    {
        //return ['id' => 1, 'teste' => true, 'situacao' => 'OK'];
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => 'UTF-8',
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "GET",
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => [
                "Cache-Control: no-cache",
                "Accept: application/json",
                "Content-Type: application/json",
                "Authorization: Bearer " . $this->token,
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            $this->log->write('get() URL:' . $url . ' - Error Curl ' . print_r($err, true));
            return false;
        } else {
            $this->log->write('get() URL:' . $url . ' - Dados retornados ' . print_r(json_decode($response, true), true));
            return json_decode($response, true);
        }
    }

    /*
     * PAGAMENTOS - CAPTURA OU CANCELAMENTO
     *
     */

    /*
    public function capturaCodeIugu($order, $status)
    {
        if (empty($order['payment_code'])) {
            return false;
        }

        require_once DIR_SYSTEM . 'library/iugu/Iugu.php';
        $conf = $this->model_module_codemarket_module->getModulo('259');
        if (empty($conf)) {
            return false;
        }

        //Token de Segurança
        $token = $conf->iugu_token;
        if (!empty($conf->iugu_teste) && $conf->iugu_teste == 1 && !empty($conf->iugu_tokenteste)) {
            $token = $conf->iugu_tokenteste;
        }

        Iugu::setApiKey($token);
        $invoice = Iugu_Invoice::fetch($order['iugu_order_id']);

        if (empty($invoice) || $invoice->status != 'in_analysis') {
            echo "Pedido com Status diferente de em análise<br>";
            return false;
        }

        if (strtoupper($status) == 'APROVAR') {
            $invoice->capture();
        }

        if (strtoupper($status) == 'RECUSAR') {
            $invoice->cancel();
        }
        //$invoice->refund()
        return true;
    }
    */

    /**
     *
     * Captura e Cancelamento da melhoria Pagamento GetNet do Opencart Brasil
     *
     * @param $order_id
     * @param string $type
     *
     * @return bool
     */
    public function PaymentGetnet($order_id, $type = 'capture')
    {
        $this->log->write('PaymentGetnet() - Iniciado Pedido ' . $order_id);

        $query = $this->db->query("
            SELECT o.store_id, og.*
            FROM `" . DB_PREFIX . "order_getnet_api` og
            INNER JOIN `" . DB_PREFIX . "order` o ON (og.order_id = o.order_id)
            WHERE og.order_id = '" . (int) $order_id . "'
        ");

        if ($query->num_rows) {
            $transaction_info = $query->row;
        } else {
            $this->log->write('PaymentGetnet() - sem dados na tabela order_getnet_api para o Pedido ' . $order_id);
            return false;
        }

        $order_getnet_api_id = $transaction_info['order_getnet_api_id'];

        $payment_type = $transaction_info['payment_type'];

        $chave = $this->config->get('payment_getnet_api_' . $payment_type . '_chave');
        $dados['chave'] = $chave[$transaction_info['store_id']];
        $dados['debug'] = $this->config->get('payment_getnet_api_' . $payment_type . '_debug');
        $dados['ambiente'] = $this->config->get('payment_getnet_api_' . $payment_type . '_ambiente');
        $dados['seller_id'] = $this->config->get('payment_getnet_api_' . $payment_type . '_seller_id');
        $dados['client_id'] = $this->config->get('payment_getnet_api_' . $payment_type . '_client_id');
        $dados['client_secret'] = $this->config->get('payment_getnet_api_' . $payment_type . '_client_secret');
        $dados['payment_id'] = $transaction_info['payment_id'];
        $dados['amount'] = $transaction_info['authorized_amount'];

        require_once(DIR_SYSTEM . 'library/getnet_api/getnet.php');
        $getnet = new Getnet();
        $getnet->setParametros($dados);

        if ($type == 'capture') {
            $resposta = $getnet->setTransacaoConfirmar();

            if (!empty($resposta->status)) {
                switch ($resposta->status) {
                    case 'CONFIRMED':
                        $dados['order_getnet_api_id'] = $order_getnet_api_id;
                        $dados['status'] = $resposta->status;
                        $dados['confirmed_at'] = $resposta->credit_confirm->confirm_date;
                        $dados['confirmed_amount'] = $resposta->amount;
                        $dados['json'] = json_encode($resposta);

                        $this->db->query("
                            UPDATE `" . DB_PREFIX . "order_getnet_api`
                            SET status = '" . $this->db->escape($dados['status']) . "',
                            confirmed_at = '" . $this->db->escape($dados['confirmed_at']) . "',
                            confirmed_amount = '" . $this->db->escape($dados['confirmed_amount']) . "',
                            json = '" . $dados['json'] . "'
                            WHERE order_getnet_api_id = '" . (int) $dados['order_getnet_api_id'] . "'
                        ");

                        $this->salvarStatusPedido($order_id, $this->conf->code_status_clearsale_aprovado);
                        $this->log->write('PaymentGetnet() - Pedido ' . $order_id . ' Capturado com Sucesso');
                        break;
                    default:
                        $this->log->write('PaymentGetnet() - Pedido ' . $order_id . ' falhou a Captura' . print_r($resposta, true));
                        return false;
                        break;
                }
            } else {
                $this->log->write('PaymentGetnet() - Pedido ' . $order_id . ' falhou a Captura' . print_r($resposta, true));
                return false;
            }
        } else if ($type == 'cancel') {
            $resposta = $getnet->setTransacaoCancelar();

            if (!empty($resposta->status)) {
                switch ($resposta->status) {
                    case 'CANCELED':
                        $dados['order_getnet_api_id'] = $order_getnet_api_id;
                        $dados['canceled_at'] = $resposta->credit_cancel->canceled_at;
                        $dados['canceled_amount'] = $resposta->amount;
                        $dados['status'] = $resposta->status;
                        $dados['json'] = json_encode($resposta);

                        $this->db->query("
                            UPDATE `" . DB_PREFIX . "order_getnet_api`
                            SET status = '" . $this->db->escape($dados['status']) . "',
                            canceled_at = '" . $this->db->escape($dados['canceled_at']) . "',
                            canceled_amount = '" . $this->db->escape($dados['canceled_amount']) . "',
                            json = '" . $dados['json'] . "'
                            WHERE order_getnet_api_id = '" . (int) $dados['order_getnet_api_id'] . "'
                        ");

                        $this->salvarStatusPedido($order_id, $this->conf->code_status_clearsale_negado);
                        $this->log->write('PaymentGetnet() - Pedido ' . $order_id . ' Cancelado com Sucesso');
                        break;
                    default:
                        $this->log->write('PaymentGetnet() - Pedido ' . $order_id . ' falhou o Cancelamento' . print_r($resposta, true));
                        return false;
                        break;
                }
            } else {
                $this->log->write('PaymentGetnet() - Pedido ' . $order_id . ' falhou o Cancelamento' . print_r($resposta, true));
                return false;
            }
        }

        return true;
    }

    /**
     *
     * Captura e Cancelamento da melhoria Pagamento Cielo do Opencart Brasil
     *
     * @param $order_id
     * @param string $type
     *
     * @return bool
     */
    public function PaymentCielo($order_id, $type = 'capture')
    {
        $this->log->write('PaymentCielo() - Iniciado Pedido ' . $order_id);

        $query = $this->db->query("
            SELECT oc.*, o.store_id
            FROM `" . DB_PREFIX . "order_cielo_api` oc
            INNER JOIN `" . DB_PREFIX . "order` o ON (o.order_id = oc.order_id)
            WHERE oc.order_id = '" . (int) $order_id . "';
        ");

        if ($query->num_rows) {
            $transaction_info = $query->row;
        } else {
            $this->log->write('PaymentCielo() - Sem dados na tabela order_cielo_api para o Pedido ' . $order_id);
            return false;
        }

        $order_cielo_api_id = $transaction_info['order_cielo_api_id'];

        if ($transaction_info['tipo'] == 'CreditCard') {
            $tipo = 'credito';
        } else if ($transaction_info['tipo'] == 'DebitCard') {
            $tipo = 'debito';
        } else if ($transaction_info['tipo'] == 'Boleto') {
            $tipo = 'boleto';
        } else if ($transaction_info['tipo'] == 'EletronicTransfer') {
            $tipo = 'transferencia';
        } else {
            $tipo = 'credito';
        }

        $dados['PaymentId'] = $transaction_info['paymentId'];
        $chave = $this->config->get('payment_cielo_api_' . $tipo . '_chave');
        $dados['Chave'] = $chave[$transaction_info['store_id']];
        $dados['Debug'] = $this->config->get('payment_cielo_api_' . $tipo . '_debug');
        $dados['Ambiente'] = $this->config->get('payment_cielo_api_' . $tipo . '_ambiente');
        $dados['MerchantId'] = $this->config->get('payment_cielo_api_' . $tipo . '_merchantid');
        $dados['MerchantKey'] = $this->config->get('payment_cielo_api_' . $tipo . '_merchantkey');

        require_once(DIR_SYSTEM . 'library/cielo_api/cielo.php');
        $cielo = new Cielo();
        $cielo->setParametros($dados);

        if ($type == 'capture') {
            $resposta = $cielo->setCapturar();

            if (!empty($resposta->ReasonMessage)) {
                switch ($resposta->ReasonMessage) {
                    case 'Successful':
                        $dados['order_cielo_api_id'] = $order_cielo_api_id;
                        $dados['status'] = $resposta->Status;
                        $dados['json'] = json_encode($resposta, JSON_HEX_QUOT | JSON_HEX_APOS);

                        $this->db->query("
                            UPDATE `" . DB_PREFIX . "order_cielo_api`
                            SET status = '" . $this->db->escape($dados['status']) . "',
                                json = '" . $dados['json'] . "'
                            WHERE order_cielo_api_id = '" . (int) $dados['order_cielo_api_id'] . "'
                        ");

                        $this->salvarStatusPedido($order_id, $this->conf->code_status_clearsale_aprovado);
                        $this->log->write('PaymentCielo() - Pedido ' . $order_id . ' Capturado com Sucesso');
                        break;
                    default:
                        $this->log->write('PaymentCielo() - Pedido ' . $order_id . ' falhou a Captura' . print_r($resposta, true));
                        return false;
                        break;
                }
            } else {
                $this->log->write('PaymentCielo() - Pedido ' . $order_id . ' falhou a Captura' . print_r($resposta, true));
                return false;
            }
        } else if ($type == 'cancel') {
            $resposta = $cielo->setCancelar();

            if (!empty($resposta->ReasonMessage)) {
                switch ($resposta->ReasonMessage) {
                    case 'Successful':
                        $dados['order_cielo_api_id'] = $order_cielo_api_id;
                        $dados['status'] = $resposta->Status;
                        $dados['json'] = json_encode($resposta, JSON_HEX_QUOT | JSON_HEX_APOS);

                        $this->db->query("
                            UPDATE `" . DB_PREFIX . "order_cielo_api`
                            SET status = '" . $this->db->escape($dados['status']) . "',
                                json = '" . $dados['json'] . "'
                            WHERE order_cielo_api_id = '" . (int) $dados['order_cielo_api_id'] . "'
                        ");

                        $this->salvarStatusPedido($order_id, $this->conf->code_status_clearsale_negado);
                        $this->log->write('PaymentCielo() - Pedido ' . $order_id . ' Cancelado com Sucesso');
                        break;
                    default:
                        $this->log->write('PaymentCielo() - Pedido ' . $order_id . ' falhou o Cancelamento' . print_r($resposta, true));
                        return false;
                        break;
                }
            } else {
                $this->log->write('PaymentCielo() - Pedido ' . $order_id . ' falhou o Cancelamento' . print_r($resposta, true));
                return false;
            }
        }

        return true;
    }

    /**
     *
     * Captura e Cancelamento da melhoria Pagamento Rede do Opencart Brasil
     *
     * @param $order_id
     * @param string $type
     *
     * @return bool
     */
    public function PaymentRede($order_id, $type = 'capture')
    {
        $this->log->write('PaymentRede() - Iniciado - Pedido ' . $order_id);

        $query = $this->db->query("
            SELECT orr.*, o.date_added, o.store_id
            FROM `" . DB_PREFIX . "order_rede_rest` orr
            INNER JOIN `" . DB_PREFIX . "order` o ON (o.order_id = orr.order_id)
            WHERE orr.order_id = '" . (int) $order_id . "';
        ");

        if ($query->num_rows) {
            $transaction_info = $query->row;
        } else {
            return false;
        }

        $order_rede_rest_id = $transaction_info['order_rede_rest_id'];

        // Lidar com as versões
        if (empty($transaction_info['tipo'])) {
            $transaction_info['tipo'] = !empty($transaction_info['type']) ? $transaction_info['type'] : 'credito';
        }

        if ($transaction_info['tipo'] == 'credito') {
            $tipo = 'credito';
        } else if ($transaction_info['tipo'] == 'debito') {
            $tipo = 'debito';
        } else {
            $tipo = 'credito';
        }

        // Lidar com as versões
        if (!empty($this->config->get('module_rede_rest_chave'))) {
            $name_conf = 'module_rede_rest';
            $chave = $this->config->get($name_conf . '_chave');
            $dados['chave'] = $chave[$transaction_info['store_id']];
            $dados['sandbox'] = $this->config->get($name_conf . '_sandbox');
            $dados['debug'] = $this->config->get($name_conf . '_debug');
            $dados['filiacao'] = $this->config->get($name_conf . '_filiacao');
            $dados['token'] = $this->config->get($name_conf . '_token');
            $dados['tid'] = $transaction_info['tid'];
        } else {
            $chave = $this->config->get('payment_rede_rest_' . $tipo . '_chave');
            $dados['chave'] = $chave[$transaction_info['store_id']];
            $dados['sandbox'] = $this->config->get('payment_rede_rest_' . $tipo . '_ambiente');
            $dados['debug'] = $this->config->get('payment_rede_rest_' . $tipo . '_debug');
            $dados['filiacao'] = $this->config->get('payment_rede_rest_' . $tipo . '_filiacao');
            $dados['token'] = $this->config->get('payment_rede_rest_' . $tipo . '_token');
            $dados['tid'] = $transaction_info['tid'];
        }

        // Lidar com as versões
        if (!empty($transaction_info['autorizacaoValor'])) {
            $dados['amount'] = number_format($transaction_info['autorizacaoValor'], 2, '', '');
        } else {
            $dados['amount'] = !empty($transaction_info['authorized_total']) ? number_format($transaction_info['authorized_total'], 2, '', '') : 0;
        }

        // Lidar com as versões
        if (is_file(DIR_SYSTEM . 'library/rede_rest/rede.php')) {
            require_once(DIR_SYSTEM . 'library/rede_rest/rede.php');
        } else {
            require_once(DIR_SYSTEM . 'library/rede-rest/rede.php');
        }

        $rede = new Rede();
        $rede->setParametros($dados);

        if ($type == 'capture') {
            try {
                $resposta = $rede->setCapture();
            } catch (Exception $e) {
                $this->log->write('PaymentRede() - Erro na Captura do Pedido : ' . $order_id . ', retorno ' . print_r($e, true));
            }

            $this->log->write('PaymentRede() - Debug - Pedido ' . $order_id . ' ' . print_r($resposta, true));

            if (isset($resposta->returnCode)) {
                switch ($resposta->returnCode) {
                    case '00': /* Success. */
                        $dados['order_rede_rest_id'] = $order_rede_rest_id;
                        $dados['status'] = 'capturada';
                        $dados['json'] = json_encode($resposta);

                        // Lidar com as versões
                        $query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "order_rede_rest` LIKE 'json'");
                        if ($query->num_rows) {
                            $jsonCollum = 'json';
                        } else {
                            $jsonCollum = 'json_last_response';
                        }

                        $this->db->query("
                            UPDATE " . DB_PREFIX . "order_rede_rest
                            SET status = '" . $this->db->escape($dados['status']) . "',
                            " . $jsonCollum . " = '" . $dados['json'] . "'
                            WHERE order_rede_rest_id = '" . (int) $dados['order_rede_rest_id'] . "';
                        ");

                        $this->salvarStatusPedido($order_id, $this->conf->code_status_clearsale_aprovado);
                        $this->log->write('PaymentRede() - Pedido ' . $order_id . ' Capturado com sucesso');
                        break;
                }
            } else {
                $this->log->write('PaymentRede() - Pedido ' . $order_id . ' falhou a Captura, retorno: ' . print_r($resposta, true));
                return false;
            }
        } else if ($type == 'cancel') {
            try {
                $resposta = $rede->setCancel();
            } catch (Exception $e) {
                $this->log->write('PaymentRede() - Erro no Cancelamento do Pedido : ' . $order_id . ', retorno ' . print_r($e, true));
            }

            if (!empty($resposta->returnCode)) {
                switch ($resposta->returnCode) {
                    case '354': /* expirou o prazo de cancelamento */
                        $json['error'] = 'Expirou o Prazo do Cancelamento';
                        break;
                    case '355': /* Transaction already canceled */
                    case '359': /* Refund successful */
                        $dados['order_rede_rest_id'] = $order_rede_rest_id;
                        $dados['status'] = 'cancelada';
                        $dados['json'] = json_encode($resposta);

                        // Lidar com as versões
                        $query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "order_rede_rest` LIKE 'json'");
                        if ($query->num_rows) {
                            $this->db->query("
                                UPDATE " . DB_PREFIX . "order_rede_rest
                                SET status = '" . $this->db->escape($dados['status']) . "',
                                json = '" . $dados['json'] . "'
                                WHERE order_rede_rest_id = '" . (int) $dados['order_rede_rest_id'] . "';
                            ");
                        } else {
                            $dados['canceled_total'] = $dados['amount'];
                            $this->db->query("
                                UPDATE " . DB_PREFIX . "order_rede_rest
                                SET status = '" . $this->db->escape($dados['status']) . "',
                                canceled_total = '" . $this->db->escape($dados['canceled_total']) . "',
                                json_last_response = '" . $dados['json'] . "'
                                WHERE order_rede_rest_id = '" . (int) $dados['order_rede_rest_id'] . "';
                            ");
                        }

                        $this->salvarStatusPedido($order_id, $this->conf->code_status_clearsale_negado);
                        $this->log->write('PaymentRede() - Pedido ' . $order_id . ' Cancelado com Sucesso');
                        break;
                    case '360': /* Refund request has been successful */
                        $dados['order_rede_rest_id'] = $order_rede_rest_id;
                        $dados['status'] = 'processando';
                        $dados['json'] = json_encode($resposta);

                        // Lidar com as versões
                        $query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "order_rede_rest` LIKE 'json'");
                        if ($query->num_rows) {
                            $this->db->query("
                                UPDATE " . DB_PREFIX . "order_rede_rest
                                SET status = '" . $this->db->escape($dados['status']) . "',
                                json = '" . $dados['json'] . "'
                                WHERE order_rede_rest_id = '" . (int) $dados['order_rede_rest_id'] . "';
                            ");
                        } else {
                            $dados['canceled_total'] = $dados['amount'];
                            $this->db->query("
                                UPDATE " . DB_PREFIX . "order_rede_rest
                                SET status = '" . $this->db->escape($dados['status']) . "',
                                canceled_total = '" . $this->db->escape($dados['canceled_total']) . "',
                                json_last_response = '" . $dados['json'] . "'
                                WHERE order_rede_rest_id = '" . (int) $dados['order_rede_rest_id'] . "';
                            ");
                        }

                        $this->salvarStatusPedido($order_id, $this->conf->code_status_clearsale_negado);
                        $this->log->write('PaymentRede() - Pedido ' . $order_id . ' Cancelado com Sucesso');
                        break;
                }
            } else {
                $this->log->write('PaymentRede() - Pedido ' . $order_id . ' falhou o Cancelamento, retorno: ' . print_r($resposta, true));
                return false;
            }
        }

        return true;
    }
}
