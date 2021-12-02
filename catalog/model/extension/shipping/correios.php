<?php
class ModelExtensionShippingCorreios extends Model {
    const EXTENSION = 'shipping_correios';

    private $customer_group_id;
    private $weight_class_id;
    private $length_class_id;
    private $peso_pedido;
    private $endereco;
    private $cep_destino;
    private $codigo_servico;
    private $desativar_cotacoes;
    private $comprimento_minimo;
    private $largura_minima;
    private $altura_minima;
    private $peso_minimo;
    private $comprimento_maximo;
    private $largura_maxima;
    private $altura_maxima;
    private $soma_maxima;
    private $peso_maximo;
    private $limite_manuseio;
    private $manuseio_especial;

    public function getQuote($address) {
        $this->load->language('extension/shipping/correios');

        $this->endereco = $address;

        if ($this->config->get(self::EXTENSION . '_status')) {
            $status = $this->getValidarDestino();
        } else {
            $status = false;
        }

        $method_data = array();

        if ($status) {
            $quote_data = $this->getCotacoes();

            if ($quote_data) {
                if (strlen(trim($this->config->get(self::EXTENSION . '_imagem'))) > 0) {
                    $title = '<img src="' . HTTPS_SERVER . 'image/' . $this->config->get(self::EXTENSION . '_imagem') . '" alt="' . $this->config->get(self::EXTENSION . '_titulo') . '" />';
                } else {
                    $title = $this->config->get(self::EXTENSION . '_titulo');
                }

                $method_data = array(
                    'code' => 'correios',
                    'title' => $title,
                    'quote' => $quote_data,
                    'sort_order' => $this->config->get(self::EXTENSION . '_sort_order'),
                    'error' => false
                );
            }
        }

        return $method_data;
    }

    public function getQuoteAdaptor($address) {
        $this->load->language('extension/shipping/correios');

        $this->endereco = $address;

        $status = $this->getValidarDestino();

        $quote_data = array();

        if ($status) {
            $quote_data = $this->getCotacoes();
        }

        return $quote_data;
    }

    private function getValidarDestino() {
        $status = false;

        if ($this->getValidarCEP()) {
            if ($this->getValidarRegiao()) {
                $status = true;
            }
        }

        return $status;
    }

    private function getValidarCEP() {
        $this->cep_destino = preg_replace('/[^0-9]/', '', $this->endereco['postcode']);
        if (strlen($this->cep_destino) != 8) { return false; }

        return true;
    }

