<?php
// Text
$_['text_detalhes']       = 'Dados do cartão de crédito:';
$_['text_carregando']     = 'Carregando...';
$_['text_autorizou']      = 'Concluindo. Aguarde...';
$_['text_de']             = '&nbsp;de&nbsp;';
$_['text_total']          = '&nbsp;no total de&nbsp;';
$_['text_juros']          = 'de juros';
$_['text_sem_juros']      = 'sem juros';
$_['text_com_juros']      = 'com juros';
$_['text_cartao_credito'] = 'Cartão de Crédito';
$_['text_mes']            = 'Mês';
$_['text_ano']            = 'Ano';
$_['text_comprovante']    = '<b>COMPROVANTE:</b>';
$_['text_autorizado']     = 'Pagamento autorizado';
$_['text_capturado']      = 'Pagamento confirmado';
$_['text_em_analise']     = 'Pagamento em análise';
$_['text_nao_autorizado'] = 'Negado pelo emissor';
$_['text_fuso_horario']   = '(Horário de Brasília)';
$_['text_tentativas']     = 'O limite de tentativas de pagamento com o cartão de crédito foi excedido.';
$_['text_sandbox']        = '<b>Atenção:</b><br>'.
                            'Você está no ambiente Sandbox (apenas teste).<br>'.
                            'Utilize um cartão de teste para realizar o pagamento.';

// Entry
$_['entry_bandeira']      = 'Bandeira do cartão:';
$_['entry_cartao']        = 'Número do cartão:';
$_['entry_validade_mes']  = 'Validade/Mês:';
$_['entry_validade_ano']  = 'Validade/Ano:';
$_['entry_codigo']        = 'Código de segurança:';
$_['entry_nome']          = 'Nome impresso no cartão:';
$_['entry_documento']     = 'CPF do titular do cartão:';
$_['entry_parcelas']      = 'Parcelado em:';
$_['entry_captcha']       = 'Confirme abaixo que não você é um robô:';
$_['entry_pedido']        = 'Pedido: ';
$_['entry_data']          = 'Data: ';
$_['entry_tid']           = 'TID: ';
$_['entry_nsu']           = 'NSU: ';
$_['entry_tipo']          = 'Pago com: ';
$_['entry_total']         = 'Total: ';
$_['entry_status']        = 'Status: ';

// Error
$_['error_permissao']     = '<b>Erro nos dados enviados.</b>';
$_['error_dados']         = '<b>Os dados do cartão não foram preenchidos corretamente.</b><br>'.
                            'Preencha novamente os campos com os dados corretos.';
$_['error_autorizacao']   = '<b>O pagamento não foi autorizado pelo emissor do cartão.</b><br>'.
                            'Verifique se o cartão possui limite disponível para o pagamento total do pedido, mesmo que você esteja pagando parcelado.<br>'.
                            '<b>Importante:</b> Se o cartão estiver bloqueado ou com restrição, o pagamento não será autorizado.<br>'.
                            'Para mais informações entre em contato com o emissor do seu cartão.<br>'.
                            'Você também pode tentar outro cartão de crédito ou selecionar outra forma de pagamento.<br>'.
                            'Em caso de dúvidas, entre em contato com nosso atendimento.';
$_['error_captcha']       = '<b>Atenção:</b><br>'.
                            'Você deve confirmar que não é um robô.';
$_['error_preenchimento'] = '<b>Atenção:</b><br>'.
                            'Todos os campos são de preenchimento obrigatório.';
$_['error_validacao']     = '<b>Atenção:</b><br>'.
                            'Os dados abaixo precisam ser preenchidos corretamente:<br>'.
                            '<b>%s</b><br>Após corrigir os dados você poderá finalizar seu pedido novamente.<br>'.
                            'Qualquer dúvida, entre em contato com nosso atendimento.';
$_['error_tentativas']    = '<b>Atenção:</b><br>'.
                            'Você excedeu o limite de tentativas para pagamento.<br>'.
                            'Em caso de dúvidas, entre em contato com nosso atendimento.';
$_['error_status']        = '<b>Atenção:</b><br>'.
                            'Não foi possível obter uma resposta sobre a autorização do seu pagamento.<br>'.
                            'Tente novamente ou selecione outra forma de pagamento.<br>'.
                            'Em caso de dúvidas, entre em contato com nosso atendimento.';
$_['error_configuracao']  = '<b>Atenção:</b><br>'.
                            'Não foi possível autorizar o pagamento por problemas técnicos.<br>'.
                            'Tente novamente mais tarde ou selecione outra forma de pagamento.<br>'.
                            'Em caso de dúvidas, entre em contato com nosso atendimento.';
$_['error_bandeiras']     = '<b>Atenção:</b><br>'.
                            'Nenhum cartão foi ativado nas configurações da extensão.';
