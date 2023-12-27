const urlSistema = new URL(window.location.href);
let validatePayment = 0;
const container = document.querySelector(".payment-success");

const intervalId = setInterval(checkPaymentStatus, 1000);

function checkPaymentStatus() {
    fetchPaymentStatus()
        .then(handleResponse)
        .catch(handleError);
}

function fetchPaymentStatus() {
    return fetch(`${urlSistema.origin}?route=extension/payment/gerencianet/ajax/OFPaymentStatusAjaxHandler&identificadorPagamento=${identificadorPagamento}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    });
}

function handleResponse(response) {
    if (response.ok) {
        return response.json().then(checkStatus);
    }
    throw new Error('Erro na resposta da requisição.');
}

function checkStatus(ofStatus) {
    if (ofStatus.paid) {
        clearInterval(intervalId);
        redirectToOrderInfo(ofStatus.idFatura);
        return;
    }

    if (validatePayment === 30) {
        clearInterval(intervalId);
        showErrorMessage(ofStatus.idFatura);
        return;
    }

    validatePayment++;
}

function redirectToOrderInfo(orderId) {
    window.location.href = `${urlSistema.origin}?route=account/order/info&order_id=${orderId}`;
}

function showErrorMessage(orderId) {
    const link = createLinkElement(orderId);
    container.classList.add("fade-out");
    document.querySelector(".spinner").style.display = "none";

    setTimeout(() => {
        updateContainerWithError(link);
        container.classList.add("fade-in");
    }, 500);
}

function createLinkElement(orderId) {
    const link = document.createElement('a');
    link.href = `${urlSistema.origin}?route=account/order/info&order_id=${orderId}`;
    link.className = 'button';
    link.innerText = 'Visualizar Pedido';
    return link;
}

function updateContainerWithError(link) {
    container.querySelector("h1").innerText = "Pagamento Não Processado";
    container.querySelector("p").innerText = "O tempo para processamento do pagamento expirou. Por favor, verifique seu pedido e o banco utilizado para pagamento";
    container.appendChild(link);
}
