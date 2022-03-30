<?php
// Heading
$_['heading_title']                   = 'e.Rede Cartão de Crédito';

// Text
$_['text_extension']                  = 'Extensões';
$_['text_success']                    = 'Pagamento e.Rede Cartão de Crédito modificado com sucesso!';
$_['text_edit']                       = 'Configurações do pagamento e.Rede Cartão de Crédito';
$_['text_rede_rest_credito']          = '<a target="_blank" href="https://www.userede.com.br/novo/e-rede"><img src="view/image/payment/rede_rest.png" alt="e.Rede Cartão de Crédito" title="e.Rede Cartão de Crédito" style="border: 1px solid #EEEEEE;" /></a>';
$_['text_image_manager']              = 'Gerenciador de arquivos';
$_['text_browse']                     = 'Localizar';
$_['text_clear']                      = 'Apagar';
$_['text_manual']                     = 'Apenas autorizar';
$_['text_automatica']                 = 'Autorizar e capturar';
$_['text_ativar']                     = 'Ativar:';
$_['text_parcelamentos']              = 'Parcelamentos';
$_['text_regras']                     = 'Regras';
$_['text_formato_parcelas']           = '%sx de %s%%';
$_['text_configurar_taxas']           = 'Taxa total por parcela';
$_['text_campo']                      = 'Campo:';
$_['text_coluna']                     = 'Coluna na tabela de pedidos';
$_['text_razao']                      = 'Coluna Razão Social:';
$_['text_cnpj']                       = 'Coluna CNPJ:';
$_['text_cpf']                        = 'Coluna CPF:';
$_['text_numero_fatura']              = 'Coluna Número para fatura:';
$_['text_numero_entrega']             = 'Coluna Número para entrega:';
$_['text_complemento_fatura']         = 'Coluna Complemento para fatura:';
$_['text_complemento_entrega']        = 'Coluna Complemento para entrega:';
$_['text_bootstrap_v3']               = 'Bootstrap v3';
$_['text_skeleton']                   = 'Skeleton';
$_['text_btn_default']                = 'Default';
$_['text_btn_primary']                = 'Primary';
$_['text_btn_success']                = 'Success';
$_['text_btn_info']                   = 'Info';
$_['text_btn_warning']                = 'Warning';
$_['text_btn_danger']                 = 'Danger';
$_['text_texto']                      = 'Cor do texto';
$_['text_fundo']                      = 'Cor do fundo';
$_['text_borda']                      = 'Cor da borda';
$_['text_recaptcha']                  = 'Integração com Google reCAPTCHA V2';
$_['text_recaptcha_registrar']        = '<a target="_blank" href="https://www.google.com/recaptcha/admin">Clique aqui</a> para acessar o site do Google reCAPTCHA e gerar suas credenciais de acesso.';
$_['text_homologacao']                = 'Homologação';
$_['text_producao']                   = 'Produção';

// Tab
$_['tab_geral']                       = 'Configurações';
$_['tab_bandeiras']                   = 'Bandeiras';
$_['tab_situacoes']                   = 'Situações';
$_['tab_campos']                      = 'Dados do cliente';
$_['tab_finalizacao']                 = 'Finalização';
$_['tab_clearsale']                   = 'ClearSale Start';

// Info
$_['info_geral']                      = 'Configurações básicas da extensão.';
$_['info_bandeiras']                  = 'Configurações relacionadas ao pagamento.';
$_['info_parcelamentos']              = 'Configurações das bandeiras, parcelas com/sem juros e das taxas.';
$_['info_regras']                     = 'Configurações das regras de parcelamento conforme o total do pedido.';
$_['info_situacoes']                  = 'Configurações das situações do pedido conforme o retorno da Rede.';
$_['info_campos']                     = 'Configurações dos campos extras do cadastro do cliente.<br>
                                         <b>Importante:</b> Para cadastrar campos personalizados, vá no menu <b>Clientes > Personalizar cadastro</b> e cadastre os campos extras como CPF e número do endereço.<br>
                                         <b>Observação:</b> Se os campos extras foram criados diretamente na tabela de pedidos, selecione a opção "<b>Coluna na tabela de pedidos</b>", e selecione a coluna na tabela *_order correspondente.';
