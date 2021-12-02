<?php

/**
 * © Copyright 2013-2021 Codemarket - Todos os direitos reservados.
 * Class ControllerModuleCodeClearsale
 */
class ControllerModuleCodeClearsale extends Controller
{
    private $conf;
    private $clearsale;

    /**
     * ModelModuleCodeClearsale constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('checkout/order');
        $this->load->model('module/code_clearsale');
        $this->clearsale = $this->model_module_code_clearsale;

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
            $this->log->write('__construct() - Integração ClearSale Total desativada, verifique a configuração, config:' . print_r($conf, true));
            exit('Integração ClearSale Total desativada, verifique a configuração!');
        }

        $this->conf = $conf;

        if (empty($this->conf->code_token_seguranca) || empty($this->request->get['token']) ||
            $this->conf->code_token_seguranca != $this->request->get['token'] || empty($this->request->get['order_id'])) {
            if (empty($this->request->get['route']) || $this->request->get['route'] != 'module/code_clearsale/webhook') {
                exit("<h1>Acesso não autorizado!</h1>");
            }
        }

        return true;
    }

    //index.php?route=module/code_clearsale/index&token=
    public function index()
    {
        echo "<h1>Dentro do Codemarket - ClearSale Total</h1>";
    }

    //index.php?route=module/code_clearsale/webhook
    public function webhook()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $this->log->write('webhook() - Iniciado');
        $this->log->write('webhook() - Dados do Post: ' . print_r($data, true));

        echo "<h1>Webhook</h1>";

        if (empty($data['code']) || empty($data['date']) || empty($data['type']) || $data['type'] != 'status') {
            $this->log->write('webhook() - Dados faltando: ' . print_r($data, true));
            echo "<h1>Webhook dados faltando!</h1>";
            return false;
        }

        $this->clearsale->webhookAPI($data['code']);
        return true;
    }

    //index.php?route=module/code_clearsale/dadosPedido&token=&order_id=75,80,100
    public function dadosPedido()
    {
        if (empty($this->request->get['order_id'])) {
            exit("<h1>Sem um Pedido!</h1>");
        }

        echo "<h1>Dados Pedidos " . $this->request->get['order_id'] . "</h1>";

        $ids = explode(",", $this->request->get['order_id']);

        foreach ($ids as $id) {
            echo "<h2>Dados Pedido " . $id . "</h2>";
            $dados = $this->clearsale->dadosPedido($id);
            echo "<pre>";
            print_r($dados);
            echo "</pre>";
        }
    }

    //index.php?route=module/code_clearsale/incluirPedido&token=&order_id=75,80,100&type=2
    public function incluirPedido()
    {
        if (empty($this->request->get['order_id'])) {
            exit("<h1>Sem um Pedido!</h1>");
        }

        echo "<h1>Incluindo Pedidos " . $this->request->get['order_id'] . "</h1>";
        $type = !empty($this->request->get['type']) && $this->request->get['type'] == 2 ? 2 : 1;

        $ids = explode(",", $this->request->get['order_id']);

        foreach ($ids as $id) {
            $this->clearsale->incluirPedidoAPI($id, $type);
        }
        echo "<h1>Pedido incluido com sucesso</h1>";
    }

    //index.php?route=module/code_clearsale/salvarDadosServico&token=
    public function salvarDadosServico()
    {
        $data = [
            'packageID' => '1db7e96e-42f6-4ec9-8765-9cd8643bb7a1',
            'orders'    => [
                'code'   => 64,
                'status' => 'AMAP',
                'score'  => '',
            ],
        ];

        echo "<h1>Salvando dados do Serviço</h1>";
        $this->clearsale->salvarDadosServico(64, $data, 'code_clearsale');
    }

    //index.php?route=module/code_clearsale/consultarStatus&token=&order_id=75
    public function consultarStatus()
    {
        if (empty($this->request->get['order_id'])) {
            exit("<h1>Sem um Pedido!</h1>");
        }

        echo "<h1>Consultando Status do Pedido " . $this->request->get['order_id'] . "</h1>";
        $dados = $this->clearsale->consultaStatusAPI($this->request->get['order_id']);
        echo "<pre>";
        print_r($dados);
        echo "</pre>";
    }

    //index.php?route=module/code_clearsale/consultarDadosServico&token=&order_id=75&service=code_clearsale_webhook
    public function consultarDadosServico()
    {
        if (empty($this->request->get['order_id'])) {
            exit("<h1>Sem um Pedido!</h1>");
        }

        echo "<h1>Consultando dados do Serviço Pedido " . $this->request->get['order_id'] . "</h1>";
        $dados = $this->clearsale->consultarDadosServico($this->request->get['order_id']);
        echo "<pre>";
        print_r($dados);
        echo "</pre>";
    }

    //index.php?route=module/code_clearsale/atualizaStatus&token=&order_id=75&status=APM
    public function atualizaStatus()
    {
        if (empty($this->request->get['order_id']) || empty($this->request->get['status'])) {
            exit("<h1>Sem um Pedido!</h1>");
        }

        echo "<h1>Atualiza Status Pedido " . $this->request->get['order_id'] . "</h1>";
        $dados = $this->clearsale->atualizaStatusAPI($this->request->get['order_id'], $this->request->get['status']);
        echo "<pre>";
        print_r($dados);
        echo "</pre>";
    }

    //index.php?route=module/code_clearsale/marcacaoChargeback&token=&order_id=75
    public function marcacaoChargeback()
    {
        if (empty($this->request->get['order_id'])) {
            exit("<h1>Sem um Pedido!</h1>");
        }

        echo "<h1>Marcando Chargeback Pedido " . $this->request->get['order_id'] . "</h1>";
        $dados = $this->clearsale->marcacaoChargebackAPI($this->request->get['order_id'], 'O Pagamento teve um Chargeback');
        echo "<pre>";
        print_r($dados);
        echo "</pre>";
    }

    //index.php?route=module/code_clearsale/capture&token=&order_id=75&payment=rede_rest_credito
    public function capture()
    {
        if (empty($this->request->get['order_id']) || empty($this->request->get['payment'])) {
            exit("<h1>Sem um Pedido ou Pagamento!</h1>");
        }

        $order_id = $this->request->get['order_id'];
        $this->log->write('capture() - Capturando Pedido ' . $order_id);
        echo "<h1>Capturando Pedido " . $order_id . "</h1>";

        switch ($this->request->get['payment']) {
            case "codemarket_iugu":
                //$this->clearsale->capturaCodeIugu($order_id, 'APROVAR');
                break;
            case "cielo_api_credito":
            case "cielo_api_debito":
                $this->clearsale->PaymentCielo($order_id, 'capture');
                break;
            case "rede_rest_credito":
            case "rede_rest_debito":
                $this->clearsale->PaymentRede($order_id, 'capture');
                break;
            case "getnet_api_credito":
            case "getnet_api_debito":
                $this->clearsale->PaymentGetnet($order_id, 'capture');
                break;
        }

        $this->log->write('capture() - Capturado Pedido ' . $order_id);
        echo "<h1>Capturado Pedido " . $order_id . "</h1>";
    }

    //index.php?route=module/code_clearsale/cancel&token=&order_id=75&payment=rede_rest_credito
    public function cancel()
    {
        if (empty($this->request->get['order_id']) || empty($this->request->get['payment'])) {
            exit("<h1>Sem um Pedido ou Pagamento!</h1>");
        }

        $order_id = $this->request->get['order_id'];
        $this->log->write('cancel() - Cancelando Pedido ' . $order_id);
        echo "<h1>Cancelando Pedido " . $order_id . "</h1>";

        switch ($this->request->get['payment']) {
            case "codemarket_iugu":
                //$this->clearsale->capturaCodeIugu($order_id, 'APROVAR');
                break;
            case "cielo_api_credito":
            case "cielo_api_debito":
                $this->clearsale->PaymentCielo($order_id, 'cancel');
                break;
            case "rede_rest_credito":
            case "rede_rest_debito":
                $this->clearsale->PaymentRede($order_id, 'cancel');
                break;
            case "getnet_api_credito":
            case "getnet_api_debito":
                $this->clearsale->PaymentGetnet($order_id, 'cancel');
                break;
        }

        $this->log->write('cancel() - Cancelado Pedido ' . $order_id);
        echo "<h1>Cancelado Pedido " . $order_id . "</h1>";
    }
}
