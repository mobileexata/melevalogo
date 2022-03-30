<?php
require_once DIR_SYSTEM . 'library/rede-rest/engine.php';

class ControllerExtensionModuleRedeRest extends Controller {
    use RedeRestEngine;

    const TYPE = 'module_';
    const NAME = 'rede_rest';
    const CODE = self::TYPE . self::NAME;
    const EXTENSIONS = 'marketplace/extension';
    const EXTENSION = 'extension/module/' . self::NAME;
    const MODEL = 'model_extension_module_' . self::NAME;
    const PERMISSION = 'extension/' . self::NAME;

    private $error = array();

    public function index() {
        $data = $this->load->language(self::EXTENSION);

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting(self::CODE, $this->format_fields());

            $this->update();

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link(self::EXTENSIONS, 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['requisitos'] = $this->getRedeRestRequirements();

        $data['version'] = $this->getRedeRestVersion();

        $data['user_token'] = $this->session->data['user_token'];

        $erros = array(
            'warning',
            'chave',
            'filiacao',
            'token',
            'url_key'
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
            'href' => $this->url->link(self::EXTENSIONS, 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link(self::EXTENSION, 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link(self::EXTENSION, 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link(self::EXTENSIONS, 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        $url_key = substr(sha1(time()), -30);

        $campos = array(
            'chave' => array(0),
            'filiacao' => '',
            'token' => '',
            'sandbox' => '1',
            'debug' => '1',
            'url_key' => $url_key,
            'status' => '1'
        );

        foreach ($campos as $campo => $valor) {
            if (isset($this->request->post[$campo])) {
                $data[$campo] = $this->request->post[$campo];
            } else {
                $data[$campo] = !is_null($this->config->get(self::CODE . '_' . $campo)) ? $this->config->get(self::CODE . '_' . $campo) : $valor;
            }
        }

        $data['stores_data'][] = array(
            'store_id' => 0,
            'name' => $this->config->get('config_name')
        );

        $this->load->model('setting/store');
        $stores = $this->model_setting_store->getStores();

        foreach ($stores as $store) {
            $data['stores_data'][] = array(
                'store_id' => $store['store_id'],
                'name' => $store['name']
            );
        }

        $data['url_notificacao'] = HTTPS_CATALOG . 'index.php?route=' .  self::EXTENSION . '/notificacao&key=';

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view(self::EXTENSION, $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', self::EXTENSION)) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ($this->getRedeRestRequirements()) {
            $this->error['warning'] = $this->language->get('error_requisitos');
        }

        $chave = array_filter($this->request->post['chave']);
        if (empty($chave)) {
            $this->error['chave'] = $this->language->get('error_chave');
        }

        $erros = array(
            'filiacao',
            'token',
            'url_key'
        );

        foreach ($erros as $erro) {
            if (!($this->request->post[$erro])) {
                $this->error[$erro] = $this->language->get('error_obrigatorio');
            }
        }

        if ($this->error && !isset($this->error['warning'])) {
            $this->error['warning'] = $this->language->get('error_warning');
        }

        return !$this->error;
    }

    public function install() {
        $this->load->model(self::EXTENSION);
        $this->{self::MODEL}->update();
    }

    public function uninstall() {
        /*
        $this->load->model(self::EXTENSION);
        $this->{self::MODEL}->uninstall();
        */
        $this->load->model('user/user_group');
        $this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', self::PERMISSION . '/transaction');
        $this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', self::PERMISSION . '/transaction');
        $this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', self::PERMISSION . '/log');
        $this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', self::PERMISSION . '/log');
    }

    public function update() {
        $this->load->model(self::EXTENSION);
        $this->{self::MODEL}->update();

        if (!$this->user->hasPermission('modify', self::PERMISSION . '/transaction')) {
            $this->load->model('user/user_group');
            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', self::PERMISSION . '/transaction');
            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', self::PERMISSION . '/transaction');
            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', self::PERMISSION . '/log');
            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', self::PERMISSION . '/log');
        }
    }

    private function format_fields() {
        $valores = array_values($this->request->post);

        $chaves = array_map(function($field) {
            return self::CODE . '_' . $field;
        }, array_keys($this->request->post));

        return array_combine($chaves, $valores);
    }
}
