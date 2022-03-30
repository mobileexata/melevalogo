<?php

require_once DIR_SYSTEM.'library/code/code_menvio/vendor/autoload.php';

use GuzzleHttp\Client;

/**
 * Class ModelExtensionShippingCodeMelhorenvio
 * © Copyright 2013-2022 Codemarket - Todos os direitos reservados.
 *
 * @property \Cart\Cart                   cart
 * @property \Session                     session
 * @property \Loader                      load
 * @property \ModelModuleCodemarketModule model_module_codemarket_module
 * @property \Cart\Currency               currency
 * @property \DB\MySQLi                   db
 * @property \Cart\Weight                 weight
 * @property \Cart\Length                 length
 */
class ModelExtensionShippingCodeMelhorEnvio extends Model
{
    private $conf;
    private $log;
    private $url;
    private $token;
    private $debug;
    
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->log = new Log('Code-MelhorEnvio-'.date('m-Y').'.log');
        
        try {
            $this->load->model('module/codemarket_module');
        } catch (\Exception $e) {
            die('Model não instalado');
        }
        
        $this->conf = $this->model_module_codemarket_module->getModulo('524');
        
        $this->url = 'https://www.melhorenvio.com.br';
        if ((int)$this->conf->env === 0) {
            $this->url = 'https://sandbox.melhorenvio.com.br';
            $this->token = $this->conf->apiTokenSandbox;
        }
        
