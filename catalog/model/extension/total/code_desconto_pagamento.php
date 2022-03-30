<?php
/**
 * @author    Felipo Antonoff <contato@codemarket.com.br>
 * @copyright Copyright © 2013-2022 Codemarket - Todos os direitos reservados
 * @link      https://www.codemarket.com.br Codemarket - Inovando seu E-commerce
 * @link      https://felipoantonoff.com Felipo Antonoff
 */

/**
 * Class ModelExtensionTotalCodeDescontoPagamento
 */
class ModelExtensionTotalCodeDescontoPagamento extends Model
{
    
    private $conf;
    private $status;
    
    /**
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('module/codemarket_module');
        $conf = $this->model_module_codemarket_module->getModulo('487');
        
        if (empty($conf->ch) || $conf->ch != 1) {
            $this->status = false;
            
            return false;
        }
        
        $this->status = true;
        $this->conf = $conf;
    }
    
    /**
     * @param $total
     */
    public function getTotal($total)
    {
        if (empty($this->session->data['payment_method']['code'])
            || empty($this->status)
        ) {
            return [];
        }
        
        $pagamento = $this->session->data['payment_method']['title'];
        $pagamento_cod = $this->session->data['payment_method']['code'];
        $total_geral = $this->cart->getSubTotal();
        $desconto = 0;
        $total_produtos = $this->cart->countProducts();
        
        //Percorrendo os 4 descontos possíveis
        for ($i = 1; $i <= 4; $i++) {
            $c1 = $i.'1';
            $c2 = $i.'2';
            $c3 = $i.'3';
            $c4 = $i.'4';
            $c5 = $i.'5';
            $c6 = $i.'6';
            
            /*
             * 1 = Forma de Pagamento
             * 2 = Valor mínimo
             * 3 = Valor máximo
             * 4 = Quantidade mínima de Produtos
             * 5 = Desconto ou Taxa
             * 6 = Tipo de Desconto (Porcentagem ou Fixo)
             * firstbuy = Primeira Compra 1 = Sim
             */
            
            $discountFirst = $this->roleFirstBuy($total_geral, $i);
            /**
             * - Verificar se tem Desconto na Primeira Compra
             * - Se tiver Desconto, ativar por ele mesmo que não esteja dentro do total mínimo
             */
            
            if (!empty($this->conf->{"c$c1"})
                && $this->conf->{"c$c1"} == $pagamento_cod
                && isset($this->conf->{"c$c5"})
                && isset($this->conf->{"c$c6"})
                // Regra
                && ((isset($this->conf->{"c$c2"})
                        && $total_geral >= $this->conf->{"c$c2"}
                        && isset($this->conf->{"c$c3"})
                        && $total_geral <= $this->conf->{"c$c3"}
                        && isset($this->conf->{"c$c4"})
                        && $total_produtos >= $this->conf->{"c$c4"})
                    || (!empty($discountFirst)))
            
            ) {
                $produtos = $this->cart->getProducts();
                $descontar = 0;
                foreach ($produtos as $key => $p) {
                    $q = $this->db->query(
                        "SELECT c487post FROM ".DB_PREFIX
                        ."product WHERE product_id = '"
                        .(int)$p['product_id']
                        ."' and c487post IS NOT NULL LIMIT 1"
                    );
                    
                    if (isset($q->row['c487post'])) {
                        $q = $q->row['c487post'];
                        
                        $qpost = json_decode($q, true);
                        //print_r($qpost);
                        if (isset($p['price']) && !empty($qpost[$i]['c1'])
                            && $qpost[$i]['c1'] == $pagamento_cod
                            && !empty($qpost[$i]['c5'])
                            && !empty($qpost[$i]['c6'])
                            && !empty($qpost[$i]['ch'])
                            && $qpost[$i]['ch'] == 1
                        ) {
                            if ($qpost[$i]['c6'] == 1) {
                                $desconto += ((int)$qpost[$i]['c5'] / 100)
                                    * ($p['price'] * $p['quantity']);
                            } else {
                                $desconto += $qpost[$i]['c5']
                                    * $p['quantity'];
                            }
                            
                            unset($produtos[$key]);
                            $descontar += ($p['price'] * $p['quantity']);
                        }
                    }
                }
                
                $total_sub = $total_geral - $descontar;
                if (!empty($produtos) && !empty($this->conf->{"c$c5"})) {
                    if ($this->conf->{"c$c6"} == 1) {
                        $desconto += ((int)$this->conf->{"c$c5"} / 100)
                            * ($total_sub);
                    } else {
                        $desconto += (float)$this->conf->{"c$c5"};
                    }
                }
                
                break;
            }
        }
        
        if ($desconto > 0 && ($total_geral - $desconto) > 0) {
            $cg1 = 10;
            if (isset($this->conf->cg1) && !empty($this->conf->cg1)) {
                $cg1 = $this->conf->cg1;
            }
            
            $titulo = 'Desconto '.$pagamento;
            $total['totals'][] = [
                'code' => 'code_desconto_pagamento',
                'title' => $titulo,
                'value' => -$desconto,
                'sort_order' => $cg1,
            ];
            
            $total['total'] -= $desconto;
        } else {
            if ($desconto < 0) {
                $cg1 = 10;
                if (isset($this->conf->cg1) && !empty($this->conf->cg1)) {
                    $cg1 = $this->conf->cg1;
                }
                
                $titulo = 'Taxa '.$pagamento;
                $total['totals'][] = [
                    'code' => 'code_desconto_pagamento',
                    'title' => $titulo,
                    'value' => abs($desconto),
                    'sort_order' => $cg1,
                ];
                
                $total['total'] += abs($desconto);
            }
        }
    }
    
