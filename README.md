# Plugin de Pagamento Efí para OpenCart 4

Este plugin oficial do **Efí**, permite oferecer múltiplas formas de pagamento em sua loja OpenCart: **Pix**, **Boleto Bancário**, **Cartão de Crédito** e **Open Finance**.

> ℹ️ Todos os campos obrigatórios devem ser preenchidos corretamente para que o plugin funcione.  
> 📘 Veja como obter suas credenciais e certificados na [documentação oficial do Efí](https://dev.efipay.com.br/docs).

---

## 🚀 Instalação do Plugin

1. Acesse o painel administrativo do OpenCart.
2. Vá até o menu `Extensões > Instalador de Extensões`.
3. Clique em **Enviar** e selecione o arquivo [`efi.ocmod.zip`](./upload/efi.ocmod.zip) localizado na pasta `upload` deste repositório.
4. Após o upload, vá para `Extensões > Extensões`, escolha `Pagamentos` no seletor.
5. Procure por **Efí Bank** e clique em **Instalar**.
6. Após instalar, clique em **Editar** para configurar as credenciais, certificados e demais campos obrigatórios.

> 📌 **Importante**: após configurar o plugin, vá até `Extensões > Modificações` e clique em **Atualizar** (ícone de recarregar) para aplicar as alterações.

---

## ⚙️ Configurações Gerais

| Campo                              | Descrição |
|------------------------------------|-----------|
| **Client_Id e Client_Secret (produção ou desenvolvimento)**             | [🔗 Saiba como obter](https://dev.efipay.com.br/docs/api-cobrancas/credenciais#criar-uma-aplica%C3%A7%C3%A3o-ou-configurar-uma-j%C3%A1-existente) |
| **Identificador da conta**         | Código único da conta no Efí. Para localizá-lo, acesse: `Menu API > Aplicações > Introdução > Identificador da conta`. |
| **Ordem de Exibição**              | Ordem em que a forma de pagamento será listada no checkout. |
| **Status do pedido ao finalizar o pagamento** | Define o status que será atribuído ao pedido após pagamento. |
| **Ativar ambiente de teste**       | Habilita o modo sandbox para testes. |
| **Ativar Plugin**                  | Liga ou desliga o uso do plugin. |

---

## 💸 Configuração do Pix

| Campo                    | Descrição |
|--------------------------|-----------|
| **Chave Pix**            | Chave cadastrada no aplicativo do Efí. |
| **Tempo de expiração da cobrança** | Tempo em horas que a cobrança ficará disponível. |
| **Certificado**          | Arquivo `.p12` gerado via painel do Efí. [Ver instruções](https://dev.efipay.com.br/docs/api-pix/credenciais#gerando-um-certificado-p12) |
| **Desconto**             | Valor fixo ou percentual. `10` = R$10, `5%` = percentual. |
| **Validar mTLS**         | Veja [aqui](https://dev.efipay.com.br/docs/api-pix/webhooks#entendendo-o-padr%C3%A3o-mtls) se sua conta exige esse padrão. |
| **Ativar**               | Ativa o pagamento por Pix. |

---

## 🧾 Configuração do Boleto

| Campo                    | Descrição |
|--------------------------|-----------|
| **Dias para vencimento do boleto** | Número de dias após emissão para o vencimento. |
| **Desconto**             | Valor fixo ou percentual. `10` = R$10, `5%` = percentual. |
| **Enviar e-mail para o cliente final** | Se ativo, o boleto será enviado por e-mail. |
| **Configuração de multa** | Valor da multa após vencimento. Ex: `200` = 2%. |
| **Configuração de juros** | Valor de juros por dia. Ex: `33` = 0,033% ao dia. |
| **Mensagem no boleto**   | Texto opcional que aparecerá impresso. |
| **Ativar**               | Ativa o pagamento por boleto bancário. |

---

## 💳 Cartão de Crédito

| Campo       | Descrição |
|-------------|-----------|
| **Ativar**  | Ativa o pagamento por cartão de crédito. |

---

## 🔐 Open Finance

| Campo                             | Descrição |
|-----------------------------------|-----------|
| **Chave Pix para recebimento**    | Chave que receberá os pagamentos via Open Finance. |
| **Desconto**                      | Valor fixo ou percentual. `10` = R$10, `5%` = percentual. |
| **Certificado**                   | Arquivo `.p12` gerado via painel do Efí. [Ver instruções](https://dev.efipay.com.br/docs/api-pix/credenciais#gerando-um-certificado-p12) |
| **Ativar**                        | Habilita a opção de Open Finance. |

---

## 📄 Changelog

Veja todas as mudanças no [CHANGELOG.md](CHANGELOG.md)

---

## ✅ Requisitos

- OpenCart `4.1.0.1`
- PHP `8.1` 
- Conta ativa no [Efí](https://efipay.com.br)
- Certificados e chaves de API (conforme documentação)

---

## 📄 Licença

Distribuído sob a licença MIT. Veja mais em [LICENSE](LICENSE).

---

## 🛠 Suporte

Para dúvidas, abra uma _issue_ aqui no GitHub ou consulte a [documentação do Efí](https://dev.efipay.com.br/docs).