$_['info_finalizacao']                = 'Configurações utilizadas durante e após a finalização do pedido.';
$_['info_clearsale']                  = 'Contrate o serviço de antifraude da ClearSale no plano Start para utilizá-lo.';

// Column
$_['column_bandeira']                 = 'Bandeira';
$_['column_ativa']                    = 'Habilitada';
$_['column_parcelas']                 = 'Parcelar em até';
$_['column_sem_juros']                = 'Parcelas sem juros';
$_['column_juros']                    = 'Taxa de juros (%)';
$_['column_acao']                     = 'Ação';
$_['column_total']                    = 'A partir do total';
$_['column_acao']                     = 'Ação';

// Button
$_['button_save_stay']                = 'Salvar e continuar';
$_['button_save']                     = 'Salvar configurações';
$_['button_configurar_taxas']         = 'Configurar taxas';
$_['button_copiar_taxas']             = 'Copiar taxas';

// Entry
$_['entry_lojas']                     = 'Lojas:';
$_['entry_tipos_clientes']            = 'Tipos de clientes:';
$_['entry_total']                     = 'Total mínimo:';
$_['entry_geo_zone']                  = 'Região geográfica:';
$_['entry_status']                    = 'Situação:';
$_['entry_sort_order']                = 'Posição:';
$_['entry_minimo']                    = 'Mínimo por parcela:';
$_['entry_desconto']                  = 'Desconto à vista (%):';
$_['entry_captura']                   = 'Tipo de captura:';
$_['entry_soft_descriptor']           = 'Identificação na fatura:';
$_['entry_dias']                      = 'Quantidade de dias para:';
$_['entry_dias_capturar']             = 'Capturar a autorização:';
$_['entry_dias_cancelar_autorizacao'] = 'Cancelar a autorização:';
$_['entry_dias_cancelar_captura']     = 'Cancelar a captura:';
$_['entry_situacao_pendente']         = 'Transação pendente:';
$_['entry_situacao_autorizada']       = 'Transação autorizada:';
$_['entry_situacao_nao_autorizada']   = 'Transação não autorizada:';
$_['entry_situacao_capturada']        = 'Transação capturada:';
$_['entry_situacao_cancelada']        = 'Transação cancelada:';
$_['entry_custom_razao_id']           = 'Razão Social:';
$_['entry_custom_cnpj_id']            = 'CNPJ:';
$_['entry_custom_cpf_id']             = 'CPF:';
$_['entry_custom_numero_id']          = 'Número:';
$_['entry_custom_complemento_id']     = 'Complemento:';
$_['entry_titulo']                    = 'Título da extensão:';
$_['entry_imagem']                    = 'Imagem da extensão:';
$_['entry_instrucoes']                = 'Instruções:';
$_['entry_exibir_juros']              = 'Exibir percentual de juros:';
$_['entry_tema']                      = 'Tema do formulário:';
$_['entry_estilo_botao']              = 'Estilo do botão confirmar:';
$_['entry_botao_normal']              = 'Cor inicial do botão confirmar:';
$_['entry_botao_efeito']              = 'Cor de efeito do botão confirmar:';
$_['entry_texto_botao']               = 'Texto do botão confirmar:';
$_['entry_container_botao']           = 'Container do botão confirmar:';
$_['entry_codigo_css']                = 'Código CSS:';
$_['entry_recaptcha_site_key']        = 'Site key:';
$_['entry_recaptcha_secret_key']      = 'Secret key:';
$_['entry_recaptcha_status']          = 'Situação:';
$_['entry_clearsale_codigo']          = 'Código de integração:';
$_['entry_clearsale_ambiente']        = 'Ambiente de integração:';

