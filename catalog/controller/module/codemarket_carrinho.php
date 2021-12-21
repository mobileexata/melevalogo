<?php

/**
 *
 * © Copyright 2013-2021 Codemarket - Todos os direitos reservados.
 * Class ControllerModuleCodemarketCarrinho
 */
class ControllerModuleCodemarketCarrinho extends Model
{
    public function teste()
    {
        $this->load->model('module/codemarket_carrinho');
        $this->model_module_codemarket_carrinho->index(1);
    }

    public function teste_enviar()
    {
        $this->load->model('module/codemarket_carrinho');
        $this->model_module_codemarket_carrinho->index(2);
    }

    public function carrinhos()
    {
        $this->load->model('module/codemarket_carrinho');
        $carts = $this->model_module_codemarket_carrinho->carrinhos();

        echo "<h2>Carrinhos Primeiro Envio</h2>";
        print("<pre>" . print_r($carts['carrinhos'], true) . "</pre>");

        echo "<h2>Carrinhos Segundo Envio</h2>";
        print("<pre>" . print_r($carts['carrinhos2'], true) . "</pre>");
    }

    public function rodar()
    {
        $this->load->model('module/codemarket_carrinho');
        $this->model_module_codemarket_carrinho->index(0);
    }
}
