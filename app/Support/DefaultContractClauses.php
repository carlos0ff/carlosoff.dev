<?php

namespace App\Support;

/**
 * Textos padrão das cláusulas, usados para pré-preencher contratos novos.
 *
 * São um ponto de partida comercial razoável, NÃO um parecer jurídico: cada texto deve ser revisado
 * por profissional habilitado e ajustado ao caso antes do uso. Todos podem ser editados por contrato.
 */
class DefaultContractClauses
{
    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            'schedule' => 'O cronograma de execução será acordado entre as partes no início do projeto e poderá ser revisto por escrito. Atrasos causados pela falta de informações, acessos, aprovações ou materiais de responsabilidade da CONTRATANTE prorrogam os prazos pelo mesmo período.',

            'contractor_obligations' => "a) Executar os serviços com diligência, boas práticas de engenharia de software e dentro do escopo contratado;\nb) Manter a CONTRATANTE informada sobre o andamento do projeto e comunicar previamente riscos, impedimentos e alterações de prazo;\nc) Disponibilizar ambiente de homologação para validação das entregas, quando aplicável;\nd) Manter sigilo sobre informações e dados a que tiver acesso em razão do contrato;\ne) Entregar o código-fonte, a documentação e os acessos previstos nas condições deste contrato.",

            'client_obligations' => "a) Fornecer, nos prazos combinados, informações, conteúdos, acessos e aprovações necessários à execução dos serviços;\nb) Indicar um responsável com poderes para validar entregas e tomar decisões sobre o projeto;\nc) Avaliar as entregas no ambiente indicado e registrar aprovação, aprovação com ressalvas ou reprovação, com a descrição das pendências encontradas;\nd) Efetuar os pagamentos nas datas e condições acordadas;\ne) Responsabilizar-se pela licitude dos conteúdos e dados que inserir no sistema.",

            'support_included' => 'Suporte técnico para esclarecimento de dúvidas de uso e análise de falhas do sistema, pelos canais oficiais e no horário comercial (segunda a sexta, das 9h às 18h, exceto feriados nacionais). Não inclui treinamentos presenciais, desenvolvimento de novas funcionalidades nem suporte a sistemas de terceiros.',

            'maintenance_included' => 'Manutenção corretiva de defeitos do sistema entregue e atualizações de segurança das dependências utilizadas, aplicadas em janelas previamente comunicadas. Melhorias, novas funcionalidades e mudanças de regras de negócio são tratadas como solicitação adicional.',

            'warranty' => 'Garantia de 90 (noventa) dias, contados do aceite de cada entrega, para correção sem custo de defeitos que façam o sistema funcionar em desacordo com o escopo aprovado. A garantia não cobre falhas causadas por alterações feitas por terceiros, uso indevido, mudanças de infraestrutura não administrada pela CONTRATADA ou serviços de terceiros.',

            'domain_hosting' => 'O registro do domínio, a hospedagem e o desenvolvimento do software são serviços distintos. A titularidade do domínio permanece com quem consta no registro, ainda que a CONTRATADA o administre tecnicamente. As condições de hospedagem, backup, custos e responsabilidades constam do Termo de Domínio e Hospedagem.',

            'acceptance_criteria' => 'Cada entrega será considerada aceita quando a CONTRATANTE registrar a aprovação no portal do cliente ou assinar o Termo de Validação e Aceite. Reprovações devem descrever as pendências de forma objetiva, para correção e nova validação. A ausência de manifestação não é considerada aceite.',

            'suspension_rules' => 'Em caso de inadimplência, serão observadas a política financeira deste contrato (tolerância, avisos e prazos) e a forma de restrição nela prevista. Antes de qualquer restrição a CONTRATANTE será notificada com antecedência e poderá regularizar a pendência ou apresentar contestação; cobranças contestadas ou em negociação não ensejam restrição enquanto pendentes de análise. Em nenhuma hipótese haverá exclusão de dados, bloqueio de backups ou degradação intencional de desempenho. Confirmado o pagamento, o serviço será reativado conforme a política contratada.',

            'termination' => 'Qualquer das partes poderá rescindir este contrato mediante aviso prévio por escrito de 30 (trinta) dias. Serão devidos os valores dos serviços já prestados até a data da rescisão. No encerramento, as partes assinarão o Termo de Encerramento e Entrega, com a transferência dos entregáveis, dados e acessos previstos.',

            'intellectual_property' => 'Após a quitação integral dos valores de desenvolvimento, os direitos patrimoniais sobre o código-fonte desenvolvido especificamente para a CONTRATANTE serão a ela cedidos. Bibliotecas, componentes e ferramentas de uso geral da CONTRATADA, bem como softwares de terceiros e de código aberto, permanecem sob suas respectivas licenças.',

            'data_protection' => 'As partes cumprirão a Lei nº 13.709/2018 (LGPD). A CONTRATANTE é controladora dos dados pessoais inseridos no sistema e a CONTRATADA atua como operadora, tratando-os apenas para a execução deste contrato e conforme as instruções da CONTRATANTE. A CONTRATADA adotará medidas de segurança adequadas e comunicará incidentes de segurança relevantes. Informações confidenciais de qualquer das partes não serão divulgadas a terceiros, obrigação que permanece por 5 (cinco) anos após o término do contrato.',

            'scope_changes' => 'Alterações e novas funcionalidades fora do escopo serão analisadas e orçadas pela CONTRATADA, com descrição, impacto, estimativa de horas, valor e prazo. Só serão executadas e cobradas após aprovação expressa da CONTRATANTE, registrada no portal do cliente ou por assinatura do termo de solicitação.',

            'price_adjustment' => 'Os valores recorrentes serão reajustados anualmente, a cada aniversário do contrato, pela variação acumulada do IPCA/IBGE nos 12 (doze) meses anteriores, ou por outro índice que venha a substituí-lo.',

            'taxes' => 'Os valores informados já incluem os tributos incidentes sobre os serviços da CONTRATADA. Tributos, tarifas bancárias e encargos de responsabilidade da CONTRATANTE não estão incluídos.',

            'third_party_licenses' => 'Licenças, assinaturas e serviços de terceiros (ex.: APIs pagas, serviços de e-mail, mapas, certificados especiais) não estão incluídos e serão contratados e pagos diretamente pela CONTRATANTE, salvo acordo em contrário por escrito.',

            'discounts' => 'Não há descontos aplicáveis, salvo os concedidos por escrito e registrados neste contrato.',

            'not_included' => 'Não estão incluídos: criação de conteúdo e textos, produção de imagens, migração de dados de sistemas legados, integrações não descritas no escopo, treinamentos presenciais, suporte fora do horário comercial e serviços de terceiros.',

            'support_channels' => 'Abertura de chamados pelo portal do cliente (canal oficial, com registro de prazos) e e-mail de suporte. Atendimento em horário comercial, de segunda a sexta, das 9h às 18h, exceto feriados nacionais.',

            'emergency' => 'Incidentes que deixem o sistema totalmente indisponível em produção devem ser abertos com prioridade urgente pelo portal. Atendimentos emergenciais fora do horário comercial, quando não previstos no plano contratado, serão orçados à parte e realizados mediante aprovação.',

            'late_payment' => 'Em caso de atraso, incidirão multa de 2% (dois por cento) e juros de mora de 1% (um por cento) ao mês, calculados pro rata die sobre o valor em aberto.',

            'forum' => 'Fica eleito o foro da comarca da sede da CONTRATADA para dirimir questões oriundas deste contrato, com renúncia a qualquer outro, por mais privilegiado que seja.',
        ];
    }
}
