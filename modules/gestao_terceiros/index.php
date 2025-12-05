<?php
// modules/gestao_terceiros/terceiro/cadastrar_terceiro.php

// Mostrar erros (dev) – depois pode desativar em produção
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config.php';

// Se precisar de sessão para algo (token, etc.), descomente
// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }

include_once ROOT_PATH . '/system/includes/user_head.php';
?>

<style>
    form {
        max-width: 500px;
        margin: 0 auto;
        padding: 15px;
    }

    input,
    select {
        width: 100%;
        padding: 10px;
        margin: 5px 0;
    }

    input[type="checkbox"] {
        width: auto;
        margin-right: 5px;
    }

    .cor-footer {
        background-color: #CCC;
    }

    button {
        padding: 10px;
        margin-top: 5px;
    }
</style>

<script>
    $(function () {
        $("#fornecedor").autocomplete({
            source: 'autocomplete.php' // mantém relativo à pasta atual
        });

        // Mostrar/ocultar campo de nome da agência
        $("#tem_agencia").change(function () {
            if ($(this).val() === "Sim") {
                $("#nome_agencia_wrapper").show();
                $("#nome_agencia").attr('required', 'required');
            } else {
                $("#nome_agencia_wrapper").hide();
                $("#nome_agencia").removeAttr('required');
            }
        }).trigger('change');
    });

    function toggleSenha() {
        const campoSenha = document.getElementById("senha");
        campoSenha.type = campoSenha.type === "password" ? "text" : "password";
    }

    function showDescription() {
        const descriptions = {
            consultor_ou_auditor: 'Profissionais que realizam auditorias ou consultorias em diversas áreas, como segurança alimentar, finanças, etc.',
            entregador: 'Pessoas encarregadas de entregar mercadorias ao supermercado ou aos clientes, caso o supermercado ofereça serviços de entrega.',
            limpeza_e_conservacao: 'Equipes responsáveis pela limpeza e manutenção da higiene do supermercado.',
            motorista: 'Profissionais que transportam mercadorias, seja para entrega ou para abastecimento do supermercado.',
            promotor_de_vendas_ou_repositor: 'Profissionais responsáveis por promover produtos específicos dentro do supermercado, organizando displays e interagindo com os clientes para aumentar as vendas.',
            seguranca_terceirizada: 'Profissionais de segurança contratados para garantir a segurança do local.',
            servicos_de_marketing: 'Especialistas que ajudam o supermercado a planejar e executar estratégias de marketing e promoção.',
            tecnicos_de_manutencao: 'Especialistas responsáveis por realizar reparos e manutenção em equipamentos e instalações do supermercado.',
            tecnicos_de_telefonia_e_ti: 'Profissionais que prestam suporte técnico para sistemas de telefonia e tecnologia da informação do supermercado.'
        };

        const select = document.getElementById('categoria');
        const description = descriptions[select.value] || '';
        document.getElementById('description').textContent = description;
    }
</script>

<body>
<?php
include_once ROOT_PATH . '/system/includes/user_navbar_offline.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 text-center">

            <form action="<?php echo BASE_URL; ?>/modules/gestao_terceiros/terceiro/processar_formulario.php" method="post">

            <label for="titulo"><h4 class="text-center">CADASTRO DE PRESTADORES TERCEIROS</h4></label>
            
            <label for="nome_completo">Nome Completo:</label>
                <input type="text" id="nome_completo" name="nome_completo" required>

                <label for="cpf">CPF (somente números):</label>
                <input type="text" id="cpf" name="cpf" required>

                <label for="categoria">Categoria do prestador</label>
                <select id="categoria" name="categoria" required onchange="showDescription()">
                    <option value="">Selecionar</option>
                    <option value="consultor_ou_auditor">Auditor / Consultor</option>
                    <option value="entregador">Entregador</option>
                    <option value="limpeza_e_conservacao">Limpeza e Conservação</option>
                    <option value="motorista">Motorista</option>
                    <option value="promotor_de_vendas_ou_repositor">Promotor de Vendas / Repositor</option>
                    <option value="seguranca_terceirizada">Segurança Terceirizada</option>
                    <option value="servicos_de_marketing">Serviços de Marketing</option>
                    <option value="tecnicos_de_manutencao">Técnicos de Manutenção</option>
                    <option value="tecnicos_de_telefonia_e_ti">Técnicos de Telefonia / TI</option>
                </select>

                <p id="description"></p>

                <label for="fornecedor">Fornecedor:</label>
                <input type="text" id="fornecedor" name="fornecedor" required>

                <label for="tem_agencia">Tem agência?</label>
                <select id="tem_agencia" name="tem_agencia">
                    <option value="">Selecionar</option>
                    <option value="Não">Não</option>
                    <option value="Sim">Sim</option>
                </select>

                <div id="nome_agencia_wrapper">
                    <label for="nome_agencia">Nome da Agência:</label>
                    <input type="text" id="nome_agencia" name="nome_agencia">
                </div>

                <label for="celular">Celular:</label>
                <input type="text" id="celular" name="celular" required>

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>

                <label for="senha">Senha:</label>
                <div class="row">
                    <div class="col-md-9 col-9">
                        <input type="password" id="senha" name="senha" required>
                    </div>
                    <div class="col-md-3 col-3">
                        <button type="button" onclick="toggleSenha()" title="Exibir senha.">👁️</button>
                    </div>
                </div>

                <label for="texto-qrcode">
                    Após completar o cadastro, você deverá solicitar o QR Code necessário para acessar as unidades do Grupo ABC.
                </label>

                <label for="aceite_termos">
                    <div class="row">
                        <div class="col-md-2">
                            <input type="checkbox" id="aceite_termos" name="aceite_termos" required>
                        </div>
                        <div class="col-md-10">
                            Aceito a
                            <a href="<?php echo BASE_URL; ?>/modules/gestao_terceiros/terceiro/politicadeprivacidade.php" target="_blank">
                                política de privacidade
                            </a>
                        </div>
                    </div>
                </label>

                <br>

                <button type="reset">Limpar</button>
                <button type="submit">Cadastrar</button>
                <br><br>

                <a href="<?php echo BASE_URL; ?>/modules/gestao_terceiros/terceiro/atualizar-cadastro.php">Atualizar Cadastro</a> |
                <a href="<?php echo BASE_URL; ?>/modules/gestao_terceiros/terceiro/recuperar-senha.php">Recuperar Senha</a> |
                <a href="<?php echo BASE_URL; ?>/modules/gestao_terceiros/terceiro/politicadeprivacidade.php">Política de Privacidade</a>
            </form>
        </div>
    </div>
</div>

<?php include_once ROOT_PATH . '/system/includes/user_code_footer.php'; ?>
<?php include_once ROOT_PATH . '/system/includes/user_footer.php'; ?>
