<?php
class ControllerExtensionModuleRedeRest extends Controller {
    const TYPE = 'module_';
    const NAME = 'rede_rest';
    const CODE = self::TYPE . self::NAME;
    const EXTENSION = 'extension/module/' . self::NAME;
    const MODEL = 'model_extension_module_' . self::NAME;
    const PAYMENT_CODE = 'payment_rede_rest';

    public function notificacao() {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->validate()) {
            exit();
        }

        $input = file_get_contents('php://input');

        $json = json_decode($input);

        if (json_last_error() !== JSON_ERROR_NONE) {
            exit();
        }

        if (empty($json)) {
            exit();
        }

        if (!isset($json->tid) || !isset($json->type)) {
            exit();
        }

        $tid = $json->tid;
        $type = $json->type;

        if (empty($tid) || empty($type)) {
            exit();
        }

        if ($type == 'refund') {
            $this->load->model(self::EXTENSION);
            $transaction = $this->{self::MODEL}->getTransaction($tid);

            if ($transaction) {
                $this->update($transaction);
            }
        }
    }

    private function validate() {
        if (!$this->config->get(self::CODE . '_status')) {
            return false;
        }

        if (!isset($this->request->get['key'])) {
            return false;
        }

        if ($this->config->get(self::CODE . '_url_key') != $this->request->get['key']) {
            return false;
        }

        return true;
    }

    private function update($transaction) {
        $chave = $this->config->get(self::CODE . '_chave');
        $dados['chave'] = $chave[$transaction['store_id']];
        $dados['sandbox'] = $this->config->get(self::CODE . '_sandbox');
        $dados['debug'] = $this->config->get(self::CODE . '_debug');
        $dados['filiacao'] = $this->config->get(self::CODE . '_filiacao');
        $dados['token'] = $this->config->get(self::CODE . '_token');
        $dados['tid'] = $transaction['tid'];

        try {
            require_once(DIR_SYSTEM . 'library/rede-rest/rede.php');
            $rede = new Rede();
            $rede->setParametros($dados);
            $resposta = $rede->getTransaction();

        } catch (Exception $e) {
            return false;
        }

        if (empty($resposta)) {
            return false;
        }

        $this->load->language(self::EXTENSION);

        $status = '';
        $comment = '';
        $order_status_id = 0;
        $canceled_date = '';
        $canceled_total = '';
        $type = $transaction['type'];

        if (isset($resposta->refunds)) {
            foreach ($resposta->refunds as $refund) {
                switch ($refund->status) {
                    case 'Done': /* cancelamento concluído */
                        $canceled_date = $refund->refundDateTime;
                        $canceled_total = $refund->amount / 100;

                        $status = 'cancelada';
                        $comment = $this->language->get('text_cancelado');
                        $order_status_id = $this->config->get(self::PAYMENT_CODE . '_' . $type . '_situacao_cancelada_id');

                        break;
                    case 'Denied': /* cancelamento negado */
                        $status = 'negada';

                        break;
                    case 'Processing': /* processando cancelamento */
                        $status = 'processando';

                        break;
                }
            }
        } elseif (
            isset($resposta->authorization)
            || isset($resposta->capture)
        ) {
            switch ($resposta->authorization->status) {
                case 'Pending': /* autorizada */
                    $status = 'autorizada';
                    $comment = $this->language->get('text_autorizado');
                    $order_status_id = $this->config->get(self::PAYMENT_CODE . '_' . $type . '_situacao_autorizada_id');

                    break;
                case 'Approved': /* capturada */
                    $status = 'capturada';
                    $comment = $this->language->get('text_capturado');
                    $order_status_id = $this->config->get(self::PAYMENT_CODE . '_' . $type . '_situacao_capturada_id');

                    break;
                case 'Denied': /* cancelamento negado */
                    $status = 'negada';

                    break;
                case 'Canceled': /* cancelamento confirmado */
                    $status = 'cancelada';
                    $comment = $this->language->get('text_cancelado');
                    $order_status_id = $this->config->get(self::PAYMENT_CODE . '_' . $type . '_situacao_cancelada_id');

                    break;
            }
        }

        if (!empty($status)) {
            $card_brand = isset($resposta->authorization->brand->name) ? $resposta->authorization->brand->name : '';
            $card_holder = isset($resposta->authorization->brand->cardHolderName) ? $resposta->authorization->brand->cardHolderName : $resposta->authorization->cardHolderName;
            $authorization_code = isset($resposta->authorization->brand->authorizationCode) ? $resposta->authorization->brand->authorizationCode : $resposta->authorization->authorizationCode;
            $captured_date = isset($resposta->capture->dateTime) ? $resposta->capture->dateTime : '';
            $captured_total = isset($resposta->capture->amount) ? $resposta->capture->amount / 100 : '';

            $dados = array(
                'order_rede_rest_id' => $transaction['order_rede_rest_id'],
                'status' => $status,
                'card_brand' => $card_brand,
                'card_bin' => $resposta->authorization->cardBin,
                'card_end' => $resposta->authorization->last4,
                'card_holder' => $card_holder,
                'tid' => $resposta->authorization->tid,
                'nsu' => $resposta->authorization->nsu,
                'authorization_code' => $authorization_code,
                'authorized_date' => $resposta->authorization->dateTime,
                'authorized_total' => $resposta->authorization->amount / 100,
                'captured_date' => $captured_date,
                'captured_total' => $captured_total,
                'canceled_date' => $canceled_date,
                'canceled_total' => $canceled_total,
                'json_last_response' => json_encode($resposta)
            );

            $this->load->model(self::EXTENSION);
            $this->{self::MODEL}->updateTransaction($dados);

            if (
                $order_status_id > 0
                && $transaction['order_status_id'] != $order_status_id
            ) {
                $this->load->model('checkout/order');
                $this->model_checkout_order->addOrderHistory($transaction['order_id'], $order_status_id, $comment, true);
            }
        }
    }
}
