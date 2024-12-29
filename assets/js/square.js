async function initializeCard(payments) {
    const card = await payments.card();
    await card.attach('#card-container');
    return card;
}

async function tokenize(paymentMethod) {
    const result = await paymentMethod.tokenize();
    if (result.status === 'OK') {
        return result.token;
    } else {
        return null;
    }
}

document.addEventListener('DOMContentLoaded', async function () {
    document.getElementById('main').innerHTML = document.getElementById('main').innerHTML.replace(/(\b premium\b)/gi, '<span class="premium">$1</span>');
    const card = await initializeCard(payments);

    document.getElementById('card-button').addEventListener('click', async function () {
        document.getElementById('card-button').style.display = "none";
        document.getElementById('loader').style.display = "block";
        const token = await tokenize(card);
        if (token) {
            fetch('pay_proceed', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    source_id: token,
                    currency: document.getElementById('price-select').value
                }),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success == false) {
                        document.getElementById('card-button').style.display = "block";
                        document.getElementById('loader').style.display = "none";
                        document.getElementById('payment-status-container').innerText = data.message;
                        document.getElementById('payment-details-container').innerText = data.details;
                    } else {
                        document.getElementById('loader').style.display = "none";
                        document.getElementById('success').style.display = "block";
                        document.getElementById('payment-status-container').innerText = "";
                        document.getElementById('payment-details-container').innerText = "";
                    }
                })
                .catch(error => {
                    document.getElementById('payment-status-container').innerText = "Failed";
                    document.getElementById('payment-details-container').innerText = "Unexpected error";
                    document.getElementById('card-button').style.display = "block";
                    document.getElementById('loader').style.display = "none";
                });
        }
    });
});
