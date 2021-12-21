<?php

/**
 *
 * © Copyright 2013-2021 Codemarket - Todos os direitos reservados.
 * Class ModelModuleCodemarketCarrinho
 */
class ModelModuleCodemarketCarrinho extends Model
{
    private $log;
    private $conf;

    /**
     * ModelModuleCodemarketCarrinho constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('module/codemarket_module');
        $conf = $this->model_module_codemarket_module->getModulo('476');

        if (empty($this->request->get['token']) || empty($conf->code_token) || $this->request->get['token'] != $conf->code_token) {
            exit("
                <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/bulma/0.8.2/css/bulma.min.css\" integrity=\"sha256-qS+snwBgqr+iFVpBB58C9UCxKFhyL03YHpZfdNUhSEw=\" crossorigin=\"anonymous\" />
                <div class=\"notification is-danger notification-padding\" style='margin: 50px;'>
                <h1 class='content is-medium has-text-centered is-uppercase has-text-weight-medium'>Acesso não autorizado</h1>
                </div>
            ");
        }

        if (
            (!empty($conf->cnh)) and ($conf->cnh == 1) and (!empty($conf->cn2)) and
            (!empty($conf->cn3)) and (!empty($conf->cn4)) and (!empty($conf->cn5)) and
            (!empty($conf->cn6)) and (!empty($conf->cn7)) and (!empty($conf->cnb1)) and
            (!empty($conf->cnb2))
        ) {
            $this->conf = $conf;
            $this->log = new Log('Code-RecuperarCarrinhoPremium-' . date('m-Y') . '.log');
            $this->log->write('Dentro do Carrinho, passou no teste');
            return true;
        } else {
            exit("
                <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/bulma/0.8.2/css/bulma.min.css\" integrity=\"sha256-qS+snwBgqr+iFVpBB58C9UCxKFhyL03YHpZfdNUhSEw=\" crossorigin=\"anonymous\" />
                <div class=\"notification is-danger notification-padding\" style='margin: 50px;'>
                <h1 class='content is-medium has-text-centered is-uppercase has-text-weight-medium'>Acesso não autorizado</h1>
                </div>
            ");
        }
    }

    /**
     *
     * Lista os Carrinhos
     *
     * @return array
     */
    public function carrinhos()
    {
        echo "<h1>Carrinhos Loja</h1>";
        $conf = $this->conf;
        $confn2 = trim((int) $conf->cn2);
        $confn3 = trim((int) $conf->cn3);
        $confn4 = trim((int) $conf->cn4);
        $confn5 = trim((int) $conf->cn5);

        //Caso seja produção, primeiro envio, intervalo em minutos
        $carrinho = $this->db->query("
            SELECT DISTINCT(c.customer_id), c.session_id, c.date_added FROM `" . DB_PREFIX . "cart` c
            LEFT JOIN `code_carrinhos` cc ON (c.session_id = cc.session_id)
            WHERE
            c.customer_id > 0 AND 
            (cc.session_id IS NULL OR CHAR_LENGTH(cc.session_id) <= 4) AND
            (NOW() - INTERVAL '" . (int) $confn5 . "' MINUTE) >= c.date_added AND
            (NOW() - INTERVAL '" . (int) $confn2 . "' DAY) < c.date_added
            ORDER BY c.date_added DESC
        ");

        $total = $this->db->query("
            SELECT COUNT(c.cart_id) as total FROM `" . DB_PREFIX . "cart` c
        ");

        if (isset($total->row['total'])) {
            echo "<h3>Total Carrinhos Geral: " . $total->row['total'] . "</h3>";
        }

        $total = $this->db->query("
            SELECT COUNT(DISTINCT(c.customer_id)) as total FROM `" . DB_PREFIX . "cart` c
            LEFT JOIN `code_carrinhos` cc ON (c.session_id = cc.session_id)
            WHERE
            c.customer_id > 0 AND 
            (cc.session_id IS NULL OR CHAR_LENGTH(cc.session_id) <= 4) AND
            (NOW() - INTERVAL '" . (int) $confn5 . "' MINUTE) >= c.date_added AND
            (NOW() - INTERVAL '" . (int) $confn2 . "' DAY) < c.date_added
            ORDER BY c.date_added DESC
        ");

        if (isset($total->row['total'])) {
            echo "<h3>Total Carrinhos Primeiro Envio: " . $total->row['total'] . "</h3>";
        }

        //Depois do primeiro envio, consultando o carrinho
        $carrinho2 = $this->db->query("
            SELECT DISTINCT(c.customer_id), c.session_id FROM `" . DB_PREFIX . "cart` c
            INNER JOIN `code_carrinhos` cc ON (c.session_id = cc.session_id)
            WHERE 
            c.customer_id > 0 AND 
            CHAR_LENGTH(cc.session_id)>= 5 AND 
            cc.envios < '" . (int) $confn3 . "' AND
            (NOW() - INTERVAL '" . (int) $confn4 . "' HOUR) >= cc.modificado AND
            (NOW() - INTERVAL '" . (int) $confn2 . "' DAY) < c.date_added
            ORDER BY c.date_added DESC
        ");

        //Depois do primeiro envio, consultando o carrinho
        $total = $this->db->query("
            SELECT COUNT(DISTINCT(c.customer_id)) as total FROM `" . DB_PREFIX . "cart` c
            INNER JOIN `code_carrinhos` cc ON (c.session_id = cc.session_id)
            WHERE 
            c.customer_id > 0 AND 
            CHAR_LENGTH(cc.session_id)>= 5 AND 
            cc.envios < '" . (int) $confn3 . "' AND
            (NOW() - INTERVAL '" . (int) $confn4 . "' HOUR) >= cc.modificado AND
            (NOW() - INTERVAL '" . (int) $confn2 . "' DAY) < c.date_added
            ORDER BY c.date_added DESC
        ");

        if (isset($total->row['total'])) {
            echo "<h3>Total Carrinhos Segundo Envio: " . $total->row['total'] . "</h3>";
        }

        $data = [
            'carrinhos'  => $carrinho->rows,
            'carrinhos2' => $carrinho2->rows,
        ];

        return $data;
    }

    /*
    1) Um prazo para expirar o carrinho do cliente, logo expirando não tem mais envio para ele, até que ele entre de novo na loja e coloque novos no carrinho
    2) Quantidade de envio para o cliente, depois de atingida, expira o carrinho
    3) Intervalo em horas para os envios
    4) Tempo em minutos para o primeiro envio
    Cron Job rodar em intervalos curtos
    Exemplo: 10 mins

    Marcar o 1 envio

    Criar links de chamadas
    Usar tags https://support.google.com/analytics/answer/1033867?hl=pt-BR

    tabala code_carrinhos
    session_id varchar(32) NOT NULL,
    cliente_id int NOT NULL,
    pedido_id int DEFAULT NULL,
    envios smallint DEFAULT 0,
    criado datetime NOT NULL,
    modificado datetime NOT NULL,

    Depois de cada envio criar ou atualizar
    session_id, envios e cliente_id

    //Recursos extras
    Tag na URL para que vai para o Checkout/Carrinho, tipo creturn=1 e salvar na sessão
    Depois quando fechado o pedido, verifica se existe o creturn na sessão, se existir marca na tabela code_carrinhos o id do pedido em pedido_id

    Google Analytics

    No futuro:
    Talvez por Modal, após X mins do último produto ser adicionado ou do carrinho criado
    Abrir um Modal com Desconto ou aviso

    Pode ser adicionado talvez pelo confirmar, depois de X minutos nele abrir o Modal, para o cliente não abandonar

     */

    /**
     *
     * @param integer $teste
     */
    public function index($teste)
    {
        $conf = $this->conf;
        $confn2 = trim((int) $conf->cn2);
        $confn3 = trim((int) $conf->cn3);
        $confn4 = trim((int) $conf->cn4);
        $confn5 = trim((int) $conf->cn5);

        $email = '';
        if (!empty($this->request->get['email'])) {
            $email = $this->request->get['email'];
        }

        //Limpa o Carrinho para carrinho sem cliente ou prazo expirado
        $this->limparCarrinho($teste, $confn2);

        //Um cliente, pode ter N produtos
        //Buscar primeiro só os clientes e depois os Produtos dele

        //Buscando só o cliente, primeiro envio
        //SELECT DISTINCT(customer_id) FROM `oc_cart` c LEFT JOIN `code_carrinhos` cc ON c.session_id = cc.session_id WHERE customer_id > 0 and cc.session_id IS NULL

        //Caso seja modo de teste
        if ($teste == 1 or $teste == 2) {
            $carrinho = $this->db->query("
                SELECT DISTINCT(c.customer_id), c.session_id FROM `" . DB_PREFIX . "cart` c
                WHERE
                c.customer_id > 0 ORDER BY customer_id DESC LIMIT 1
            ");
        } else {
            //Caso seja produção, primeiro envio, intervalo em minutos
            $carrinho = $this->db->query("
                SELECT DISTINCT(c.customer_id), c.session_id FROM `" . DB_PREFIX . "cart` c
                LEFT JOIN `code_carrinhos` cc ON (c.session_id = cc.session_id)
                WHERE
                c.customer_id > 0 AND 
                (cc.session_id IS NULL OR CHAR_LENGTH(cc.session_id) <= 4) AND
                (NOW() - INTERVAL '" . (int) $confn5 . "' MINUTE) >= c.date_added AND
                (NOW() - INTERVAL '" . (int) $confn2 . "' DAY) < c.date_added
                ORDER BY c.date_added DESC
            ");
        }

        //Verificando se tem cliente no primeiro envio
        if (!empty($carrinho->row['customer_id'])) {
            $carrinho = $carrinho->rows;
            $this->log->write('Carrinho retornado, primeiro envio ou modo de teste' . print_r($carrinho, true));
            $this->rodar($conf, $carrinho, $teste, $email);
        }

        //Depois do primeiro envio, consultando o carrinho
        $carrinho2 = $this->db->query("
            SELECT DISTINCT(c.customer_id), c.session_id FROM `" . DB_PREFIX . "cart` c
            INNER JOIN `code_carrinhos` cc ON (c.session_id = cc.session_id)
            WHERE 
            c.customer_id > 0 AND 
            CHAR_LENGTH(cc.session_id)>= 5 AND 
            cc.envios < '" . (int) $confn3 . "' AND
            (NOW() - INTERVAL '" . (int) $confn4 . "' HOUR) >= cc.modificado AND
            (NOW() - INTERVAL '" . (int) $confn2 . "' DAY) < c.date_added
            ORDER BY c.date_added DESC
        ");

        //Verificando se tem cliente no segundo envio
        if (isset($carrinho2->row['customer_id'])) {
            $carrinho2 = $carrinho2->rows;
            $this->log->write('Carrinho2 retornado, segundo ou demais envios' . print_r($carrinho2, true));
            $this->rodar($conf, $carrinho2, $teste, $email);
        }

        echo '<h1>Rodado o Recuperar Carrinho Premium com sucesso</h1>';
    }

    /**
     *
     * Limpa o Carrinho para carrinho sem cliente ou prazo expirado
     *
     * @param $teste
     * @param $prazoExpirar
     *
     * @return bool
     */
    public function limparCarrinho($teste, $prazoExpirar)
    {
        //--- DELETAR CARRINHO SEM CLIENTE ---
        //Deletando os Carrinhos no qual não tem mais cliente na tabela customer
        $deletar = $this->db->query("
            SELECT  DISTINCT(ca.customer_id) FROM `" . DB_PREFIX . "cart` ca
            WHERE  
            NOT EXISTS (SELECT c.customer_id FROM `" . DB_PREFIX . "customer` c WHERE ca.customer_id = c.customer_id)
            AND ca.customer_id > 0
        ");

        $deletar_ids = '';
        if (!empty($deletar->rows[0])) {
            foreach ($deletar->rows as $d) {
                $deletar_ids .= $d['customer_id'] . ',';
            }
        }

        if (!empty($deletar_ids)) {
            $deletar_ids = trim($deletar_ids, ',');
            $deletar_ids = '(' . $deletar_ids . ')';
            //(5,6,7,10,11,13,13,13,13,14,15,15)

            $this->log->write('Deletando carrinhos sem cliente cadastrado, IDs dos customer inexistentes: ' . $deletar_ids);

            $this->db->query("
                DELETE FROM " . DB_PREFIX . "cart
                WHERE
                customer_id IN " . $deletar_ids . "
            ");
        }
        //--- FIM DELETAR CARRINHO SEM CLIENTE ---

        //--- EXPIRAR CARRINHO ---

        //Apenas para produção, por isso teste igual 0
        if (!empty($teste)) {
            return true;
        }

        /*
         * NOW() = Agora
         * NOW() = 10/05/2020
         * prazoExpirar = dias mínimos para expirar
         * prazoExpirar = 8
         * data_added = Data que foi adicionado ao Carrinho
         * date_added = 01/05/2020
         *
         * 10/05/2020 - 8 = 02/05/2020 >= data_added
         */
        $deletar = $this->db->query("
            SELECT DISTINCT(customer_id) FROM `" . DB_PREFIX . "cart`
            WHERE
            (NOW() - INTERVAL '" . (int) $prazoExpirar . "' DAY) >= date_added
        ");

        $deletar_ids = '';
        if (!empty($deletar->rows[0])) {
            foreach ($deletar->rows as $d) {
                $deletar_ids .= $d['customer_id'] . ',';
            }
        }

        if (!empty($deletar_ids)) {
            $deletar_ids = trim($deletar_ids, ',');
            $deletar_ids = '(' . $deletar_ids . ')';
            //(5,6,7,10,11,13,13,13,13,14,15,15)

            $this->log->write('Deletando carrinhos expirados, IDs dos customer: ' . $deletar_ids);

            $this->db->query("
                DELETE FROM " . DB_PREFIX . "cart
                WHERE
                customer_id IN " . $deletar_ids . "
            ");
        }
        //--- FIM EXPIRAR CARRINHO ---
    }

    /**
     *
     * Roda os envios chamando a função enviar()
     *
     * @param $conf
     * @param $carrinho
     * @param $teste
     * @param $email
     */
    public function rodar($conf, $carrinho, $teste, $email)
    {
        $conf = [
            'cn1'  => $conf->cn1,
            'cn6'  => $conf->cn6,
            'cn7'  => $conf->cn7,
            'cnb1' => $conf->cnb1,
            'cnb2' => $conf->cnb2,
        ];

        $this->log->write('Dentro do rodar()');
        //$this->log->write('Dentro do rodar() com conf ' . print_r($conf, true));

        $cliente_enviado = [];
        foreach ($carrinho as $c) {
            $cliente_id = (int) $c['customer_id'];
            $sessao_id = $c['session_id'];

            //Buscando o cliente
            $cliente = $this->db->query("
                SELECT *  FROM `" . DB_PREFIX . "customer`
                WHERE customer_id = '" . $cliente_id . "' AND status = 1 LIMIT 1
            ");

            $this->log->write('Cliente retornado ' . print_r($cliente->row, true));

            if (!empty($cliente->row['customer_id']) && empty($cliente_enviado[$cliente_id])) {
                $cliente = $cliente->row;

                //Montagem dos Produtos
                $produto = $this->produtos($cliente_id, $sessao_id);

                $dados = [
                    'sessao_id' => $sessao_id,
                    'conf'      => $conf,
                    'teste'     => $teste,
                    'produto'   => $produto,
                    'cliente'   => [
                        'cliente_id' => $cliente_id,
                        'nome'       => $cliente['firstname'],
                        'sobrenome'  => $cliente['lastname'],
                        'email'      => $cliente['email'],
                    ],
                ];

                $this->log->write('Chamando o enviar()');
                //$this->log->write('Chamando o enviar com os dados ' . print_r($dados, true));
                $this->enviar($dados, $email);

                //Segurança extra, marca os clientes enviados para não deixar enviar de novo na mesma chamada
                $cliente_enviado[$cliente_id] = true;
            }
        }
    }

    /**
     *
     * Realiza os envios
     *
     * @param $dados
     * @param $email_teste
     *
     * @throws \Exception
     */
    public function enviar($dados, $email_teste)
    {
        $conf = $dados['conf'];
        $cliente = $dados['cliente'];
        $produto = $dados['produto'];
        $sessao_id = $dados['sessao_id'];
        $teste = $dados['teste'];
        $botao = $this->botao($conf['cnb1'], $conf['cnb2'], $sessao_id);

        $email = $cliente['email'];
        $assunto = str_replace(['{nome}', '{sobrenome}'], [$cliente['nome'], $cliente['sobrenome']], $conf['cn6']);

        $logo = $this->config->get('config_url') . 'image/' . $this->config->get('config_logo');
        $conteudo = str_replace(['{nome}', '{sobrenome}', '{produtos}', '{logo}', '{botao}'], [$cliente['nome'], $cliente['sobrenome'], $produto, $logo, $botao], $conf['cn7']);
        $conteudo = "<span style=' text-decoration: none;
                font-family:Open Sans,sans-serif; font-size:12px;'>$conteudo</span>";

        if ($teste == 1) {
            echo "<b>Assunto</b>: " . $assunto;
            echo "<br><br>";
            echo "<b>Conteúdo</b>: " . $conteudo;
            $this->log->write('Fim do teste sem enviar E-mail');
            exit();
        }

        if ($teste == 2) {
            $email = $this->config->get('config_email');

            $this->log->write('Modificado o E-mail para o teste com envio de E-mail');
            if (!empty($email_teste)) {
                $email = $email_teste;
            }
        } else {
            //Verificando se já existe no code_carrinhos
            $existe = $this->db->query("
              SELECT envios, code_carrinhos_id FROM code_carrinhos
              WHERE cliente_id  = '" . (int) $cliente['cliente_id'] . "' LIMIT 1
            ");

            //Caso já exista, ver se passou do limite
            if (!empty($existe->row['envios']) && (int) $existe->row['envios'] >= (int) $this->conf->cn3) {
                $this->log->write('No LIMITE de envios, email: ' . $email . ', verificação extra 2, envios feitos ' . $existe->row['envios']);
                return true;
            }
        }

        if (version_compare(VERSION, '3.0.0.0', '>=')) {
            $mail = new Mail($this->config->get('config_mail_engine'));
            $mail->parameter = $this->config->get('config_mail_parameter');
            $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
            $mail->smtp_username = $this->config->get('config_mail_smtp_username');
            $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
            $mail->smtp_port = $this->config->get('config_mail_smtp_port');
            $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
        } else if (version_compare(VERSION, '2.2.0.0', '>=')) {
            $mail = new Mail();
            $mail->protocol = $this->config->get('config_mail_protocol');
            $mail->parameter = $this->config->get('config_mail_parameter');
            $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
            $mail->smtp_username = $this->config->get('config_mail_smtp_username');
            $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
            $mail->smtp_port = $this->config->get('config_mail_smtp_port');
            $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
        } else if (version_compare(VERSION, '2.2.0.0', '<')) {
            $mail = new Mail($this->config->get('config_mail'));
        }

        if (!empty($conf['cn1'])) {
            $mail->setTags($conf['cn1']);
        }

        $mail->setTo($email);
        $mail->setFrom($this->config->get('config_email'));
        $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
        $mail->setSubject(html_entity_decode($assunto, ENT_QUOTES, 'UTF-8'));
        $mail->setHtml($conteudo);
        $mail->send();

        if ($teste == 2) {
            echo "E-mail de teste enviado para sua loja.<br>";
            echo "<b>E-mail</b>: " . $email;
            echo "<b>Assunto</b>: " . $assunto;
            echo "<br><br>";
            echo "<b>Conteúdo</b>: " . $conteudo;

            $this->log->write('Fim do teste com envio de E-mail');
            exit();
        }

        echo "<br><br>";
        echo "<b>E-mail</b>: " . $email;
        echo "<b>Assunto</b>: " . $assunto;
        //echo "<br><br>";
        //echo "<b>Conteúdo</b>: " . $conteudo;

        //--- ALTERANDO NA CODE_CARRINHOS ---
        /*
        code_carrinhos_id int NOT NULL AUTO_INCREMENT,
        session_id varchar(32) NOT NULL,
        cliente_id int NOT NULL,
        pedido_id int DEFAULT NULL,
        envios smallint DEFAULT 0,
        criado datetime NOT NULL,
        modificado datetime NOT NULL,
         */

        //$this->log->write('E-mail Enviado com Assunto: ' . $assunto . '<br> Conteúdo: ' . $conteudo . ' e E-mail ' . $email);
        $this->log->write('E-mail Enviado com ASSSUNTO: ' . $assunto . ' e E-MAIL:' . $email);

        //Verificando se já existe no code_carrinhos
        $existe = $this->db->query("
              SELECT code_carrinhos_id FROM code_carrinhos
              WHERE session_id  = '" . $this->db->escape($sessao_id) . "' LIMIT 1
            ");

        //Caso já exista, alterar a quantidade enviada, se não cria
        if (!empty($existe->row['code_carrinhos_id'])) {
            $this->db->query("
                    UPDATE code_carrinhos SET
                    envios = envios+1,
                    modificado = NOW()
                    WHERE code_carrinhos_id  = '" . $existe->row['code_carrinhos_id'] . "'
                ");
        } else {
            $this->db->query("
                    INSERT INTO code_carrinhos SET
                    session_id =  '" . $this->db->escape($sessao_id) . "',
                    cliente_id = '" . (int) $cliente['cliente_id'] . "',
                    envios = 1,
                    criado = NOW(),
                    modificado = NOW()
                ");
        }

        //--- FIM ALTERANDO NA CODE_CARRINHOS ---
        $this->log->write('Enviado com sucesso o E-mail e adicionado a tabela code_carrinhos');
    }

    //--- MONTAGEM DOS VISUAIS ---

    //Criando o Botão de chamada
    public function botao($b1, $b2, $sessao_id)
    {
        $botao = '';

        if (!empty($b1) and !empty($b2)) {
            if (strripos($b2, '?') == false) {
                $url = $b2 . '?creturn=' . $sessao_id;
            } else {
                $url = $b2 . '&creturn=' . $sessao_id;
            }

            $botao = "
                <a href='$url' style = '
                background-color: #2ecc71;
                border-radius: 5px;
                box-shadow: 0px 5px 0px 0px #15B358;
                color: #fff;
                text-decoration: none;
                text-align: center;
                padding: 15px 30px;
                font-size: 24px;
                font-weight: bold;
                margin: 0px;
                '>$b1</a>
            ";
        }

        return $botao;
    }

    //Criando a tabela com a listagem dos produtos
    public function produtos($cliente_id, $session_id)
    {
        $produtos = $this->getProducts($cliente_id, $session_id);
        $produto = '';

        if (!empty($produtos)) {
            $this->load->language('module/codemarket_carrinho');
            $text_imagem = $this->language->get('text_imagem');
            $text_nome = $this->language->get('text_nome');
            $text_preco = $this->language->get('text_preco');
            $text_total = $this->language->get('text_total');
            $text_quantidade = $this->language->get('text_quantidade');

            $produto = "
                <table style='
                    width: 100%;
                    max-width: 100%;
                    margin-bottom: 20px;
                    background-color: transparent;
                    border-spacing: 0;
                    border-collapse: collapse;
                    font-weight: 400;
                    color: #666;
                    font-size: 12px;
                    line-height: 20px;
                    border: 1px solid #ddd;
                    margin-bottom: 15px;
                    border: 1px solid #DDD;
                    ' cellpadding='4'
                    cellspacing='0'
                    border='0' width='100%' align='left'>
                
                <thead>
                <tr style='border: 1px solid #DDD;' >
                    <td class='image'><b>$text_imagem</b></td>
                    <td class='name'><b>$text_nome</b></td>
                    <td class='quantity'><b>$text_quantidade</b></td>
                    <td class='name'><b>$text_preco</b></td>
                    <td class='quantity'><b>$text_total</b></td>
                </tr>
                </thead>
                <tbody>
            ";

            $this->load->model('tool/image');
            foreach ($produtos as $product) {
                if ($product['image']) {
                    //Verificar versão Opencart
                    if (version_compare(VERSION, '3.0.0.0', '>=')) {
                        $image = $this->model_tool_image->resize($product['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height'));
                    } else if (version_compare(VERSION, '2.2.0.0', '>=')) {
                        $image = $this->model_tool_image->resize($product['image'], $this->config->get($this->config->get('config_theme') . '_image_cart_width'), $this->config->get($this->config->get('config_theme') . '_image_cart_height'));
                    } else {
                        $image = $this->model_tool_image->resize($product['image'], $this->config->get('config_image_cart_width'), $this->config->get('config_image_cart_height'));
                    }
                } else {
                    $image = '';
                }

                $options_names = '';
                foreach ($product['option'] as $option) {
                    if ($option['type'] != 'file') {
                        $value = $option['value'];
                    } else {
                        $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

                        if ($upload_info) {
                            $value = $upload_info['name'];
                        } else {
                            $value = '';
                        }
                    }
                    $options_names .= '<br><small>' . $option['name'] . ': ' . $value . '</small>';
                }

                //Até 80 caracteres para a descrição do Produto
                $name = mb_substr($product['name'] . $options_names, 0, 260, 'UTF-8');

                $href = $this->url->link('product/product', 'product_id=' . $product['product_id']);
                $q = $product['quantity'];
                $produto .= "
                    <tr style='border: 1px solid #DDD;' >
                    <td class='image'>
                ";

                if ($image) {
                    $produto .= "<a href='$href'><img src='$image' alt='$name' title='$name' /></a>";
                }

                $produto .= "
                    </td>
                    <td><a style='text-decoration: none;' href='$href'>$name</a>
                    <div>
                ";

                $preco = $this->currency->format($product['price'], $this->session->data['currency']);
                $total = $this->currency->format($product['total'], $this->session->data['currency']);

                $produto .= " 
                    </div></td>
                    <td class='quantity'>$q</td>
                    <td>$preco</td>
                    <td>$total</td>
                    </tr>
                ";
            }

            $produto .= "
                </tbody>
                </table>
            ";
        }

        return $produto;
    }

    //--- AUXILIARES ---

    //Lista dos produtos
    public function getProducts($customer_id, $session_id)
    {
        $product_data = [];

        $cart_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "cart WHERE customer_id = '" . (int) $customer_id . "' AND session_id = '" . $this->db->escape($session_id) . "'");

        foreach ($cart_query->rows as $cart) {
            $stock = true;

            $product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_store p2s LEFT JOIN " . DB_PREFIX . "product p ON (p2s.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p2s.store_id = '" . (int) $this->config->get('config_store_id') . "' AND p2s.product_id = '" . (int) $cart['product_id'] . "' AND pd.language_id = '" . (int) $this->config->get('config_language_id') . "' AND p.date_available <= NOW() AND p.status = '1'");

            if ($product_query->num_rows && ($cart['quantity'] > 0)) {
                $option_price = 0;
                $option_points = 0;
                $option_weight = 0;

                $option_data = [];

                foreach (json_decode($cart['option']) as $product_option_id => $value) {
                    $option_query = $this->db->query("SELECT po.product_option_id, po.option_id, od.name, o.type FROM " . DB_PREFIX . "product_option po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE po.product_option_id = '" . (int) $product_option_id . "' AND po.product_id = '" . (int) $cart['product_id'] . "' AND od.language_id = '" . (int) $this->config->get('config_language_id') . "'");

                    if ($option_query->num_rows) {
                        if ($option_query->row['type'] == 'select' || $option_query->row['type'] == 'radio' || $option_query->row['type'] == 'image') {
                            $option_value_query = $this->db->query("SELECT pov.option_value_id, ovd.name, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = '" . (int) $value . "' AND pov.product_option_id = '" . (int) $product_option_id . "' AND ovd.language_id = '" . (int) $this->config->get('config_language_id') . "'");

                            if ($option_value_query->num_rows) {
                                if ($option_value_query->row['price_prefix'] == '+') {
                                    $option_price += $option_value_query->row['price'];
                                } else if ($option_value_query->row['price_prefix'] == '-') {
                                    $option_price -= $option_value_query->row['price'];
                                }

                                if ($option_value_query->row['points_prefix'] == '+') {
                                    $option_points += $option_value_query->row['points'];
                                } else if ($option_value_query->row['points_prefix'] == '-') {
                                    $option_points -= $option_value_query->row['points'];
                                }

                                if ($option_value_query->row['weight_prefix'] == '+') {
                                    $option_weight += $option_value_query->row['weight'];
                                } else if ($option_value_query->row['weight_prefix'] == '-') {
                                    $option_weight -= $option_value_query->row['weight'];
                                }

                                if ($option_value_query->row['subtract'] && (!$option_value_query->row['quantity'] || ($option_value_query->row['quantity'] < $cart['quantity']))) {
                                    $stock = false;
                                }

                                $option_data[] = [
                                    'product_option_id'       => $product_option_id,
                                    'product_option_value_id' => $value,
                                    'option_id'               => $option_query->row['option_id'],
                                    'option_value_id'         => $option_value_query->row['option_value_id'],
                                    'name'                    => $option_query->row['name'],
                                    'value'                   => $option_value_query->row['name'],
                                    'type'                    => $option_query->row['type'],
                                    'quantity'                => $option_value_query->row['quantity'],
                                    'subtract'                => $option_value_query->row['subtract'],
                                    'price'                   => $option_value_query->row['price'],
                                    'price_prefix'            => $option_value_query->row['price_prefix'],
                                    'points'                  => $option_value_query->row['points'],
                                    'points_prefix'           => $option_value_query->row['points_prefix'],
                                    'weight'                  => $option_value_query->row['weight'],
                                    'weight_prefix'           => $option_value_query->row['weight_prefix'],
                                ];
                            }
                        } else if ($option_query->row['type'] == 'checkbox' && is_array($value)) {
                            foreach ($value as $product_option_value_id) {
                                $option_value_query = $this->db->query("SELECT pov.option_value_id, ovd.name, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = '" . (int) $product_option_value_id . "' AND pov.product_option_id = '" . (int) $product_option_id . "' AND ovd.language_id = '" . (int) $this->config->get('config_language_id') . "'");

                                if ($option_value_query->num_rows) {
                                    if ($option_value_query->row['price_prefix'] == '+') {
                                        $option_price += $option_value_query->row['price'];
                                    } else if ($option_value_query->row['price_prefix'] == '-') {
                                        $option_price -= $option_value_query->row['price'];
                                    }

                                    if ($option_value_query->row['points_prefix'] == '+') {
                                        $option_points += $option_value_query->row['points'];
                                    } else if ($option_value_query->row['points_prefix'] == '-') {
                                        $option_points -= $option_value_query->row['points'];
                                    }

                                    if ($option_value_query->row['weight_prefix'] == '+') {
                                        $option_weight += $option_value_query->row['weight'];
                                    } else if ($option_value_query->row['weight_prefix'] == '-') {
                                        $option_weight -= $option_value_query->row['weight'];
                                    }

                                    if ($option_value_query->row['subtract'] && (!$option_value_query->row['quantity'] || ($option_value_query->row['quantity'] < $cart['quantity']))) {
                                        $stock = false;
                                    }

                                    $option_data[] = [
                                        'product_option_id'       => $product_option_id,
                                        'product_option_value_id' => $product_option_value_id,
                                        'option_id'               => $option_query->row['option_id'],
                                        'option_value_id'         => $option_value_query->row['option_value_id'],
                                        'name'                    => $option_query->row['name'],
                                        'value'                   => $option_value_query->row['name'],
                                        'type'                    => $option_query->row['type'],
                                        'quantity'                => $option_value_query->row['quantity'],
                                        'subtract'                => $option_value_query->row['subtract'],
                                        'price'                   => $option_value_query->row['price'],
                                        'price_prefix'            => $option_value_query->row['price_prefix'],
                                        'points'                  => $option_value_query->row['points'],
                                        'points_prefix'           => $option_value_query->row['points_prefix'],
                                        'weight'                  => $option_value_query->row['weight'],
                                        'weight_prefix'           => $option_value_query->row['weight_prefix'],
                                    ];
                                }
                            }
                        } else if ($option_query->row['type'] == 'text' || $option_query->row['type'] == 'textarea' || $option_query->row['type'] == 'file' || $option_query->row['type'] == 'date' || $option_query->row['type'] == 'datetime' || $option_query->row['type'] == 'time') {
                            $option_data[] = [
                                'product_option_id'       => $product_option_id,
                                'product_option_value_id' => '',
                                'option_id'               => $option_query->row['option_id'],
                                'option_value_id'         => '',
                                'name'                    => $option_query->row['name'],
                                'value'                   => $value,
                                'type'                    => $option_query->row['type'],
                                'quantity'                => '',
                                'subtract'                => '',
                                'price'                   => '',
                                'price_prefix'            => '',
                                'points'                  => '',
                                'points_prefix'           => '',
                                'weight'                  => '',
                                'weight_prefix'           => '',
                            ];
                        }
                    }
                }

                $price = $product_query->row['price'];

                // Product Discounts
                $discount_quantity = 0;

                foreach ($cart_query->rows as $cart_2) {
                    if ($cart_2['product_id'] == $cart['product_id']) {
                        $discount_quantity += $cart_2['quantity'];
                    }
                }

                $product_discount_query = $this->db->query("SELECT price FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int) $cart['product_id'] . "' AND customer_group_id = '" . (int) $this->config->get('config_customer_group_id') . "' AND quantity <= '" . (int) $discount_quantity . "' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY quantity DESC, priority ASC, price ASC LIMIT 1");

                if ($product_discount_query->num_rows) {
                    $price = $product_discount_query->row['price'];
                }

                // Product Specials
                $product_special_query = $this->db->query("SELECT price FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int) $cart['product_id'] . "' AND customer_group_id = '" . (int) $this->config->get('config_customer_group_id') . "' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY priority ASC, price ASC LIMIT 1");

                if ($product_special_query->num_rows) {
                    $price = $product_special_query->row['price'];
                }

                // Reward Points
                $product_reward_query = $this->db->query("SELECT points FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int) $cart['product_id'] . "' AND customer_group_id = '" . (int) $this->config->get('config_customer_group_id') . "'");

                if ($product_reward_query->num_rows) {
                    $reward = $product_reward_query->row['points'];
                } else {
                    $reward = 0;
                }

                // Downloads
                $download_data = [];

                $download_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_download p2d LEFT JOIN " . DB_PREFIX . "download d ON (p2d.download_id = d.download_id) LEFT JOIN " . DB_PREFIX . "download_description dd ON (d.download_id = dd.download_id) WHERE p2d.product_id = '" . (int) $cart['product_id'] . "' AND dd.language_id = '" . (int) $this->config->get('config_language_id') . "'");

                foreach ($download_query->rows as $download) {
                    $download_data[] = [
                        'download_id' => $download['download_id'],
                        'name'        => $download['name'],
                        'filename'    => $download['filename'],
                        'mask'        => $download['mask'],
                    ];
                }

                // Stock
                if (!$product_query->row['quantity'] || ($product_query->row['quantity'] < $cart['quantity'])) {
                    $stock = false;
                }

                $recurring_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "recurring r LEFT JOIN " . DB_PREFIX . "product_recurring pr ON (r.recurring_id = pr.recurring_id) LEFT JOIN " . DB_PREFIX . "recurring_description rd ON (r.recurring_id = rd.recurring_id) WHERE r.recurring_id = '" . (int) $cart['recurring_id'] . "' AND pr.product_id = '" . (int) $cart['product_id'] . "' AND rd.language_id = " . (int) $this->config->get('config_language_id') . " AND r.status = 1 AND pr.customer_group_id = '" . (int) $this->config->get('config_customer_group_id') . "'");

                if ($recurring_query->num_rows) {
                    $recurring = [
                        'recurring_id'    => $cart['recurring_id'],
                        'name'            => $recurring_query->row['name'],
                        'frequency'       => $recurring_query->row['frequency'],
                        'price'           => $recurring_query->row['price'],
                        'cycle'           => $recurring_query->row['cycle'],
                        'duration'        => $recurring_query->row['duration'],
                        'trial'           => $recurring_query->row['trial_status'],
                        'trial_frequency' => $recurring_query->row['trial_frequency'],
                        'trial_price'     => $recurring_query->row['trial_price'],
                        'trial_cycle'     => $recurring_query->row['trial_cycle'],
                        'trial_duration'  => $recurring_query->row['trial_duration'],
                    ];
                } else {
                    $recurring = false;
                }

                $product_data[] = [
                    'cart_id'         => $cart['cart_id'],
                    'product_id'      => $product_query->row['product_id'],
                    'name'            => $product_query->row['name'],
                    'model'           => $product_query->row['model'],
                    'shipping'        => $product_query->row['shipping'],
                    'image'           => $product_query->row['image'],
                    'option'          => $option_data,
                    'download'        => $download_data,
                    'quantity'        => $cart['quantity'],
                    'minimum'         => $product_query->row['minimum'],
                    'subtract'        => $product_query->row['subtract'],
                    'stock'           => $stock,
                    'price'           => ($price + $option_price),
                    'total'           => ($price + $option_price) * $cart['quantity'],
                    'reward'          => $reward * $cart['quantity'],
                    'points'          => ($product_query->row['points'] ? ($product_query->row['points'] + $option_points) * $cart['quantity'] : 0),
                    'tax_class_id'    => $product_query->row['tax_class_id'],
                    'weight'          => ($product_query->row['weight'] + $option_weight) * $cart['quantity'],
                    'weight_class_id' => $product_query->row['weight_class_id'],
                    'length'          => $product_query->row['length'],
                    'width'           => $product_query->row['width'],
                    'height'          => $product_query->row['height'],
                    'length_class_id' => $product_query->row['length_class_id'],
                    'recurring'       => $recurring,
                ];
            }
        }

        return $product_data;
    }
}
