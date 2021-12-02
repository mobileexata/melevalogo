<?php
class ControllerExtensionShippingCorreios extends Controller {
    const EXTENSION = 'shipping_correios';

    private $error = array();

    public function index() {
        $data = $this->load->language('extension/shipping/correios');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting(self::EXTENSION, $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            if (isset($this->request->post['save_stay']) && ($this->request->post['save_stay'] = 1)) {
                $this->response->redirect($this->url->link('extension/shipping/correios', 'user_token=' . $this->session->data['user_token'], true));
            } else {
                $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
            }
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $erros = array(
            'warning',
            'chave',
            'stores',
            'customer_groups',
            'weight_class_id',
            'length_class_id',
            'cep_origem',
            'limite_manuseio',
            'taxa_manuseio',
            'servicos',
            'promocoes',
            'restricoes',
            'bloqueios',
            'titulo'
        );

        foreach ($erros as $erro) {
            if (isset($this->error[$erro])) {
                $data['error_' . $erro] = $this->error[$erro];
            } else {
                $data['error_' . $erro] = '';
            }
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/shipping/correios', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/shipping/correios', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);

        include_once(DIR_SYSTEM . 'library/correios/versao.php');

        $lib = DIR_SYSTEM . 'library/correios/correios.php';
        if (is_file($lib)) {
            if (!is_readable($lib)) {
                chmod($lib, 0644);
            }
        }

        $servicos = array();

        $servicos[] = array(
            'codigo' => '04510',
            'descricao' => 'PAC',
            'mao_propria' => 'n',
            'aviso_recebimento' => 'n',
            'valor_declarado' => 'n',
            'minimo_declarado' => '20.50',
            'maximo_declarado' => '3000.00',
            'comprimento_minimo' => '16',
            'comprimento_maximo' => '100',
            'largura_minima' => '11',
            'largura_maxima' => '100',
            'altura_minima' => '2',
            'altura_maxima' => '100',
            'peso_minimo' => '0.300',
            'peso_maximo' => '30.00',
            'soma_maxima' => '200'
        );

        $servicos[] = array(
            'codigo' => '04014',
            'descricao' => 'SEDEX',
            'mao_propria' => 'n',
            'aviso_recebimento' => 'n',
            'valor_declarado' => 'n',
            'minimo_declarado' => '20.50',
            'maximo_declarado' => '10000.00',
            'comprimento_minimo' => '16',
            'comprimento_maximo' => '100',
            'largura_minima' => '11',
            'largura_maxima' => '100',
            'altura_minima' => '2',
            'altura_maxima' => '100',
            'peso_minimo' => '0.300',
            'peso_maximo' => '30.00',
            'soma_maxima' => '200'
        );

        $campos = array(
            'chave' => array(0),
            'stores' => array(0),
            'customer_groups' => array(0),
            'total' => '',
            'peso_minimo' => '',
            'peso_maximo' => '',
            'weight_class_id' => '',
            'length_class_id' => '',
            'tax_class_id' => '',
            'geo_zone_id' => '',
            'status' => '',
            'sort_order' => '',
            'codigo_administrativo' => '',
            'senha_acesso' => '',
            'cep_origem' => '',
            'prazo_adicional' => '',
            'formato' => '',
            'limite_manuseio' => '70',
            'taxa_manuseio' => '79.00',
            'tipo_custo' => '',
            'custo_adicional' => '',
            'faixas_ceps' => '',
            'debug' => '',
            'servicos' => $servicos,
            'promocoes' => array(),
            'restricoes' => array(),
            'bloqueios' => array(),
            'titulo' => '',
            'imagem' => ''
        );

        foreach ($campos as $campo => $valor) {
            if (isset($this->request->post[self::EXTENSION . '_' . $campo])) {
                $data[self::EXTENSION . '_' . $campo] = $this->request->post[self::EXTENSION . '_' . $campo];
            } else {
                $valor = ($this->config->get(self::EXTENSION . '_' . $campo)) ? $this->config->get(self::EXTENSION . '_' . $campo) : $valor;
                $data[self::EXTENSION . '_' . $campo] = $valor;
            }
        }

        $data['store_default'] = $this->config->get('config_name');
        $this->load->model('setting/store');
        $data['stores'] = $this->model_setting_store->getStores();

        $this->load->model('customer/customer_group');
        $data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

        $this->load->model('localisation/weight_class');
        $data['weight_classes'] = $this->model_localisation_weight_class->getWeightClasses();

        $this->load->model('localisation/length_class');
        $data['length_classes'] = $this->model_localisation_length_class->getLengthClasses();

        $this->load->model('localisation/tax_class');
        $data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        $data['formatos'] = array(
            '1' => $this->language->get('text_caixa_pacote'),
            //'2' => $this->language->get('text_rolo_prisma'),
            //'3' => $this->language->get('text_envelope')
        );

        $data['descontos'] = array(
            'P' => $this->language->get('text_percentual'),
            'F' => $this->language->get('text_fixo'),
            'U' => $this->language->get('text_unico')
        );

        $data['categories'] = array();

        $this->load->model('catalog/category');
        $categories = $this->model_catalog_category->getCategories(array('sort' => 'name', 'order' => 'ASC'));

        foreach ($categories as $category) {
            $data['categories'][] = array(
                'category_id' => $category['category_id'],
                'name' => str_replace("'", "&#39;", $category['name'])
            );
        }

        $this->load->model('tool/image');
        if (isset($this->request->post[self::EXTENSION . '_imagem']) && is_file(DIR_IMAGE . $this->request->post[self::EXTENSION . '_imagem'])) {
            $data['thumb'] = $this->model_tool_image->resize($this->request->post[self::EXTENSION . '_imagem'], 100, 100);
        } elseif (is_file(DIR_IMAGE . $this->config->get(self::EXTENSION . '_imagem'))) {
            $data['thumb'] = $this->model_tool_image->resize($this->config->get(self::EXTENSION . '_imagem'), 100, 100);
        } else {
            $data['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
        }
        $data['no_image'] = $this->model_tool_image->resize('no_image.png', 100, 100);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/shipping/correios', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/shipping/correios')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        $chave = array_filter($this->request->post[self::EXTENSION . '_chave']);
        if (empty($chave)) {
            $this->error['chave'] = $this->language->get('error_chave');
        }

        if (empty($this->request->post[self::EXTENSION . '_stores'])) {
            $this->error['stores'] = $this->language->get('error_stores');
        }

        if (empty($this->request->post[self::EXTENSION . '_customer_groups'])) {
            $this->error['customer_groups'] = $this->language->get('error_customer_groups');
        }

        if (strlen(preg_replace('/[^0-9]/', '', $this->request->post[self::EXTENSION . '_cep_origem'])) != 8) {
            $this->error['cep_origem'] = $this->language->get('error_cep_origem');
        }

        if ($this->request->post[self::EXTENSION . '_limite_manuseio'] <= 0) {
            $this->error['limite_manuseio'] = $this->language->get('error_limite_manuseio');
        }

        if ($this->request->post[self::EXTENSION . '_taxa_manuseio'] <= 0) {
            $this->error['taxa_manuseio'] = $this->language->get('error_taxa_manuseio');
        }

        if (empty($this->request->post[self::EXTENSION . '_servicos'])) {
            $this->error['servicos'] = $this->language->get('error_servicos');
        }

        if (!empty($this->request->post[self::EXTENSION . '_servicos'])) {
            $colunas = array(
                'codigo',
                'descricao',
                'minimo_declarado',
                'maximo_declarado',
                'comprimento_minimo',
                'comprimento_maximo',
                'largura_minima',
                'largura_maxima',
                'altura_minima',
                'altura_maxima',
                'peso_minimo',
                'peso_maximo',
                'soma_maxima'
            );

            foreach ($this->request->post[self::EXTENSION . '_servicos'] as $servico) {
                foreach ($colunas as $coluna) {
                    if (utf8_strlen($servico[$coluna]) == 0) {
                        $this->error['servicos'] = $this->language->get('error_servico_invalido');
                        break 2;
                    }
                }
            }
        }

        if (!empty($this->request->post[self::EXTENSION . '_promocoes'])) {
            $colunas = array(
                'codigo',
                'descricao',
                'cep_inicial',
                'cep_final',
                'desconto'
            );

            foreach ($this->request->post[self::EXTENSION . '_promocoes'] as $promocao) {
                foreach ($colunas as $coluna) {
                    if (utf8_strlen($promocao[$coluna]) == 0) {
                        $this->error['promocoes'] = $this->language->get('error_promocao_invalida');
                        break 2;
                    }
                }
            }
        }

        if (!empty($this->request->post[self::EXTENSION . '_restricoes'])) {
            $colunas = array(
                'codigo',
                'descricao',
                'cep_inicial',
                'cep_final'
            );

            foreach ($this->request->post[self::EXTENSION . '_restricoes'] as $restricao) {
                foreach ($colunas as $coluna) {
                    if (utf8_strlen($restricao[$coluna]) == 0) {
                        $this->error['restricoes'] = $this->language->get('error_restricao_invalida');
                        break 2;
                    }
                }
            }
        }

        if (!empty($this->request->post[self::EXTENSION . '_bloqueios'])) {
            $colunas = array(
                'codigo',
                'category_id'
            );

            foreach ($this->request->post[self::EXTENSION . '_bloqueios'] as $bloqueio) {
                foreach ($colunas as $coluna) {
                    if (utf8_strlen($bloqueio[$coluna]) == 0) {
                        $this->error['bloqueios'] = $this->language->get('error_bloqueio_invalido');
                        break 2;
                    }
                }
            }
        }

        $erros = array(
            'weight_class_id',
            'length_class_id',
            'titulo'
        );

        foreach ($erros as $erro) {
            if (!(trim($this->request->post[self::EXTENSION. '_' . $erro]))) {
                $this->error[$erro] = $this->language->get('error_obrigatorio');
            }
        }

        if ($this->error && !isset($this->error['warning'])) {
            $this->error['warning'] = $this->language->get('error_warning');
        }

        return !$this->error;
    }
}