<?php
// Text
$_['text_detalhes']       = 'Dados do cartão de débito:';
$_['text_carregando']     = 'Carregando...';
$_['text_redirecionando'] = 'Concluindo. Aguarde...';
$_['text_cartao_debito']  = 'Cartão de Débito';
$_['text_mes']            = 'Mês';
$_['text_ano']            = 'Ano';
$_['text_comprovante']    = '<b>COMPROVANTE:</b>';
$_['text_pendente']       = 'Aguardando autenticação.';
$_['text_nao_autorizado'] = 'Pagamento não autorizado.';
$_['text_capturado']      = 'Pagamento confirmado';
$_['text_fuso_horario']   = '(Horário de Brasília)';
$_['text_tentativas']     = 'O limite de tentativas de pagamento com o cartão de débito foi excedido.';
$_['text_sandbox']        = '<b>Atenção:</b><br>'.
                            'Você está no ambiente Sandbox (apenas teste).<br>'.
                            'Utilize um cartão de teste para realizar o pagamento.';
$_['text_info']           = '<b>Requisitos para utilização dos cartões Visa e Mastercard:</b><br>'.
                            '<br>'.
                            '- O cartão <b>deve possuir código de segurança</b> (geralmente no verso do cartão físico).<br>'.
                            '- O cartão deve está habilitado para realizar pagamentos através de <b>débito online (lojas online)</b>.<br>'.
                            '- O emissor do cartão deve ter habilitado o serviço <b>Verified by Visa</b> ou <b>Mastercard SecureCode</b>.<br>'.
                            '- <b>Ao confirmar o pagamento</b>, será iniciado o processo de autenticação do titular do cartão de débito.<br>'.
                            '- <b>Para realizar a autenticação</b> tenha em mãos o celular cadastrado no Internet Banking, token, cartão de códigos, CPF, data de nascimento ou senha do cartão de débito, pois estes poderão ser solicitados.<br>'.
                            '<br>'.
                            '<b>Em caso de dúvidas sobre os requisitos, entre em contato com o emissor do seu cartão de débito.</b>';
$_['text_instrucoes']     = '<p><b>INSTRUÇÕES PARA AUTENTICAÇÃO</b></p>'.
                            '<p>Geramos uma solicitação de pagamento por cartão de débito que irá expirar em poucos minutos, por isso é importante que você realize a autenticação do pagamento imediatamente.</p>'.
                            '<p>Para realizar a autenticação, utilize o botão abaixo:</p>'.
                            '<p><a href="%s">Realizar autenticação</a></p>';
$_['text_falhou']         = '<p><b>PAGAMENTO NÃO AUTORIZADO</b></p>'.
                            '<p>Caso deseje pagar com cartão de débito, recomendamos que realize um novo pedido, mas certifique-se que o emissor do seu cartão de débito não limita a quantidade de tentativas de pagamento por dia.</p>';

// Entry
$_['entry_bandeira']      = 'Bandeira do cartão:';
$_['entry_cartao']        = 'Número do cartão:';
$_['entry_validade_mes']  = 'Validade/Mês:';
$_['entry_validade_ano']  = 'Validade/Ano:';
$_['entry_codigo']        = 'Código de segurança:';
$_['entry_nome']          = 'Nome impresso no cartão:';
$_['entry_valor']         = 'Valor à vista:';
$_['entry_pedido']        = 'Pedido: ';
$_['entry_data']          = 'Data: ';
$_['entry_tid']           = 'TID: ';
$_['entry_tipo']          = 'Pago com: ';
$_['entry_total']         = 'Total: ';
$_['entry_status']        = 'Status: ';

// Error
$_['error_permissao']     = '<b>Erro nos dados enviados.</b>';
$_['error_dados']         = '<b>Os dados do cartão não foram preenchidos corretamente.</b><br>'.
                            'Preencha novamente os campos com os dados corretos.';
$_['error_autorizacao']   = '<b>O pagamento por cartão de débito não foi autorizado.</b><br>'.
                            '<br>'.
                            '<b>VERIFIQUE:</b><br>'.
                            '- Se o cartão de débito possui <b>limite disponível</b> para o pagamento à vista do pedido.<br>'.
                            '- Se você <b>preencheu corretamente</b> todos os campos com os dados do cartão de débito.<br>'.
                            '- Se o cartão de débito possui os <b>requisitos para realizar pagamentos</b> através de lojas online.<br>'.
                            '<br>'.
                            '<b>Importante:</b><br>'.
                            'Caso deseje, realize a autenticação só mais uma vez, pois o emissor poderá bloquear o cartão por 24 horas.';
$_['error_preenchimento'] = '<b>Atenção:</b><br>'.
                            'Todos os campos são de preenchimento obrigatório.';
$_['error_tentativas']    = '<b>Atenção:</b><br>'.
                            'Você excedeu o limite de tentativas para pagamento.<br>'.
                            'Em caso de dúvidas, entre em contato com nosso atendimento.';
$_['error_json']          = '<b>Atenção:</b><br>'.
                            'Não foi possível autorizar o seu pagamento.<br>'.
                            'Tente novamente, e em caso de dúvidas, entre em contato com nosso atendimento.';
$_['error_configuracao']  = '<b>Atenção:</b><br>'.
                            'Não foi possível autorizar o seu pagamento por problemas técnicos.<br>'.
                            'Tente novamente mais tarde ou selecione outra forma de pagamento.<br>'.
                            'Em caso de dúvidas, entre em contato com nosso atendimento.';
$_['error_bandeiras']     = '<b>Atenção:</b><br>'.
                            'Nenhum cartão foi ativado nas configurações da extensão.';