    /**
     * Retorna o Aviso
     *
     * @param $method_data
     *
     * @return array
     */
    public function aviso($method_data)
    {
        /**
         * code_informativo
         * 4 = Não
         * 3 = Sim Geral
         * 2 = Sim - Primeira Compra Habilitado
         * 1 = Sim - Primeira Compra Ativo Desconto
         */
        if (empty($this->status)
            || (!empty($this->conf->code_informativo)
                && (int)$this->conf->code_informativo === 3)
        ) {
            return $method_data;
        }
        
        $total_geral = $this->cart->getSubTotal();
        $total_produtos = $this->cart->countProducts();
        
        //Percorrendo os 4 descontos possíveis
        for ($i = 1; $i <= 4; $i++) {
            $c1 = $i.'1';
            $c2 = $i.'2';
            $c3 = $i.'3';
            $c4 = $i.'4';
            $c5 = $i.'5';
            $c6 = $i.'6';
            
            if (empty($this->conf->{"c$c1"})
                || empty($method_data[$this->conf->{"c$c1"}])
                || empty($method_data[$this->conf->{"c$c1"}]['code'])
            ) {
                continue;
            }
            
            $firstBuy = $i.'firstbuy';
            
            if (!empty($this->conf->code_informativo)
                && (int)$this->conf->code_informativo === 2
                && !empty($this->conf->{"c$firstBuy"})
                && (int)$this->conf->{"c$firstBuy"} === 1
            ) {
                return $method_data;
            }
            
            $pagamento_cod = $method_data[$this->conf->{"c$c1"}]['code'];
            $discountFirst = $this->roleFirstBuy($total_geral, $i);
            
            if (!empty($discountFirst) && !empty($this->conf->code_informativo)
                && (int)$this->conf->code_informativo === 1
            ) {
                return $method_data;
            }
            
            //Desconto aplicado
            if ($this->conf->{"c$c1"} == $pagamento_cod
                && isset($this->conf->{"c$c5"})
                && isset($this->conf->{"c$c6"})
                // Regra
                && ((isset($this->conf->{"c$c2"})
                        && $total_geral >= $this->conf->{"c$c2"}
                        && isset($this->conf->{"c$c3"})
                        && $total_geral <= $this->conf->{"c$c3"}
                        && isset($this->conf->{"c$c4"})
                        && $total_produtos >= $this->conf->{"c$c4"})
                    || (!empty($discountFirst)))
            ) {
                //Code Desconto //
                $produtos = $this->cart->getProducts();
                $descontar = 0;
                $desconto = 0;
                
                foreach ($produtos as $key => $p) {
                    $q = $this->db->query(
                        "SELECT c487post FROM ".DB_PREFIX
                        ."product WHERE product_id = '"
                        .(int)$p['product_id']
                        ."' and c487post IS NOT NULL LIMIT 1"
                    );
                    if (isset($q->row['c487post'])) {
                        $q = $q->row['c487post'];
                        
                        $qpost = json_decode($q, true);
                        
                        if (isset($p['price']) && !empty($qpost[$i]['c1'])
                            && $qpost[$i]['c1'] == $pagamento_cod
                            && !empty($qpost[$i]['c5'])
                            && !empty($qpost[$i]['c6'])
                            && !empty($qpost[$i]['ch'])
                            && $qpost[$i]['ch'] == 1
                        ) {
                            {
                                if ($qpost[$i]['c6'] == 1) {
                                    $desconto += ((int)$qpost[$i]['c5']
                                            / 100) * ($p['price']
                                            * $p['quantity']);
                                } else {
                                    $desconto += $qpost[$i]['c5']
                                        * $p['quantity'];
                                }
                                
                                unset($produtos[$key]);
                                $descontar += ($p['price']
                                    * $p['quantity']);
                            }
                        }
                    }
                }
                
                $total_sub = $total_geral - $descontar;
                if (!empty($produtos) && !empty($this->conf->{"c$c5"})) {
                    if ($this->conf->{"c$c6"} == 1) {
                        $desconto += ((int)$this->conf->{"c$c5"} / 100)
                            * ($total_sub);
                    } else {
                        $desconto += (float)$this->conf->{"c$c5"};
                    }
                }
                
                if ($desconto > 0) {
                    // Fim Code Desconto //
                    $desconto = $this->currency->format(
                        $desconto,
                        $this->session->data['currency']
                    );
                    $method_data[$this->conf->{"c$c1"}]['title']
                        = '<p class="alert487 alert-success">'
                        .$method_data[$this->conf->{"c$c1"}]['title']
                        .' - Desconto de '.$desconto.'</p>';
                } else {
                    $desconto = $this->currency->format(
                        abs($desconto),
                        $this->session->data['currency']
                    );
                    $method_data[$this->conf->{"c$c1"}]['title']
                        = $method_data[$this->conf->{"c$c1"}]['title']
                        .' - Taxa de '.$desconto;
                }
                //Aviso Desconto
            } else {
                // Aviso de quanto falta para o Desconto
                if ($this->conf->{"c$c1"} == $pagamento_cod
                    && !empty($this->conf->{"c$c1"})
                    && isset($this->conf->{"c$c2"})
                    && isset($this->conf->{"c$c3"})
                    && isset($this->conf->{"c$c4"})
                    && isset($this->conf->{"c$c5"})
                    && isset($this->conf->{"c$c6"})
                ) {
                    //Code Desconto //
                    $produtos = $this->cart->getProducts();
                    $descontar = 0;
                    $desconto = 0;
                    foreach ($produtos as $key => $p) {
                        $q = $this->db->query(
                            "SELECT c487post FROM ".DB_PREFIX
                            ."product WHERE product_id = '"
                            .(int)$p['product_id']
                            ."' and c487post IS NOT NULL LIMIT 1"
                        );
                        if (isset($q->row['c487post'])) {
                            $q = $q->row['c487post'];
                            
                            $qpost = json_decode($q, true);
                            
                            if (isset($p['price']) && !empty($qpost[$i]['c1'])
                                && $qpost[$i]['c1'] == $pagamento_cod
                                && !empty($qpost[$i]['c5'])
                                && !empty($qpost[$i]['c6'])
                                && !empty($qpost[$i]['ch'])
                                && $qpost[$i]['ch'] == 1
                            ) {
                                if ($qpost[$i]['c6'] == 1) {
                                    $desconto += ((int)$qpost[$i]['c5']
                                            / 100) * ($p['price']
                                            * $p['quantity']);
                                } else {
                                    $desconto += $qpost[$i]['c5']
                                        * $p['quantity'];
                                }
                                
                                unset($produtos[$key]);
                                $descontar += ($p['price']
                                    * $p['quantity']);
                            }
                        }
                    }
                    
                    $total_sub = $total_geral - $descontar;
                    if (!empty($produtos)
                        && !empty($this->conf->{"c$c5"})
                    ) {
                        if ($this->conf->{"c$c6"} == 1) {
                            $desconto += ((int)$this->conf->{"c$c5"} / 100)
                                * ($total_sub);
                        } else {
                            $desconto += (float)$this->conf->{"c$c5"};
                        }
                    }
                    
                    $desconto_numero = $desconto;
                    $desconto = $this->currency->format(
                        $desconto,
                        $this->session->data['currency']
                    );
                    // Fim Code Desconto //
                    $falta1 = false;
                    $falta2 = false;
                    
                    $falta = $this->conf->{"c$c2"} - $total_geral;
                    
                    if ($falta > 0) {
                        $falta1 = true;
                        $falta = $this->currency->format(
                            $falta,
                            $this->session->data['currency']
                        );
                    }
                    
                    $falta_q = $this->conf->{"c$c4"} - $total_produtos;
                    
                    if ($falta_q > 0) {
                        $falta2 = true;
                        $faltat = 'produto';
                        
                        if ($falta_q > 1) {
                            $faltat = 'produtos';
                        }
                    }
                    
                    if (!empty($falta1) && !empty($falta2)) {
                        $falta_texto = ' - Compre '.$falta.' e mais '
                            .$falta_q.' '.$faltat.' para ganhar '
                            .$desconto.' ou mais em Desconto</p>';
                    } else {
                        if (!empty($falta1)) {
                            $falta_texto = ' - Compre mais '.$falta
                                .' em produtos, para ganhar '.$desconto
                                .' ou mais em Desconto</p>';
                        } else {
                            if (!empty($falta2)) {
                                $falta_texto = ' - Compre mais '
                                    .$falta_q.' '.$faltat
                                    .' para ganhar '.$desconto
                                    .' ou mais em Desconto</p>';
                            }
                        }
                    }
                    
                    if (!empty($falta_texto) && $desconto_numero > 0) {
                        $method_data[$this->conf->{"c$c1"}]['title']
                            = '<p class="alert487 alert-info">'
                            .$method_data[$this->conf->{"c$c1"}]['title']
                            .$falta_texto;
                    }
                }
            }
        }
        
        return $method_data;
    }
    
    /**
     * Regra para Desconto na Primeira Compra
     *
     * @param float $subTotal
     * @param int   $index
     *
     * @return bool
     */
    private function roleFirstBuy(float $subTotal, int $index): bool
    {
        $firstBuy = $index.'firstbuy';
        
        if ($subTotal <= 0 || empty($this->customer->getId())
            || empty($this->conf->{"c$firstBuy"})
            || $this->conf->{"c$firstBuy"} != 1
        ) {
            return false;
        }
        
        $queryFisrtBuy = $this->db->query(
            "SELECT order_id FROM `".DB_PREFIX."order`
             WHERE customer_id = '"
            .(int)$this->customer->getId()."' AND order_status_id != 0 LIMIT 1"
        )->row;
        
        if (!empty($queryFisrtBuy['order_id'])) {
            return false;
        }
        
        return true;
    }
}
