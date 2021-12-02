<?php
trait RedeRestEngine
{
    /*
    * Versão atual da extensão.
    */
    private static $rede_rest_version = '2.0.5';

    /*
    * URL da API de tags do repositório no Github.
    * Ex.: https://api.github.com/repos/opencartbrasil/extensions/tags
    */
    private static $repository = '';

    /*
    * Retorna a versão atual da extensão.
    */
    public function getRedeRestVersion()
    {
        return self::$rede_rest_version;
    }

    /*
    * Retorna um array com os erros de requisito.
    */
    public function getRedeRestRequirements()
    {
        return $this->checkRedeRestRequirements();
    }

    /*
    * Verifica se há upgrade para a extensão.
    * Retorna false ou a última versão.
    */
    public function getRedeRestUpgrade()
    {
        $latest_version = $this->checkRedeRestLatestVersion();

        if ($latest_version > $this->getRedeRestVersion()) {
            return $latest_version;
        }

        return false;
    }

    /*
    * Executa a análise de requisitos.
    * Retorna um array vazio ou com erros.
    */
    private function checkRedeRestRequirements()
    {
        $alerts = [];

        if (phpversion() < '5.6') {
            $alerts[] = 'Deve ser utilizado no mínimo PHP 5.6';
        }

        //if (!class_exists('DateTime')) {
            //$alerts[] = 'A classe DateTime do PHP precisa ser habilitada.';
        //}

        if (!extension_loaded('curl')) {
            $alerts[] = 'A biblioteca cURL do PHP precisa ser habilitada.';
        }

        if (!extension_loaded('json')) {
            $alerts[] = 'A biblioteca json do PHP precisa ser habilitada.';
        }

        return $alerts;
    }

    /*
    * Conecta na URL da API de tags do repositório no Github.
    * Retorna false ou o número da versão.
    */
    private function checkRedeRestLatestVersion()
    {
        if (!self::$repository) {
            return false;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::$repository);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, "curl");

        ob_start();
        curl_exec($ch);
        curl_close($ch);

        $lines = ob_get_contents();
        ob_end_clean();
        $json = json_decode($lines, true);

        if (!$json || !isset($json[0]['name'])) {
            return false;
        }

        return $json[0]['name'];
    }
}