    private function getValidarRegiao() {
        $status = false;

        $query = $this->db->query("
            SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone`
            WHERE geo_zone_id = '" . (int) $this->config->get(self::EXTENSION . '_geo_zone_id') . "'
            AND country_id = '" . (int) $this->endereco['country_id'] . "'
            AND (zone_id = '" . (int) $this->endereco['zone_id'] . "' OR zone_id = '0')
        ");

        if (!$this->config->get(self::EXTENSION . '_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        }

        return $status;
    }

    private function getRestricoes() {
        $cep_destino = $this->cep_destino;

        $restricoes = $this->config->get(self::EXTENSION . '_restricoes');
        if (is_array($restricoes) && !empty($restricoes)) {
            foreach ($restricoes as $restricao) {
                $cep_inicial = preg_replace('/[^0-9]/', '', $restricao['cep_inicial']);
                $cep_final = preg_replace('/[^0-9]/', '', $restricao['cep_final']);

                if (strlen($cep_inicial) == 8 && strlen($cep_final) == 8) {
                    if ($cep_destino >= $cep_inicial && $cep_destino <= $cep_final && $restricao['codigo'] == $this->codigo_servico) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function getBloqueios() {
        $bloqueios = $this->config->get(self::EXTENSION . '_bloqueios');

        if (is_array($bloqueios) && !empty($bloqueios)) {
            $produtos = $this->cart->getProducts();
            foreach ($produtos as $chave => $produto) {
                if ($produto['shipping']) {
                    $query = $this->db->query("
                        SELECT * FROM " . DB_PREFIX . "product_to_category
                        WHERE product_id = '" . (int) $produto['product_id'] . "'
                    ");

                    $categories = array();
                    foreach ($query->rows as $result) {
                        $categories[] = $result['category_id'];
                    }

                    foreach ($bloqueios as $bloqueio) {
                        if (in_array($bloqueio['category_id'], $categories) && $bloqueio['codigo'] == $this->codigo_servico) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    private function getTotal() {
        $total = 0;

        if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
            $taxes = $this->cart->getTaxes();

            $totals = array();

            $total_data = array(
                'totals' => &$totals,
                'taxes'  => &$taxes,
                'total'  => &$total
            );

            $sort_order = array();

            $this->load->model('setting/extension');
            $results = $this->model_setting_extension->getExtensions('total');

            foreach ($results as $key => $value) {
                $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
            }

            array_multisort($sort_order, SORT_ASC, $results);

            foreach ($results as $result) {
                if ($this->config->get('total_' . $result['code'] . '_status')) {
                    $this->load->model('extension/total/' . $result['code']);
                    $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
                }
            }

            $sort_order = array();

            foreach ($totals as $key => $value) {
                $sort_order[$key] = $value['sort_order'];
            }

            array_multisort($sort_order, SORT_ASC, $totals);

            $shipping_value = 0;
            foreach ($totals as $result) {
                if ($result['code'] == "shipping") {
                    $shipping_value = $result['value'];
                } else {
                    $total = $result['value'] - $shipping_value;
                }
            }
        }

        return $total;
    }

    private function getCotacoes() {
        $quote_data = array();

        $requisitos = $this->getRequisitos();
        if ($requisitos == false) {
            return $quote_data;
        }

        $servicos = $this->config->get(self::EXTENSION . '_servicos');
        if (is_array($servicos) && !empty($servicos)) {
            $cep_destino = $this->cep_destino;

            $chave = $this->config->get(self::EXTENSION . '_chave');
            $chave = $chave[$this->config->get('config_store_id')];
            $codigo_adm = $this->config->get(self::EXTENSION . '_codigo_administrativo');
            $senha_acesso = $this->config->get(self::EXTENSION . '_senha_acesso');
            $cep_origem = $this->config->get(self::EXTENSION . '_cep_origem');
            $formato = $this->config->get(self::EXTENSION . '_formato');

            $debug = $this->config->get(self::EXTENSION . '_debug');
            require_once(DIR_SYSTEM . 'library/correios/correios.php');
            $correios = new Correios();

            $custo_adicional = $this->config->get(self::EXTENSION . '_custo_adicional');
            $tipo_custo = $this->config->get(self::EXTENSION . '_tipo_custo');
            $taxa_manuseio = $this->config->get(self::EXTENSION . '_taxa_manuseio');
            $promocoes = $this->config->get(self::EXTENSION . '_promocoes');

            $total_pedido = $this->getTotal();

            $currency_code = $this->session->data['currency'];
            $tax_class_id = $this->config->get(self::EXTENSION . '_tax_class_id');
            $config_tax = $this->config->get('config_tax');

            $prazo_adicional = $this->config->get(self::EXTENSION . '_prazo_adicional');
            $exibir_data = $this->config->get(self::EXTENSION . '_exibir_data');

            foreach ($servicos as $servico) {
                $this->codigo_servico = $servico['codigo'];

                $restricoes = $this->getRestricoes();
                $bloqueios = $this->getBloqueios();
                if ($restricoes == false && $bloqueios == false) {
                    $this->desativar_cotacoes = $servico['desativar_cotacoes'];
                    $this->comprimento_minimo = $servico['comprimento_minimo'];
                    $this->largura_minima = $servico['largura_minima'];
                    $this->altura_minima = $servico['altura_minima'];
                    $this->peso_minimo = $servico['peso_minimo'];
                    $this->comprimento_maximo = $servico['comprimento_maximo'];
                    $this->largura_maxima = $servico['largura_maxima'];
                    $this->altura_maxima = $servico['altura_maxima'];
                    $this->soma_maxima = $servico['soma_maxima'];
                    $this->peso_maximo = $servico['peso_maximo'];

                    $codigo = $servico['codigo'];
                    $descricao = $servico['descricao'];
                    $mao_propria = $servico['mao_propria'];
                    $aviso_recebimento = $servico['aviso_recebimento'];
                    $valor_declarado = $servico['valor_declarado'];
                    $minimo_declarado = $servico['minimo_declarado'];
                    $maximo_declarado = $servico['maximo_declarado'];

                    $dias = 0;
                    $valor = 0;
                    $obs = false;

                    $pacotes = $this->getPacotes();

                    if (count($pacotes) > 0) {
                        foreach ($pacotes as $pacote) {

                            if ($valor_declarado == 's') {
                                $valor_declarado = max($pacote['preco'], $minimo_declarado);
                                $valor_declarado = ($valor_declarado > $maximo_declarado) ? $maximo_declarado : $valor_declarado;
                            } else {
                                $valor_declarado = 0;
                            }

                            $dados = array(
                                "chave" => $chave,
                                "codigo_adm" => $codigo_adm,
                                "senha_acesso" => $senha_acesso,
                                "cep_origem" => $cep_origem,
                                "cep_destino" => $cep_destino,
                                "formato" => $formato,
                                "peso" => $pacote['peso'],
                                "comprimento" => $pacote['comprimento'],
                                "altura" => $pacote['altura'],
                                "largura" => $pacote['largura'],
                                "mao_propria" => $mao_propria,
                                "aviso_recebimento" => $aviso_recebimento,
                                "valor_declarado" => $valor_declarado,
                                "codigo" => $codigo
                            );

                            if ($debug) { $this->log->write($dados); };

                            $correios->setParametros($dados);
                            $resposta = $correios->getCotacao();

                            if ($debug) { $this->log->write($resposta); };

                            if (is_object($resposta)) {
                                foreach ($resposta as $resultado) {
                                    $dias = (string) $resultado->PrazoEntrega;
                                    $valor_real = (string) $resultado->Valor;
                                    $valor_real = str_replace(".", "", $valor_real);
                                    $valor_formatado = str_replace(",", ".", $valor_real);

                                    if ($dias > 0 && $valor_formatado > 0) {
                                        $valor += $valor_formatado;

                                        $obs = (string) $resultado->Erro;
                                        if ($obs == '010') {
                                            $obs = '010';
                                        } else if ($obs == '011') {
                                            $obs = '011';
                                        } else {
                                            $obs = false;
                                        }
                                    } else {
                                        $dias = 0; $valor = 0;
                                        break;
                                    }
                                }
                            } else {
                                $dias = 0; $valor = 0;
                                break;
                            }
                        }
                    }

                    if ($dias > 0 && $valor > 0) {
                        if ($custo_adicional > 0) {
                            $custo = 0;
                            if ($tipo_custo == 'P') {
                                $custo = (($valor * (float) $custo_adicional)/100);
                            } else {
                                $custo = (float) $custo_adicional;
                            }
                            $valor = $valor + $custo;
                        }

                        if ($this->manuseio_especial) {
                            $valor = $valor + $taxa_manuseio;
                        }

                        if (is_array($promocoes) && !empty($promocoes)) {
                            $desconto = 0;

                            foreach ($promocoes as $promocao) {
                                if ($promocao['customer_group'] == '' || $promocao['customer_group'] == $this->customer_group_id) {
                                    $cep_inicial = preg_replace('/[^0-9]/', '', $promocao['cep_inicial']);
                                    $cep_final = preg_replace('/[^0-9]/', '', $promocao['cep_final']);

                                    if (strlen($cep_inicial) == 8 && strlen($cep_final) == 8) {
                                        if ($cep_destino >= $cep_inicial && $cep_destino <= $cep_final && $promocao['codigo'] == $this->codigo_servico) {
                                            if ($total_pedido >= $promocao['total']) {
                                                $peso_maximo = ($promocao['peso_maximo'] > 0) ? $promocao['peso_maximo'] : $this->peso_pedido;

                                                if ($this->peso_pedido >= $promocao['peso_minimo'] && $this->peso_pedido <= $peso_maximo) {
                                                    if ($promocao['desconto'] > 0) {
                                                        if ($promocao['tipo_desconto'] == 'P') {
                                                            $desconto = (($valor * (float) $promocao['desconto']) / 100);
                                                        } else if ($promocao['tipo_desconto'] == 'F') {
                                                            $desconto = (float) $promocao['desconto'];
                                                        } else if ($promocao['tipo_desconto'] == 'U') {
                                                            $valor = (float) $promocao['desconto'];
                                                        }
                                                        $descricao = $promocao['descricao'];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            if ($desconto > 0) {
                                $valor = $valor - $desconto;
                            }
                        }

                        $text = $this->currency->format($this->tax->calculate($valor, $tax_class_id, $config_tax), $currency_code, '1.00', true);

                        $dias = ($prazo_adicional > '0') ? $dias + $prazo_adicional : $dias;

                        if ($exibir_data) {
                            $previsao_entrega = $this->previsao_entrega($dias);
                            if ($obs) {
                                if ($obs == '010') {
                                    $title = sprintf($this->language->get('text_previsao_entrega_obs_10'), $descricao, $previsao_entrega);
                                } else if ($obs == '011') {
                                    $title = sprintf($this->language->get('text_previsao_entrega_obs_11'), $descricao, $previsao_entrega);
                                }
                            } else {
                                $title = sprintf($this->language->get('text_previsao_entrega'), $descricao, $previsao_entrega);
                            }
                        } else {
                            if ($obs) {
                                if ($obs == '010') {
                                    $title = sprintf(($dias > '1') ? $this->language->get('text_dias_entrega_obs_10') : $this->language->get('text_dia_entrega_obs_10'), $descricao, $dias);
                                } else if ($obs == '011') {
                                    $title = sprintf(($dias > '1') ? $this->language->get('text_dias_entrega_obs_11') : $this->language->get('text_dia_entrega_obs_11'), $descricao, $dias);
                                }
                            } else {
                                $title = sprintf(($dias > '1') ? $this->language->get('text_dias_entrega') : $this->language->get('text_dia_entrega'), $descricao, $dias);
                            }
                        }

                        $quote_data[$codigo] = array(
                            'code' => 'correios.' . $codigo,
                            'title' => $title,
                            'cost' => $valor,
                            'tax_class_id' => $tax_class_id,
                            'text' => $text
                        );
                    } else {
                        $this->log->write(sprintf($this->language->get('error_servico'), $descricao, $cep_destino));
                    }
                }
            }
        }

        return $quote_data;
    }

    private function getRequisitos() {
        if (!in_array($this->config->get('config_store_id'), $this->config->get(self::EXTENSION . '_stores'))) {
            return false;
        }

        if ($this->customer->isLogged()) {
            $this->customer_group_id = $this->customer->getGroupId();
        } elseif (isset($this->session->data['guest']['customer_group_id'])) {
            $this->customer_group_id = $this->session->data['guest']['customer_group_id'];
        } else {
            $this->customer_group_id = $this->config->get('config_customer_group_id');
        }
        if (!in_array($this->customer_group_id, $this->config->get(self::EXTENSION . '_customer_groups'))) {
            return false;
        }

        $subtotal = $this->cart->getSubTotal();
        if ($subtotal < $this->config->get(self::EXTENSION . '_total')) {
            return false;
        }

        $this->weight_class_id = $this->config->get(self::EXTENSION . '_weight_class_id');
        $this->length_class_id = $this->config->get(self::EXTENSION . '_length_class_id');

        $this->peso_pedido = $this->weight->convert($this->cart->getWeight(), $this->config->get('config_weight_class_id'), $this->weight_class_id);

        if ($this->config->get(self::EXTENSION . '_peso_maximo') > 0) {
            $peso_maximo = $this->config->get(self::EXTENSION . '_peso_maximo');
        } else {
            $peso_maximo = $this->peso_pedido;
        }

        if ($this->peso_pedido <= 0 || $this->peso_pedido < $this->config->get(self::EXTENSION . '_peso_minimo') || $this->peso_pedido > $peso_maximo) {
            return false;
        }

        return true;
    }

    private function getTruncar($valor, $decimais) {
        $raiz = 10;
        $multiplicador = pow($raiz, $decimais);
        $resultado = ((int) ($valor * $multiplicador)) / $multiplicador;
        return number_format($resultado, $decimais);
    }

    private function getPacotes() {
        $comprimento_minimo = $this->comprimento_minimo;
        $largura_minima = $this->largura_minima;
        $altura_minima = $this->altura_minima;
        $peso_minimo = $this->peso_minimo;

        $comprimento_maximo = $this->comprimento_maximo;
        $largura_maxima = $this->largura_maxima;
        $altura_maxima = $this->altura_maxima;
        $soma_maxima = $this->soma_maxima;
        $cubagem_maxima = pow(($soma_maxima/3.18), 3);
        $peso_maximo = $this->peso_maximo;

        $limite_manuseio = $this->config->get(self::EXTENSION . '_limite_manuseio');
        $this->manuseio_especial = false;

        $cubagem = 0;
        $peso_total = 0;
        $preco_total = 0;

        $pacotes = array();
        $caixas = array();
        $id = 0;

        $produtos = $this->cart->getProducts();
        foreach ($produtos as $produto) {
            if ($produto['shipping']) {
                if ($produto['width'] <= 0 || $produto['length'] <= 0 || $produto['height'] <= 0) {
                    $this->log->write(sprintf($this->language->get('error_dimensao_minima'), $produto['name'], $produto['model']));
                    return false;
                }

                if ($produto['weight'] <= 0) {
                    $this->log->write(sprintf($this->language->get('error_peso_minimo'), $produto['name'], $produto['model']));
                    return false;
                }

                $quantidade = $produto['quantity'];
                $preco_unitario = $produto['price'];
                $peso_unitario = $this->weight->convert($produto['weight'] / $quantidade, $produto['weight_class_id'], $this->weight_class_id);

                if ($peso_unitario > $peso_maximo) {
                    $this->log->write(sprintf($this->language->get('error_peso_maximo'), $produto['name'], $produto['model']));
                    return false;
                }

                $comprimento = $this->length->convert($produto['length'], $produto['length_class_id'], $this->length_class_id);
                $largura = $this->length->convert($produto['width'], $produto['length_class_id'], $this->length_class_id);
                $altura = $this->length->convert($produto['height'], $produto['length_class_id'], $this->length_class_id);

                if ($comprimento > $comprimento_maximo || $largura > $largura_maxima || $altura > $altura_maxima) {
                    $this->log->write(sprintf($this->language->get('error_dimensao_maxima'), $produto['name'], $produto['model']));
                    return false;
                }

                if ($comprimento > $limite_manuseio || $largura > $limite_manuseio || $altura > $limite_manuseio) {
                    $this->manuseio_especial = true;
                }

                for ($i = 1; $i <= $quantidade; $i++) {
                    $preco_total += $preco_unitario;
                    $peso_total += $peso_unitario;
                    $cubagem += ($comprimento * $largura * $altura);

                    if ($cubagem > $cubagem_maxima || $peso_total > $peso_maximo) {
                        $id = (!isset($caixas[0])) ? $id : $id + 1;

                        $cubagem = ($comprimento * $largura * $altura);
                        $peso_total = $peso_unitario;
                        $preco_total = $preco_unitario;
                    }

                    $caixas[$id]['preco_total'] = $preco_total;
                    $caixas[$id]['peso_total'] = $peso_total;
                    $caixas[$id]['cubagem'] = $cubagem;
                }
            }
        }

        $qtd_caixas = count($caixas);
        if ($qtd_caixas > 0) {
            if ($qtd_caixas > 1 && $this->desativar_cotacoes == 's') {
                return $pacotes;
            }

            for ($i = 0; $i < $qtd_caixas; $i++) {
                $cubagem = $caixas[$i]['cubagem'];
                $raiz_cubica = ceil(pow($cubagem, 1/3));

                $comprimento = max($raiz_cubica, $comprimento_minimo);
                $largura = max($raiz_cubica, $largura_minima);
                $altura = max($raiz_cubica, $altura_minima);
                $peso = max($caixas[$i]['peso_total'], $peso_minimo);

                $comprimento = ($comprimento > $comprimento_maximo) ? $comprimento_maximo : $comprimento;
                $largura = ($largura > $largura_maxima) ? $largura_maxima : $largura;
                $altura = ($altura > $altura_maxima) ? $altura_maxima : $altura;

                $pacotes[$i] = array(
                    'comprimento' => $this->getTruncar($comprimento, 2),
                    'largura' => $this->getTruncar($largura, 2),
                    'altura' => $this->getTruncar($altura, 2),
                    'preco' => $caixas[$i]['preco_total'],
                    'peso' => $peso
                );
            }
        }

        return $pacotes;
    }

    private function previsao_entrega($dias) {
        $qtd_dias = 0;
        $dias_uteis = 0;
        while ($dias_uteis < $dias) {
            $qtd_dias++;
            if (($dia_da_semana = date("w", "86400" * $qtd_dias + mktime(0,0,0,date('m'),date('d'),date('Y')))) != '0' && $dia_da_semana != '6') {
               $dias_uteis++;
            }
        }

        return date("d/m/Y", "86400" * $qtd_dias + mktime(0,0,0,date('m'),date('d'),date('Y')));
    }
}