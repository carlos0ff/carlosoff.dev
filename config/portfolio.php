<?php

/*
|--------------------------------------------------------------------------
| Portfolio content — FONTE DA IMPORTAÇÃO INICIAL
|--------------------------------------------------------------------------
|
| Este arquivo NÃO alimenta mais o site. Experiência, formação, cursos, stack e
| Sobre são gerenciados no painel (/admin) e lidos do banco por
| App\Services\PortfolioContent. O que está aqui só é usado por
| Database\Seeders\PortfolioContentSeeder para preencher tabelas vazias
| (`php artisan db:seed --class=PortfolioContentSeeder`); nunca sobrescreve edições.
|
| Campos: company, role, period, description, logo (caminho em /public, opcional),
| url (opcional) e current. `period` nunca é inventado.
|
*/

return [

    'experiences' => [
        [
            'company' => 'Tribunal Superior Eleitoral',
            'role' => 'Técnico de Suporte em Urnas Eletrônicas',
            'period' => 'Set 2026 – Presente',
            'current' => true,
            'description' => 'Instala, configura e testa urnas eletrônicas antes da votação; presta suporte presencial às equipes durante o processo eleitoral; auxilia na logística de distribuição, lacração e devolução dos equipamentos.',
            'logo' => 'image/empresas/tse.jpg',
            'url' => null,
        ],
        [
            'company' => 'Future Data',
            'role' => 'Técnico em Informática',
            'period' => 'Mar 2026 – Presente',
            'current' => true,
            'description' => 'Manutenção preventiva e corretiva em computadores, notebooks e impressoras; instalação e configuração de sistemas operacionais, drivers e impressoras em rede; diagnóstico de falhas, formatação, backup e recuperação de dados.',
            'logo' => 'image/empresas/future-data.jpg',
            'url' => null,
        ],
        [
            'company' => 'Fada Quality Service LTDA',
            'role' => 'Auxiliar Técnico de Controle de Qualidade',
            'period' => 'Jun 2021 – Ago 2025',
            'current' => false,
            'description' => 'Atualização e reprogramação de ECUs automotivas do grupo Stellantis (CDA, Can Flash, Canape); interpretação de diagramas elétricos e criação de cabos para reflash.',
            'logo' => 'image/empresas/fada-quality.jpg',
            'url' => null,
        ],
    ],

    /*
    | Formação acadêmica (página Sobre). Mesmo formato das experiências: `role` é o curso.
    | `skills` (opcional) vira as etiquetas de "Competências" do item.
    */
    'education' => [
        [
            'company' => 'Centro Universitário de João Pessoa (Unipê)',
            'role' => 'Bacharelado, Computer Science',
            'period' => 'Fev 2024 – Dez 2027',
            'current' => true,
            'description' => 'Formação com base sólida em fundamentos da computação: Teoria dos Grafos (Dijkstra, BFS/DFS e componentes conexos), Organização e Arquitetura de Computadores (hierarquia de memória, cache e arquiteturas RISC, CISC e Von Neumann) e Fundamentos Matemáticos para Computação. Atuei como Team Leader de turma (ago–dez/2024), sendo o principal elo entre alunos, professores e coordenação.',
            'skills' => ['Microsserviços', 'Banco de dados'],
            'logo' => 'image/instituicoes/unipe.jpg',
            'url' => null,
        ],
        [
            'company' => 'UNIBRA - Centro Universitário Brasileiro',
            'role' => 'Curso Superior de Tecnologia (CST), Análise e desenvolvimento de sistemas',
            'period' => 'Fev 2023 – Dez 2025 · Trancada',
            'current' => false,
            'description' => 'Curso focado em lógica de programação, desenvolvimento de aplicações web, manutenção de sistemas, gestão de bancos de dados e fundamentos de segurança da informação. Participei de projetos práticos de aplicações web, buscando soluções seguras e com boa experiência de uso. O curso está trancado, mas contribuiu para minha formação técnica e profissional.',
            'skills' => ['HTML', 'CSS'],
            'logo' => 'image/instituicoes/unibra.jpg',
            'url' => null,
        ],
        [
            'company' => 'ETE - Aderico Alves de Vasconcelos',
            'role' => 'Curso Técnico Integrado, Redes de computadores',
            'period' => 'Fev 2016 – Dez 2018',
            'current' => false,
            'description' => "• Redes: configuração de roteadores, switches, VLANs, TCP/IP, DHCP, DNS, Wi-Fi e segurança de redes.\n• Manutenção e suporte: montagem e manutenção de hardware, instalação de Windows e Linux, virtualização e noções de cloud computing.\n• Segurança da informação: firewalls, criptografia, VPN e boas práticas de proteção de dados.\n• Ferramentas: Cisco Packet Tracer, VMware, Active Directory e Windows Server.",
            'skills' => [],
            'logo' => 'image/instituicoes/ete.jpg',
            'url' => null,
        ],
    ],

    /*
    | Cursos e certificações (página Sobre). Cada item abre no modal "Ver certificado".
    |
    | title   Nome do curso/certificação (obrigatório)
    | issuer  Instituição emissora (obrigatório)
    | date    Texto livre, ex.: 'Mar 2026' (opcional)
    | description  Descrição breve exibida no card (opcional)
    | file    Caminho do certificado em /public, ex.: 'image/certificados/curso.png'
    |         (imagem .png/.jpg/.webp ou .pdf). Sem `file`, o botão não aparece.
    | logo    Logo da instituição em /public (opcional)
    |
    | ATENÇÃO: os 4 itens abaixo são EXEMPLOS (imagens em image/certificados/exemplo-*.png).
    | Substitua pelos seus cursos e certificados reais.
    */
    'certifications' => [
        [
            'title' => 'Curso de exemplo 1',
            'issuer' => 'Instituição emissora',
            'date' => 'Mês/Ano',
            'description' => 'Breve descrição do curso ou da certificação (exemplo).',
            'file' => 'image/certificados/exemplo-1.png',
        ],
        [
            'title' => 'Curso de exemplo 2',
            'issuer' => 'Instituição emissora',
            'date' => 'Mês/Ano',
            'description' => 'Breve descrição do curso ou da certificação (exemplo).',
            'file' => 'image/certificados/exemplo-2.png',
        ],
        [
            'title' => 'Curso de exemplo 3',
            'issuer' => 'Instituição emissora',
            'date' => 'Mês/Ano',
            'description' => 'Breve descrição do curso ou da certificação (exemplo).',
            'file' => 'image/certificados/exemplo-3.png',
        ],
        [
            'title' => 'Curso de exemplo 4',
            'issuer' => 'Instituição emissora',
            'date' => 'Mês/Ano',
            'description' => 'Breve descrição do curso ou da certificação (exemplo).',
            'file' => 'image/certificados/exemplo-4.png',
        ],
    ],

    /*
    | Stack (página Sobre). `icon` é um SVG em /public (image/stack/*.svg); `invert` clareia
    | ícones escuros (ex.: GitHub) no tema dark. Sem `icon`, o chip usa um ícone padrão.
    | Lista montada a partir do que aparece no projeto e nas suas experiências/formação:
    | ajuste livremente (adicione/remova itens e baixe o SVG correspondente).
    */
    'stack' => [
        ['name' => 'PHP', 'icon' => 'image/stack/php.svg'],
        ['name' => 'Laravel', 'icon' => 'image/stack/laravel.svg'],
        ['name' => 'Java', 'icon' => 'image/stack/java.svg'],
        ['name' => 'HTML', 'icon' => 'image/stack/html5.svg'],
        ['name' => 'CSS', 'icon' => 'image/stack/css3.svg'],
        ['name' => 'JavaScript', 'icon' => 'image/stack/javascript.svg'],
        ['name' => 'Tailwind CSS', 'icon' => 'image/stack/tailwindcss.svg'],
        ['name' => 'MySQL', 'icon' => 'image/stack/mysql.svg'],
        ['name' => 'Docker', 'icon' => 'image/stack/docker.svg'],
        ['name' => 'GitHub', 'icon' => 'image/stack/github.svg', 'invert' => true],
    ],

    /*
    | Introdução da página Sobre. Textos escritos só com fatos já informados (formação,
    | experiências e stack) — revise e ajuste ao seu jeito.
    |
    | photo   Caminho da foto em /public (ex.: 'image/perfil.jpg'). Sem foto, mostra as iniciais.
    | resume  Caminho do currículo em /public. Basta colocar o PDF em public/docs/curriculo.pdf: sem o arquivo, o botão fica desativado.
    | email   E-mail exibido abaixo do botão. Sem e-mail, a linha não aparece.
    */
    'about' => [
        'name' => 'José Carlos',
        'headline' => 'Sou José Carlos, desenvolvedor backend em PHP e Laravel.',
        'paragraphs' => [
            'Estudante de Ciência da Computação no Unipê, com formação técnica em Redes de Computadores pela ETE e experiência em suporte técnico, manutenção de computadores e controle de qualidade.',
            'Trabalho com desenvolvimento web usando PHP e Laravel. Este portfólio foi construído com essas tecnologias e integra a API do GitHub para exibir meus repositórios e minhas contribuições.',
        ],
        'quote' => 'Se me encontrar por aí, não hesite em dar um oi! Vamos tomar um café e trocar ideia sobre as novidades do desenvolvimento web ou falar sobre nossa linguagem de programação favorita. ⚡',
        'photo' => 'image/perfil/jose-carlos.jpg',
        'resume' => 'docs/curriculo.pdf',
        'email' => null,
    ],

    /*
    | Quantidade de projetos por página em /projetos.
    */
    'projects_per_page' => 6,

];
