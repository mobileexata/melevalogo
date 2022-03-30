<?php
// Heading
$_['heading_title']                = 'Transação';

// Text
$_['text_transactions']            = 'Transações';
$_['text_rede_rest']               = 'e.Rede';
$_['text_edit']                    = 'Informações da transação';
$_['text_debito']                  = 'Cartão de Débito';
$_['text_credito']                 = 'Cartão de Crédito';
$_['text_autorizada']              = 'Autorizada';
$_['text_capturada']               = 'Capturada';
$_['text_processando']             = 'Processando cancelamento';
$_['text_cancelada']               = 'Cancelada';
$_['text_negada']                  = 'Cancelamento negado';
$_['text_confirm_yes']             = 'Sim, desejo confirmar';
$_['text_confirm_no']              = 'Não';
$_['text_confirm_capturar']        = '<h4>Você tem certeza que deseja realizar a captura?<h4><p><b>Atenção:</b> Se confirmada, esta ação não poderá ser desfeita.</p>';
$_['text_confirm_cancelar']        = '<h4>Você tem certeza que deseja realizar o cancelamento?</h4><p><b>Atenção:</b> Se confirmada, esta ação não poderá ser desfeita.</p>';
$_['text_consultando']             = 'Consultando a transação na Rede...';
$_['text_cancelando']              = 'Cancelando a transação na Rede...';
$_['text_capturando']              = 'Capturando a transação na Rede...';
$_['text_aguarde']                 = 'Aguarde...';
$_['text_dia']                     = 'dia';
$_['text_dias']                    = 'dias';
$_['text_dias_capturar']           = 'Capturar até %s (restam %s %s).';
$_['text_dias_captura_expirado']   = 'Aviso: O prazo para capturar já expirou. Entre em contato com a Rede para mais informações.';
$_['text_dias_cancelar']           = 'Cancelar até %s (restam %s %s).<br>Alguns cartões só podem ser cancelados 24 horas após o pagamento.<br>Caso você não consiga cancelar, entre em contato com a Rede para mais informações.';
$_['text_data_autorizacao']        = 'Autorizada em';
$_['text_valor_autorizado']        = 'Valor autorizado';
$_['text_data_captura']            = 'Capturada em';
$_['text_valor_capturado']         = 'Valor capturado';
$_['text_data_cancelamento']       = 'Cancelada em';
$_['text_valor_cancelado']         = 'Valor cancelado';
$_['text_valor_cancelado_parcial'] = 'Valor cancelado parcialmente';
$_['text_fuso_horario']            = '(Horário de Brasília)';

// Tab
$_['tab_timeline']                 = 'Linha do tempo';
$_['tab_details']                  = 'Detalhes';
$_['tab_json_first_response']      = 'Primeiro JSON recebido';
$_['tab_json_last_response']       = 'Último JSON recebido';

// Button
$_['button_consultar']             = 'Consultar na Rede';
$_['button_capturar']              = 'Capturar na Rede';
$_['button_cancelar']              = 'Cancelar na Rede';
$_['button_antifraude']            = 'Solicitar análise';

// Entry
$_['entry_order_id']               = 'Pedido nº:';
$_['entry_added']                  = 'Data do pedido:';
$_['entry_total']                  = 'Total do pedido:';
$_['entry_customer']               = 'Cliente:';
$_['entry_tid']                    = 'TID - Identificador da Transação:';
$_['entry_nsu']                    = 'NSU - Número Sequencial Único:';
$_['entry_codigo_autorizacao']     = 'Código de autorização:';
$_['entry_parcelamento']           = 'Parcelado em:';
$_['entry_status']                 = 'Status da transação:';
$_['entry_clearsale']              = 'Antifraude ClearSale:';
$_['entry_cancelar_total']         = 'Informe o valor a ser cancelado';

// Error
$_['error_permission']             = 'Atenção: Você não tem permissão para modificar as transações e.Rede!';
$_['error_iframe']                 = 'Seu navegador não tem suporte para iframes.';
$_['error_warning']                = 'Selecione uma transação válida.';
$_['error_consultar']              = 'Não foi possível consultar a transação.';
$_['error_capturar']               = 'Não foi possível capturar a transação.';
$_['error_cancelar']               = 'Não foi possível cancelar a transação.';
$_['error_cancelar_total']         = 'É necessário informar um valor válido para o cancelamento.';
$_['error_cancelar_falhou']        = 'Não é possível realizar o cancelamento.<br>Tente novamente após algumas horas.';
$_['error_cancelar_expirado']      = 'Não é possível realizar o cancelamento, pois o prazo já expirou.<br>Entre em contato com o atendimento da Rede para mais informações.';
$_['error_cancelar_parcial']       = 'Não é possível realizar o cancelamento parcial.<br>Entre em contato com o atendimento da Rede para mais informações.';