        $this->token = $this->conf->apiToken;
        $this->debug = !empty($this->conf->code_debug)
        && $this->conf->code_debug == 1
            ? 1 : 0;
    }
    
    /**
     * @param $address
     *
     * @return array
     * @throws \Exception
     */
    public function getQuote($address)
    {
        $this->log->write("CotarFrete - Passo 1 Dentro da cotação");
        if (empty($this->conf->status)) {
            $this->log->write("CotarFrete - Passo 2 módulo desabilitado");
            
            //$this->log->write("CotarFrete - Passo 2 Configuração".print_r($this->conf, true));
            return [];
        }
        
        $this->log->write("CotarFrete - Passo 2 módulo habilitado");
        
        if (empty($this->conf->geo_zone_id)) {
            $status = true;
        } else {
            $query = $this->db->query(
                "SELECT * FROM ".DB_PREFIX
                ."zone_to_geo_zone WHERE geo_zone_id = '".(int)
                $this->conf->geo_zone_id."' AND country_id = '"
                .(int)$address['country_id']."' AND (zone_id = '".
                (int)$address['zone_id']."' OR zone_id = '0')
            "
            );
            
            if ($query->num_rows) {
                $status = true;
            } else {
                $status = false;
            }
        }
        
        if (empty($status)) {
            $this->log->write(
                "CotarFrete - Passo 3 desabilitado pela região, use na Configuraçào Todas as regiões de preferência"
            );
            
            return [];
        }
        
        $products = $this->cart->getProducts();
        
        foreach ($products as $p => $product) {
            if (!$product['shipping']) {
                unset($products[$p]);
            }
        }
        $services = [];
        foreach ($this->conf->servicos as $svc) {
            if (!empty($svc) && $svc->status === true) {
                $services [] = $svc->id;
            }
        }
        
        $data = [];
        foreach ($this->conf->servicos as $servico) {
            if (empty($servico) || $servico->status == 0) {
                continue;
            }
            
            $data[$servico->id] = [
                'to' => [
                    'postal_code' => preg_replace(
                        '/\D/',
                        '',
                        $address['postcode']
                    ),
                    'address' => $address['address_1'],
                    'number' => preg_replace('/\D/', '', $address['address_1']),
                ],
                'from' => [
                    'postal_code' => preg_replace(
                        '/\D/',
                        '',
                        $this->conf->origem
                    ),
                    'address' => $this->conf->address,
                    'number' => $this->conf->number,
                ],
                'products' => array_map(function ($product) use ($servico) {
                    $data = [
                        'id' => $product['product_id'],
                        'weight' => (float)$this->weight->convert(
                            $product['weight'] / (int)$product['quantity'],
                            $product['weight_class_id'],
                            $this->conf->weight_class_id
                        ),
                        'length' => (float)$this->length->convert(
                            $product['length'],
                            $product['length_class_id'],
                            $this->conf->length_class_id
                        ),
                        'width' => (float)$this->length->convert(
                            $product['width'],
                            $product['length_class_id'],
                            $this->conf->length_class_id
                        ),
                        'height' => (float)$this->length->convert(
                            $product['height'],
                            $product['length_class_id'],
                            $this->conf->length_class_id
                        ),
                        'quantity' => (int)$product['quantity'],
                        'unitary_value' => (float)$product['price'],
                    ];
                    
                    //DECLARAR VALOR
                    if ($this->hasOption($servico, 'vd')) {
                        if (!empty($this->conf->declarar_tabela)
                            && !empty($product['product_id'])
                        ) {
                            $declararGet = $this->db->query(
                                "SELECT * FROM ".$this->conf->declarar_tabela."
                                WHERE
                                product_id = '".(int)$product['product_id']."'
                                LIMIT 1
                            "
                            );
                        }
                        
                        if (!empty($this->conf->declarar_campo)
                            && !empty($declararGet->row[$this->conf->declarar_campo])
                        ) {
                            $custo
                                = (float)$declararGet->row[$this->conf->declarar_campo];
                        } else {
                            $custo = (float)$product['price'];
                        }
                        
                        //Caso o preço no carrinho for menor que o custo, usar ele como custo
                        if ($product['price'] < $custo) {
                            $custo = (float)$product['price'];
                        }
                        
                        $data['insurance_value'] = $custo;
                    }
                    
                    return $data;
                }, $products),
                'options' => [
                    "receipt" => $this->hasOption($servico, 'ar'),
                    "own_hand" => $this->hasOption($servico, 'mp'),
                    "collect" => $this->hasOption($servico, 'cl'),
                ],
                'services' => (string)$servico->id,
            ];
        }
        
        try {
            if (!empty($this->conf->code_post) && $this->conf->code_post == 1) {
                $post = $this->post($data);
            } else {
                $post = $this->post_curl($data);
            }
        } catch (\Exception $e) {
            $this->log->write("CotarFrete - Erro na cotação".print_r($e, true));
            
            return [];
        }
        
        if (empty($post)) {
            $this->log->write(
                "CotarFrete - Sem retorno, verificar dimensões, peso, se o produto está habilitada ou refazer o Token do Melhor Envio"
                .print_r($post, true)
            );
            $this->log->write(
                "CotarFrete - Pode ir na Configuração da Melhoria -> Melhor Envio e Ativar o Modo Debug para ter mais detalhes no Log"
            );
            
            return [];
        }
        
        if (empty($post)) {
            $quote_data = [];
        } else {
            foreach ($post as $quote) {
                if (empty($quote) || !empty($quote->error)) {
                    continue;
                }
                
                $servico = $this->conf->servicos[$quote->id];
                $title = $servico->title;
                
                if ($this->conf->servicos[$quote->id]->extraDays > 0) {
                    $deliver = $quote->delivery_time.' a '
                        .($quote->delivery_time
                            + $this->conf->servicos[$quote->id]->extraDays);
                } else {
                    $deliver = $quote->delivery_time;
                }
                
                if (!empty($this->conf->deliver_message)) {
                    $title = str_replace(
                        ['{servico}', '{prazo}'],
                        [$title, $deliver],
                        $this->conf->deliver_message
                    );
                } else {
                    $title = str_replace(
                        ['{servico}', '{prazo}'],
                        [$title, $deliver],
                        '{servico} - (Prazo estimado {prazo} dias úteis)'
                    );
                }
                
                $data = $this->rulePrice($quote, $address);
                
                $quote_data[$quote->id] = [
                    'code' => 'code_melhorenvio.'.$quote->id,
                    'melhorenvio_id' => $quote->id,
                    'title' => $title,
                    'cost' => $data['price'],
                    'tax_class_id' => 0,
                    'text' => $data['text'],
                ];
            }
        }
        
        if (empty($this->conf->title)) {
            $title = 'Transportadoras';
        } else {
            $title = $this->conf->title;
        }
        
        if (empty($this->conf->sort_order)) {
            $order = 1;
        } else {
            $order = $this->conf->sort_order;
        }
        
        $method_data = [];
        if (!empty($quote_data)) {
            //Ordenando o array
            $quote_data_sort = $quote_data;
            $quote_data = [];
            $columns = array_column($quote_data_sort, 'cost');
            array_multisort($columns, SORT_ASC, $quote_data_sort);
            
            $i = 1;
            foreach ($quote_data_sort as $qd) {
                $qd['code'] = 'code_melhorenvio.'.$i;
                $quote_data[$i] = $qd;
                $i++;
            }
            
            $method_data = [
                'code' => 'code_melhorenvio',
                'title' => $title,
                'quote' => $quote_data,
                'sort_order' => $order,
                'error' => false,
            ];
            
            if (isset($this->session->data['melhor_envio'])) {
                unset($this->session->data['melhor_envio']);
            }
            
            $this->session->data['melhor_envio']['post'] = $post;
            
            $this->log->write(
                "CotarFrete - Passo Final Cotação realizada com sucesso"
            );
            /*
            $this->session->data['melhor_envio']['quoted_data'] = $quote_data;
            $this->log->write('CotarFrete - Debug POST: '.print_r($this->session->data['melhor_envio']['post'], true));
            $this->log->write('CotarFrete - Debug Quoted Data: '.print_r($this->session->data['melhor_envio']['quoted_data'], true));
            */
        }
        
        return $method_data;
    }
    
    /**
     * Retorna o Preço e o Text final do Frete
     * Aplica regras de Desconto no Preço
     *
     * @param object $quote
     * @param array  $address
     *
     * @return array
     */
    private function rulePrice(
        object $quote,
        array $address
    ): array {
        if (isset($this->conf->servicos[$quote->id]->extraTax)) {
            $tax = trim($this->conf->servicos[$quote->id]->extraTax);
            $tax = explode("%", $tax);
            
            //print_r($tax); exit();
            
            if (isset($tax[1])) {
                // Verificando se é desconto
                if (!empty($tax[0]) && (string)$tax[0] === '-') {
                    $percentage = (100 - (float)$tax[1]) / 100;
                } else {
                    $percentage = (100 + (float)$tax[1]) / 100;
                }
                
                $price = (float)$quote->price * $percentage;
            } else {
                $price = (float)$quote->price + (float)$tax[0];
            }
        } else {
            $price = (float)$quote->price;
        }
        
        // Regras de Descontos
        //print_r($this->conf->descontos); exit();
        if (!empty($this->conf->descontos)) {
            $subtotal = $this->cart->getSubTotal();
            $quantity = $this->cart->countProducts();
            $test = [];
            
            foreach ($this->conf->descontos as $desconto) {
                if (!isset($desconto->subtotal_min)
                    || !isset($desconto->subtotal_max)
                    || empty($desconto->qtd_min)
                    || empty($desconto->valor_desconto)
                    || empty($desconto->primeira_compra)
                    || empty($desconto->desconto_maximo)
                    || empty($desconto->servicos)
                    || empty($desconto->estados)
                    || empty($desconto->habilitar)
                    || $desconto->habilitar != 1
                ) {
                    continue;
                }
                
                // Verificar Primeira Compra, se 2 geral e 1 primeira compra
                if ((int)$desconto->primeira_compra === 1) {
                    // Cliente não logado
                    if (empty($this->customer->getId())) {
                        continue;
                    }
                    
                    // Verificando se é a primeira Compra ou com Status 0 nos Pedidos
                    
                    $queryFisrtBuy = $this->db->query(
                        "SELECT order_id FROM `".DB_PREFIX."order`
                                WHERE customer_id = '"
                        .(int)$this->customer->getId()
                        ."' AND order_status_id != 0 LIMIT 1"
                    )->row;
                    
                    if (!empty($queryFisrtBuy['order_id'])) {
                        continue;
                    }
                }
                
                $status = false;
                $status2 = false;
                
                //print_r($desconto->servicos);
                //print_r($quote);
                
                // Verificar serviços e estados
                foreach ($desconto->servicos as $servico) {
                    // Se 0 é para todos os Serviços/Transportadoras
                    if (empty($servico) || (int)$servico === (int)$quote->id) {
                        $status = true;
                        break;
                    }
                }
                
                if (!empty($status)) {
                    foreach ($desconto->estados as $estado) {
                        // Se 0 é para todos os Estados
                        if (empty($estado)
                            || (int)$estado === (int)$address['zone_id']
                        ) {
                            /*
                            print_r($desconto->estados);
                            print_r($address['zone_id']);
                            exit();
                            */
                            
                            $status2 = true;
                            break;
                        }
                    }
                    
                    if (empty($status2)) {
                        $status = false;
                    }
                }
                
                if (!empty($status)) {
                    if ($quantity >= $desconto->qtd_min
                        && $subtotal >= $desconto->subtotal_min
                        && $subtotal <= $desconto->subtotal_max
                    ) {
                        // Regra de Desconto
                        $tax = trim($desconto->valor_desconto);
                        $tax = explode("%", $tax);
                        
                        if (isset($tax[1])) {
                            $percentage = (100 - (float)$tax[1]) / 100;
                            $discount = $price * $percentage;
                        } else {
                            $discount = $price - (float)$tax[0];
                        }
                        
                        // Verificando o valor de desconto e se é acima do Desconto Máximo
                        if (($price - $discount)
                            > (float)$desconto->desconto_maximo
                        ) {
                            /*
                                echo "<h3>Valor com Desconto {$discount}</h3>";
                                echo "<h3>Desconto Máximo {$desconto->desconto_maximo}</h3>";
                                exit();
                            */
                            
                            $discount = $price
                                - (float)$desconto->desconto_maximo;
                        }
                        
                        /*
                            echo "<h3>Preço {$price}</h3>";
                            echo "<h3>Valor com Desconto {$discount}</h3>";
                            exit();
                        */
                        
                        $discount = round($discount, 2);
                        
                        // Prioridade no menor valor
                        if (!isset($discountMin) || $discount < $discountMin) {
                            //echo $discount;
                            //exit();
                            $discountMin = $discount;
                            /*
                            $test[] = [
                                'discountMin' => $discountMin,
                                'quote' => $quote->id
                            ];
                            */
                        }
                    }
                }
            }
        }
        
        //print_r($test);
        
        if (!empty($discountMin)) {
            $price = round($discountMin, 2);
        } else {
            $price = round($price, 2);
        }
        
        if ($price <= 0) {
            $price = 0;
            $text = 'Grátis';
        }
        
        if (empty($text)) {
            $text = $this->currency->format(
                (float)$price,
                $this->session->data['currency']
            );
        }
        
        return ['price' => $price, 'text' => $text];
    }
    
    /**
     * @param $data
     *
     * @return array
     */
    private function post_curl($data)
    {
        $url = $this->url.'/api/v2/me/shipment/calculate';
        //$this->log->write('post() Data:' . print_r($data, true));
        
        if ($this->debug) {
            $logDebug = new Log('Code-MelhorEnvio-Debug-'.date('m-Y').'.log');
            $logDebug->write('post() - Dentro do modo Debug');
        }
        
        $results = [];
        
        foreach ($data as $id => $dataService) {
            if ($this->debug) {
                $logDebug->write('post() - Testando serviço ID: '.$id);
                $logDebug->write(
                    'post() - Dados Serviço ID '.$id.': '.print_r(
                        $dataService,
                        true
                    )
                );
            }
            
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_TIMEOUT => 17,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($dataService),
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_HTTPHEADER => [
                    "Cache-Control: no-cache",
                    "Accept: application/json",
                    "Content-Type: application/json",
                    "Authorization: Bearer ".$this->token,
                ],
            ]);
            
            //print_r(json_encode($data)); exit();
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            
            if ($err) {
                if ($this->debug) {
                    $logDebug->write(
                        'post() URL: '.$url.' - Error Curl Serviço ID '.$id.': '
                        .print_r($err, true).' e resposta: '.print_r(
                            $response,
                            true
                        )
                    );
                    $logDebug->write(
                        'post() URL: '.$url.' - Token usado Serviço ID '.$id
                        .': '.print_r($this->token, true)
                    );
                }
            } else {
                $quote = json_decode($response, true);
                
                if (!empty($quote[0])) {
                    $quote = $quote[0];
                }
                
                if (!empty($quote) && empty($quote['error'])
                    && !empty($quote['id'])
                    && !empty($quote['delivery_time'])
                    && !empty($quote['price'])
                ) {
                    $results[$id] = json_decode(json_encode($quote));
                }
                
                if ($this->debug) {
                    $logDebug->write(
                        'post() URL: '.$url.' - Dados retornados Serviço ID '
                        .$id.': '.print_r(json_decode($response, true), true)
                    );
                }
            }
            
            // 0,1s = 10 = 1s
            usleep(100000);
        }
        
        return $results;
    }
    
    /**
     * @param $data
     *
     * @return array
     */
    private function post($data)
    {
        $url = $this->url.'/api/v2/me/shipment/calculate';
        
        //$this->log->write('post() Data:' . print_r($data, true));
        if ($this->debug) {
            $logDebug = new Log('Code-MelhorEnvio-Debug-'.date('m-Y').'.log');
            $logDebug->write('post() - Dentro do modo Debug');
            
            foreach ($data as $id => $dataService) {
                $logDebug->write('post() - Testando serviço ID: '.$id);
                $logDebug->write(
                    'post() - Dados Serviço ID '.$id.': '.print_r(
                        $dataService,
                        true
                    )
                );
                
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 3,
                    CURLOPT_TIMEOUT => 17,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => json_encode($dataService),
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_HTTPHEADER => [
                        "Cache-Control: no-cache",
                        "Accept: application/json",
                        "Content-Type: application/json",
                        "Authorization: Bearer ".$this->token,
                    ],
                ]);
                
                //print_r(json_encode($data)); exit();
                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);
                
                if ($err) {
                    $logDebug->write(
                        'post() URL: '.$url.' - Error Curl Serviço ID '.$id.': '
                        .print_r($err, true).' e resposta: '.print_r(
                            $response,
                            true
                        )
                    );
                    $logDebug->write(
                        'post() URL: '.$url.' - Token usado Serviço ID '.$id
                        .': '.print_r($this->token, true)
                    );
                } else {
                    $logDebug->write(
                        'post() URL: '.$url.' - Dados retornados Serviço ID '
                        .$id.': '.print_r(json_decode($response, true), true)
                    );
                }
            }
        }
        
        $requests = [];
        foreach ($data as $id => $dataService) {
            $requests[$id] = new \GuzzleHttp\Psr7\Request('POST', $url, [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'authorization' => 'Bearer '.$this->token,
            ], json_encode($dataService));
        }
        
        //print_r($requests);
        $client = new Client();
        
        $results = [];
        
        $pool = new \GuzzleHttp\Pool($client, $requests, [
            'concurrency' => count($requests),
            'fulfilled' => function (
                \GuzzleHttp\Psr7\Response $response,
                $serviceId
            ) use (&$results, $data) {
                $content = $response->getBody()->getContents();
                $quote = json_decode($content, true);
                
                if (!empty($quote[0])) {
                    $quote = $quote[0];
                }
                
                if (!empty($quote) && empty($quote['error'])
                    && !empty($quote['id'])
                    && !empty($quote['delivery_time'])
                    && !empty($quote['price'])
                ) {
                    $results[$serviceId] = json_decode(json_encode($quote));
                }
            },
        ]);
        // run queue
        $pool->promise()->wait();
        
        return $results;
    }
    
    /**
     * @param $servico
     * @param $svc
     *
     * @return bool
     */
    private function hasOption($servico, $svc)
    {
        if (!empty($servico) && !empty($servico->status)
            && !empty($servico->{$svc})
        ) {
            return true;
        }
        
        return false;
    }
}