// Help
$_['help_lojas']                      = 'Lojas em que a extensão será oferecida como forma de pagamento.';
$_['help_tipos_clientes']             = 'Tipos de clientes para quem a extensão será oferecida como forma de pagamento.';
$_['help_total']                      = 'Total mínimo que o pedido deve alcançar para exibir a forma de pagamento. Pode ficar em branco.';
$_['help_minimo']                     = 'É o valor mínimo de uma parcela quando o pagamento for parcelado.';
$_['help_desconto']                   = 'Desconto para pagamento à vista (crédito 1x). Pode ficar em branco.';
$_['help_captura']                    = 'Em caso de análise antifraude, utilize a opção <b>Apenas autorizar</b> (a captura será manual).';
$_['help_soft_descriptor']            = 'Palavra com até 13 caracteres que será exibida na fatura do cliente (identificação da loja).';
$_['help_dias']                       = 'Entre em contato com a Rede para verificar quantos dias há para capturar e cancelar um pagamento.';
$_['help_situacao_pendente']          = 'Quando a transação for iniciada.';
$_['help_situacao_autorizada']        = 'Quando a transação for autorizada.';
$_['help_situacao_nao_autorizada']    = 'Quando a transação não for autorizada.';
$_['help_situacao_capturada']         = 'Quando a transação for capturada.';
$_['help_situacao_cancelada']         = 'Quando a transação for cancelada.';
$_['help_custom_razao_id']            = 'Só selecione se você tiver o campo para preencher a Razão Social no cadastro do cliente.';
$_['help_custom_cnpj_id']             = 'Só selecione se você tiver o campo para preencher o CNPJ no cadastro do cliente.';
$_['help_custom_cpf_id']              = 'Selecione o campo que armazena o CPF no cadastro do cliente.';
$_['help_custom_numero_id']           = 'Selecione o campo que armazena o número no endereço do cliente.';
$_['help_custom_complemento_id']      = 'Só selecione se você tiver o campo para preencher o complemento no endereço do cliente.';
$_['help_titulo']                     = 'Título que será exibido para o cliente na etapa de seleção da forma de pagamento.';
$_['help_imagem']                     = 'Selecione uma imagem para ser exibida no lugar do título na etapa de seleção da forma de pagamento.';
$_['help_instrucoes']                 = 'Página de informação que será exibida para o cliente acima do formulário do cartão.';
$_['help_exibir_juros']               = 'Se Não, aparecerá somente "com juros" ao lado do valor das parcelas.';
$_['help_tema']                       = 'Dependendo do checkout utilizado, o tema Bootstrap pode ser a melhor opção.';
$_['help_estilo_botao']               = 'É baseado no padrão Bootstrap.';
$_['help_botao_normal']               = 'Quando não estiver pressionado ou não estiver com o mouse sobre ele.';
$_['help_botao_efeito']               = 'Quando for pressionado ou quando o mouse estiver sobre ele.';
$_['help_texto_botao']                = 'É exibido dentro do botão.';
$_['help_container_botao']            = 'Class ou ID ligado ao botão confirmar. Só altere se for necessário identificar o botão confirmar em um checkout diferente do padrão.';
$_['help_codigo_css']                 = 'Utilizado para estilizar a aparência do comprovante de pagamento exibido na página de sucesso (após realizar o pagamento).';
$_['help_recaptcha_site_key']         = 'É gerada no site do Google reCAPTCHA.';
$_['help_recaptcha_secret_key']       = 'É gerada no site do Google reCAPTCHA.';
$_['help_recaptcha_status']           = 'Selecione Habilitado para que o Google reCAPTCHA seja exibido no formulário de pagamento.';
$_['help_clearsale_codigo']           = 'Fornecido pela ClearSale.';
$_['help_clearsale_ambiente']         = 'Relacionado ao código de integração.';

// Error
$_['error_permission']                = 'Atenção: Você não tem permissão para modificar a extensão e.Rede Cartão de Crédito!';
$_['error_warning']                   = 'Atenção: A extensão não foi configurada corretamente! Verifique todos os campos para corrigir os erros.';
$_['error_stores']                    = 'Selecione pelo menos uma loja.';
$_['error_customer_groups']           = 'Selecione pelo menos um tipo de cliente.';
$_['error_soft_descriptor']           = 'Preencha corretamente.';
$_['error_dias']                      = 'Preencha os dias.';
$_['error_taxa']                      = 'A taxa de juros precisa ser maior que zero.';
$_['error_taxas']                     = 'Configure as taxas para as parcelas com juros.';
$_['error_campos_opcao']              = 'Selecione o campo.';
$_['error_campos_coluna']             = 'Selecione a coluna.';
$_['error_obrigatorio']               = 'Preenchimento obrigatório.';
