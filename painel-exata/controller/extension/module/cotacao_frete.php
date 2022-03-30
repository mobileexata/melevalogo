<?php
class ControllerExtensionModuleCotacaoFrete extends Controller {
    private $error = array();

    public function index() {
        $data = $this->load->language('extension/module/cotacao_frete');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_cotacao_frete', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            if (isset($this->request->post['save_stay']) && ($this->request->post['save_stay'] == '1')) {
                $this->response->redirect($this->url->link('extension/module/cotacao_frete', 'user_token=' . $this->session->data['user_token'], true));
            } else {
                $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
            }
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['user_token'] = $this->session->data['user_token'];

        $erros = array(
            'warning'
        );

        foreach ($erros as $erro) {
            if (isset($this->error[$erro])) {
                $data['error_'.$erro] = $this->error[$erro];
            } else {
                $data['error_'.$erro] = '';
            }
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/cotacao_frete', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/module/cotacao_frete', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        $data['versao'] = '2.5.1';

        $codigo_css = <<<'EOT'
#formulario-cotacao {}
#resultado-cotacao {
  border: 1px dashed #CCCCCC; margin-bottom: 10px;
}
div.cotacao-area {
  margin-bottom: 5px;
}
div.cotacao-titulo {
  padding: 2px 5px;
  font-size: 13px;
  font-weight: 600;
  background-color: #ECECEC;
}
div.cotacao-opcao {
  line-height: 20px;
}
EOT;

        $campos = array(
            'languages' => array(0),
            'stores' => array(0),
            'exibir_pais' => '',
            'country_id' => '',
            'exibir_estado' => '',
            'zone_id' => '',
            'exibir_numero' => '',
            'exibir_documento' => '',
            'codigo_manual' => '',
            'container_opcoes' => '#product',
            'codigo_css' => $codigo_css,
            'status' => ''
        );

        foreach ($campos as $campo => $valor) {
            if (!empty($valor)) {
                if (isset($this->request->post['module_cotacao_frete_'.$campo])) {
                    $data['module_cotacao_frete_'.$campo] = $this->request->post['module_cotacao_frete_'.$campo];
                } else if ($this->config->get('module_cotacao_frete_'.$campo)) {
                    $data['module_cotacao_frete_'.$campo] = $this->config->get('module_cotacao_frete_'.$campo);
                } else {
                    $data['module_cotacao_frete_'.$campo] = $valor;
                }
            } else {
                if (isset($this->request->post['module_cotacao_frete_'.$campo])) {
                    $data['module_cotacao_frete_'.$campo] = $this->request->post['module_cotacao_frete_'.$campo];
                } else {
                    $data['module_cotacao_frete_'.$campo] = $this->config->get('module_cotacao_frete_'.$campo);
                }
            }
        }

        $data['language_default'] = $this->config->get('config_language');
        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();

        $data['store_default'] = $this->config->get('config_name');
        $this->load->model('setting/store');
        $data['stores'] = $this->model_setting_store->getStores();

        $this->load->model('localisation/country');
        $data['countries'] = $this->model_localisation_country->getCountries();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/cotacao_frete', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/cotacao_frete')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ($this->error && !isset($this->error['warning'])) {
            $this->error['warning'] = $this->language->get('error_warning');
        }

        return !$this->error;
    }
}