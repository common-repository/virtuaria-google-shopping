=== Virtuaria - Google Shopping ===
Contributors: tecnologiavirtuaria
Tags: integration, google, merchant, catalog, feed, marketing
Requires at least: 4.7
Tested up to: 6.4.1
Stable tag: 1.0.7
Requires PHP: 7.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Integra o catálogo de sua loja Woocommerce ao Google Shopping. Permite instalar o Google Analytics e gerar estatísticas diversas.

== Description ==

Gere e mantenha atualizado automaticamente um link (feed xml do Google Shopping) com todos os produtos da sua loja virtual. O feed segue todas as recomendações do Google para permitir enviar o catálogo de produtos da sua loja virtual para o Google Merchant.

Recursos:
* Gera e atualiza automaticamente um feed de produtos de sua loja;
* Permite configurar categorias de produtos que não devem estar em seu feed;
* Frequência de atualizações do feed definida via configuração;
* Opção para gerar o feed, manualmente via botão na tela de configuração;
* Adicione o Google Analytics a seu site.

Google Analytics é a ferramenta de monitoramento e análise de sites mais utilizada no mundo. Ela se integra com outros serviços do Google, como Ads e Search Console. Com ela é possível monitorar o perfil de quem acessa seu site, páginas mais acessadas, conversões, dispositivos, cidades e outros dados.

Este plugin foi desenvolvido sem nenhum incentivo do Google. Nenhum dos desenvolvedores deste plugin possuem vínculos com esta empresa. E note que este plugin foi feito baseado na documentação da API pública do google.

**Observação:** Os prints foram feitos em um painel wordpress/woocommerce personalizado pela Virtuaria objetivando otimizar o uso em lojas virtuais, por isso o fundo verde.

**Para mais informações, acesse** [virtuaria.com.br - desenvolvimento de plugins, criação e hospedagem de lojas virtuais](https://virtuaria.com.br/).

= Compatibilidade =

Compatível com Woocommerce 5.8.0 ou superior

### Descrição em Inglês: ###

Automatically generate and keep updated a link (Google Shopping xml feed) with all the products in your virtual store, with this plugin. The feed follows all of Google's recommendations to allow you to send your product catalog from your online store to Google Merchant.

Resources:
* Automatically generates and updates a feed of products from your store;
* Allows you to configure product categories that should not be in your feed;
* Frequency of feed updates defined via configuration;
* Option to manually generate the feed via a button on the configuration screen.



== Installation ==

= Instalação do plugin: =

* Envie os arquivos do plugin para a pasta wp-content/plugins, ou instale usando o instalador de plugins do WordPress.
* Ative o plugin.
* Navegue para o menu Integração google e defina seu código Pixel.

= Requerimentos: =

Ter instalado o [WooCommerce](http://wordpress.org/plugins/woocommerce/).



### Instalação e configuração em Inglês: ###

* Upload plugin files to your plugins folder, or install using WordPress built-in Add New Plugin installer;
* Activate the plugin;
* Navigate to the google Integration menu and set create feed.


== Frequently Asked Questions ==

= Qual é a licença do plugin? =

Este plugin está licenciado como GPLv3.

= O que eu preciso para utilizar este plugin? =

* Ter instalado uma versão atual do plugin WooCommerce.
* Possuir uma conta no google.

= Qual a frequência de atualização do Feed? =

Por padrão o Feed é atualizado uma vez ao dia, por volta das 02:00 horas, porém, via configuração é possível aumentar essa frequência em até 4x.

= Qual URL para acessar o Feed gerado? =

O feed pode ser encontrado em https://seudominio.com.br/virtuaria-google-shopping. A página de configuração contém link direto para seu feed.

= Ativei o plugin mas o feed está em branco, o que pode ser? =

* Servidor com pouca memória disponível;
* Pasta do plugin sem permissão de escrita em seu servidor, isto é necessário para geração do arquivo XML.
* Para outras situações, verificar todos os logs indo em “WooCommerce” > “Status do sistema” > “Logs”.

== Screenshots ==

1. Configurações do plugin;


== Upgrade Notice ==
Nenhuma atualização disponível

== Changelog ==
= 1.0.7 2023-11-22 =
* Melhorando escapes para caracteres especiais;
* Convertendo título e descrição de produtos para padrão camel case;
* Remoção do campo g:adult do feed.
= 1.0.6 2023-09-14 =
* Correção de problema na configuração do plugin quando os grupos de produtos não existirem no ambiente.
= 1.0.5 2023-06-26 =
* Ignorando produtos agrupados para o feed XML.
= 1.0.4 2023-05-22 =
* Ajuste na Compatibilidade com PHP 8+.
= 1.0.3 2023-02-01 =
* Campo novo no cadastro do produto para definir a marca do mesmo.
= 1.0.2 2022-12-29 =
* Bug fixes.
= 1.0.1 2022-12-29 =
* Descontinuando a configuração do tagmanager.
= 1.0.0 2022-11-28 =
* Versão inicial.