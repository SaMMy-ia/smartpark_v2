===========================================
  SMARTPARK - Sistema de Gerenciamento
  Inteligente de Estacionamentos
===========================================

DESCRIÇÃO
---------
SmartPark é um sistema completo de gerenciamento de estacionamentos com interface moderna,
responsiva e suporte a dark mode. O sistema possui três níveis de acesso (Admin, Funcionário
e Usuário) com funcionalidades específicas para cada perfil.

REQUISITOS DO SISTEMA
---------------------
- PHP 8.1 ou superior
- MySQL 8.0 ou superior
- Apache/XAMPP ou LAMP
- Navegador web moderno (Chrome, Firefox, Edge, Safari)

TECNOLOGIAS UTILIZADAS
----------------------
- Backend: PHP 8.1+ com PDO
- Banco de Dados: MySQL 8.0+
- Frontend: HTML5, Tailwind CSS 3.x, Alpine.js, jQuery 3.7
- Gráficos: Chart.js
- Ícones: Font Awesome 6.5

INSTALAÇÃO
----------
1. EXTRAIR ARQUIVOS
   - Extraia o conteúdo do ZIP para a pasta htdocs do XAMPP
   - Caminho exemplo: C:\xampp\htdocs\SmartPark

2. CRIAR BANCO DE DADOS
   - Abra o phpMyAdmin (http://localhost/phpmyadmin)
   - Clique em "Importar" no menu superior
   - Selecione o arquivo "smartpark.sql" localizado na pasta raiz do projeto
   - Clique em "Executar" para importar o banco de dados

3. CONFIGURAR CONEXÃO (Opcional)
   - Se suas credenciais do MySQL forem diferentes de root/'', edite o arquivo config.php
   - Altere as constantes DB_USER e DB_PASS conforme necessário

4. ACESSAR O SISTEMA
   - Abra seu navegador
   - Acesse: http://localhost/smartpark-main
   - Você será redirecionado para a página de login

CREDENCIAIS DE ACESSO
----------------------
ADMINISTRADOR:
  Email: admin@smartpark.com
  Senha: admin123

FUNCIONÁRIO:
  Email: joao@smartpark.com
  Senha: func123

USUÁRIO COMUM:
  Email: lucas@email.com
  Senha: user123

FUNCIONALIDADES POR PERFIL
---------------------------

ADMINISTRADOR:
- Dashboard com gráficos de ocupação e receita
- CRUD completo de estacionamentos
- CRUD completo de vagas
- Gerenciamento de usuários
- Visualização e gerenciamento de todas as reservas
- Visualização de pagamentos
- Geração de relatórios
- Visualização de logs do sistema

FUNCIONÁRIO:
- Dashboard com estatísticas
- Gerenciamento de estacionamentos
- Gerenciamento de vagas
- Aprovação/cancelamento de reservas
- Visualização de pagamentos

USUÁRIO COMUM:
- Dashboard personalizado
- Busca de vagas disponíveis com filtros
- Reserva de vagas
- Pagamento de reservas (simulado)
- Visualização de reservas ativas
- Histórico de reservas
- Gerenciamento de perfil

ESTRUTURA DO PROJETO
--------------------
SmartPark/
├── index.php              (Página de login)
├── register.php           (Registro de novos usuários)
├── logout.php             (Logout)
├── recover.php            (Recuperação de senha)
├── config.php             (Configuração do banco de dados)
├── smartpark.sql          (Script SQL do banco de dados)
├── README.txt             (Este arquivo)
│
├── admin/                 (Páginas do administrador)
│   ├── dashboard.php
│   ├── estacionamentos.php
│   ├── vagas.php
│   ├── usuarios.php
│   ├── reservas.php
│   ├── pagamentos.php
│   ├── relatorios.php
│   └── logs.php
│
├── funcionario/           (Páginas do funcionário)
│   ├── dashboard.php
│   ├── estacionamentos.php
│   ├── vagas.php
│   ├── reservas.php
│   └── pagamentos.php
│
├── usuario/               (Páginas do usuário)
│   ├── dashboard.php
│   ├── buscar-vagas.php
│   ├── reservar.php
│   ├── minhas-reservas.php
│   ├── pagamento.php
│   ├── historico.php
│   └── perfil.php
│
├── includes/              (Arquivos compartilhados)
│   ├── header.php
│   ├── footer.php
│   ├── auth.php
│   └── functions.php
│
└── assets/
    └── js/
        └── main.js

RECURSOS PRINCIPAIS
-------------------
✓ Interface moderna e responsiva (mobile-first)
✓ Dark mode com persistência via localStorage
✓ Autenticação segura com sessões PHP
✓ Senhas criptografadas com password_hash
✓ Proteção contra SQL Injection (PDO prepared statements)
✓ Proteção contra XSS (htmlspecialchars)
✓ CSRF protection em formulários
✓ Validação client-side e server-side
✓ Paginação em tabelas
✓ Busca e filtros em tempo real
✓ Gráficos interativos (Chart.js)
✓ Sistema de notificações (flash messages)
✓ Logs de ações dos usuários
✓ Cálculo automático de valores de reserva
✓ Sistema de pagamento simulado

SEGURANÇA
---------
- Todas as senhas são armazenadas com hash bcrypt
- Prepared statements para prevenir SQL Injection
- Sanitização de outputs para prevenir XSS
- Verificação de roles em todas as páginas protegidas
- Sessões seguras com regeneração de ID no login
- Validação de dados no frontend e backend

BANCO DE DADOS
--------------
O sistema utiliza 6 tabelas principais:
- usuarios: Armazena dados dos usuários (admin, funcionário, usuario)
- estacionamentos: Dados dos estacionamentos
- vagas: Vagas de cada estacionamento
- reservas: Reservas feitas pelos usuários
- pagamentos: Pagamentos das reservas
- logs: Registro de ações no sistema

DARK MODE
---------
O sistema possui suporte completo a dark mode:
- Toggle disponível no header
- Preferência salva no localStorage
- Todas as páginas adaptadas para modo escuro

SUPORTE E PROBLEMAS
-------------------
Problemas comuns e soluções:

1. ERRO DE CONEXÃO COM BANCO DE DADOS
   - Verifique se o MySQL está rodando
   - Confirme as credenciais em config.php
   - Certifique-se de que o banco 'smartpark' foi criado

2. PÁGINA EM BRANCO
   - Ative a exibição de erros no PHP
   - Verifique os logs de erro do Apache
   - Confirme que todas as extensões PHP necessárias estão ativas

3. ESTILOS NÃO CARREGAM
   - Verifique sua conexão com a internet (CDNs)
   - Limpe o cache do navegador

4. SESSÃO NÃO PERSISTE
   - Verifique as permissões da pasta de sessões do PHP
   - Confirme que cookies estão habilitados no navegador

DESENVOLVIMENTO
---------------
Para desenvolvedores que desejam estender o sistema:

1. Todas as funções auxiliares estão em includes/functions.php
2. Funções de autenticação em includes/auth.php
3. Use a função e() para sanitizar outputs
4. Use prepared statements para queries
5. Siga o padrão de nomenclatura existente
6. Adicione logs para ações importantes

NOTAS IMPORTANTES
-----------------
- Este é um sistema de demonstração
- O sistema de pagamento é simulado (não processa pagamentos reais)
- Os emails de recuperação de senha são logados no console (não são enviados)
- Recomenda-se usar HTTPS em produção
- Altere as credenciais padrão em produção

LICENÇA
-------
Este projeto foi desenvolvido para fins educacionais e de demonstração.

CONTATO
-------
Para suporte ou dúvidas:
Email: contato@smartpark.com
Website: http://localhost/smartpark-main

===========================================
Desenvolvido com ❤️ usando PHP, MySQL e Tailwind CSS
© 2026 SmartPark. Todos os direitos reservados.
===========================================
